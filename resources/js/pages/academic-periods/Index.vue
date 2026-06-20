<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowUpDown, MoreHorizontal, Pencil, Trash2, Plus, CalendarDays, SlidersHorizontal, X } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog, DialogContent, DialogDescription,
    DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

// ── Types ─────────────────────────────────────────────────────────────────────
export interface AcademicPeriod {
    id:         number;
    name:       string;
    start_date: string;
    end_date:   string;
    active:  boolean;
    status:     'draft' | 'active' | 'closed';
    created_at: string;
}

interface EvaluationParameter {
    id:         number;
    name:       string;
    percentage: number;
    is_final:   boolean;
}

interface Props {
    academicPeriods: AcademicPeriod[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administración',      href: dashboard().url },
    { title: 'Períodos Académicos', href: '/academic-periods' },
];

const STATUS_LABELS: Record<string, { label: string; variant: 'default' | 'secondary' | 'outline' }> = {
    draft:  { label: 'Borrador', variant: 'secondary' },
    active: { label: 'Activo',   variant: 'default'   },
    closed: { label: 'Cerrado',  variant: 'outline'   },
};

const STATUSES = [
    { value: 'draft',  label: 'Borrador' },
    { value: 'active', label: 'Activo'   },
    { value: 'closed', label: 'Cerrado'  },
];

// ── Dialog crear/editar período ───────────────────────────────────────────────
const formDialogOpen   = ref(false);
const deleteDialogOpen = ref(false);
const isEditing        = ref(false);
const selectedPeriod   = ref<AcademicPeriod | null>(null);
const periodToDelete   = ref<AcademicPeriod | null>(null);

const form = useForm({
    name:       '',
    start_date: '',
    end_date:   '',
    status:     'draft' as 'draft' | 'active' | 'closed',
    active:  false,
});

const openCreateDialog = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    formDialogOpen.value = true;
};

const openEditDialog = (period: AcademicPeriod) => {
    isEditing.value      = true;
    selectedPeriod.value = period;
    form.clearErrors();
    form.name       = period.name;
    form.start_date = period.start_date;
    form.end_date   = period.end_date;
    form.status     = period.status;
    form.active  = Boolean(period.active);
    formDialogOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value && selectedPeriod.value) {
        form.put(`/academic-periods/${selectedPeriod.value.id}`, {
            preserveScroll: true,
            only: ['academicPeriods'],
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Período académico actualizado exitosamente');
            },
            onError: () => toast.error('Error al actualizar el período académico'),
        });
    } else {
        form.post('/academic-periods', {
            preserveScroll: true,
            only: ['academicPeriods'],
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Período académico creado exitosamente');
            },
            onError: () => toast.error('Error al crear el período académico'),
        });
    }
};

const openDeleteDialog = (period: AcademicPeriod) => {
    periodToDelete.value   = period;
    deleteDialogOpen.value = true;
};

const handleDelete = () => {
    if (!periodToDelete.value) return;
    router.delete(`/academic-periods/${periodToDelete.value.id}`, {
        preserveScroll: true,
        only: ['academicPeriods'],
        onSuccess: () => {
            deleteDialogOpen.value = false;
            periodToDelete.value   = null;
            toast.success('Período académico eliminado exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al eliminar el período académico');
        },
    });
};

// ── Dialog parámetros de evaluación ──────────────────────────────────────────
const paramsDialogOpen    = ref(false);
const paramsPeriod        = ref<AcademicPeriod | null>(null);
const parameters          = ref<EvaluationParameter[]>([]);
const loadingParams       = ref(false);
const editingParam        = ref<EvaluationParameter | null>(null);
const deletingParamId     = ref<number | null>(null);

const paramForm = useForm({
    academic_period_id: 0,
    curriculum_id:      null as null,
    name:               '',
    percentage:         '' as unknown as number,
    is_final:           false,
});

const editParamForm = useForm({
    name:       '',
    percentage: '' as unknown as number,
    is_final:   false,
});

const totalPercentage = computed(() =>
    parameters.value.reduce((sum, p) => sum + Number(p.percentage), 0)
);

const remainingPercentage = computed(() => 100 - totalPercentage.value);

const openParamsDialog = async (period: AcademicPeriod) => {
    paramsPeriod.value         = period;
    editingParam.value         = null;
    paramForm.academic_period_id = period.id;
    paramForm.name             = '';
    paramForm.percentage       = '' as unknown as number;
    paramForm.is_final         = false;
    paramForm.clearErrors();
    paramsDialogOpen.value     = true;
    await fetchParameters(period.id);
};

const fetchParameters = async (periodId: number) => {
    loadingParams.value = true;
    try {
        const res = await fetch(`/academic-periods/${periodId}/parameters`);
        const data = await res.json();
        parameters.value = data;
    } catch {
        toast.error('Error al cargar los parámetros');
    } finally {
        loadingParams.value = false;
    }
};

const handleAddParam = () => {
    paramForm.post('/evaluation-parameters', {
        preserveScroll: true,
        onSuccess: () => {
            paramForm.name       = '';
            paramForm.percentage = '' as unknown as number;
            paramForm.is_final   = false;
            paramForm.clearErrors();
            toast.success('Parámetro agregado exitosamente');
            fetchParameters(paramsPeriod.value!.id);
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al agregar el parámetro');
        },
    });
};

const startEditParam = (param: EvaluationParameter) => {
    editingParam.value       = param;
    editParamForm.name       = param.name;
    editParamForm.percentage = param.percentage;
    editParamForm.is_final   = param.is_final;
    editParamForm.clearErrors();
};

const cancelEditParam = () => {
    editingParam.value = null;
    editParamForm.clearErrors();
};

const handleUpdateParam = (paramId: number) => {
    editParamForm.put(`/evaluation-parameters/${paramId}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingParam.value = null;
            toast.success('Parámetro actualizado exitosamente');
            fetchParameters(paramsPeriod.value!.id);
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al actualizar el parámetro');
        },
    });
};

const handleDeleteParam = (paramId: number) => {
    deletingParamId.value = paramId;
    router.delete(`/evaluation-parameters/${paramId}`, {
        preserveScroll: true,
        onSuccess: () => {
            deletingParamId.value = null;
            toast.success('Parámetro eliminado exitosamente');
            fetchParameters(paramsPeriod.value!.id);
        },
        onError: (errors) => {
            deletingParamId.value = null;
            toast.error((Object.values(errors)[0] as string) || 'Error al eliminar el parámetro');
        },
    });
};

// ── Columns ───────────────────────────────────────────────────────────────────
const columns: ColumnDef<AcademicPeriod>[] = [
    {
        accessorKey: 'name',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => column.toggleSorting(column.getIsSorted() === 'asc') },
                () => ['Nombre', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        id: 'dates',
        header: 'Período',
        cell: ({ row }) => {
            const period = row.original;
            return h('div', { class: 'text-sm text-muted-foreground' },
                `${period.start_date} → ${period.end_date}`
            );
        },
    },
    {
        accessorKey: 'status',
        header: 'Estado',
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            const config = STATUS_LABELS[status] ?? { label: status, variant: 'secondary' as const };
            return h(Badge, { variant: config.variant }, () => config.label);
        },
    },
    {
        accessorKey: 'active',
        header: 'Activo',
        cell: ({ row }) => {
            const active = row.getValue('active') as boolean;
            return h(Badge, { variant: active ? 'default' : 'secondary' }, () => active ? 'Sí' : 'No');
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const period = row.original;
            return h(DropdownMenu, {}, {
                default: () => [
                    h(DropdownMenuTrigger, { asChild: true }, {
                        default: () => h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, {
                            default: () => [
                                h('span', { class: 'sr-only' }, 'Abrir menú'),
                                h(MoreHorizontal, { class: 'h-4 w-4' }),
                            ],
                        }),
                    }),
                    h(DropdownMenuContent, { align: 'end' }, {
                        default: () => [
                            h(DropdownMenuLabel, {}, () => 'Acciones'),
                            h(DropdownMenuItem, {
                                onClick: () => openParamsDialog(period),
                            }, () => [h(SlidersHorizontal, { class: 'mr-2 h-4 w-4' }), 'Parámetros de evaluación']),
                            h(DropdownMenuSeparator),
                            h(DropdownMenuItem, {
                                onClick: () => openEditDialog(period),
                            }, () => [h(Pencil, { class: 'mr-2 h-4 w-4' }), 'Editar']),
                            h(DropdownMenuItem, {
                                class: 'text-destructive',
                                onClick: () => openDeleteDialog(period),
                            }, () => [h(Trash2, { class: 'mr-2 h-4 w-4' }), 'Eliminar']),
                        ],
                    }),
                ],
            });
        },
    },
];
</script>

<template>
    <Head title="Períodos Académicos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-3">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Períodos Académicos</h2>
                        <p class="text-muted-foreground">Gestiona los períodos académicos del sistema</p>
                    </div>
                    <Button @click="openCreateDialog">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Período
                    </Button>
                </div>

                <DataTable
                    :columns="columns"
                    :data="academicPeriods"
                    filter-column="name"
                    filter-placeholder="Buscar por nombre..."
                />

            </div>
        </div>

        <!-- Dialog: crear / editar período -->
        <Dialog v-model:open="formDialogOpen">
            <DialogContent class="max-w-lg">
                <template v-if="formDialogOpen">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <CalendarDays class="h-5 w-5 text-muted-foreground" />
                            {{ isEditing ? 'Editar Período Académico' : 'Nuevo Período Académico' }}
                        </DialogTitle>
                        <DialogDescription>
                            {{ isEditing ? 'Modifica los datos del período' : 'Completa los datos para registrar un nuevo período' }}
                        </DialogDescription>
                    </DialogHeader>

                    <form class="space-y-4 py-2" @submit.prevent="submitForm">
                        <div class="space-y-1">
                            <Label for="name">Nombre <span class="text-destructive">*</span></Label>
                            <Input id="name" v-model="form.name" placeholder="Ej: 2024-A" :class="{ 'border-destructive': form.errors.name }" />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <Label for="start_date">Fecha de inicio <span class="text-destructive">*</span></Label>
                                <Input id="start_date" v-model="form.start_date" type="date" :class="{ 'border-destructive': form.errors.start_date }" />
                                <p v-if="form.errors.start_date" class="text-xs text-destructive">{{ form.errors.start_date }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label for="end_date">Fecha de fin <span class="text-destructive">*</span></Label>
                                <Input id="end_date" v-model="form.end_date" type="date" :class="{ 'border-destructive': form.errors.end_date }" />
                                <p v-if="form.errors.end_date" class="text-xs text-destructive">{{ form.errors.end_date }}</p>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <Label>Estado <span class="text-destructive">*</span></Label>
                            <Select v-model="form.status">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.status }">
                                    <SelectValue placeholder="Seleccionar estado..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in STATUSES" :key="s.value" :value="s.value">
                                        {{ s.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                        </div>

                        <div class="flex items-center gap-3">
                            <Switch v-model="form.active" />
                            <Label>Marcar como período activo</Label>
                        </div>
                    </form>

                    <DialogFooter>
                        <Button variant="outline" :disabled="form.processing" @click="formDialogOpen = false">Cancelar</Button>
                        <Button :disabled="form.processing" @click="submitForm">
                            {{ form.processing ? 'Guardando...' : isEditing ? 'Actualizar' : 'Crear Período' }}
                        </Button>
                    </DialogFooter>
                </template>
            </DialogContent>
        </Dialog>

        <!-- Dialog: parámetros de evaluación -->
        <Dialog v-model:open="paramsDialogOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <SlidersHorizontal class="h-5 w-5 text-muted-foreground" />
                        Parámetros de evaluación
                    </DialogTitle>
                    <DialogDescription>
                        Parámetros globales de
                        <span class="font-medium">{{ paramsPeriod?.name }}</span>.
                        Estos aplican a todas las materias del período.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">

                    <!-- Barra de progreso del total -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Total asignado</span>
                            <span
                                class="font-medium"
                                :class="totalPercentage === 100 ? 'text-green-600 dark:text-green-400' : totalPercentage > 100 ? 'text-destructive' : 'text-foreground'"
                            >
                                {{ totalPercentage }}%
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="totalPercentage === 100 ? 'bg-green-500' : totalPercentage > 100 ? 'bg-destructive' : 'bg-primary'"
                                :style="{ width: `${Math.min(totalPercentage, 100)}%` }"
                            />
                        </div>
                        <p v-if="totalPercentage < 100" class="text-xs text-muted-foreground">
                            Faltan {{ remainingPercentage }}% por asignar para completar el 100%.
                        </p>
                        <p v-else-if="totalPercentage === 100" class="text-xs text-green-600 dark:text-green-400">
                            Los parámetros suman exactamente 100%.
                        </p>
                    </div>

                    <!-- Lista de parámetros existentes -->
                    <div class="space-y-1.5">
                        <Label class="text-sm text-muted-foreground">Parámetros actuales</Label>

                        <div
                            v-if="loadingParams"
                            class="flex items-center justify-center py-6 text-sm text-muted-foreground"
                        >
                            Cargando...
                        </div>

                        <div
                            v-else-if="parameters.length === 0"
                            class="flex items-center justify-center rounded-lg border border-dashed py-6 text-center text-sm text-muted-foreground"
                        >
                            Sin parámetros definidos
                        </div>

                        <div v-else class="space-y-1.5">
                            <div
                                v-for="param in parameters"
                                :key="param.id"
                                class="rounded-lg border"
                            >
                                <!-- Modo edición -->
                                <div v-if="editingParam?.id === param.id" class="space-y-3 p-3">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <Label class="text-xs">Nombre</Label>
                                            <Input v-model="editParamForm.name" placeholder="Ej. Parcial 1" class="h-8 text-sm" :class="{ 'border-destructive': editParamForm.errors.name }" />
                                            <p v-if="editParamForm.errors.name" class="text-xs text-destructive">{{ editParamForm.errors.name }}</p>
                                        </div>
                                        <div class="space-y-1">
                                            <Label class="text-xs">Porcentaje</Label>
                                            <Input v-model="editParamForm.percentage" type="number" min="0.01" max="100" step="0.01" placeholder="Ej. 30" class="h-8 text-sm" :class="{ 'border-destructive': editParamForm.errors.percentage }" />
                                            <p v-if="editParamForm.errors.percentage" class="text-xs text-destructive">{{ editParamForm.errors.percentage }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Checkbox :checked="editParamForm.is_final" @update:checked="(v: boolean) => editParamForm.is_final = v" />
                                        <Label class="text-xs">Es examen final</Label>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <Button variant="ghost" size="sm" @click="cancelEditParam">Cancelar</Button>
                                        <Button size="sm" :disabled="editParamForm.processing" @click="handleUpdateParam(param.id)">
                                            {{ editParamForm.processing ? 'Guardando...' : 'Guardar' }}
                                        </Button>
                                    </div>
                                </div>

                                <!-- Modo lectura -->
                                <div v-else class="flex items-center justify-between px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium">{{ param.name }}</span>
                                        <Badge variant="outline" class="text-xs">{{ param.percentage }}%</Badge>
                                        <Badge v-if="param.is_final" variant="secondary" class="text-xs">Final</Badge>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <Button variant="ghost" size="sm" class="h-7 w-7 p-0" @click="startEditParam(param)">
                                            <Pencil class="h-3.5 w-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-7 w-7 p-0 text-muted-foreground hover:text-destructive"
                                            :disabled="deletingParamId === param.id"
                                            @click="handleDeleteParam(param.id)"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Agregar nuevo parámetro -->
                    <div v-if="totalPercentage < 100" class="space-y-3 border-t pt-4">
                        <Label class="text-sm text-muted-foreground">Agregar parámetro</Label>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label for="p-name" class="text-xs">Nombre <span class="text-destructive">*</span></Label>
                                <Input
                                    id="p-name"
                                    v-model="paramForm.name"
                                    placeholder="Ej. Parcial 1"
                                    class="h-8 text-sm"
                                    :class="{ 'border-destructive': paramForm.errors.name }"
                                />
                                <p v-if="paramForm.errors.name" class="text-xs text-destructive">{{ paramForm.errors.name }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label for="p-pct" class="text-xs">Porcentaje <span class="text-destructive">*</span></Label>
                                <Input
                                    id="p-pct"
                                    v-model="paramForm.percentage"
                                    type="number"
                                    min="0.01"
                                    :max="remainingPercentage"
                                    step="0.01"
                                    :placeholder="`Máx. ${remainingPercentage}%`"
                                    class="h-8 text-sm"
                                    :class="{ 'border-destructive': paramForm.errors.percentage }"
                                />
                                <p v-if="paramForm.errors.percentage" class="text-xs text-destructive">{{ paramForm.errors.percentage }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="p-final"
                                    :checked="paramForm.is_final"
                                    @update:checked="(v: boolean) => paramForm.is_final = v"
                                />
                                <Label for="p-final" class="text-xs">Es examen final</Label>
                            </div>
                            <Button
                                size="sm"
                                :disabled="paramForm.processing || !paramForm.name || !paramForm.percentage"
                                @click="handleAddParam"
                            >
                                <Plus class="mr-1.5 h-3.5 w-3.5" />
                                {{ paramForm.processing ? 'Agregando...' : 'Agregar' }}
                            </Button>
                        </div>
                    </div>

                    <p v-if="totalPercentage >= 100" class="border-t pt-3 text-xs text-muted-foreground">
                        El total ya es 100%. Edita o elimina un parámetro existente para hacer espacio.
                    </p>

                </div>

                <DialogFooter>
                    <Button variant="outline" @click="paramsDialogOpen = false">Cerrar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Dialog: eliminar período -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Estás seguro?</DialogTitle>
                    <DialogDescription>
                        Esta acción no se puede deshacer. Se eliminará permanentemente el período
                        <span class="font-semibold">{{ periodToDelete?.name }}</span>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">Cancelar</Button>
                    <Button variant="destructive" @click="handleDelete">Eliminar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
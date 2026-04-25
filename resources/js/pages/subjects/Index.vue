<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowUpDown, MoreHorizontal, Pencil, Trash2, Plus, BookOpen, GitMerge, X } from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog, DialogContent, DialogDescription,
    DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

interface PrerequisiteOption {
    id:   number;
    name: string;
    code: string | null;
}

export interface Subject {
    id:                          number;
    code:                        string | null;
    name:                        string;
    description:                 string | null;
    credits:                     number;
    active:                      boolean;
    prerequisite_subjects_count: number;
    prerequisite_subjects:       PrerequisiteOption[];
    created_at:                  string;
}

interface Props {
    subjects:    Subject[];
    allSubjects: PrerequisiteOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Materias', href: '/subjects' },
];

const formDialogOpen   = ref(false);
const deleteDialogOpen = ref(false);
const isEditing        = ref(false);
const selectedSubject  = ref<Subject | null>(null);
const subjectToDelete  = ref<Subject | null>(null);

const form = useForm({
    code:        '',
    name:        '',
    description: '',
    credits:     '' as unknown as number,
    active:      true,
});

const openCreateDialog = () => {
    isEditing.value       = false;
    selectedSubject.value = null;
    form.code        = '';
    form.name        = '';
    form.description = '';
    form.credits     = '' as unknown as number;
    form.active      = true;
    form.clearErrors();
    formDialogOpen.value = true;
};

const openEditDialog = (subject: Subject) => {
    isEditing.value       = true;
    selectedSubject.value = subject;
    form.clearErrors();
    form.code        = subject.code        ?? '';
    form.name        = subject.name;
    form.description = subject.description ?? '';
    form.credits     = subject.credits;
    form.active      = subject.active;
    formDialogOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value && selectedSubject.value) {
        form.put(`/subjects/${selectedSubject.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Materia actualizada exitosamente');
            },
            onError: (errors) => {
                toast.error((Object.values(errors)[0] as string) || 'Error al actualizar la materia');
            },
        });
    } else {
        form.post('/subjects', {
            preserveScroll: true,
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Materia creada exitosamente');
            },
            onError: (errors) => {
                toast.error((Object.values(errors)[0] as string) || 'Error al crear la materia');
            },
        });
    }
};

const openDeleteDialog = (subject: Subject) => {
    subjectToDelete.value  = subject;
    deleteDialogOpen.value = true;
};

const handleDelete = () => {
    if (!subjectToDelete.value) return;
    router.delete(`/subjects/${subjectToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false;
            subjectToDelete.value  = null;
            toast.success('Materia eliminada exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al eliminar la materia');
        },
    });
};

const prereqDialogOpen   = ref(false);
const prereqSubject      = ref<Subject | null>(null);
const prereqForm         = useForm({ prerequisite_id: '' });
const deletingPrereqId   = ref<number | null>(null);

const openPrereqDialog = (subject: Subject) => {
    prereqSubject.value    = subject;
    prereqForm.prerequisite_id = '';
    prereqForm.clearErrors();
    prereqDialogOpen.value = true;
};

const availablePrereqs = computed(() => {
    if (!prereqSubject.value) return [];
    const existingIds = new Set(prereqSubject.value.prerequisite_subjects.map(p => p.id));
    return props.allSubjects.filter(
        s => s.id !== prereqSubject.value!.id && !existingIds.has(s.id)
    );
});

const onPrereqChange = (value: AcceptableValue) => {
    if (value) prereqForm.prerequisite_id = String(value);
};

const handleAttach = () => {
    if (!prereqSubject.value) return;
    prereqForm.post(`/subjects/${prereqSubject.value.id}/prerequisites`, {
        preserveScroll: true,
        onSuccess: () => {
            prereqForm.prerequisite_id = '';
            prereqForm.clearErrors();
            toast.success('Prerequisito agregado exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al agregar prerequisito');
        },
    });
};

const handleDetach = (prerequisiteId: number) => {
    if (!prereqSubject.value) return;
    deletingPrereqId.value = prerequisiteId;
    router.delete(`/subjects/${prereqSubject.value.id}/prerequisites/${prerequisiteId}`, {
        preserveScroll: true,
        onSuccess: () => {
            deletingPrereqId.value = null;
            toast.success('Prerequisito eliminado exitosamente');
        },
        onError: (errors) => {
            deletingPrereqId.value = null;
            toast.error((Object.values(errors)[0] as string) || 'Error al eliminar prerequisito');
        },
    });
};

const goToSubject = (id: number) => router.visit(`/subjects/${id}`);

const columns: ColumnDef<Subject>[] = [
    {
        accessorKey: 'code',
        header: 'Código',
        cell: ({ row }) => {
            const code = row.getValue('code') as string | null;
            return h('div', { class: 'font-mono text-sm text-muted-foreground' }, code ?? '—');
        },
    },
    {
        accessorKey: 'name',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => column.toggleSorting(column.getIsSorted() === 'asc') },
                () => ['Nombre', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('name')),
    },
    {
        accessorKey: 'credits',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => column.toggleSorting(column.getIsSorted() === 'asc') },
                () => ['Créditos', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'text-center' }, row.getValue('credits')),
    },
    {
        accessorKey: 'prerequisite_subjects_count',
        header: 'Prerequisitos',
        cell: ({ row }) => {
            const count = row.getValue('prerequisite_subjects_count') as number;
            return h(Badge, { variant: count > 0 ? 'outline' : 'secondary' }, () =>
                count > 0 ? `${count} prerequisito${count !== 1 ? 's' : ''}` : 'Ninguno'
            );
        },
    },
    {
        accessorKey: 'active',
        header: 'Estado',
        cell: ({ row }) => {
            const active = row.getValue('active') as boolean;
            return h(Badge, { variant: active ? 'default' : 'secondary' }, () =>
                active ? 'Activa' : 'Inactiva'
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Registrada',
        cell: ({ row }) => h('div', { class: 'text-sm text-muted-foreground' }, row.getValue('created_at')),
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const subject = row.original;
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
                                onClick: () => goToSubject(subject.id),
                            }, () => [h(BookOpen, { class: 'mr-2 h-4 w-4' }), 'Ver detalle']),
                            h(DropdownMenuItem, {
                                onClick: () => openPrereqDialog(subject),
                            }, () => [h(GitMerge, { class: 'mr-2 h-4 w-4' }), 'Prerequisitos']),
                            h(DropdownMenuSeparator),
                            h(DropdownMenuItem, {
                                onClick: () => openEditDialog(subject),
                            }, () => [h(Pencil, { class: 'mr-2 h-4 w-4' }), 'Editar']),
                            h(DropdownMenuItem, {
                                class: 'text-destructive',
                                onClick: () => openDeleteDialog(subject),
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
    <Head title="Materias" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-3">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Materias</h2>
                        <p class="text-muted-foreground">
                            Catálogo de materias disponibles en la institución
                        </p>
                    </div>
                    <Button @click="openCreateDialog">
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva materia
                    </Button>
                </div>

                <DataTable
                    :columns="columns"
                    :data="props.subjects"
                    filter-column="name"
                    filter-placeholder="Buscar por nombre..."
                />

            </div>
        </div>

        <Dialog v-model:open="formDialogOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <BookOpen class="h-5 w-5 text-muted-foreground" />
                        {{ isEditing ? 'Editar materia' : 'Nueva materia' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Modifica los datos de la materia.' : 'Completa los datos para registrar una nueva materia.' }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4 py-2" @submit.prevent="submitForm">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 space-y-1">
                            <Label for="f-name">Nombre <span class="text-destructive">*</span></Label>
                            <Input id="f-name" v-model="form.name" placeholder="Ej. Matemáticas I" :class="{ 'border-destructive': form.errors.name }" />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label for="f-code">Código</Label>
                            <Input id="f-code" v-model="form.code" placeholder="Ej. MAT-101" :class="{ 'border-destructive': form.errors.code }" />
                            <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label for="f-credits">Créditos <span class="text-destructive">*</span></Label>
                            <Input id="f-credits" v-model="form.credits" type="number" min="1" max="20" placeholder="Ej. 4" :class="{ 'border-destructive': form.errors.credits }" />
                            <p v-if="form.errors.credits" class="text-xs text-destructive">{{ form.errors.credits }}</p>
                        </div>
                        <div class="col-span-2 space-y-1">
                            <Label for="f-description">Descripción</Label>
                            <Input id="f-description" v-model="form.description" placeholder="Breve descripción de la materia" :class="{ 'border-destructive': form.errors.description }" />
                            <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                        </div>
                        <div class="col-span-2 flex items-center gap-3">
                            <Checkbox id="f-active" :checked="form.active" @update:checked="(v: boolean) => form.active = v" />
                            <Label for="f-active">Materia activa</Label>
                        </div>
                    </div>
                </form>

                <DialogFooter>
                    <Button variant="outline" :disabled="form.processing" @click="formDialogOpen = false">Cancelar</Button>
                    <Button :disabled="form.processing" @click="submitForm">
                        {{ form.processing ? 'Guardando...' : isEditing ? 'Actualizar' : 'Crear materia' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="prereqDialogOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <GitMerge class="h-5 w-5 text-muted-foreground" />
                        Prerequisitos
                    </DialogTitle>
                    <DialogDescription>
                        Gestiona los prerequisitos de
                        <span class="font-medium">{{ prereqSubject?.name }}</span>.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">

                    <div class="space-y-2">
                        <Label class="text-sm text-muted-foreground">Prerequisitos actuales</Label>

                        <div
                            v-if="prereqSubject?.prerequisite_subjects.length === 0"
                            class="flex items-center justify-center rounded-lg border border-dashed py-6 text-center text-sm text-muted-foreground"
                        >
                            Sin prerequisitos asignados
                        </div>

                        <div v-else class="space-y-1.5">
                            <div
                                v-for="prereq in prereqSubject?.prerequisite_subjects"
                                :key="prereq.id"
                                class="flex items-center justify-between rounded-lg border px-3 py-2"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{{ prereq.name }}</span>
                                    <span v-if="prereq.code" class="font-mono text-xs text-muted-foreground">
                                        {{ prereq.code }}
                                    </span>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 w-7 p-0 text-muted-foreground hover:text-destructive"
                                    :disabled="deletingPrereqId === prereq.id"
                                    @click="handleDetach(prereq.id)"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2 border-t pt-4">
                        <Label class="text-sm text-muted-foreground">Agregar prerequisito</Label>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <Select
                                    :model-value="prereqForm.prerequisite_id"
                                    @update:model-value="onPrereqChange"
                                >
                                    <SelectTrigger :class="{ 'border-destructive': prereqForm.errors.prerequisite_id }">
                                        <SelectValue placeholder="Selecciona una materia..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="subject in availablePrereqs"
                                            :key="subject.id"
                                            :value="String(subject.id)"
                                        >
                                            {{ subject.name }}
                                            <span v-if="subject.code" class="ml-1 font-mono text-xs text-muted-foreground">
                                                {{ subject.code }}
                                            </span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="prereqForm.errors.prerequisite_id" class="mt-1 text-xs text-destructive">
                                    {{ prereqForm.errors.prerequisite_id }}
                                </p>
                            </div>
                            <Button
                                :disabled="!prereqForm.prerequisite_id || prereqForm.processing"
                                @click="handleAttach"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Agregar
                            </Button>
                        </div>
                        <p v-if="availablePrereqs.length === 0" class="text-xs text-muted-foreground">
                            No hay más materias disponibles para agregar como prerequisito.
                        </p>
                    </div>

                </div>

                <DialogFooter>
                    <Button variant="outline" @click="prereqDialogOpen = false">Cerrar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Estás seguro?</DialogTitle>
                    <DialogDescription>
                        Esta acción no se puede deshacer. Se eliminará permanentemente
                        <span class="font-semibold">{{ subjectToDelete?.name }}</span>
                        del catálogo de materias.
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
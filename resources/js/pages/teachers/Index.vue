<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowUpDown, MoreHorizontal, Pencil, Trash2, Plus, BookUser } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogScrollContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { toast } from 'vue-sonner';
import { useForm } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

export interface Teacher {
    id: number;
    full_name: string;
    identification_number: string;
    institution: string | null;
    hire_date: string | null;
    academic_degree: string | null;
    active: boolean;
    created_at: string;
    person_id: number;
    institution_id: number | null;
}

interface CatalogItem { id: number; name: string; }
interface PersonItem  extends CatalogItem { identification_number: string; }

interface Props {
    teachers:     Teacher[];
    people:       PersonItem[];
    institutions: CatalogItem[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administración', href: dashboard().url },
    { title: 'Docentes', href: '/teachers' },
];

const deleteDialogOpen = ref(false);
const formDialogOpen   = ref(false);
const isEditing        = ref(false);

const selectedTeacher = ref<Teacher | null>(null);
const teacherToDelete = ref<Teacher | null>(null);

const emptyForm = {
    person_id:       '' as number | '',
    institution_id:  '' as number | '',
    hire_date:       '',
    academic_degree: '',
    active:          '1',
};

const form = useForm({ ...emptyForm });

const openCreateDialog = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    formDialogOpen.value = true;
};

const openEditDialog = (teacher: Teacher) => {
    isEditing.value = true;
    form.reset();
    form.clearErrors();

    form.person_id       = teacher.person_id;
    form.institution_id  = teacher.institution_id ?? '';
    form.hire_date       = teacher.hire_date       ?? '';
    form.academic_degree = teacher.academic_degree ?? '';
    form.active          = teacher.active ? '1' : '0';

    selectedTeacher.value = teacher;
    formDialogOpen.value  = true;
};

const submitForm = () => {
    if (isEditing.value && selectedTeacher.value) {
        form.put(`/teachers/${selectedTeacher.value.id}`, {
            preserveScroll: true,
            only: ['teachers'],
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Docente actualizado exitosamente');
            },
        });
    } else {
        form.post('/teachers', {
            preserveScroll: true,
            only: ['teachers'],
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Docente creado exitosamente');
            },
        });
    }
};

const openDeleteDialog = (teacher: Teacher) => {
    teacherToDelete.value  = teacher;
    deleteDialogOpen.value = true;
};

const handleDelete = () => {
    if (!teacherToDelete.value) return;

    router.delete(`/teachers/${teacherToDelete.value.id}`, {
        preserveScroll: true,
        only: ['teachers'],
        onSuccess: () => {
            deleteDialogOpen.value = false;
            teacherToDelete.value  = null;
            toast.success('Docente eliminado exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al eliminar el docente');
        },
    });
};

const columns: ColumnDef<Teacher>[] = [
    {
        accessorKey: 'full_name',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => column.toggleSorting(column.getIsSorted() === 'asc') },
                () => ['Nombre Completo', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('full_name')),
    },
    {
        accessorKey: 'identification_number',
        header: 'Cédula',
        cell: ({ row }) => h('div', { class: 'font-mono text-sm' }, row.getValue('identification_number')),
    },
    {
        accessorKey: 'institution',
        header: 'Institución',
        cell: ({ row }) => h('div', { class: 'text-sm' }, (row.getValue('institution') as string | null) ?? '—'),
    },
    {
        accessorKey: 'academic_degree',
        header: 'Título Académico',
        cell: ({ row }) => h('div', { class: 'text-sm' }, (row.getValue('academic_degree') as string | null) ?? '—'),
    },
    {
        accessorKey: 'hire_date',
        header: 'Fecha de Ingreso',
        cell: ({ row }) => h('div', { class: 'text-sm text-muted-foreground' }, (row.getValue('hire_date') as string | null) ?? '—'),
    },
    {
        accessorKey: 'active',
        header: 'Estado',
        cell: ({ row }) => {
            const active = row.getValue('active') as boolean;
            return h(Badge, { variant: active ? 'default' : 'secondary' }, () => active ? 'Activo' : 'Inactivo');
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const teacher = row.original;
            return h(DropdownMenu, {}, {
                default: () => [
                    h(DropdownMenuTrigger, { asChild: true }, {
                        default: () => h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, {
                            default: () => [h('span', { class: 'sr-only' }, 'Abrir menú'), h(MoreHorizontal, { class: 'h-4 w-4' })],
                        }),
                    }),
                    h(DropdownMenuContent, { align: 'end' }, {
                        default: () => [
                            h(DropdownMenuLabel, {}, () => 'Acciones'),
                            h(DropdownMenuItem, {
                                onClick: () => {
                                    navigator.clipboard.writeText(teacher.identification_number);
                                    toast.success('Cédula copiada al portapapeles');
                                },
                            }, () => 'Copiar cédula'),
                            h(DropdownMenuSeparator),
                            h(DropdownMenuItem, { onClick: () => openEditDialog(teacher) }, () => [
                                h(Pencil, { class: 'mr-2 h-4 w-4' }), 'Ver detalles',
                            ]),
                            h(DropdownMenuItem, {
                                class: 'text-destructive',
                                onClick: () => openDeleteDialog(teacher),
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
    <Head title="Docentes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-3">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Gestión de Docentes</h2>
                        <p class="text-muted-foreground">Consulta y administra el registro de docentes en el sistema</p>
                    </div>
                    <Button @click="openCreateDialog">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Docente
                    </Button>
                </div>

                <DataTable
                    :columns="columns"
                    :data="teachers"
                    filter-column="full_name"
                    filter-placeholder="Buscar por nombre..."
                />
            </div>
        </div>

        <Dialog v-model:open="formDialogOpen">
            <DialogScrollContent class="max-w-2xl w-[90vw]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <BookUser class="h-5 w-5 text-muted-foreground" />
                        {{ isEditing ? 'Ver / Editar Docente' : 'Nuevo Docente' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Modifica los datos del registro' : 'Completa los datos para registrar un nuevo docente' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-5 py-2">

                    <div>
                        <h3 class="text-xs font-semibold uppercase text-muted-foreground tracking-wider border-b pb-1 mb-3">
                            Persona
                        </h3>
                        <div class="space-y-1">
                            <Label>Persona <span class="text-destructive">*</span></Label>
                            <Select v-model="form.person_id" :disabled="isEditing">
                                <SelectTrigger>
                                    <SelectValue placeholder="Buscar persona..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="item in people" :key="item.id" :value="item.id">
                                        {{ item.name }} — {{ item.identification_number }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.person_id" class="text-destructive text-xs">{{ form.errors.person_id }}</p>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase text-muted-foreground tracking-wider border-b pb-1 mb-3">
                            Institución
                        </h3>
                        <div class="space-y-1">
                            <Label>Institución <span class="text-destructive">*</span></Label>
                            <Select v-model="form.institution_id">
                                <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="item in institutions" :key="item.id" :value="item.id">
                                        {{ item.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.institution_id" class="text-destructive text-xs">{{ form.errors.institution_id }}</p>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase text-muted-foreground tracking-wider border-b pb-1 mb-3">
                            Datos del Docente
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <Label>Título Académico</Label>
                                <Input v-model="form.academic_degree" placeholder="Ej: PhD en Matemáticas" />
                                <p v-if="form.errors.academic_degree" class="text-destructive text-xs">{{ form.errors.academic_degree }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Fecha de Ingreso</Label>
                                <Input v-model="form.hire_date" type="date" />
                                <p v-if="form.errors.hire_date" class="text-destructive text-xs">{{ form.errors.hire_date }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Estado</Label>
                                <Select v-model="form.active">
                                    <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Activo</SelectItem>
                                        <SelectItem value="0">Inactivo</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.active" class="text-destructive text-xs">{{ form.errors.active }}</p>
                            </div>
                        </div>
                    </div>

                </form>

                <DialogFooter>
                    <Button variant="outline" @click="formDialogOpen = false" :disabled="form.processing">
                        Cancelar
                    </Button>
                    <Button @click="submitForm" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : isEditing ? 'Actualizar' : 'Crear Docente' }}
                    </Button>
                </DialogFooter>
            </DialogScrollContent>
        </Dialog>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Estás seguro?</DialogTitle>
                    <DialogDescription>
                        Esta acción no se puede deshacer. Se eliminará permanentemente a
                        <span class="font-semibold">{{ teacherToDelete?.full_name }}</span>
                        y todos sus datos del sistema.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="deleteDialogOpen = false">Cancelar</Button>
                    <Button variant="destructive" @click="handleDelete">Eliminar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
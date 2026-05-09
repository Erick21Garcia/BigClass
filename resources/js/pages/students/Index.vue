<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowUpDown, MoreHorizontal, Pencil, Trash2, Plus, GraduationCap, ScrollText } from 'lucide-vue-next';

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
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

export interface Student {
    id: number;
    full_name: string;
    identification_number: string;
    enrollment_number: string;
    active: boolean;
    created_at: string;
    person_id: number;
}

interface PersonItem {
    id: number;
    name: string;
    identification_number: string;
}

interface Props {
    students: Student[];
    people:   PersonItem[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Estudiantes', href: '/students' },
];

const formDialogOpen   = ref(false);
const deleteDialogOpen = ref(false);
const isEditing        = ref(false);

const selectedStudent = ref<Student | null>(null);
const studentToDelete = ref<Student | null>(null);

const form = useForm({
    person_id:         '' as number | '',
    enrollment_number: '',
    active:            '1' as string,
});

const openCreateDialog = () => {
    isEditing.value = false;
    selectedStudent.value = null;
    form.reset();
    form.clearErrors();
    formDialogOpen.value = true;
};

const openEditDialog = (student: Student) => {
    isEditing.value = true;
    selectedStudent.value = student;
    form.reset();
    form.clearErrors();
    form.person_id         = student.person_id;
    form.enrollment_number = student.enrollment_number;
    form.active            = student.active ? '1' : '0';
    formDialogOpen.value   = true;
};

const submitForm = () => {
    if (isEditing.value && selectedStudent.value) {
        form.put(`/students/${selectedStudent.value.id}`, {
            preserveScroll: true,
            only: ['students'],
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Estudiante actualizado exitosamente');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0] as string;
                toast.error(firstError || 'Error al actualizar el estudiante');
            },
        });
    } else {
        form.post('/students', {
            preserveScroll: true,
            only: ['students'],
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Estudiante creado exitosamente');
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0] as string;
                toast.error(firstError || 'Error al crear el estudiante');
            },
        });
    }
};

const openDeleteDialog = (student: Student) => {
    studentToDelete.value  = student;
    deleteDialogOpen.value = true;
};

const handleDelete = () => {
    if (!studentToDelete.value) return;

    router.delete(`/students/${studentToDelete.value.id}`, {
        preserveScroll: true,
        only: ['students'],
        onSuccess: () => {
            deleteDialogOpen.value = false;
            studentToDelete.value  = null;
            toast.success('Estudiante eliminado exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al eliminar el estudiante');
        },
    });
};

const columns: ColumnDef<Student>[] = [
    {
        accessorKey: 'enrollment_number',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => column.toggleSorting(column.getIsSorted() === 'asc') },
                () => ['Matrícula', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'font-mono text-sm' }, row.getValue('enrollment_number')),
    },
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
        cell: ({ row }) => h('div', { class: 'font-mono text-sm text-muted-foreground' }, row.getValue('identification_number') ?? '—'),
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
        accessorKey: 'created_at',
        header: 'Registrado',
        cell: ({ row }) => h('div', { class: 'text-sm text-muted-foreground' }, row.getValue('created_at')),
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const student = row.original;
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
                                onClick: () => {
                                    navigator.clipboard.writeText(student.enrollment_number);
                                    toast.success('Matrícula copiada al portapapeles');
                                },
                            }, () => 'Copiar matrícula'),
                            h(DropdownMenuSeparator),
                            h(DropdownMenuItem, {
                                onClick: () => openEditDialog(student),
                            }, () => [h(Pencil, { class: 'mr-2 h-4 w-4' }), 'Editar']),
                            h(DropdownMenuSeparator),
                            h(DropdownMenuItem, {
                                onClick: () => window.open(`/reports/student/${student.id}/kardex`, '_blank'),
                            }, () => [h(ScrollText, { class: 'mr-2 h-4 w-4' }), 'Descargar Kardex']),
                            h(DropdownMenuSeparator),
                            h(DropdownMenuItem, {
                                class: 'text-destructive',
                                onClick: () => openDeleteDialog(student),
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
    <Head title="Estudiantes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-3">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Gestión de Estudiantes</h2>
                        <p class="text-muted-foreground">
                            Consulta y administra el registro de estudiantes en el sistema
                        </p>
                    </div>
                    <Button @click="openCreateDialog">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Estudiante
                    </Button>
                </div>

                <DataTable
                    :columns="columns"
                    :data="students"
                    filter-column="full_name"
                    filter-placeholder="Buscar por nombre..."
                />
            </div>
        </div>

        <Dialog v-model:open="formDialogOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <GraduationCap class="h-5 w-5 text-muted-foreground" />
                        {{ isEditing ? 'Editar Estudiante' : 'Nuevo Estudiante' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Modifica los datos del estudiante' : 'Completa los datos para registrar un nuevo estudiante' }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4 py-2" @submit.prevent="submitForm">

                    <div class="space-y-1">
                        <Label>Persona <span class="text-destructive">*</span></Label>
                        <Select
                            v-model="form.person_id"
                            :disabled="isEditing"
                        >
                            <SelectTrigger :class="{ 'border-destructive': form.errors.person_id }">
                                <SelectValue placeholder="Seleccionar persona..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="person in props.people"
                                    :key="person.id"
                                    :value="person.id"
                                >
                                    {{ person.name }} — {{ person.identification_number }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.person_id" class="text-xs text-destructive">{{ form.errors.person_id }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label for="enrollment_number">
                            Número de Matrícula <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="enrollment_number"
                            v-model="form.enrollment_number"
                            placeholder="Ej: MAT-2024-001"
                            :class="{ 'border-destructive': form.errors.enrollment_number }"
                        />
                        <p v-if="form.errors.enrollment_number" class="text-xs text-destructive">{{ form.errors.enrollment_number }}</p>
                    </div>

                    <div class="space-y-1">
                        <Label>Estado</Label>
                        <Select v-model="form.active">
                            <SelectTrigger :class="{ 'border-destructive': form.errors.active }">
                                <SelectValue placeholder="Seleccionar..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="1">Activo</SelectItem>
                                <SelectItem value="0">Inactivo</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.active" class="text-xs text-destructive">{{ form.errors.active }}</p>
                    </div>

                </form>

                <DialogFooter>
                    <Button variant="outline" :disabled="form.processing" @click="formDialogOpen = false">
                        Cancelar
                    </Button>
                    <Button :disabled="form.processing" @click="submitForm">
                        {{ form.processing ? 'Guardando...' : isEditing ? 'Actualizar' : 'Crear Estudiante' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Estás seguro?</DialogTitle>
                    <DialogDescription>
                        Esta acción no se puede deshacer. Se eliminará permanentemente a
                        <span class="font-semibold">{{ studentToDelete?.full_name }}</span>
                        del sistema.
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
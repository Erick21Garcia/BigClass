<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowUpDown, MoreHorizontal, Pencil, Trash2, Plus, Key  } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Badge } from '@/components/ui/badge';
import DataTable from '@/components/ui/data-table/DataTable.vue';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import permissionsRoute from '@/routes/permissions';

export interface Permission {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
}

interface Props {
    permissions: Permission[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: dashboard().url,
    },
    {
        title: 'Permisos',
        href: permissionsRoute.index().url,
    },
];

const createDialogOpen = ref(false);
const editDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const permissionToDelete = ref<Permission | null>(null);
const permissionToEdit = ref<Permission | null>(null);

const createForm = useForm({
    name: '',
    guard_name: 'web',
});

const editForm = useForm({
    name: '',
    guard_name: 'web',
});

const handleCreate = () => {
    createForm.post(permissionsRoute.store().url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            createDialogOpen.value = false;
            createForm.reset();
            toast.success('Permiso creado exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al crear el permiso');
        },
    });
};

const openEditDialog = (permission: Permission) => {
    permissionToEdit.value = permission;
    editForm.name = permission.name;
    editForm.guard_name = permission.guard_name;
    editDialogOpen.value = true;
};

const handleUpdate = () => {
    if (!permissionToEdit.value) return;

    editForm.put(permissionsRoute.update(permissionToEdit.value.id).url, {
        preserveScroll: true,
        preserveState: true,
        only: ['permissions'],
        onSuccess: () => {
            editDialogOpen.value = false;
            editForm.reset();
            toast.success('Permiso actualizado exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al actualizar el permiso');
        },
    });
};

const openDeleteDialog = (permission: Permission) => {
    permissionToDelete.value = permission;
    deleteDialogOpen.value = true;
};

const handleDelete = () => {
    if (!permissionToDelete.value) return;

    router.delete(permissionsRoute.destroy(permissionToDelete.value.id).url, {
        preserveScroll: true,
        preserveState: true,
        only: ['permissions'],
        onSuccess: () => {
            deleteDialogOpen.value = false;
            permissionToDelete.value = null;
            toast.success('Permiso eliminado exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al eliminar el permiso');
        },
    });
};

const columns: ColumnDef<Permission>[] = [
    {
        accessorKey: 'name',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Nombre', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]
            );
        },
        cell: ({ row }) => {
            return h(
                'div',
                { class: 'flex items-center gap-2' },
                [
                    h(Key, { class: 'h-4 w-4 text-muted-foreground' }),
                    h('span', { class: 'font-medium' }, row.getValue('name'))
                ]
            );
        },
    },
    {
        accessorKey: 'guard_name',
        header: 'Guard',
        cell: ({ row }) => {
            return h(
                Badge,
                { variant: 'outline' },
                () => row.getValue('guard_name')
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Fecha de Creación',
        cell: ({ row }) => {
            return h('div', { class: 'text-sm' }, row.getValue('created_at'));
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const permission = row.original;

            return h(
                DropdownMenu,
                {},
                {
                    default: () => [
                        h(
                            DropdownMenuTrigger,
                            { asChild: true },
                            {
                                default: () =>
                                    h(
                                        Button,
                                        {
                                            variant: 'ghost',
                                            class: 'h-8 w-8 p-0',
                                        },
                                        {
                                            default: () => [
                                                h('span', { class: 'sr-only' }, 'Abrir menú'),
                                                h(MoreHorizontal, { class: 'h-4 w-4' }),
                                            ],
                                        }
                                    ),
                            }
                        ),
                        h(
                            DropdownMenuContent,
                            { align: 'end' },
                            {
                                default: () => [
                                    h(DropdownMenuLabel, {}, () => 'Acciones'),
                                    h(
                                        DropdownMenuItem,
                                        {
                                            onClick: () => {
                                                navigator.clipboard.writeText(
                                                    permission.name
                                                );
                                                toast.success('Nombre del permiso copiado al portapapeles');
                                            },
                                        },
                                        () => 'Copiar nombre'
                                    ),
                                    h(DropdownMenuSeparator),
                                    h(
                                        DropdownMenuItem,
                                        {
                                            onClick: () => openEditDialog(permission),
                                        },
                                        () => [
                                            h(Pencil, { class: 'mr-2 h-4 w-4' }),
                                            'Editar',
                                        ]
                                    ),
                                    h(
                                        DropdownMenuItem,
                                        {
                                            class: 'text-destructive',
                                            onClick: () => openDeleteDialog(permission),
                                        },
                                        () => [
                                            h(Trash2, { class: 'mr-2 h-4 w-4' }),
                                            'Eliminar',
                                        ]
                                    ),
                                ],
                            }
                        ),
                    ],
                }
            );
        },
    },
];
</script>

<template>
    <Head title="Permisos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">
                            Gestión de Permisos
                        </h2>
                        <p class="text-muted-foreground">
                            Administra los permisos del sistema y controla el acceso a
                            funcionalidades
                        </p>
                    </div>
                    <Button @click="createDialogOpen = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Permiso
                    </Button>
                </div>
                <DataTable
                    :columns="columns"
                    :data="permissions"
                    filter-column="name"
                    filter-placeholder="Buscar..."
                />
            </div>
        </div>

        <Dialog v-model:open="createDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Crear Nuevo Permiso</DialogTitle>
                    <DialogDescription>
                        Define un nuevo permiso para controlar el acceso a
                        funcionalidades del sistema.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handleCreate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="create-name">Nombre del Permiso</Label>
                        <Input
                            id="create-name"
                            v-model="createForm.name"
                            placeholder="ej: users.create, posts.delete"
                            :class="{ 'border-destructive': createForm.errors.name }"
                        />
                        <p class="text-xs text-muted-foreground">
                            Usa el formato: recurso.accion (ej: users.create)
                        </p>
                        <p v-if="createForm.errors.name" class="text-sm text-destructive">
                            {{ createForm.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-guard">Guard Name</Label>
                        <Input
                            id="create-guard"
                            v-model="createForm.guard_name"
                            placeholder="web"
                            :class="{ 'border-destructive': createForm.errors.guard_name }"
                        />
                        <p class="text-xs text-muted-foreground">
                            Generalmente "web" para aplicaciones web
                        </p>
                        <p v-if="createForm.errors.guard_name" class="text-sm text-destructive">
                            {{ createForm.errors.guard_name }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="createDialogOpen = false"
                            :disabled="createForm.processing"
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="createForm.processing">
                            {{ createForm.processing ? 'Creando...' : 'Crear Permiso' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar Permiso</DialogTitle>
                    <DialogDescription>
                        Actualiza la información del permiso. Ten cuidado al cambiar
                        el nombre ya que puede afectar las validaciones existentes.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handleUpdate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="edit-name">Nombre del Permiso</Label>
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            placeholder="ej: users.create, posts.delete"
                            :class="{ 'border-destructive': editForm.errors.name }"
                        />
                        <p class="text-xs text-muted-foreground">
                            Usa el formato: recurso.accion (ej: users.create)
                        </p>
                        <p v-if="editForm.errors.name" class="text-sm text-destructive">
                            {{ editForm.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-guard">Guard Name</Label>
                        <Input
                            id="edit-guard"
                            v-model="editForm.guard_name"
                            placeholder="web"
                            :class="{ 'border-destructive': editForm.errors.guard_name }"
                        />
                        <p class="text-xs text-muted-foreground">
                            Generalmente "web" para aplicaciones web
                        </p>
                        <p v-if="editForm.errors.guard_name" class="text-sm text-destructive">
                            {{ editForm.errors.guard_name }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="editDialogOpen = false"
                            :disabled="editForm.processing"
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="editForm.processing">
                            {{ editForm.processing ? 'Actualizando...' : 'Actualizar Permiso' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Estás seguro?</DialogTitle>
                    <DialogDescription>
                        Esta acción no se puede deshacer. Esto eliminará permanentemente
                        el permiso
                        <span class="font-semibold">{{ permissionToDelete?.name }}</span>
                        y puede afectar a roles y usuarios que lo tengan asignado.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="deleteDialogOpen = false"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="handleDelete"
                    >
                        Eliminar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
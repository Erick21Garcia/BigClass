<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowUpDown, MoreHorizontal, Pencil, Trash2, Plus, ShieldCheck, KeyRound } from 'lucide-vue-next';

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
import rolesRoute from '@/routes/roles';

interface Permission {
    id: number;
    name: string;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions: Permission[];
    created_at: string;
}

interface Props {
    roles: Role[];
    permissions: Permission[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: dashboard().url },
    { title: 'Roles', href: rolesRoute.index().url },
];

const createDialogOpen = ref(false);
const editDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const permissionsDialogOpen = ref(false);

const roleToDelete = ref<Role | null>(null);
const roleToEdit = ref<Role | null>(null);
const roleToManagePermissions = ref<Role | null>(null);

const createForm = useForm({
    name: '',
    guard_name: 'web',
});

const editForm = useForm({
    name: '',
    guard_name: 'web',
});

const permissionsForm = useForm({
    permissions: [] as number[],
});

const permissionSearch = ref('');

const filteredPermissions = computed(() => {
    if (!permissionSearch.value) return props.permissions;
    const q = permissionSearch.value.toLowerCase();
    return props.permissions.filter(p => p.name.toLowerCase().includes(q));
});

const handleCreate = () => {
    createForm.post(rolesRoute.store().url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            createDialogOpen.value = false;
            createForm.reset();
            toast.success('Rol creado exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al crear el rol');
        },
    });
};

const openEditDialog = (role: Role) => {
    roleToEdit.value = role;
    editForm.name = role.name;
    editForm.guard_name = role.guard_name;
    editDialogOpen.value = true;
};

const handleUpdate = () => {
    if (!roleToEdit.value) return;
    editForm.put(rolesRoute.update(roleToEdit.value.id).url, {
        preserveScroll: true,
        preserveState: true,
        only: ['roles'],
        onSuccess: () => {
            editDialogOpen.value = false;
            editForm.reset();
            toast.success('Rol actualizado exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al actualizar el rol');
        },
    });
};

const openPermissionsDialog = (role: Role) => {
    const freshRole = props.roles.find(r => r.id === role.id) ?? role;
    roleToManagePermissions.value = freshRole;
    permissionsForm.permissions = freshRole.permissions.map(p => p.id);
    permissionSearch.value = '';
    permissionsDialogOpen.value = true;
};

const togglePermission = (id: number) => {
    const idx = permissionsForm.permissions.indexOf(id);
    if (idx === -1) {
        permissionsForm.permissions.push(id);
    } else {
        permissionsForm.permissions.splice(idx, 1);
    }
};

const isPermissionSelected = (id: number) =>
    permissionsForm.permissions.includes(id);

const handleSyncPermissions = () => {
    if (!roleToManagePermissions.value) return;

    permissionsForm.post(`/roles/${roleToManagePermissions.value.id}/permissions`, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            permissionsDialogOpen.value = false;
            toast.success('Permisos actualizados exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al actualizar los permisos');
        },
    });
};

const openDeleteDialog = (role: Role) => {
    roleToDelete.value = role;
    deleteDialogOpen.value = true;
};

const handleDelete = () => {
    if (!roleToDelete.value) return;
    router.delete(rolesRoute.destroy(roleToDelete.value.id).url, {
        preserveScroll: true,
        preserveState: true,
        only: ['roles'],
        onSuccess: () => {
            deleteDialogOpen.value = false;
            roleToDelete.value = null;
            toast.success('Rol eliminado exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al eliminar el rol');
        },
    });
};

const columns: ColumnDef<Role>[] = [
    {
        accessorKey: 'name',
        header: ({ column }) =>
            h(
                Button,
                { variant: 'ghost', onClick: () => column.toggleSorting(column.getIsSorted() === 'asc') },
                () => ['Nombre', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]
            ),
        cell: ({ row }) =>
            h('div', { class: 'flex items-center gap-2' }, [
                h(ShieldCheck, { class: 'h-4 w-4 text-muted-foreground' }),
                h('span', { class: 'font-medium' }, row.getValue('name')),
            ]),
    },
    {
        accessorKey: 'permissions',
        header: 'Permisos',
        cell: ({ row }) => {
            const permissions = row.getValue('permissions') as Permission[];

            if (!permissions.length) {
                return h('span', { class: 'text-xs text-muted-foreground italic' }, 'Sin permisos');
            }

            const visible = permissions.slice(0, 3);
            const remaining = permissions.length - visible.length;

            return h('div', { class: 'flex flex-wrap gap-1' }, [
                ...visible.map(p =>
                    h(Badge, { variant: 'secondary', class: 'text-xs' }, () => p.name)
                ),
                remaining > 0
                    ? h(Badge, { variant: 'outline', class: 'text-xs' }, () => `+${remaining} más`)
                    : null,
            ]);
        },
    },
    {
        accessorKey: 'guard_name',
        header: 'Guard',
        cell: ({ row }) =>
            h(Badge, { variant: 'outline' }, () => row.getValue('guard_name')),
    },
    {
        accessorKey: 'created_at',
        header: 'Fecha de Creación',
        cell: ({ row }) =>
            h('div', { class: 'text-sm' }, row.getValue('created_at')),
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const role = row.original;

            return h(DropdownMenu, {}, {
                default: () => [
                    h(DropdownMenuTrigger, { asChild: true }, {
                        default: () =>
                            h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, {
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
                                    navigator.clipboard.writeText(role.name);
                                    toast.success('Nombre del rol copiado al portapapeles');
                                },
                            }, () => 'Copiar nombre'),
                            h(DropdownMenuSeparator),
                            h(DropdownMenuItem, { onClick: () => openPermissionsDialog(role) }, () => [
                                h(KeyRound, { class: 'mr-2 h-4 w-4' }),
                                'Gestionar permisos',
                            ]),
                            h(DropdownMenuItem, { onClick: () => openEditDialog(role) }, () => [
                                h(Pencil, { class: 'mr-2 h-4 w-4' }),
                                'Editar',
                            ]),
                            h(DropdownMenuItem, {
                                class: 'text-destructive',
                                onClick: () => openDeleteDialog(role),
                            }, () => [
                                h(Trash2, { class: 'mr-2 h-4 w-4' }),
                                'Eliminar',
                            ]),
                        ],
                    }),
                ],
            });
        },
    },
];
</script>

<template>
    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Gestión de Roles</h2>
                        <p class="text-muted-foreground">
                            Administra los roles del sistema y controla el acceso a funcionalidades
                        </p>
                    </div>
                    <Button @click="createDialogOpen = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Rol
                    </Button>
                </div>
                <DataTable
                    :columns="columns"
                    :data="roles"
                    filter-column="name"
                    filter-placeholder="Buscar..."
                />
            </div>
        </div>

        <Dialog v-model:open="createDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Crear Nuevo Rol</DialogTitle>
                    <DialogDescription>
                        Define un nuevo rol para agrupar permisos y asignarlo a usuarios.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="handleCreate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="create-name">Nombre del Rol</Label>
                        <Input
                            id="create-name"
                            v-model="createForm.name"
                            placeholder="ej: admin, editor, viewer"
                            :class="{ 'border-destructive': createForm.errors.name }"
                        />
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
                        <p class="text-xs text-muted-foreground">Generalmente "web" para aplicaciones web</p>
                        <p v-if="createForm.errors.guard_name" class="text-sm text-destructive">
                            {{ createForm.errors.guard_name }}
                        </p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="createDialogOpen = false" :disabled="createForm.processing">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="createForm.processing">
                            {{ createForm.processing ? 'Creando...' : 'Crear Rol' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar Rol</DialogTitle>
                    <DialogDescription>
                        Actualiza la información del rol. Ten cuidado al cambiar el nombre
                        ya que puede afectar los usuarios que lo tienen asignado.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="handleUpdate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="edit-name">Nombre del Rol</Label>
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            placeholder="ej: admin, editor, viewer"
                            :class="{ 'border-destructive': editForm.errors.name }"
                        />
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
                        <p class="text-xs text-muted-foreground">Generalmente "web" para aplicaciones web</p>
                        <p v-if="editForm.errors.guard_name" class="text-sm text-destructive">
                            {{ editForm.errors.guard_name }}
                        </p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="editDialogOpen = false" :disabled="editForm.processing">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="editForm.processing">
                            {{ editForm.processing ? 'Actualizando...' : 'Actualizar Rol' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="permissionsDialogOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Gestionar Permisos</DialogTitle>
                    <DialogDescription>
                        Asigna o revoca permisos para el rol
                        <span class="font-semibold">{{ roleToManagePermissions?.name }}</span>.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3">
                    <Input
                        v-model="permissionSearch"
                        placeholder="Buscar permiso..."
                        class="h-8 text-sm"
                    />

                    <p class="text-xs text-muted-foreground">
                        {{ permissionsForm.permissions.length }} de {{ permissions.length }} permisos seleccionados
                    </p>

                    <div class="max-h-72 overflow-y-auto rounded-md border divide-y">
                        <label
                            v-for="permission in filteredPermissions"
                            :key="permission.id"
                            class="flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-muted/50 transition-colors"
                        >
                        <Checkbox
                            :model-value="isPermissionSelected(permission.id)"
                            @update:model-value="() => togglePermission(permission.id)"
                        />
                            <span class="text-sm font-mono">{{ permission.name }}</span>
                        </label>

                        <div v-if="filteredPermissions.length === 0" class="px-3 py-4 text-center text-sm text-muted-foreground">
                            No se encontraron permisos
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="permissionsDialogOpen = false" :disabled="permissionsForm.processing">
                        Cancelar
                    </Button>
                    <Button type="button" @click="handleSyncPermissions" :disabled="permissionsForm.processing">
                        {{ permissionsForm.processing ? 'Guardando...' : 'Guardar cambios' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Estás seguro?</DialogTitle>
                    <DialogDescription>
                        Esta acción no se puede deshacer. Esto eliminará permanentemente el rol
                        <span class="font-semibold">{{ roleToDelete?.name }}</span>
                        y puede afectar a los usuarios que lo tengan asignado.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" @click="deleteDialogOpen = false">
                        Cancelar
                    </Button>
                    <Button type="button" variant="destructive" @click="handleDelete">
                        Eliminar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
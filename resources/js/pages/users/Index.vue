<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowUpDown, MoreHorizontal, Pencil, Trash2, Plus } from 'lucide-vue-next';

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

export interface Role {
    id: number;
    name: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string;
}

interface Props {
    users: User[];
    roles: Role[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: dashboard().url,
    },
    {
        title: 'Usuarios',
        href: '/users',
    },
];

const createDialogOpen = ref(false);
const editDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const userToDelete = ref<User | null>(null);
const userToEdit = ref<User | null>(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

const toggleRole = (form: typeof createForm | typeof editForm, roleName: string) => {
    const index = form.roles.indexOf(roleName);
    if (index > -1) {
        form.roles.splice(index, 1);
    } else {
        form.roles.push(roleName);
    }
};

const handleCreate = () => {
    createForm.post('/users', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            createDialogOpen.value = false;
            createForm.reset();
            toast.success('Usuario creado exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al crear el usuario');
        },
    });
};

const openEditDialog = (user: User) => {
    userToEdit.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.roles = [...user.roles];
    editDialogOpen.value = true;
};

const handleUpdate = () => {
    if (!userToEdit.value) return;

    editForm.put(`/users/${userToEdit.value.id}`, {
        preserveScroll: true,
        preserveState: true,
        only: ['users'],
        onSuccess: () => {
            editDialogOpen.value = false;
            editForm.reset();
            toast.success('Usuario actualizado exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al actualizar el usuario');
        },
    });
};

const openDeleteDialog = (user: User) => {
    userToDelete.value = user;
    deleteDialogOpen.value = true;
};

const handleDelete = () => {
    if (!userToDelete.value) return;

    router.delete(`/users/${userToDelete.value.id}`, {
        preserveScroll: true,
        preserveState: true,
        only: ['users'],
        onSuccess: () => {
            deleteDialogOpen.value = false;
            userToDelete.value = null;
            toast.success('Usuario eliminado exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al eliminar el usuario');
        },
    });
};

const columns: ColumnDef<User>[] = [
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
            return h('div', { class: 'font-medium' }, row.getValue('name'));
        },
    },
    {
        accessorKey: 'email',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Email', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]
            );
        },
        cell: ({ row }) => {
            return h('div', { class: 'lowercase' }, row.getValue('email'));
        },
    },
    {
        accessorKey: 'roles',
        header: 'Roles',
        cell: ({ row }) => {
            const roles = row.getValue('roles') as string[];
            return h(
                'div',
                { class: 'flex gap-1 flex-wrap' },
                roles.map((role) =>
                    h(
                        Badge,
                        {
                            variant:
                                role === 'admin'
                                    ? 'default'
                                    : role === 'editor'
                                      ? 'secondary'
                                      : 'outline',
                        },
                        () => role
                    )
                )
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Fecha de Registro',
        cell: ({ row }) => {
            return h('div', row.getValue('created_at'));
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const user = row.original;

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
                                                    user.id.toString()
                                                );
                                                toast.success('ID copiado al portapapeles');
                                            },
                                        },
                                        () => 'Copiar ID'
                                    ),
                                    h(DropdownMenuSeparator),
                                    h(
                                        DropdownMenuItem,
                                        {
                                            onClick: () => openEditDialog(user),
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
                                            onClick: () => openDeleteDialog(user),
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
    <Head title="Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">
                            Gestión de Usuarios
                        </h2>
                        <p class="text-muted-foreground">
                            Administra los usuarios y sus roles en el sistema
                        </p>
                    </div>
                    <Button @click="createDialogOpen = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo Usuario
                    </Button>
                </div>
                <DataTable 
                    :columns="columns" 
                    :data="users" 
                    filter-column="email"
                    filter-placeholder="Buscar..."
                />
            </div>
        </div>

        <Dialog v-model:open="createDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Crear Nuevo Usuario</DialogTitle>
                    <DialogDescription>
                        Completa los datos para crear un nuevo usuario en el sistema.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handleCreate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="create-name">Nombre</Label>
                        <Input
                            id="create-name"
                            v-model="createForm.name"
                            placeholder="Nombre completo"
                            :class="{ 'border-destructive': createForm.errors.name }"
                        />
                        <p
                            v-if="createForm.errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ createForm.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-email">Email</Label>
                        <Input
                            id="create-email"
                            v-model="createForm.email"
                            type="email"
                            placeholder="usuario@ejemplo.com"
                            :class="{ 'border-destructive': createForm.errors.email }"
                        />
                        <p
                            v-if="createForm.errors.email"
                            class="text-sm text-destructive"
                        >
                            {{ createForm.errors.email }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-password">Contraseña</Label>
                        <Input
                            id="create-password"
                            v-model="createForm.password"
                            type="password"
                            placeholder="••••••••"
                            :class="{ 'border-destructive': createForm.errors.password }"
                        />
                        <p
                            v-if="createForm.errors.password"
                            class="text-sm text-destructive"
                        >
                            {{ createForm.errors.password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-password-confirmation"
                            >Confirmar Contraseña</Label
                        >
                        <Input
                            id="create-password-confirmation"
                            v-model="createForm.password_confirmation"
                            type="password"
                            placeholder="••••••••"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label>Roles</Label>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="role in roles"
                                :key="role.id"
                                :variant="
                                    createForm.roles.includes(role.name)
                                        ? 'default'
                                        : 'outline'
                                "
                                class="cursor-pointer"
                                @click="toggleRole(createForm, role.name)"
                            >
                                {{ role.name }}
                            </Badge>
                        </div>
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
                            {{ createForm.processing ? 'Creando...' : 'Crear Usuario' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar Usuario</DialogTitle>
                    <DialogDescription>
                        Actualiza la información del usuario. Deja la contraseña en
                        blanco si no deseas cambiarla.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="handleUpdate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="edit-name">Nombre</Label>
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            placeholder="Nombre completo"
                            :class="{ 'border-destructive': editForm.errors.name }"
                        />
                        <p
                            v-if="editForm.errors.name"
                            class="text-sm text-destructive"
                        >
                            {{ editForm.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-email">Email</Label>
                        <Input
                            id="edit-email"
                            v-model="editForm.email"
                            type="email"
                            placeholder="usuario@ejemplo.com"
                            :class="{ 'border-destructive': editForm.errors.email }"
                        />
                        <p
                            v-if="editForm.errors.email"
                            class="text-sm text-destructive"
                        >
                            {{ editForm.errors.email }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-password">Nueva Contraseña (opcional)</Label>
                        <Input
                            id="edit-password"
                            v-model="editForm.password"
                            type="password"
                            placeholder="••••••••"
                            :class="{ 'border-destructive': editForm.errors.password }"
                        />
                        <p
                            v-if="editForm.errors.password"
                            class="text-sm text-destructive"
                        >
                            {{ editForm.errors.password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-password-confirmation"
                            >Confirmar Nueva Contraseña</Label
                        >
                        <Input
                            id="edit-password-confirmation"
                            v-model="editForm.password_confirmation"
                            type="password"
                            placeholder="••••••••"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label>Roles</Label>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="role in roles"
                                :key="role.id"
                                :variant="
                                    editForm.roles.includes(role.name)
                                        ? 'default'
                                        : 'outline'
                                "
                                class="cursor-pointer"
                                @click="toggleRole(editForm, role.name)"
                            >
                                {{ role.name }}
                            </Badge>
                        </div>
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
                            {{
                                editForm.processing
                                    ? 'Actualizando...'
                                    : 'Actualizar Usuario'
                            }}
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
                        al usuario
                        <span class="font-semibold">{{ userToDelete?.name }}</span>
                        y removerá todos sus datos del sistema.
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
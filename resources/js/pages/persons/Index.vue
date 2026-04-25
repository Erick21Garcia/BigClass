<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table';
import { h, ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowUpDown, MoreHorizontal, Pencil, Trash2, Plus, UserRound } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

export interface Person {
    id: number;
    full_name: string;
    first_name: string;
    second_name: string | null;
    first_surname: string;
    second_surname: string | null;
    identification_number: string;
    type_identification: string | null;
    phone: string | null;
    cellphone: string | null;
    birthdate: string | null;
    place_birth: string | null;
    sex: string | null;
    marital_status: string | null;
    nationality: string | null;
    education_level: string | null;
    main_street: string | null;
    secondary_street: string | null;
    neighborhood: string | null;
    reference: string | null;
    country: string | null;
    province: string | null;
    city: string | null;
    created_at: string;
    sex_id: number | null;
    type_identification_id: number | null;
    marital_status_id: number | null;
    nationality_id: number | null;
    education_level_id: number | null;
    countries_id: number | null;
    provinces_id: number | null;
    cities_id: number | null;
}

interface CatalogItem { id: number; name: string; }
interface ProvinceItem extends CatalogItem { country_id: number; }
interface CityItem extends CatalogItem { province_id: number; }

interface Props {
    people: Person[];
    sexes: CatalogItem[];
    typeIdentifications: CatalogItem[];
    maritalStatuses: CatalogItem[];
    nationalities: CatalogItem[];
    educationLevels: CatalogItem[];
    countries: CatalogItem[];
    provinces: ProvinceItem[];
    cities: CityItem[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administración', href: dashboard().url },
    { title: 'Personas', href: '/people' },
];

const deleteDialogOpen = ref(false);
const formDialogOpen   = ref(false);
const isEditing        = ref(false);

const selectedPerson = ref<Person | null>(null);
const personToDelete = ref<Person | null>(null);

const emptyForm = {
    first_name:             '',
    second_name:            '',
    first_surname:          '',
    second_surname:         '',
    identification_number:  '',
    phone:                  '',
    cellphone:              '',
    birthdate:              '',
    place_birth:            '',
    main_street:            '',
    secondary_street:       '',
    neighborhood:           '',
    reference:              '',
    sex_id:                 '' as number | '',
    type_identification_id: '' as number | '',
    marital_status_id:      '' as number | '',
    nationality_id:         '' as number | '',
    education_level_id:     '' as number | '',
    countries_id:           '' as number | '',
    provinces_id:           '' as number | '',
    cities_id:              '' as number | '',
};

const form = useForm({ ...emptyForm });

const filteredProvinces = computed(() =>
    form.countries_id
        ? props.provinces.filter(p => p.country_id === Number(form.countries_id))
        : []
);

const filteredCities = computed(() =>
    form.provinces_id
        ? props.cities.filter(c => c.province_id === Number(form.provinces_id))
        : []
);

const onCountryChange  = () => { form.provinces_id = ''; form.cities_id = ''; };
const onProvinceChange = () => { form.cities_id = ''; };

const openCreateDialog = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    formDialogOpen.value = true;
};

const openEditDialog = (person: Person) => {
    isEditing.value = true;
    form.reset();
    form.clearErrors();

    form.first_name             = person.first_name;
    form.second_name            = person.second_name ?? '';
    form.first_surname          = person.first_surname;
    form.second_surname         = person.second_surname ?? '';
    form.identification_number  = person.identification_number;
    form.phone                  = person.phone ?? '';
    form.cellphone              = person.cellphone ?? '';
    form.birthdate              = person.birthdate ?? '';
    form.place_birth            = person.place_birth ?? '';
    form.main_street            = person.main_street ?? '';
    form.secondary_street       = person.secondary_street ?? '';
    form.neighborhood           = person.neighborhood ?? '';
    form.reference              = person.reference ?? '';
    form.sex_id                 = person.sex_id ?? '';
    form.type_identification_id = person.type_identification_id ?? '';
    form.marital_status_id      = person.marital_status_id ?? '';
    form.nationality_id         = person.nationality_id ?? '';
    form.education_level_id     = person.education_level_id ?? '';
    form.countries_id           = person.countries_id ?? '';
    form.provinces_id           = person.provinces_id ?? '';
    form.cities_id              = person.cities_id ?? '';

    selectedPerson.value = person;
    formDialogOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value && selectedPerson.value) {
        form.put(`/people/${selectedPerson.value.id}`, {
            preserveScroll: true,
            only: ['people'],
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Persona actualizada exitosamente');
            },
        });
    } else {
        form.post('/people', {
            preserveScroll: true,
            only: ['people'],
            onSuccess: () => {
                formDialogOpen.value = false;
                toast.success('Persona creada exitosamente');
            },
        });
    }
};

const openDeleteDialog = (person: Person) => {
    personToDelete.value  = person;
    deleteDialogOpen.value = true;
};

const handleDelete = () => {
    if (!personToDelete.value) return;

    router.delete(`/people/${personToDelete.value.id}`, {
        preserveScroll: true,
        only: ['people'],
        onSuccess: () => {
            deleteDialogOpen.value = false;
            personToDelete.value  = null;
            toast.success('Persona eliminada exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al eliminar la persona');
        },
    });
};

const columns: ColumnDef<Person>[] = [
    {
        accessorKey: 'identification_number',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => column.toggleSorting(column.getIsSorted() === 'asc') },
                () => ['Cédula', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'font-mono text-sm' }, row.getValue('identification_number')),
    },
    {
        accessorKey: 'full_name',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => column.toggleSorting(column.getIsSorted() === 'asc') },
                () => ['Nombre Completo', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('full_name')),
    },
    {
        accessorKey: 'cellphone',
        header: 'Celular',
        cell: ({ row }) => h('div', { class: 'text-sm' }, (row.getValue('cellphone') as string | null) ?? '—'),
    },
    {
        accessorKey: 'city',
        header: 'Ciudad',
        cell: ({ row }) => h('div', { class: 'text-sm' }, (row.getValue('city') as string | null) ?? '—'),
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
            const person = row.original;
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
                                    navigator.clipboard.writeText(person.identification_number);
                                    toast.success('Cédula copiada al portapapeles');
                                },
                            }, () => 'Copiar cédula'),
                            h(DropdownMenuSeparator),
                            h(DropdownMenuItem, { onClick: () => openEditDialog(person) }, () => [
                                h(Pencil, { class: 'mr-2 h-4 w-4' }), 'Ver detalles',
                            ]),
                            h(DropdownMenuItem, {
                                class: 'text-destructive',
                                onClick: () => openDeleteDialog(person),
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
    <Head title="Personas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="space-y-3">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Gestión de Personas</h2>
                        <p class="text-muted-foreground">Consulta y administra el registro de personas en el sistema</p>
                    </div>
                    <Button @click="openCreateDialog">
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Persona
                    </Button>
                </div>

                <DataTable
                    :columns="columns"
                    :data="people"
                    filter-column="full_name"
                    filter-placeholder="Buscar por nombre..."
                />
            </div>
        </div>

        <Dialog v-model:open="formDialogOpen">
            <DialogScrollContent class="max-w-4xl w-[90vw]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <UserRound class="h-5 w-5 text-muted-foreground" />
                        {{ isEditing ? 'Ver / Editar Persona' : 'Nueva Persona' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Modifica los datos del registro' : 'Completa los datos para registrar una nueva persona' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-5 py-2">

                    <div>
                        <h3 class="text-xs font-semibold uppercase text-muted-foreground tracking-wider border-b pb-1 mb-3">
                            Identificación
                        </h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <Label>Tipo <span class="text-destructive">*</span></Label>
                                <Select v-model="form.type_identification_id">
                                    <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="item in typeIdentifications" :key="item.id" :value="item.id">
                                            {{ item.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type_identification_id" class="text-destructive text-xs">{{ form.errors.type_identification_id }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Número <span class="text-destructive">*</span></Label>
                                <Input v-model="form.identification_number" placeholder="Ej: 1234567890" />
                                <p v-if="form.errors.identification_number" class="text-destructive text-xs">{{ form.errors.identification_number }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Fecha de Nacimiento</Label>
                                <Input v-model="form.birthdate" type="date" />
                                <p v-if="form.errors.birthdate" class="text-destructive text-xs">{{ form.errors.birthdate }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase text-muted-foreground tracking-wider border-b pb-1 mb-3">
                            Nombres
                        </h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <Label>Primer Nombre <span class="text-destructive">*</span></Label>
                                <Input v-model="form.first_name" placeholder="Ej: Juan" />
                                <p v-if="form.errors.first_name" class="text-destructive text-xs">{{ form.errors.first_name }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Segundo Nombre</Label>
                                <Input v-model="form.second_name" placeholder="Ej: Carlos" />
                                <p v-if="form.errors.second_name" class="text-destructive text-xs">{{ form.errors.second_name }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Primer Apellido <span class="text-destructive">*</span></Label>
                                <Input v-model="form.first_surname" placeholder="Ej: Pérez" />
                                <p v-if="form.errors.first_surname" class="text-destructive text-xs">{{ form.errors.first_surname }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Segundo Apellido</Label>
                                <Input v-model="form.second_surname" placeholder="Ej: García" />
                                <p v-if="form.errors.second_surname" class="text-destructive text-xs">{{ form.errors.second_surname }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase text-muted-foreground tracking-wider border-b pb-1 mb-3">
                            Datos Personales
                        </h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <Label>Sexo <span class="text-destructive">*</span></Label>
                                <Select v-model="form.sex_id">
                                    <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="item in sexes" :key="item.id" :value="item.id">{{ item.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.sex_id" class="text-destructive text-xs">{{ form.errors.sex_id }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Estado Civil</Label>
                                <Select v-model="form.marital_status_id">
                                    <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="item in maritalStatuses" :key="item.id" :value="item.id">{{ item.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.marital_status_id" class="text-destructive text-xs">{{ form.errors.marital_status_id }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Nacionalidad</Label>
                                <Select v-model="form.nationality_id">
                                    <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="item in nationalities" :key="item.id" :value="item.id">{{ item.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.nationality_id" class="text-destructive text-xs">{{ form.errors.nationality_id }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Nivel de Educación</Label>
                                <Select v-model="form.education_level_id">
                                    <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="item in educationLevels" :key="item.id" :value="item.id">{{ item.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.education_level_id" class="text-destructive text-xs">{{ form.errors.education_level_id }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Lugar de Nacimiento</Label>
                                <Input v-model="form.place_birth" placeholder="Ej: Quito" />
                                <p v-if="form.errors.place_birth" class="text-destructive text-xs">{{ form.errors.place_birth }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase text-muted-foreground tracking-wider border-b pb-1 mb-3">
                            Contacto
                        </h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <Label>Teléfono</Label>
                                <Input v-model="form.phone" placeholder="Ej: 022345678" />
                                <p v-if="form.errors.phone" class="text-destructive text-xs">{{ form.errors.phone }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Celular</Label>
                                <Input v-model="form.cellphone" placeholder="Ej: 0991234567" />
                                <p v-if="form.errors.cellphone" class="text-destructive text-xs">{{ form.errors.cellphone }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-semibold uppercase text-muted-foreground tracking-wider border-b pb-1 mb-3">
                            Dirección
                        </h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <Label>País</Label>
                                <Select v-model="form.countries_id" @update:modelValue="onCountryChange">
                                    <SelectTrigger><SelectValue placeholder="Seleccionar..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="item in countries" :key="item.id" :value="item.id">{{ item.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.countries_id" class="text-destructive text-xs">{{ form.errors.countries_id }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Provincia</Label>
                                <Select v-model="form.provinces_id" :disabled="!form.countries_id" @update:modelValue="onProvinceChange">
                                    <SelectTrigger><SelectValue placeholder="Seleccionar país primero..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="item in filteredProvinces" :key="item.id" :value="item.id">{{ item.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.provinces_id" class="text-destructive text-xs">{{ form.errors.provinces_id }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Ciudad</Label>
                                <Select v-model="form.cities_id" :disabled="!form.provinces_id">
                                    <SelectTrigger><SelectValue placeholder="Seleccionar provincia primero..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="item in filteredCities" :key="item.id" :value="item.id">{{ item.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.cities_id" class="text-destructive text-xs">{{ form.errors.cities_id }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Calle Principal</Label>
                                <Input v-model="form.main_street" placeholder="Ej: Av. Amazonas" />
                                <p v-if="form.errors.main_street" class="text-destructive text-xs">{{ form.errors.main_street }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Calle Secundaria</Label>
                                <Input v-model="form.secondary_street" placeholder="Ej: Juan León Mera" />
                                <p v-if="form.errors.secondary_street" class="text-destructive text-xs">{{ form.errors.secondary_street }}</p>
                            </div>
                            <div class="space-y-1">
                                <Label>Barrio</Label>
                                <Input v-model="form.neighborhood" placeholder="Ej: La Mariscal" />
                                <p v-if="form.errors.neighborhood" class="text-destructive text-xs">{{ form.errors.neighborhood }}</p>
                            </div>
                            <div class="col-span-3 space-y-1">
                                <Label>Referencia</Label>
                                <Input v-model="form.reference" placeholder="Ej: Frente al parque" />
                                <p v-if="form.errors.reference" class="text-destructive text-xs">{{ form.errors.reference }}</p>
                            </div>
                        </div>
                    </div>

                </form>

                <DialogFooter>
                    <Button variant="outline" @click="formDialogOpen = false" :disabled="form.processing">
                        Cancelar
                    </Button>
                    <Button @click="submitForm" :disabled="form.processing">
                        {{ form.processing ? 'Guardando...' : isEditing ? 'Actualizar' : 'Crear Persona' }}
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
                        <span class="font-semibold">{{ personToDelete?.full_name }}</span>
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
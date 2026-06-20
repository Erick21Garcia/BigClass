<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Building2, MapPin, Mail, Phone, Pencil, Trash2,
    Plus, ArrowLeft, GraduationCap
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

interface Faculty {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    dean_name: string | null;
    active: boolean;
}

interface Institution {
    id: number;
    name: string;
    type: string;
    code: string | null;
    acronym: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    province: string | null;
    country: string | null;
    active: boolean;
    faculties: Faculty[];
}

interface Props {
    institution: Institution;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Instituciones', href: '/institutions' },
    { title: props.institution.name, href: `/institutions/${props.institution.id}` },
];

const INSTITUTION_TYPES = [
    { value: 'university', label: 'Universidad' },
    { value: 'institute',  label: 'Instituto'   },
    { value: 'college',    label: 'Colegio'     },
    { value: 'school',     label: 'Escuela'     },
    { value: 'other',      label: 'Otro'        },
];

const editDialogOpen        = ref(false);
const deleteDialogOpen      = ref(false);
const createFacultyDialog   = ref(false);

const editForm = useForm({
    name:     props.institution.name,
    type:     props.institution.type,
    code:     props.institution.code     ?? '',
    acronym:  props.institution.acronym  ?? '',
    email:    props.institution.email    ?? '',
    phone:    props.institution.phone    ?? '',
    address:  props.institution.address  ?? '',
    city:     props.institution.city     ?? '',
    province: props.institution.province ?? '',
    country:  props.institution.country  ?? '',
    active:   props.institution.active,
});

const handleUpdate = () => {
    editForm.put(`/institutions/${props.institution.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
            toast.success('Institución actualizada exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al actualizar la institución');
        },
    });
};

const handleDelete = () => {
    router.delete(`/institutions/${props.institution.id}`, {
        onSuccess: () => {
            toast.success('Institución eliminada exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al eliminar la institución');
        },
    });
};

const facultyForm = useForm({
    institution_id: props.institution.id,
    name:           '',
    code:           '',
    description:    '',
    dean_name:      '',
    active:         true,
});

const handleCreateFaculty = () => {
    facultyForm.post('/faculties', {
        preserveScroll: true,
        onSuccess: () => {
            createFacultyDialog.value = false;
            facultyForm.reset();
            facultyForm.institution_id = props.institution.id;
            toast.success('Facultad creada exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al crear la facultad');
        },
    });
};

const goToFaculty = (id: number) => {
    router.visit(`/faculties/${id}`);
};
</script>

<template>
    <Head :title="props.institution.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-primary/10 p-3 text-primary">
                                <Building2 class="h-6 w-6" />
                            </div>
                            <div>
                                <CardTitle class="text-2xl">
                                    {{ props.institution.name }}
                                    <span v-if="props.institution.acronym" class="ml-2 font-mono text-base text-muted-foreground">
                                        ({{ props.institution.acronym }})
                                    </span>
                                </CardTitle>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Badge :variant="props.institution.active ? 'default' : 'secondary'">
                                {{ props.institution.active ? 'Activa' : 'Inactiva' }}
                            </Badge>
                            <Button variant="outline" size="sm" @click="editDialogOpen = true">
                                <Pencil class="mr-2 h-4 w-4" />
                                Editar
                            </Button>
                            <Button variant="destructive" size="sm" @click="deleteDialogOpen = true">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Eliminar
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-if="props.institution.email" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Mail class="h-4 w-4 shrink-0" />
                            {{ props.institution.email }}
                        </div>
                        <div v-if="props.institution.phone" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Phone class="h-4 w-4 shrink-0" />
                            {{ props.institution.phone }}
                        </div>
                        <div
                            v-if="props.institution.address || props.institution.city || props.institution.province || props.institution.country"
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <MapPin class="h-4 w-4 shrink-0" />
                            {{ [props.institution.address, props.institution.city, props.institution.province, props.institution.country].filter(Boolean).join(', ') }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold tracking-tight">Facultades</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ props.institution.faculties.length }} facultad{{ props.institution.faculties.length !== 1 ? 'es' : '' }} registrada{{ props.institution.faculties.length !== 1 ? 's' : '' }}
                        </p>
                    </div>
                    <Button @click="createFacultyDialog = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Facultad
                    </Button>
                </div>

                <div
                    v-if="props.institution.faculties.length === 0"
                    class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center text-muted-foreground"
                >
                    <GraduationCap class="mb-4 h-10 w-10 opacity-30" />
                    <p class="font-medium">No hay facultades registradas</p>
                    <p class="mt-1 text-sm">Agrega la primera usando el botón de arriba</p>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <Card
                        v-for="faculty in props.institution.faculties"
                        :key="faculty.id"
                        class="group cursor-pointer transition-all duration-200 hover:border-primary/50 hover:shadow-md"
                        @click="goToFaculty(faculty.id)"
                    >
                        <CardHeader class="pb-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="rounded-md bg-primary/10 p-2 text-primary transition-colors group-hover:bg-primary/20">
                                        <GraduationCap class="h-4 w-4" />
                                    </div>
                                    <CardTitle class="text-base leading-tight">
                                        {{ faculty.name }}
                                    </CardTitle>
                                </div>
                                <Badge
                                    :variant="faculty.active ? 'default' : 'secondary'"
                                    class="shrink-0 text-xs"
                                >
                                    {{ faculty.active ? 'Activa' : 'Inactiva' }}
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-1.5">
                            <CardDescription v-if="faculty.code" class="font-mono text-xs">
                                Código: {{ faculty.code }}
                            </CardDescription>
                            <CardDescription v-if="faculty.dean_name" class="text-xs">
                                Decano: {{ faculty.dean_name }}
                            </CardDescription>
                            <CardDescription v-if="faculty.description" class="line-clamp-2 text-xs">
                                {{ faculty.description }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Editar Institución</DialogTitle>
                    <DialogDescription>
                        Actualiza los datos de la institución.
                    </DialogDescription>
                </DialogHeader>

                <form class="mt-2 space-y-4" @submit.prevent="handleUpdate">
                    <div class="grid grid-cols-2 gap-4">

                        <div class="col-span-2 space-y-1">
                            <Label for="edit-name">Nombre <span class="text-destructive">*</span></Label>
                            <Input id="edit-name" v-model="editForm.name" placeholder="Ej. Universidad Central" :class="{ 'border-destructive': editForm.errors.name }" />
                            <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label>Tipo <span class="text-destructive">*</span></Label>
                            <Select v-model="editForm.type">
                                <SelectTrigger :class="{ 'border-destructive': editForm.errors.type }">
                                    <SelectValue placeholder="Seleccionar tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in INSTITUTION_TYPES" :key="t.value" :value="t.value">
                                        {{ t.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="editForm.errors.type" class="text-xs text-destructive">{{ editForm.errors.type }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-code">Código</Label>
                            <Input id="edit-code" v-model="editForm.code" placeholder="Ej. UC-001" :class="{ 'border-destructive': editForm.errors.code }" />
                            <p v-if="editForm.errors.code" class="text-xs text-destructive">{{ editForm.errors.code }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-acronym">Acrónimo</Label>
                            <Input id="edit-acronym" v-model="editForm.acronym" placeholder="Ej. UC" :class="{ 'border-destructive': editForm.errors.acronym }" />
                            <p v-if="editForm.errors.acronym" class="text-xs text-destructive">{{ editForm.errors.acronym }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-email">Correo electrónico</Label>
                            <Input id="edit-email" v-model="editForm.email" type="email" placeholder="contacto@institucion.edu" :class="{ 'border-destructive': editForm.errors.email }" />
                            <p v-if="editForm.errors.email" class="text-xs text-destructive">{{ editForm.errors.email }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-phone">Teléfono</Label>
                            <Input id="edit-phone" v-model="editForm.phone" placeholder="+593 99 999 9999" :class="{ 'border-destructive': editForm.errors.phone }" />
                            <p v-if="editForm.errors.phone" class="text-xs text-destructive">{{ editForm.errors.phone }}</p>
                        </div>

                        <div class="col-span-2 space-y-1">
                            <Label for="edit-address">Dirección</Label>
                            <Input id="edit-address" v-model="editForm.address" placeholder="Av. Principal 123" :class="{ 'border-destructive': editForm.errors.address }" />
                            <p v-if="editForm.errors.address" class="text-xs text-destructive">{{ editForm.errors.address }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-city">Ciudad</Label>
                            <Input id="edit-city" v-model="editForm.city" placeholder="Quito" :class="{ 'border-destructive': editForm.errors.city }" />
                            <p v-if="editForm.errors.city" class="text-xs text-destructive">{{ editForm.errors.city }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-province">Provincia</Label>
                            <Input id="edit-province" v-model="editForm.province" placeholder="Pichincha" :class="{ 'border-destructive': editForm.errors.province }" />
                            <p v-if="editForm.errors.province" class="text-xs text-destructive">{{ editForm.errors.province }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-country">País</Label>
                            <Input id="edit-country" v-model="editForm.country" placeholder="Ecuador" :class="{ 'border-destructive': editForm.errors.country }" />
                            <p v-if="editForm.errors.country" class="text-xs text-destructive">{{ editForm.errors.country }}</p>
                        </div>

                        <div class="col-span-2 flex items-center gap-3">
                            <Switch v-model="editForm.active" />
                            <Label>Institución activa</Label>
                        </div>

                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" :disabled="editForm.processing" @click="editDialogOpen = false">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="editForm.processing">
                            {{ editForm.processing ? 'Guardando...' : 'Guardar cambios' }}
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
                        Esta acción no se puede deshacer. Se eliminará permanentemente
                        <span class="font-semibold">{{ props.institution.name }}</span>
                        y todas sus facultades asociadas podrían verse afectadas.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">Cancelar</Button>
                    <Button variant="destructive" @click="handleDelete">Eliminar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="createFacultyDialog">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Nueva Facultad</DialogTitle>
                    <DialogDescription>
                        Agrega una facultad a {{ props.institution.name }}.
                    </DialogDescription>
                </DialogHeader>

                <form class="mt-2 space-y-4" @submit.prevent="handleCreateFaculty">
                    <div class="grid grid-cols-2 gap-4">

                        <div class="col-span-2 space-y-1">
                            <Label for="f-name">Nombre <span class="text-destructive">*</span></Label>
                            <Input id="f-name" v-model="facultyForm.name" placeholder="Ej. Facultad de Ingeniería" :class="{ 'border-destructive': facultyForm.errors.name }" />
                            <p v-if="facultyForm.errors.name" class="text-xs text-destructive">{{ facultyForm.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="f-code">Código</Label>
                            <Input id="f-code" v-model="facultyForm.code" placeholder="Ej. FI-001" :class="{ 'border-destructive': facultyForm.errors.code }" />
                            <p v-if="facultyForm.errors.code" class="text-xs text-destructive">{{ facultyForm.errors.code }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="f-dean">Nombre del Decano</Label>
                            <Input id="f-dean" v-model="facultyForm.dean_name" placeholder="Ej. Dr. Juan Pérez" :class="{ 'border-destructive': facultyForm.errors.dean_name }" />
                            <p v-if="facultyForm.errors.dean_name" class="text-xs text-destructive">{{ facultyForm.errors.dean_name }}</p>
                        </div>

                        <div class="col-span-2 space-y-1">
                            <Label for="f-description">Descripción</Label>
                            <Input id="f-description" v-model="facultyForm.description" placeholder="Breve descripción de la facultad" :class="{ 'border-destructive': facultyForm.errors.description }" />
                            <p v-if="facultyForm.errors.description" class="text-xs text-destructive">{{ facultyForm.errors.description }}</p>
                        </div>

                        <div class="col-span-2 flex items-center gap-3">
                            <Switch v-model="facultyForm.active" />
                            <Label>Facultad activa</Label>
                        </div>

                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" :disabled="facultyForm.processing" @click="createFacultyDialog = false">
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="facultyForm.processing">
                            {{ facultyForm.processing ? 'Guardando...' : 'Crear Facultad' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
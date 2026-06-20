<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    GraduationCap, Pencil, Trash2, Plus, ArrowLeft, BookOpen
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
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

interface Career {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    modality: string | null;
    duration_semesters: number | null;
    title_awarded: string | null;
    active: boolean;
}

interface Faculty {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    dean_name: string | null;
    active: boolean;
    institution: {
        id: number;
        name: string;
    };
    careers: Career[];
}

interface Props {
    faculty: Faculty;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Instituciones',             href: '/institutions' },
    { title: props.faculty.institution.name, href: `/institutions/${props.faculty.institution.id}` },
    { title: props.faculty.name,          href: `/faculties/${props.faculty.id}` },
];

const editDialogOpen        = ref(false);
const deleteDialogOpen      = ref(false);
const createCareerDialog    = ref(false);

const editForm = useForm({
    institution_id: props.faculty.institution.id,
    name:           props.faculty.name,
    code:           props.faculty.code        ?? '',
    description:    props.faculty.description ?? '',
    dean_name:      props.faculty.dean_name   ?? '',
    active:         props.faculty.active,
});

const handleUpdate = () => {
    editForm.put(`/faculties/${props.faculty.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
            toast.success('Facultad actualizada exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al actualizar la facultad');
        },
    });
};

const handleDelete = () => {
    router.delete(`/faculties/${props.faculty.id}`, {
        onSuccess: () => {
            toast.success('Facultad eliminada exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al eliminar la facultad');
        },
    });
};

const careerForm = useForm({
    faculty_id:          props.faculty.id,
    name:                '',
    code:                '',
    description:         '',
    modality:            '',
    duration_semesters:  '' as unknown as number,
    title_awarded:       '',
    active:              true,
});

const handleCreateCareer = () => {
    careerForm.post('/careers', {
        preserveScroll: true,
        onSuccess: () => {
            createCareerDialog.value = false;
            careerForm.reset();
            careerForm.faculty_id = props.faculty.id;
            toast.success('Carrera creada exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al crear la carrera');
        },
    });
};

const MODALITIES = [
    { value: 'presencial',   label: 'Presencial'   },
    { value: 'semipresencial', label: 'Semipresencial' },
    { value: 'virtual',      label: 'Virtual'      },
];

const getModalityLabel = (value: string) =>
    MODALITIES.find(m => m.value === value)?.label ?? value;

const goToCareer = (id: number) => {
    router.visit(`/careers/${id}`);
};
</script>

<template>
    <Head :title="props.faculty.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-primary/10 p-3 text-primary">
                                <GraduationCap class="h-6 w-6" />
                            </div>
                            <div>
                                <CardTitle class="text-2xl">
                                    {{ props.faculty.name }}
                                </CardTitle>
                                <CardDescription class="mt-0.5">
                                    {{ props.faculty.institution.name }}
                                    <span v-if="props.faculty.code" class="ml-2 font-mono">
                                        · {{ props.faculty.code }}
                                    </span>
                                </CardDescription>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Badge :variant="props.faculty.active ? 'default' : 'secondary'">
                                {{ props.faculty.active ? 'Activa' : 'Inactiva' }}
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
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div v-if="props.faculty.dean_name" class="text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">Decano:</span>
                            {{ props.faculty.dean_name }}
                        </div>
                        <div v-if="props.faculty.description" class="text-sm text-muted-foreground sm:col-span-2">
                            <span class="font-medium text-foreground">Descripción:</span>
                            {{ props.faculty.description }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold tracking-tight">Carreras</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ props.faculty.careers.length }} carrera{{ props.faculty.careers.length !== 1 ? 's' : '' }} registrada{{ props.faculty.careers.length !== 1 ? 's' : '' }}
                        </p>
                    </div>
                    <Button @click="createCareerDialog = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Nueva Carrera
                    </Button>
                </div>

                <div
                    v-if="props.faculty.careers.length === 0"
                    class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center text-muted-foreground"
                >
                    <BookOpen class="mb-4 h-10 w-10 opacity-30" />
                    <p class="font-medium">No hay carreras registradas</p>
                    <p class="mt-1 text-sm">Agrega la primera usando el botón de arriba</p>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <Card
                        v-for="career in props.faculty.careers"
                        :key="career.id"
                        class="group cursor-pointer transition-all duration-200 hover:border-primary/50 hover:shadow-md"
                        @click="goToCareer(career.id)"
                    >
                        <CardHeader class="pb-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="rounded-md bg-primary/10 p-2 text-primary transition-colors group-hover:bg-primary/20">
                                        <BookOpen class="h-4 w-4" />
                                    </div>
                                    <CardTitle class="text-base leading-tight">
                                        {{ career.name }}
                                    </CardTitle>
                                </div>
                                <Badge
                                    :variant="career.active ? 'default' : 'secondary'"
                                    class="shrink-0 text-xs"
                                >
                                    {{ career.active ? 'Activa' : 'Inactiva' }}
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-1.5">
                            <CardDescription v-if="career.code" class="font-mono text-xs">
                                Código: {{ career.code }}
                            </CardDescription>
                            <CardDescription v-if="career.modality" class="text-xs">
                                {{ getModalityLabel(career.modality) }}
                            </CardDescription>
                            <CardDescription v-if="career.duration_semesters" class="text-xs">
                                {{ career.duration_semesters }} semestres
                            </CardDescription>
                            <CardDescription v-if="career.title_awarded" class="line-clamp-1 text-xs">
                                {{ career.title_awarded }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Editar Facultad</DialogTitle>
                    <DialogDescription>Actualiza los datos de la facultad.</DialogDescription>
                </DialogHeader>

                <form class="mt-2 space-y-4" @submit.prevent="handleUpdate">
                    <div class="grid grid-cols-2 gap-4">

                        <div class="col-span-2 space-y-1">
                            <Label for="edit-name">Nombre <span class="text-destructive">*</span></Label>
                            <Input id="edit-name" v-model="editForm.name" placeholder="Ej. Facultad de Ingeniería" :class="{ 'border-destructive': editForm.errors.name }" />
                            <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-code">Código</Label>
                            <Input id="edit-code" v-model="editForm.code" placeholder="Ej. FI-001" :class="{ 'border-destructive': editForm.errors.code }" />
                            <p v-if="editForm.errors.code" class="text-xs text-destructive">{{ editForm.errors.code }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-dean">Nombre del Decano</Label>
                            <Input id="edit-dean" v-model="editForm.dean_name" placeholder="Ej. Dr. Juan Pérez" :class="{ 'border-destructive': editForm.errors.dean_name }" />
                            <p v-if="editForm.errors.dean_name" class="text-xs text-destructive">{{ editForm.errors.dean_name }}</p>
                        </div>

                        <div class="col-span-2 space-y-1">
                            <Label for="edit-description">Descripción</Label>
                            <Input id="edit-description" v-model="editForm.description" placeholder="Breve descripción" :class="{ 'border-destructive': editForm.errors.description }" />
                            <p v-if="editForm.errors.description" class="text-xs text-destructive">{{ editForm.errors.description }}</p>
                        </div>

                        <div class="col-span-2 flex items-center gap-3">
                            <Switch v-model="editForm.active" />
                            <Label>Facultad activa</Label>
                        </div>

                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" :disabled="editForm.processing" @click="editDialogOpen = false">Cancelar</Button>
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
                        <span class="font-semibold">{{ props.faculty.name }}</span>
                        y puede afectar las carreras asociadas.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">Cancelar</Button>
                    <Button variant="destructive" @click="handleDelete">Eliminar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="createCareerDialog">
            <DialogContent class="max-h-[90vh] max-w-lg overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Nueva Carrera</DialogTitle>
                    <DialogDescription>
                        Agrega una carrera a {{ props.faculty.name }}.
                    </DialogDescription>
                </DialogHeader>

                <form class="mt-2 space-y-4" @submit.prevent="handleCreateCareer">
                    <div class="grid grid-cols-2 gap-4">

                        <div class="col-span-2 space-y-1">
                            <Label for="c-name">Nombre <span class="text-destructive">*</span></Label>
                            <Input id="c-name" v-model="careerForm.name" placeholder="Ej. Ingeniería en Sistemas" :class="{ 'border-destructive': careerForm.errors.name }" />
                            <p v-if="careerForm.errors.name" class="text-xs text-destructive">{{ careerForm.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="c-code">Código</Label>
                            <Input id="c-code" v-model="careerForm.code" placeholder="Ej. IS-001" :class="{ 'border-destructive': careerForm.errors.code }" />
                            <p v-if="careerForm.errors.code" class="text-xs text-destructive">{{ careerForm.errors.code }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="c-duration">Duración (semestres)</Label>
                            <Input id="c-duration" v-model="careerForm.duration_semesters" type="number" min="1" max="20" placeholder="Ej. 10" :class="{ 'border-destructive': careerForm.errors.duration_semesters }" />
                            <p v-if="careerForm.errors.duration_semesters" class="text-xs text-destructive">{{ careerForm.errors.duration_semesters }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="c-modality">Modalidad</Label>
                            <Input id="c-modality" v-model="careerForm.modality" placeholder="Ej. Presencial" :class="{ 'border-destructive': careerForm.errors.modality }" />
                            <p v-if="careerForm.errors.modality" class="text-xs text-destructive">{{ careerForm.errors.modality }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="c-title">Título que otorga</Label>
                            <Input id="c-title" v-model="careerForm.title_awarded" placeholder="Ej. Ingeniero en Sistemas" :class="{ 'border-destructive': careerForm.errors.title_awarded }" />
                            <p v-if="careerForm.errors.title_awarded" class="text-xs text-destructive">{{ careerForm.errors.title_awarded }}</p>
                        </div>

                        <div class="col-span-2 space-y-1">
                            <Label for="c-description">Descripción</Label>
                            <Input id="c-description" v-model="careerForm.description" placeholder="Breve descripción de la carrera" :class="{ 'border-destructive': careerForm.errors.description }" />
                            <p v-if="careerForm.errors.description" class="text-xs text-destructive">{{ careerForm.errors.description }}</p>
                        </div>

                        <div class="col-span-2 flex items-center gap-3">
                            <Switch v-model="careerForm.active" />
                            <Label>Carrera activa</Label>
                        </div>

                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" :disabled="careerForm.processing" @click="createCareerDialog = false">Cancelar</Button>
                        <Button type="submit" :disabled="careerForm.processing">
                            {{ careerForm.processing ? 'Guardando...' : 'Crear Carrera' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
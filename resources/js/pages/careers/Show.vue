<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    BookOpen, Pencil, Trash2, Plus, Calendar, LayoutList,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';
import {
    Dialog, DialogContent, DialogDescription,
    DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

interface Semester {
    id: number;
    name: string;
    number: number;
    active: boolean;
}

interface Career {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    modality: string | null;
    duration_semesters: number | null;
    title_awarded: string | null;
    active: boolean;
    faculty: {
        id: number;
        name: string;
        institution: {
            id: number;
            name: string;
        };
    };
    semesters: Semester[];
}

interface Props {
    career: Career;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Instituciones',                       href: '/institutions' },
    { title: props.career.faculty.institution.name, href: `/institutions/${props.career.faculty.institution.id}` },
    { title: props.career.faculty.name,             href: `/faculties/${props.career.faculty.id}` },
    { title: props.career.name,                     href: `/careers/${props.career.id}` },
];

const editDialogOpen       = ref(false);
const deleteDialogOpen     = ref(false);
const createSemesterDialog = ref(false);

const editForm = useForm({
    faculty_id:         props.career.faculty.id,
    name:               props.career.name,
    code:               props.career.code               ?? '',
    description:        props.career.description        ?? '',
    modality:           props.career.modality           ?? '',
    duration_semesters: props.career.duration_semesters ?? '' as unknown as number,
    title_awarded:      props.career.title_awarded      ?? '',
    active:             props.career.active,
});

const handleUpdate = () => {
    editForm.put(`/careers/${props.career.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
            toast.success('Carrera actualizada exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al actualizar la carrera');
        },
    });
};

const handleDelete = () => {
    router.delete(`/careers/${props.career.id}`, {
        onSuccess: () => toast.success('Carrera eliminada exitosamente'),
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al eliminar la carrera');
        },
    });
};

const semesterForm = useForm({
    career_id: props.career.id,
    number:    '' as unknown as number,
    name:      '',
    active:    true,
});

const handleCreateSemester = () => {
    semesterForm.post('/semesters', {
        preserveScroll: true,
        onSuccess: () => {
            createSemesterDialog.value = false;
            semesterForm.reset();
            semesterForm.career_id = props.career.id;
            toast.success('Semestre creado exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al crear el semestre');
        },
    });
};

const goToSemester        = (id: number) => router.visit(`/semesters/${id}`);
const goToManageCurricula = () => router.visit(`/curricula/manage?career_id=${props.career.id}`);

const sortedSemesters = () =>
    [...props.career.semesters].sort((a, b) => a.number - b.number);
</script>

<template>
    <Head :title="props.career.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-primary/10 p-3 text-primary">
                                <BookOpen class="h-6 w-6" />
                            </div>
                            <div>
                                <CardTitle class="text-2xl">{{ props.career.name }}</CardTitle>
                                <CardDescription class="mt-0.5">
                                    {{ props.career.faculty.name }} · {{ props.career.faculty.institution.name }}
                                    <span v-if="props.career.code" class="ml-2 font-mono">
                                        · {{ props.career.code }}
                                    </span>
                                </CardDescription>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Badge :variant="props.career.active ? 'default' : 'secondary'">
                                {{ props.career.active ? 'Activa' : 'Inactiva' }}
                            </Badge>
                            <Button variant="outline" size="sm" @click="goToManageCurricula">
                                <LayoutList class="mr-2 h-4 w-4" />
                                Malla curricular
                            </Button>
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
                        <div v-if="props.career.modality" class="text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">Modalidad:</span>
                            {{ props.career.modality }}
                        </div>
                        <div v-if="props.career.duration_semesters" class="text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">Duración:</span>
                            {{ props.career.duration_semesters }} semestres
                        </div>
                        <div v-if="props.career.title_awarded" class="text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">Título:</span>
                            {{ props.career.title_awarded }}
                        </div>
                        <div v-if="props.career.description" class="text-sm text-muted-foreground sm:col-span-2 lg:col-span-3">
                            <span class="font-medium text-foreground">Descripción:</span>
                            {{ props.career.description }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold tracking-tight">Semestres</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ props.career.semesters.length }}
                            semestre{{ props.career.semesters.length !== 1 ? 's' : '' }}
                            registrado{{ props.career.semesters.length !== 1 ? 's' : '' }}
                        </p>
                    </div>
                    <Button @click="createSemesterDialog = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Nuevo semestre
                    </Button>
                </div>

                <div
                    v-if="props.career.semesters.length === 0"
                    class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center text-muted-foreground"
                >
                    <Calendar class="mb-4 h-10 w-10 opacity-30" />
                    <p class="font-medium">No hay semestres registrados</p>
                    <p class="mt-1 text-sm">Agrega el primero usando el botón de arriba</p>
                </div>

                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="semester in sortedSemesters()"
                        :key="semester.id"
                        class="group cursor-pointer transition-all duration-200 hover:border-primary/50 hover:shadow-md"
                        @click="goToSemester(semester.id)"
                    >
                        <CardHeader class="pb-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="rounded-md bg-primary/10 p-2 text-primary transition-colors group-hover:bg-primary/20">
                                        <Calendar class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <CardTitle class="text-base leading-tight">
                                            {{ semester.name }}
                                        </CardTitle>
                                        <CardDescription class="text-xs">
                                            Semestre {{ semester.number }}
                                        </CardDescription>
                                    </div>
                                </div>
                                <Badge :variant="semester.active ? 'default' : 'secondary'" class="shrink-0 text-xs">
                                    {{ semester.active ? 'Activo' : 'Inactivo' }}
                                </Badge>
                            </div>
                        </CardHeader>
                    </Card>
                </div>
            </div>

        </div>

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-h-[90vh] max-w-lg overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Editar Carrera</DialogTitle>
                    <DialogDescription>Actualiza los datos de la carrera.</DialogDescription>
                </DialogHeader>

                <form class="mt-2 space-y-4" @submit.prevent="handleUpdate">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 space-y-1">
                            <Label for="edit-name">Nombre <span class="text-destructive">*</span></Label>
                            <Input id="edit-name" v-model="editForm.name" placeholder="Ej. Ingeniería en Sistemas" :class="{ 'border-destructive': editForm.errors.name }" />
                            <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-code">Código</Label>
                            <Input id="edit-code" v-model="editForm.code" placeholder="Ej. IS-001" :class="{ 'border-destructive': editForm.errors.code }" />
                            <p v-if="editForm.errors.code" class="text-xs text-destructive">{{ editForm.errors.code }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-duration">Duración (semestres)</Label>
                            <Input id="edit-duration" v-model="editForm.duration_semesters" type="number" min="1" max="20" placeholder="Ej. 10" :class="{ 'border-destructive': editForm.errors.duration_semesters }" />
                            <p v-if="editForm.errors.duration_semesters" class="text-xs text-destructive">{{ editForm.errors.duration_semesters }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-modality">Modalidad</Label>
                            <Input id="edit-modality" v-model="editForm.modality" placeholder="Ej. Presencial" :class="{ 'border-destructive': editForm.errors.modality }" />
                            <p v-if="editForm.errors.modality" class="text-xs text-destructive">{{ editForm.errors.modality }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="edit-title">Título que otorga</Label>
                            <Input id="edit-title" v-model="editForm.title_awarded" placeholder="Ej. Ingeniero en Sistemas" :class="{ 'border-destructive': editForm.errors.title_awarded }" />
                            <p v-if="editForm.errors.title_awarded" class="text-xs text-destructive">{{ editForm.errors.title_awarded }}</p>
                        </div>

                        <div class="col-span-2 space-y-1">
                            <Label for="edit-description">Descripción</Label>
                            <Input id="edit-description" v-model="editForm.description" placeholder="Breve descripción" :class="{ 'border-destructive': editForm.errors.description }" />
                            <p v-if="editForm.errors.description" class="text-xs text-destructive">{{ editForm.errors.description }}</p>
                        </div>

                        <div class="col-span-2 flex items-center gap-3">
                            <Checkbox
                                id="edit-active"
                                :checked="editForm.active"
                                @update:checked="(v: boolean) => editForm.active = v"
                            />
                            <Label for="edit-active">Carrera activa</Label>
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
                        <span class="font-semibold">{{ props.career.name }}</span>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">Cancelar</Button>
                    <Button variant="destructive" @click="handleDelete">Eliminar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="createSemesterDialog">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Nuevo semestre</DialogTitle>
                    <DialogDescription>Agrega un semestre a {{ props.career.name }}.</DialogDescription>
                </DialogHeader>

                <form class="mt-2 space-y-4" @submit.prevent="handleCreateSemester">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <Label for="s-number">Número <span class="text-destructive">*</span></Label>
                            <Input id="s-number" v-model="semesterForm.number" type="number" min="1" max="20" placeholder="Ej. 1" :class="{ 'border-destructive': semesterForm.errors.number }" />
                            <p v-if="semesterForm.errors.number" class="text-xs text-destructive">{{ semesterForm.errors.number }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="s-name">Nombre <span class="text-destructive">*</span></Label>
                            <Input id="s-name" v-model="semesterForm.name" placeholder="Ej. Primer Semestre" :class="{ 'border-destructive': semesterForm.errors.name }" />
                            <p v-if="semesterForm.errors.name" class="text-xs text-destructive">{{ semesterForm.errors.name }}</p>
                        </div>

                        <div class="col-span-2 flex items-center gap-3">
                            <Checkbox
                                id="s-active"
                                :checked="semesterForm.active"
                                @update:checked="(v: boolean) => semesterForm.active = v"
                            />
                            <Label for="s-active">Semestre activo</Label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" :disabled="semesterForm.processing" @click="createSemesterDialog = false">Cancelar</Button>
                        <Button type="submit" :disabled="semesterForm.processing">
                            {{ semesterForm.processing ? 'Guardando...' : 'Crear semestre' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, BookOpen, Plus, Trash2, LayoutList, AlertCircle,
} from 'lucide-vue-next';
import type { AcceptableValue } from 'reka-ui';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';
import {
    Dialog, DialogContent, DialogDescription,
    DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

interface SubjectInSemester {
    curriculum_id: number;
    subject_id:    number;
    subject_name:  string;
    subject_code:  string | null;
    credits:       number;
    is_mandatory:  boolean;
}

interface SemesterWithSubjects {
    id:       number;
    name:     string;
    number:   number;
    active:   boolean;
    subjects: SubjectInSemester[];
}

interface SubjectOption {
    id:      number;
    name:    string;
    code:    string | null;
    credits: number;
}

interface Career {
    id:   number;
    name: string;
    faculty: {
        id:   number;
        name: string;
        institution: {
            id:   number;
            name: string;
        };
    };
}

interface Props {
    career:    Career;
    semesters: SemesterWithSubjects[];
    subjects:  SubjectOption[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Instituciones',                                href: '/institutions' },
    { title: props.career.faculty.institution.name,          href: `/institutions/${props.career.faculty.institution.id}` },
    { title: props.career.faculty.name,                      href: `/faculties/${props.career.faculty.id}` },
    { title: props.career.name,                              href: `/careers/${props.career.id}` },
    { title: 'Malla curricular',                             href: '#' },
];

const assignDialogOpen     = ref(false);
const selectedSemesterId   = ref<number | null>(null);
const selectedSemesterName = ref('');

const assignForm = useForm({
    career_id:    props.career.id,
    semester_id:  '' as unknown as number,
    subject_id:   '',
    is_mandatory: true,
    active:       true,
    redirect_to:  'manage',
});

const openAssignDialog = (semester: SemesterWithSubjects) => {
    selectedSemesterId.value   = semester.id;
    selectedSemesterName.value = semester.name;
    assignForm.semester_id     = semester.id;
    assignForm.subject_id      = '';
    assignForm.is_mandatory    = true;
    assignForm.clearErrors();
    assignDialogOpen.value     = true;
};

const onSubjectChange = (value: AcceptableValue) => {
    if (value) assignForm.subject_id = String(value);
};

const assignedSubjectIds = computed(() => {
    if (!selectedSemesterId.value) return new Set<number>();
    const semester = props.semesters.find(s => s.id === selectedSemesterId.value);
    return new Set(semester?.subjects.map(s => s.subject_id) ?? []);
});

const availableSubjectsForAssign = computed(() =>
    props.subjects.filter(s => !assignedSubjectIds.value.has(s.id))
);

const handleAssign = () => {
    assignForm.post('/curricula', {
        onSuccess: () => {
            assignDialogOpen.value = false;
            toast.success('Materia asignada exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al asignar la materia');
        },
    });
};

const deleteDialogOpen    = ref(false);
const curriculumToDelete  = ref<{ id: number; name: string } | null>(null);

const openDeleteDialog = (curriculum: SubjectInSemester) => {
    curriculumToDelete.value = { id: curriculum.curriculum_id, name: curriculum.subject_name };
    deleteDialogOpen.value   = true;
};

const handleDelete = () => {
    if (!curriculumToDelete.value) return;

    router.delete(`/curricula/${curriculumToDelete.value.id}`, {
        onSuccess: () => {
            deleteDialogOpen.value   = false;
            curriculumToDelete.value = null;
            toast.success('Materia removida de la malla exitosamente');
        },
        onError: (errors) => {
            toast.error((Object.values(errors)[0] as string) || 'Error al remover la materia');
        },
    });
};

const goBack = () => router.visit(`/careers/${props.career.id}`);

const totalSubjects = computed(() =>
    props.semesters.reduce((sum, s) => sum + s.subjects.length, 0)
);
</script>

<template>
    <Head title="Malla curricular" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="sm" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">Malla curricular</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ props.career.name }} · {{ totalSubjects }} materia{{ totalSubjects !== 1 ? 's' : '' }} asignada{{ totalSubjects !== 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>
            </div>

            <Card v-if="props.semesters.length === 0">
                <CardContent class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground">
                    <LayoutList class="mb-3 h-10 w-10 opacity-30" />
                    <p class="font-medium">No hay semestres registrados</p>
                    <p class="mt-1 text-sm">
                        Primero crea los semestres desde la
                        <button class="text-primary underline underline-offset-2" @click="goBack">página de la carrera</button>.
                    </p>
                </CardContent>
            </Card>

            <Card v-for="semester in props.semesters" :key="semester.id">
                <CardHeader>
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-primary/10 p-2 text-primary">
                                <BookOpen class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base">{{ semester.name }}</CardTitle>
                                <CardDescription>
                                    {{ semester.subjects.length }}
                                    materia{{ semester.subjects.length !== 1 ? 's' : '' }} asignada{{ semester.subjects.length !== 1 ? 's' : '' }}
                                </CardDescription>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge :variant="semester.active ? 'default' : 'secondary'" class="text-xs">
                                {{ semester.active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                            <Button size="sm" variant="outline" @click="openAssignDialog(semester)">
                                <Plus class="mr-2 h-4 w-4" />
                                Asignar materia
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <div
                        v-if="semester.subjects.length === 0"
                        class="flex flex-col items-center justify-center rounded-lg border border-dashed py-8 text-center text-muted-foreground"
                    >
                        <BookOpen class="mb-2 h-8 w-8 opacity-30" />
                        <p class="text-sm font-medium">Sin materias asignadas</p>
                        <p class="mt-0.5 text-xs">Usa "Asignar materia" para agregar la primera.</p>
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="subject in semester.subjects"
                            :key="subject.curriculum_id"
                            class="flex items-center justify-between gap-3 rounded-lg border p-3"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium">{{ subject.subject_name }}</span>
                                <span v-if="subject.subject_code" class="font-mono text-xs text-muted-foreground">
                                    {{ subject.subject_code }}
                                </span>
                                <Badge variant="outline" class="text-xs">
                                    {{ subject.credits }} crédito{{ subject.credits !== 1 ? 's' : '' }}
                                </Badge>
                                <Badge v-if="!subject.is_mandatory" variant="secondary" class="text-xs">
                                    Optativa
                                </Badge>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="shrink-0 text-muted-foreground hover:text-destructive"
                                @click="openDeleteDialog(subject)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>

        <Dialog v-model:open="assignDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Asignar materia</DialogTitle>
                    <DialogDescription>
                        Agrega una materia a <span class="font-medium">{{ selectedSemesterName }}</span>.
                    </DialogDescription>
                </DialogHeader>

                <form class="mt-2 space-y-4" @submit.prevent="handleAssign">
                    <div class="space-y-4">

                        <div class="space-y-1">
                            <Label>Materia <span class="text-destructive">*</span></Label>
                            <Select
                                :model-value="assignForm.subject_id"
                                @update:model-value="onSubjectChange"
                            >
                                <SelectTrigger :class="{ 'border-destructive': assignForm.errors.subject_id }">
                                    <SelectValue placeholder="Selecciona una materia" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="subject in availableSubjectsForAssign"
                                        :key="subject.id"
                                        :value="String(subject.id)"
                                    >
                                        {{ subject.name }}
                                        <span v-if="subject.code" class="ml-1 font-mono text-xs text-muted-foreground">
                                            {{ subject.code }}
                                        </span>
                                        <span class="ml-1 text-xs text-muted-foreground">
                                            · {{ subject.credits }} cr.
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="assignForm.errors.subject_id" class="text-xs text-destructive">
                                {{ assignForm.errors.subject_id }}
                            </p>
                            <p v-if="availableSubjectsForAssign.length === 0" class="text-xs text-muted-foreground">
                                Todas las materias del catálogo ya están asignadas a este semestre.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox
                                id="is-mandatory"
                                :checked="assignForm.is_mandatory"
                                @update:checked="(v: boolean) => assignForm.is_mandatory = v"
                            />
                            <Label for="is-mandatory">Materia obligatoria</Label>
                        </div>

                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="assignForm.processing"
                            @click="assignDialogOpen = false"
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            :disabled="assignForm.processing || availableSubjectsForAssign.length === 0"
                        >
                            {{ assignForm.processing ? 'Asignando...' : 'Asignar' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Remover materia?</DialogTitle>
                    <DialogDescription>
                        Se removerá <span class="font-semibold">{{ curriculumToDelete?.name }}</span>
                        de la malla curricular. Esta acción no elimina la materia del catálogo.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">Cancelar</Button>
                    <Button variant="destructive" @click="handleDelete">Remover</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
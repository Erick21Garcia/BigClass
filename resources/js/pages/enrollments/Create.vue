<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    GraduationCap, ArrowLeft, BookOpen, CheckCircle2, Clock,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { toast } from 'vue-sonner';
import SubjectRow from './SubjectRow.vue';
import type { SubjectItem } from './SubjectRow.vue';
import type { BreadcrumbItem } from '@/types';
import type { AcceptableValue } from 'reka-ui';

interface AvailableSubjects {
    current: SubjectItem[];
    carryover: SubjectItem[];
}

interface StudentOption {
    id: number;
    enrollment_number: string;
    full_name: string;
}

interface AcademicPeriod {
    id: number;
    name: string;
}

interface Semester {
    id: number;
    name: string;
    number: number;
    career: {
        id: number;
        name: string;
        faculty: {
            id: number;
            name: string;
            institution: {
                id: number;
                name: string;
            };
        };
    };
}

interface Props {
    semester: Semester;
    students: StudentOption[];
    academicPeriods: AcademicPeriod[];
    selectedStudentId: number | null;
    availableSubjects: AvailableSubjects;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Instituciones',                                href: '/institutions' },
    { title: props.semester.career.faculty.institution.name, href: `/institutions/${props.semester.career.faculty.institution.id}` },
    { title: props.semester.career.faculty.name,             href: `/faculties/${props.semester.career.faculty.id}` },
    { title: props.semester.career.name,                     href: `/careers/${props.semester.career.id}` },
    { title: props.semester.name,                            href: `/semesters/${props.semester.id}` },
    { title: 'Nueva matrícula',                              href: '#' },
];

const form = useForm({
    student_id:         props.selectedStudentId ? String(props.selectedStudentId) : '',
    career_id:          props.semester.career.id,
    semester_id:        props.semester.id,
    academic_period_id: '',
    enrollment_date:    new Date().toISOString().split('T')[0],
    type:               'regular' as 'regular' | 'extraordinary' | 'special',
    status:             'active'  as 'registered' | 'active' | 'withdrawn' | 'completed',
    curricula_ids:      [] as number[],
});

const onStudentChange = (value: AcceptableValue) => {
    if (!value) return;
    const studentId = String(value);
    form.student_id = studentId;
    form.curricula_ids = [];

    router.visit(`/enrollments/create?semester_id=${props.semester.id}&student_id=${studentId}`, {
        preserveScroll: true,
        preserveState: false,
    });
};

const selectedSet = computed(() => new Set(form.curricula_ids));

const toggleSubject = (subject: SubjectItem) => {
    if (!subject.can_enroll || subject.already_enrolled) return;

    const ids = [...form.curricula_ids];
    const idx = ids.indexOf(subject.curriculum_id);

    if (idx === -1) {
        ids.push(subject.curriculum_id);
    } else {
        ids.splice(idx, 1);
    }

    form.curricula_ids = ids;
};

const totalCredits = computed(() => {
    const all = [...props.availableSubjects.current, ...props.availableSubjects.carryover];
    return all
        .filter(s => selectedSet.value.has(s.curriculum_id))
        .reduce((sum, s) => sum + s.credits, 0);
});

const hasSubjects = computed(() =>
    props.availableSubjects.current.length > 0 || props.availableSubjects.carryover.length > 0
);

const handleSubmit = () => {
    form.post('/enrollments', {
        onSuccess: () => toast.success('Matrícula creada exitosamente'),
        onError:   (errors) => toast.error((Object.values(errors)[0] as string) || 'Error al crear la matrícula'),
    });
};

const goBack = () => router.visit(`/semesters/${props.semester.id}`);
</script>

<template>
    <Head title="Nueva matrícula" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" @click="goBack">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Volver
                </Button>
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Nueva matrícula</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ props.semester.name }} · {{ props.semester.career.name }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <div class="space-y-6 lg:col-span-1">

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Datos de la matrícula</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">

                            <div class="space-y-1">
                                <Label>Estudiante <span class="text-destructive">*</span></Label>
                                <Select
                                    :model-value="form.student_id"
                                    @update:model-value="onStudentChange"
                                >
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.student_id }">
                                        <SelectValue placeholder="Selecciona un estudiante" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="student in props.students"
                                            :key="student.id"
                                            :value="String(student.id)"
                                        >
                                            {{ student.full_name }}
                                            <span class="ml-1 text-xs text-muted-foreground">
                                                ({{ student.enrollment_number }})
                                            </span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.student_id" class="text-xs text-destructive">
                                    {{ form.errors.student_id }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <Label>Período académico <span class="text-destructive">*</span></Label>
                                <Select v-model="form.academic_period_id">
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.academic_period_id }">
                                        <SelectValue placeholder="Selecciona un período" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="period in props.academicPeriods"
                                            :key="period.id"
                                            :value="String(period.id)"
                                        >
                                            {{ period.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.academic_period_id" class="text-xs text-destructive">
                                    {{ form.errors.academic_period_id }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <Label for="enrollment-date">Fecha de matrícula <span class="text-destructive">*</span></Label>
                                <Input
                                    id="enrollment-date"
                                    v-model="form.enrollment_date"
                                    type="date"
                                    :class="{ 'border-destructive': form.errors.enrollment_date }"
                                />
                                <p v-if="form.errors.enrollment_date" class="text-xs text-destructive">
                                    {{ form.errors.enrollment_date }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <Label>Tipo <span class="text-destructive">*</span></Label>
                                <Select v-model="form.type">
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                                        <SelectValue placeholder="Tipo de matrícula" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="regular">Regular</SelectItem>
                                        <SelectItem value="extraordinary">Extraordinaria</SelectItem>
                                        <SelectItem value="special">Especial</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type" class="text-xs text-destructive">
                                    {{ form.errors.type }}
                                </p>
                            </div>

                            <div class="space-y-1">
                                <Label>Estado <span class="text-destructive">*</span></Label>
                                <Select v-model="form.status">
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.status }">
                                        <SelectValue placeholder="Estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="registered">Registrado</SelectItem>
                                        <SelectItem value="active">Activo</SelectItem>
                                        <SelectItem value="withdrawn">Retirado</SelectItem>
                                        <SelectItem value="completed">Completado</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="text-xs text-destructive">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                        </CardContent>
                    </Card>

                    <Card v-if="form.student_id">
                        <CardHeader>
                            <CardTitle class="text-base">Resumen</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Materias seleccionadas</span>
                                <span class="font-medium">{{ form.curricula_ids.length }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Total de créditos</span>
                                <span class="font-medium">{{ totalCredits }}</span>
                            </div>
                            <p v-if="form.errors.curricula_ids" class="text-xs text-destructive">
                                {{ form.errors.curricula_ids }}
                            </p>
                        </CardContent>
                    </Card>

                    <div class="flex flex-col gap-2">
                        <Button
                            :disabled="form.processing || !form.student_id || form.curricula_ids.length === 0"
                            @click="handleSubmit"
                        >
                            <GraduationCap class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Matriculando...' : 'Confirmar matrícula' }}
                        </Button>
                        <Button variant="outline" :disabled="form.processing" @click="goBack">
                            Cancelar
                        </Button>
                    </div>

                </div>

                <div class="space-y-6 lg:col-span-2">

                    <Card v-if="!form.student_id">
                        <CardContent class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground">
                            <BookOpen class="mb-3 h-10 w-10 opacity-30" />
                            <p class="font-medium">Selecciona un estudiante</p>
                            <p class="mt-1 text-sm">Las materias disponibles aparecerán aquí.</p>
                        </CardContent>
                    </Card>

                    <Card v-else-if="!hasSubjects">
                        <CardContent class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground">
                            <CheckCircle2 class="mb-3 h-10 w-10 opacity-30" />
                            <p class="font-medium">Sin materias disponibles</p>
                            <p class="mt-1 text-sm">Este estudiante no tiene materias pendientes en este semestre.</p>
                        </CardContent>
                    </Card>

                    <template v-else>

                        <Card v-if="props.availableSubjects.current.length > 0">
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div class="rounded-md bg-primary/10 p-2 text-primary">
                                        <BookOpen class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <CardTitle class="text-base">Materias del semestre</CardTitle>
                                        <CardDescription>
                                            {{ props.availableSubjects.current.length }}
                                            materia{{ props.availableSubjects.current.length !== 1 ? 's' : '' }} disponible{{ props.availableSubjects.current.length !== 1 ? 's' : '' }}
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-2">
                                <SubjectRow
                                    v-for="subject in props.availableSubjects.current"
                                    :key="subject.curriculum_id"
                                    :subject="subject"
                                    :selected="selectedSet.has(subject.curriculum_id)"
                                    @toggle="toggleSubject(subject)"
                                />
                            </CardContent>
                        </Card>

                        <Card v-if="props.availableSubjects.carryover.length > 0">
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div class="rounded-md bg-amber-500/10 p-2 text-amber-600 dark:text-amber-400">
                                        <Clock class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <CardTitle class="text-base">Materias de arrastre</CardTitle>
                                        <CardDescription>
                                            Materias de semestres anteriores pendientes de aprobar
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-2">
                                <SubjectRow
                                    v-for="subject in props.availableSubjects.carryover"
                                    :key="subject.curriculum_id"
                                    :subject="subject"
                                    :selected="selectedSet.has(subject.curriculum_id)"
                                    @toggle="toggleSubject(subject)"
                                />
                            </CardContent>
                        </Card>

                    </template>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
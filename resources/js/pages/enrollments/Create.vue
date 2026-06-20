<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    GraduationCap, ArrowLeft, BookOpen, CheckCircle2, Clock,
    Upload, Download, AlertCircle, CheckCircle, XCircle, Loader2,
} from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { toast } from 'vue-sonner';
import SubjectRow from './SubjectRow.vue';
import type { SubjectItem } from './SubjectRow.vue';
import type { BreadcrumbItem } from '@/types';
import type { AcceptableValue } from 'reka-ui';

// ─── Tipos ────────────────────────────────────────────────────────────────────

interface AvailableSubjects {
    current:   SubjectItem[];
    carryover: SubjectItem[];
}

interface StudentOption {
    id:                number;
    enrollment_number: string;
    full_name:         string;
}

interface AcademicPeriod {
    id:   number;
    name: string;
}

interface Semester {
    id:        number;
    name:      string;
    number:    number;
    career_id: number;
    career: {
        id:   number;
        name: string;
        faculty: {
            id:   number;
            name: string;
            institution: { id: number; name: string };
        };
    };
}

interface PreviewRow {
    cedula:           string;
    student_name:     string | null;
    student_id:       number | null;
    codigos_materias: string[];
    curricula_ids:    number[];
    can_enroll:       boolean;
    errors:           string[];
}

interface BulkReport {
    enrolled_count: number;
    skipped_count:  number;
    enrolled:       { cedula: string; student_name: string }[];
    skipped:        { cedula: string; student_name: string; errors: string[] }[];
}

interface Props {
    semester:          Semester;
    students:          StudentOption[];
    academicPeriods:   AcademicPeriod[];
    selectedStudentId: number | null;
    availableSubjects: AvailableSubjects;
}

// ─── Props / breadcrumbs ──────────────────────────────────────────────────────

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Instituciones',                                href: '/institutions' },
    { title: props.semester.career.faculty.institution.name, href: `/institutions/${props.semester.career.faculty.institution.id}` },
    { title: props.semester.career.faculty.name,             href: `/faculties/${props.semester.career.faculty.id}` },
    { title: props.semester.career.name,                     href: `/careers/${props.semester.career.id}` },
    { title: props.semester.name,                            href: `/semesters/${props.semester.id}` },
    { title: 'Nueva matrícula',                              href: '#' },
];

// ─── Formulario individual (sin cambios) ──────────────────────────────────────

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
        preserveState:  false,
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
    return all.filter(s => selectedSet.value.has(s.curriculum_id)).reduce((sum, s) => sum + s.credits, 0);
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

// ─── Carga masiva ─────────────────────────────────────────────────────────────

const bulkFile              = ref<File | null>(null);
const bulkAcademicPeriodId  = ref('');
const bulkType              = ref<'regular' | 'extraordinary' | 'special'>('regular');
const bulkStatus            = ref<'registered' | 'active'>('active');
const bulkEnrollmentDate    = ref(new Date().toISOString().split('T')[0]);

const isPreviewing  = ref(false);
const isSubmitting  = ref(false);
const previewRows   = ref<PreviewRow[]>([]);
const bulkReport    = ref<BulkReport | null>(null);
const previewDone   = ref(false);

const canPreviewCount  = computed(() => previewRows.value.filter(r => r.can_enroll).length);
const willSkipCount    = computed(() => previewRows.value.filter(r => !r.can_enroll).length);

const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    bulkFile.value = input.files?.[0] ?? null;
    previewRows.value = [];
    previewDone.value = false;
    bulkReport.value  = null;
};

const downloadTemplate = () => {
    window.location.href = '/enrollments/bulk-template';
};

const handlePreview = async () => {
    if (!bulkFile.value || !bulkAcademicPeriodId.value) {
        toast.error('Selecciona el archivo y el período académico.');
        return;
    }

    isPreviewing.value = true;
    previewDone.value  = false;
    bulkReport.value   = null;

    try {
        const fd = new FormData();
        fd.append('file',               bulkFile.value);
        fd.append('semester_id',        String(props.semester.id));
        fd.append('academic_period_id', bulkAcademicPeriodId.value);
        fd.append('type',               bulkType.value);
        fd.append('status',             bulkStatus.value);
        fd.append('enrollment_date',    bulkEnrollmentDate.value);

        const { data } = await axios.post('/enrollments/bulk-preview', fd);
        previewRows.value = data.preview;
        previewDone.value = true;
    } catch (err: any) {
        toast.error(err?.response?.data?.message ?? 'Error al procesar el archivo.');
    } finally {
        isPreviewing.value = false;
    }
};

const handleBulkStore = async () => {
    if (!bulkFile.value) return;

    isSubmitting.value = true;

    try {
        const fd = new FormData();
        fd.append('file',               bulkFile.value);
        fd.append('semester_id',        String(props.semester.id));
        fd.append('academic_period_id', bulkAcademicPeriodId.value);
        fd.append('type',               bulkType.value);
        fd.append('status',             bulkStatus.value);
        fd.append('enrollment_date',    bulkEnrollmentDate.value);

        const { data } = await axios.post('/enrollments/bulk-store', fd);
        bulkReport.value  = data;
        previewDone.value = false;
        previewRows.value = [];
        bulkFile.value    = null;

        toast.success(`${data.enrolled_count} estudiante(s) matriculados correctamente.`);
    } catch (err: any) {
        toast.error(err?.response?.data?.message ?? 'Error al procesar la matrícula masiva.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Nueva matrícula" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" @click="goBack">
                    <ArrowLeft class="mr-2 h-4 w-4" /> Volver
                </Button>
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Nueva matrícula</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ props.semester.name }} · {{ props.semester.career.name }}
                    </p>
                </div>
            </div>

            <!-- Pestañas -->
            <Tabs default-value="individual">
                <TabsList class="w-full max-w-sm">
                    <TabsTrigger value="individual" class="flex-1">Individual</TabsTrigger>
                    <TabsTrigger value="bulk" class="flex-1">Carga masiva</TabsTrigger>
                </TabsList>

                <!-- ── Pestaña individual (igual que antes) ─────────────────── -->
                <TabsContent value="individual">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mt-4">

                        <div class="space-y-6 lg:col-span-1">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Datos de la matrícula</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">

                                    <div class="space-y-1">
                                        <Label>Estudiante <span class="text-destructive">*</span></Label>
                                        <Select :model-value="form.student_id" @update:model-value="onStudentChange">
                                            <SelectTrigger :class="{ 'border-destructive': form.errors.student_id }">
                                                <SelectValue placeholder="Selecciona un estudiante" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="student in props.students" :key="student.id" :value="String(student.id)">
                                                    {{ student.full_name }}
                                                    <span class="ml-1 text-xs text-muted-foreground">({{ student.enrollment_number }})</span>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.student_id" class="text-xs text-destructive">{{ form.errors.student_id }}</p>
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Período académico <span class="text-destructive">*</span></Label>
                                        <Select v-model="form.academic_period_id">
                                            <SelectTrigger :class="{ 'border-destructive': form.errors.academic_period_id }">
                                                <SelectValue placeholder="Selecciona un período" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="period in props.academicPeriods" :key="period.id" :value="String(period.id)">
                                                    {{ period.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.academic_period_id" class="text-xs text-destructive">{{ form.errors.academic_period_id }}</p>
                                    </div>

                                    <div class="space-y-1">
                                        <Label for="enrollment-date">Fecha de matrícula <span class="text-destructive">*</span></Label>
                                        <Input id="enrollment-date" v-model="form.enrollment_date" type="date" :class="{ 'border-destructive': form.errors.enrollment_date }" />
                                        <p v-if="form.errors.enrollment_date" class="text-xs text-destructive">{{ form.errors.enrollment_date }}</p>
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
                                        <p v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</p>
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
                                        <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                                    </div>

                                </CardContent>
                            </Card>

                            <Card v-if="form.student_id">
                                <CardHeader><CardTitle class="text-base">Resumen</CardTitle></CardHeader>
                                <CardContent class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Materias seleccionadas</span>
                                        <span class="font-medium">{{ form.curricula_ids.length }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Total de créditos</span>
                                        <span class="font-medium">{{ totalCredits }}</span>
                                    </div>
                                    <p v-if="form.errors.curricula_ids" class="text-xs text-destructive">{{ form.errors.curricula_ids }}</p>
                                </CardContent>
                            </Card>

                            <div class="flex flex-col gap-2">
                                <Button :disabled="form.processing || !form.student_id || form.curricula_ids.length === 0" @click="handleSubmit">
                                    <GraduationCap class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Matriculando...' : 'Confirmar matrícula' }}
                                </Button>
                                <Button variant="outline" :disabled="form.processing" @click="goBack">Cancelar</Button>
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
                                            <div class="rounded-md bg-primary/10 p-2 text-primary"><BookOpen class="h-4 w-4" /></div>
                                            <div>
                                                <CardTitle class="text-base">Materias del semestre</CardTitle>
                                                <CardDescription>{{ props.availableSubjects.current.length }} materia(s) disponible(s)</CardDescription>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent class="space-y-2">
                                        <SubjectRow v-for="subject in props.availableSubjects.current" :key="subject.curriculum_id" :subject="subject" :selected="selectedSet.has(subject.curriculum_id)" @toggle="toggleSubject(subject)" />
                                    </CardContent>
                                </Card>

                                <Card v-if="props.availableSubjects.carryover.length > 0">
                                    <CardHeader>
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-md bg-amber-500/10 p-2 text-amber-600 dark:text-amber-400"><Clock class="h-4 w-4" /></div>
                                            <div>
                                                <CardTitle class="text-base">Materias de arrastre</CardTitle>
                                                <CardDescription>Materias de semestres anteriores pendientes de aprobar</CardDescription>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent class="space-y-2">
                                        <SubjectRow v-for="subject in props.availableSubjects.carryover" :key="subject.curriculum_id" :subject="subject" :selected="selectedSet.has(subject.curriculum_id)" @toggle="toggleSubject(subject)" />
                                    </CardContent>
                                </Card>
                            </template>
                        </div>

                    </div>
                </TabsContent>

                <!-- ── Pestaña carga masiva ──────────────────────────────────── -->
                <TabsContent value="bulk">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mt-4">

                        <!-- Columna izquierda: configuración -->
                        <div class="space-y-6 lg:col-span-1">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Configuración</CardTitle>
                                    <CardDescription>Parámetros aplicados a todos los estudiantes del archivo.</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-4">

                                    <div class="space-y-1">
                                        <Label>Período académico <span class="text-destructive">*</span></Label>
                                        <Select v-model="bulkAcademicPeriodId">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un período" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="period in props.academicPeriods" :key="period.id" :value="String(period.id)">
                                                    {{ period.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Fecha de matrícula <span class="text-destructive">*</span></Label>
                                        <Input v-model="bulkEnrollmentDate" type="date" />
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Tipo</Label>
                                        <Select v-model="bulkType">
                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="regular">Regular</SelectItem>
                                                <SelectItem value="extraordinary">Extraordinaria</SelectItem>
                                                <SelectItem value="special">Especial</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-1">
                                        <Label>Estado inicial</Label>
                                        <Select v-model="bulkStatus">
                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="registered">Registrado</SelectItem>
                                                <SelectItem value="active">Activo</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                </CardContent>
                            </Card>

                            <!-- Subir archivo -->
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-base">Archivo Excel</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <Button variant="outline" size="sm" class="w-full" @click="downloadTemplate">
                                        <Download class="mr-2 h-4 w-4" /> Descargar plantilla
                                    </Button>

                                    <div class="space-y-1">
                                        <Label>Seleccionar archivo (.xlsx)</Label>
                                        <Input type="file" accept=".xlsx,.xls" @change="onFileChange" />
                                    </div>

                                    <Button
                                        class="w-full"
                                        :disabled="!bulkFile || !bulkAcademicPeriodId || isPreviewing"
                                        @click="handlePreview"
                                    >
                                        <Loader2 v-if="isPreviewing" class="mr-2 h-4 w-4 animate-spin" />
                                        <Upload v-else class="mr-2 h-4 w-4" />
                                        {{ isPreviewing ? 'Procesando...' : 'Previsualizar' }}
                                    </Button>
                                </CardContent>
                            </Card>

                            <!-- Resumen del preview -->
                            <Card v-if="previewDone">
                                <CardHeader><CardTitle class="text-base">Resumen</CardTitle></CardHeader>
                                <CardContent class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Total en archivo</span>
                                        <span class="font-medium">{{ previewRows.length }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-emerald-600 dark:text-emerald-400">Se matricularán</span>
                                        <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ canPreviewCount }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-destructive">Se omitirán</span>
                                        <span class="font-medium text-destructive">{{ willSkipCount }}</span>
                                    </div>

                                    <Button
                                        class="mt-3 w-full"
                                        :disabled="canPreviewCount === 0 || isSubmitting"
                                        @click="handleBulkStore"
                                    >
                                        <Loader2 v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin" />
                                        <GraduationCap v-else class="mr-2 h-4 w-4" />
                                        {{ isSubmitting ? 'Matriculando...' : `Confirmar (${canPreviewCount} estudiantes)` }}
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Columna derecha: tabla de preview / reporte -->
                        <div class="lg:col-span-2">

                            <!-- Estado vacío -->
                            <Card v-if="!previewDone && !bulkReport">
                                <CardContent class="flex flex-col items-center justify-center py-16 text-center text-muted-foreground">
                                    <Upload class="mb-3 h-10 w-10 opacity-30" />
                                    <p class="font-medium">Sube un archivo para previsualizar</p>
                                    <p class="mt-1 text-sm">Descarga la plantilla, complétala y súbela aquí.</p>
                                </CardContent>
                            </Card>

                            <!-- Tabla de previsualización -->
                            <Card v-if="previewDone && !bulkReport">
                                <CardHeader>
                                    <CardTitle class="text-base">Previsualización</CardTitle>
                                    <CardDescription>Revisa los datos antes de confirmar. Solo se procesarán las filas en verde.</CardDescription>
                                </CardHeader>
                                <CardContent class="p-0">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b bg-muted/40 text-xs text-muted-foreground">
                                                <th class="px-4 py-3 text-left font-medium">Estado</th>
                                                <th class="px-4 py-3 text-left font-medium">Cédula</th>
                                                <th class="px-4 py-3 text-left font-medium">Estudiante</th>
                                                <th class="px-4 py-3 text-left font-medium">Materias</th>
                                                <th class="px-4 py-3 text-left font-medium">Detalle</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, i) in previewRows" :key="i"
                                                :class="[
                                                    'border-b last:border-0 transition-colors',
                                                    row.can_enroll
                                                        ? 'bg-emerald-50/50 dark:bg-emerald-950/20'
                                                        : 'bg-red-50/50 dark:bg-red-950/20',
                                                ]">
                                                <td class="px-4 py-3">
                                                    <CheckCircle v-if="row.can_enroll" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                                    <XCircle v-else class="h-4 w-4 text-destructive" />
                                                </td>
                                                <td class="px-4 py-3 font-mono text-xs">{{ row.cedula }}</td>
                                                <td class="px-4 py-3">
                                                    <span v-if="row.student_name" class="font-medium">{{ row.student_name }}</span>
                                                    <span v-else class="text-muted-foreground italic">No encontrado</span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex flex-wrap gap-1">
                                                        <Badge v-for="code in row.codigos_materias" :key="code" variant="secondary" class="text-xs">
                                                            {{ code }}
                                                        </Badge>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 max-w-xs">
                                                    <span v-if="row.can_enroll" class="text-xs text-emerald-600 dark:text-emerald-400">Listo para matricular</span>
                                                    <ul v-else class="space-y-0.5">
                                                        <li v-for="(err, ei) in row.errors" :key="ei" class="flex items-start gap-1 text-xs text-destructive">
                                                            <AlertCircle class="mt-0.5 h-3 w-3 shrink-0" />
                                                            {{ err }}
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>

                            <!-- Reporte final -->
                            <div v-if="bulkReport" class="space-y-4">
                                <Card>
                                    <CardHeader>
                                        <CardTitle class="text-base flex items-center gap-2">
                                            <CheckCircle class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                            Proceso completado
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">Matriculados</span>
                                            <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ bulkReport.enrolled_count }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">Omitidos</span>
                                            <span class="font-medium text-destructive">{{ bulkReport.skipped_count }}</span>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card v-if="bulkReport.skipped.length > 0">
                                    <CardHeader>
                                        <CardTitle class="text-base text-destructive">Omitidos</CardTitle>
                                    </CardHeader>
                                    <CardContent class="p-0">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-b bg-muted/40 text-xs text-muted-foreground">
                                                    <th class="px-4 py-3 text-left font-medium">Cédula</th>
                                                    <th class="px-4 py-3 text-left font-medium">Estudiante</th>
                                                    <th class="px-4 py-3 text-left font-medium">Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(row, i) in bulkReport.skipped" :key="i" class="border-b last:border-0">
                                                    <td class="px-4 py-3 font-mono text-xs">{{ row.cedula }}</td>
                                                    <td class="px-4 py-3">{{ row.student_name }}</td>
                                                    <td class="px-4 py-3 text-xs text-destructive">{{ row.errors.join('. ') }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </CardContent>
                                </Card>
                            </div>

                        </div>
                    </div>
                </TabsContent>
            </Tabs>

        </div>
    </AppLayout>
</template>
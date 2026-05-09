<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Calendar, Pencil, Trash2, UserPlus, GraduationCap, 
        ClipboardList, FileText, FileBarChart, FileSpreadsheet  } from 'lucide-vue-next';

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
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

interface AcademicPeriod { id: number; name: string; }

interface Enrollment {
    id:              number;
    enrollment_date: string;
    type:            'regular' | 'extraordinary' | 'special';
    status:          'registered' | 'active' | 'withdrawn' | 'completed';
    student: { id: number; enrollment_number: string; full_name: string; };
    academic_period: { id: number; name: string; };
}

interface Semester {
    id: number; name: string; number: number; active: boolean;
    career: { id: number; name: string;
        faculty: { id: number; name: string;
            institution: { id: number; name: string; };
        };
    };
}

interface GradeEntry {
    evaluation_parameter_id: number;
    grade_id:                number | null;
    score:                   number | null;
}

interface Parameter {
    id: number; name: string; percentage: number; is_final: boolean;
}

interface EnrollmentItem {
    enrollment_item_id: number;
    status:             string;
    final_grade:        number | null;
    subject:            { id: number; name: string; code: string | null; };
    parameters:         Parameter[];
    grades:             GradeEntry[];
}

interface GradesData {
    enrollment_id:   number;
    student:         string;
    academic_period: string;
    items:           EnrollmentItem[];
}

interface Props {
    semester:        Semester;
    enrollments:     Enrollment[];
    academicPeriods: AcademicPeriod[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Instituciones',                                href: '/institutions' },
    { title: props.semester.career.faculty.institution.name, href: `/institutions/${props.semester.career.faculty.institution.id}` },
    { title: props.semester.career.faculty.name,             href: `/faculties/${props.semester.career.faculty.id}` },
    { title: props.semester.career.name,                     href: `/careers/${props.semester.career.id}` },
    { title: props.semester.name,                            href: `/semesters/${props.semester.id}` },
];

const editDialogOpen   = ref(false);
const deleteDialogOpen = ref(false);

const editForm = useForm({
    career_id: props.semester.career.id,
    number:    props.semester.number,
    name:      props.semester.name,
    active:    props.semester.active,
});

const handleUpdate = () => {
    editForm.put(`/semesters/${props.semester.id}`, {
        preserveScroll: true,
        onSuccess: () => { editDialogOpen.value = false; toast.success('Semestre actualizado exitosamente'); },
        onError:   (errors) => toast.error((Object.values(errors)[0] as string) || 'Error al actualizar el semestre'),
    });
};

const handleDelete = () => {
    router.delete(`/semesters/${props.semester.id}`, {
        onSuccess: () => toast.success('Semestre eliminado exitosamente'),
        onError:   (errors) => toast.error((Object.values(errors)[0] as string) || 'Error al eliminar el semestre'),
    });
};

const enrollEditDialogOpen = ref(false);
const editingEnrollment    = ref<Enrollment | null>(null);

const enrollEditForm = useForm({
    academic_period_id: '',
    enrollment_date:    '',
    type:               'regular' as Enrollment['type'],
    status:             'active'  as Enrollment['status'],
});

const openEditEnrollment = (enrollment: Enrollment) => {
    editingEnrollment.value           = enrollment;
    enrollEditForm.academic_period_id = String(enrollment.academic_period.id);
    enrollEditForm.enrollment_date    = enrollment.enrollment_date;
    enrollEditForm.type               = enrollment.type;
    enrollEditForm.status             = enrollment.status;
    enrollEditForm.clearErrors();
    enrollEditDialogOpen.value        = true;
};

const handleEnrollUpdate = () => {
    if (!editingEnrollment.value) return;
    enrollEditForm.put(`/enrollments/${editingEnrollment.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            enrollEditDialogOpen.value = false;
            editingEnrollment.value    = null;
            toast.success('Matrícula actualizada exitosamente');
        },
        onError: (errors) => toast.error((Object.values(errors)[0] as string) || 'Error al actualizar la matrícula'),
    });
};

const gradesDialogOpen = ref(false);
const gradesData       = ref<GradesData | null>(null);
const loadingGrades    = ref(false);
const savingGrade      = ref<string | null>(null); // key: `${itemId}-${paramId}`

const openGradesDialog = async (enrollment: Enrollment) => {
    gradesData.value       = null;
    gradesDialogOpen.value = true;
    loadingGrades.value    = true;

    try {
        const res  = await fetch(`/enrollments/${enrollment.id}/grades`);
        const data = await res.json();
        gradesData.value = data;
    } catch {
        toast.error('Error al cargar las notas');
        gradesDialogOpen.value = false;
    } finally {
        loadingGrades.value = false;
    }
};

// Obtener nota actual de un item+parámetro
const getScore = (item: EnrollmentItem, paramId: number): string => {
    const grade = item.grades.find(g => g.evaluation_parameter_id === paramId);
    return grade?.score !== null && grade?.score !== undefined ? String(grade.score) : '';
};

// Guardar nota al perder el foco en el input
const saveGrade = async (item: EnrollmentItem, paramId: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    const value = input.value.trim();

    if (value === '') return;

    const score = parseFloat(value);
    if (isNaN(score) || score < 0 || score > 10) {
        toast.error('La nota debe estar entre 0 y 10');
        input.value = getScore(item, paramId);
        return;
    }

    const key = `${item.enrollment_item_id}-${paramId}`;
    savingGrade.value = key;

    try {
        await axios.post('/grades', {
            enrollment_item_id:      item.enrollment_item_id,
            evaluation_parameter_id: paramId,
            score,
        });

        // Actualizar estado local
        const grade = item.grades.find(g => g.evaluation_parameter_id === paramId);
        if (grade) grade.score = score;

        // Recargar para obtener final_grade actualizado
        const enrollment = props.enrollments.find(e => e.id === gradesData.value?.enrollment_id);
        if (enrollment) {
            const { data } = await axios.get(`/enrollments/${enrollment.id}/grades`);
            gradesData.value = data;
        }

        toast.success('Nota guardada');
    } catch (error: any) {
        const msg = error?.response?.data?.errors
            ? Object.values(error.response.data.errors)[0] as string
            : 'Error al guardar la nota';
        toast.error(msg);
    } finally {
        savingGrade.value = null;
    }
};

const goToCreateEnrollment = () => router.visit(`/enrollments/create?semester_id=${props.semester.id}`);

const statusVariant = (status: Enrollment['status']): 'default' | 'secondary' | 'destructive' | 'outline' => ({
    active: 'default', registered: 'secondary', withdrawn: 'destructive', completed: 'outline',
} as const)[status] ?? 'secondary';

const statusLabel = (status: Enrollment['status']) => ({
    active: 'Activo', registered: 'Registrado', withdrawn: 'Retirado', completed: 'Completado',
}[status] ?? status);

const typeLabel = (type: Enrollment['type']) => ({
    regular: 'Regular', extraordinary: 'Extraordinaria', special: 'Especial',
}[type] ?? type);

const gradeStatusVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => ({
    aprobado:   'default',
    reprobado:  'destructive',
    en_curso:   'secondary',
} as const)[status] ?? 'secondary';

const downloadReport = (url: string) => {
    window.open(url, '_blank');
};

const actDialogOpen  = ref(false);
const actPeriodId    = ref('');
const actSectionId   = ref('');
const actSections    = ref<{ id: number; name: string; subject: string; }[]>([]);
const loadingSections = ref(false);

// Cargar secciones cuando cambia el período
const loadSections = async () => {
    if (!actPeriodId.value) return;
    loadingSections.value = true;
    try {
        const res = await axios.get('/schedules/events', {
            params: { academic_period_id: actPeriodId.value },
        });
        // Agrupar por section_id para evitar duplicados
        const seen = new Set();
        actSections.value = res.data
            .filter((e: any) => {
                if (seen.has(e.section_id)) return false;
                seen.add(e.section_id);
                return true;
            })
            .map((e: any) => ({
                id:      e.section_id,
                name:    e.section_name ?? 'Sección',
                subject: e.title,
            }));
    } finally {
        loadingSections.value = false;
    }
};

const downloadAct = () => {
    if (!actSectionId.value || !actPeriodId.value) return;
    const url = `/reports/subject-act?section_id=${actSectionId.value}&academic_period_id=${actPeriodId.value}`;
    window.open(url, '_blank');
    actDialogOpen.value = false;
};

</script>

<template>
    <Head :title="props.semester.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Info del semestre -->
            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-primary/10 p-3 text-primary">
                                <Calendar class="h-6 w-6" />
                            </div>
                            <div>
                                <CardTitle class="text-2xl">{{ props.semester.name }}</CardTitle>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge :variant="props.semester.active ? 'default' : 'secondary'">
                                {{ props.semester.active ? 'Activo' : 'Inactivo' }}
                            </Badge>
                            <Button variant="outline" size="sm" @click="editDialogOpen = true">
                                <Pencil class="mr-2 h-4 w-4" />Editar
                            </Button>
                            <Button variant="outline" size="sm" @click="actDialogOpen = true">
                                <FileSpreadsheet class="mr-2 h-4 w-4" /> Acta por materia
                            </Button>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="rounded-md bg-primary/10 p-3 text-primary">
                                <GraduationCap class="h-5 w-5" />
                            </div>
                            <div>
                                <CardTitle>Estudiantes matriculados</CardTitle>
                                <CardDescription>
                                    {{ props.enrollments.length }}
                                    {{ props.enrollments.length === 1 ? 'matrícula registrada' : 'matrículas registradas' }}
                                </CardDescription>
                            </div>
                        </div>
                        <Button size="sm" @click="goToCreateEnrollment">
                            <UserPlus class="mr-2 h-4 w-4" />Matricular estudiante
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.enrollments.length === 0"
                        class="flex flex-col items-center justify-center rounded-lg border border-dashed py-14 text-center text-muted-foreground"
                    >
                        <GraduationCap class="mb-3 h-10 w-10 opacity-30" />
                        <p class="font-medium">Sin estudiantes matriculados</p>
                        <p class="mt-1 text-sm">Haz clic en "Matricular estudiante" para agregar el primero.</p>
                    </div>

                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Estudiante</TableHead>
                                <TableHead>N° Matrícula</TableHead>
                                <TableHead>Período académico</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="enrollment in props.enrollments" :key="enrollment.id">
                                <TableCell class="font-medium">{{ enrollment.student.full_name }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ enrollment.student.enrollment_number }}</TableCell>
                                <TableCell>{{ enrollment.academic_period.name }}</TableCell>
                                <TableCell>{{ typeLabel(enrollment.type) }}</TableCell>
                                <TableCell>
                                    <Badge :variant="statusVariant(enrollment.status)">
                                        {{ statusLabel(enrollment.status) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{ enrollment.enrollment_date }}</TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                            title="Ver notas"
                                            @click="openGradesDialog(enrollment)"
                                        >
                                            <ClipboardList class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                            title="Descargar ficha de matrícula"
                                            @click="downloadReport(`/reports/enrollment/${enrollment.id}/card`)"
                                        >
                                            <FileText class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                            title="Descargar boletín de calificaciones"
                                            @click="downloadReport(`/reports/enrollment/${enrollment.id}/grades`)"
                                        >
                                            <FileBarChart class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0"
                                            title="Editar matrícula"
                                            @click="openEditEnrollment(enrollment)"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

        </div>

        <!-- Dialog: editar semestre -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar Semestre</DialogTitle>
                    <DialogDescription>Actualiza los datos del semestre.</DialogDescription>
                </DialogHeader>
                <form class="mt-2 space-y-4" @submit.prevent="handleUpdate">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <Label for="edit-number">Número <span class="text-destructive">*</span></Label>
                            <Input id="edit-number" v-model="editForm.number" type="number" min="1" max="20" :class="{ 'border-destructive': editForm.errors.number }" />
                            <p v-if="editForm.errors.number" class="text-xs text-destructive">{{ editForm.errors.number }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label for="edit-name">Nombre <span class="text-destructive">*</span></Label>
                            <Input id="edit-name" v-model="editForm.name" :class="{ 'border-destructive': editForm.errors.name }" />
                            <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                        </div>
                        <div class="col-span-2 flex items-center gap-3">
                            <Checkbox id="edit-active" :checked="editForm.active" @update:checked="(v: boolean) => editForm.active = v" />
                            <Label for="edit-active">Semestre activo</Label>
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

        <!-- Dialog: eliminar semestre -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>¿Estás seguro?</DialogTitle>
                    <DialogDescription>
                        Esta acción no se puede deshacer. Se eliminará permanentemente
                        <span class="font-semibold">{{ props.semester.name }}</span>.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">Cancelar</Button>
                    <Button variant="destructive" @click="handleDelete">Eliminar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Dialog: editar matrícula -->
        <Dialog v-model:open="enrollEditDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar matrícula</DialogTitle>
                    <DialogDescription>
                        Editando la matrícula de
                        <span class="font-medium">{{ editingEnrollment?.student.full_name }}</span>.
                    </DialogDescription>
                </DialogHeader>
                <form class="mt-2 space-y-4" @submit.prevent="handleEnrollUpdate">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="col-span-2 space-y-1">
                            <Label>Período académico <span class="text-destructive">*</span></Label>
                            <Select v-model="enrollEditForm.academic_period_id">
                                <SelectTrigger :class="{ 'border-destructive': enrollEditForm.errors.academic_period_id }">
                                    <SelectValue placeholder="Selecciona un período" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="period in props.academicPeriods" :key="period.id" :value="String(period.id)">
                                        {{ period.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="enrollEditForm.errors.academic_period_id" class="text-xs text-destructive">{{ enrollEditForm.errors.academic_period_id }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label for="enroll-date">Fecha de matrícula <span class="text-destructive">*</span></Label>
                            <Input id="enroll-date" v-model="enrollEditForm.enrollment_date" type="date" :class="{ 'border-destructive': enrollEditForm.errors.enrollment_date }" />
                            <p v-if="enrollEditForm.errors.enrollment_date" class="text-xs text-destructive">{{ enrollEditForm.errors.enrollment_date }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label>Tipo <span class="text-destructive">*</span></Label>
                            <Select v-model="enrollEditForm.type">
                                <SelectTrigger><SelectValue placeholder="Tipo" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="regular">Regular</SelectItem>
                                    <SelectItem value="extraordinary">Extraordinaria</SelectItem>
                                    <SelectItem value="special">Especial</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="col-span-2 space-y-1">
                            <Label>Estado <span class="text-destructive">*</span></Label>
                            <Select v-model="enrollEditForm.status">
                                <SelectTrigger><SelectValue placeholder="Estado" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="registered">Registrado</SelectItem>
                                    <SelectItem value="active">Activo</SelectItem>
                                    <SelectItem value="withdrawn">Retirado</SelectItem>
                                    <SelectItem value="completed">Completado</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" :disabled="enrollEditForm.processing" @click="enrollEditDialogOpen = false">Cancelar</Button>
                        <Button type="submit" :disabled="enrollEditForm.processing">
                            {{ enrollEditForm.processing ? 'Guardando...' : 'Guardar cambios' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog: notas del estudiante -->
        <Dialog v-model:open="gradesDialogOpen">
            <DialogContent class="max-h-[85vh] !max-w-5xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <ClipboardList class="h-5 w-5 text-muted-foreground" />
                        Notas del estudiante
                    </DialogTitle>
                    <!-- Cambio: descripción siempre presente -->
                    <DialogDescription>
                        <template v-if="gradesData">
                            <span class="font-medium">{{ gradesData.student }}</span>
                            · {{ gradesData.academic_period }}
                        </template>
                        <template v-else>
                            Cargando información del estudiante...
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <!-- Loading -->
                <div v-if="loadingGrades" class="flex items-center justify-center py-12 text-sm text-muted-foreground">
                    Cargando notas...
                </div>

                <!-- Sin parámetros configurados -->
                <div
                    v-else-if="gradesData && gradesData.items.length > 0 && gradesData.items[0].parameters.length === 0"
                    class="flex flex-col items-center justify-center py-10 text-center text-muted-foreground"
                >
                    <ClipboardList class="mb-3 h-10 w-10 opacity-30" />
                    <p class="font-medium">Sin parámetros de evaluación</p>
                    <p class="mt-1 text-sm">Configura los parámetros del período académico primero.</p>
                </div>

                <!-- Sin materias -->
                <div
                    v-else-if="gradesData && gradesData.items.length === 0"
                    class="flex flex-col items-center justify-center py-10 text-center text-muted-foreground"
                >
                    <ClipboardList class="mb-3 h-10 w-10 opacity-30" />
                    <p class="font-medium">Sin materias matriculadas</p>
                </div>

                <!-- Tabla de notas -->
                <div v-else-if="gradesData" class="space-y-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 pr-4 text-left font-medium text-muted-foreground">Materia</th>
                                    <th
                                        v-for="param in gradesData.items[0]?.parameters ?? []"
                                        :key="param.id"
                                        class="px-2 py-2 text-center font-medium text-muted-foreground"
                                    >
                                        <div>{{ param.name }}</div>
                                        <div class="text-xs font-normal opacity-70">{{ param.percentage }}%</div>
                                    </th>
                                    <th class="pl-4 py-2 text-center font-medium text-muted-foreground">Promedio</th>
                                    <th class="pl-2 py-2 text-center font-medium text-muted-foreground">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in gradesData.items"
                                    :key="item.enrollment_item_id"
                                    class="border-b last:border-0"
                                >
                                    <!-- Materia -->
                                    <td class="py-3 pr-4">
                                        <div class="font-medium">{{ item.subject.name }}</div>
                                        <div v-if="item.subject.code" class="font-mono text-xs text-muted-foreground">{{ item.subject.code }}</div>
                                    </td>

                                    <!-- Inputs de nota por parámetro -->
                                    <td
                                        v-for="param in item.parameters"
                                        :key="param.id"
                                        class="px-2 py-3 text-center"
                                    >
                                        <Input
                                            :default-value="getScore(item, param.id)"
                                            type="number"
                                            min="0"
                                            max="10"
                                            step="0.01"
                                            placeholder="—"
                                            class="h-8 w-20 text-center text-sm"
                                            :disabled="savingGrade === `${item.enrollment_item_id}-${param.id}`"
                                            @blur="saveGrade(item, param.id, $event)"
                                        />
                                    </td>

                                    <!-- Promedio final -->
                                    <td class="pl-4 py-3 text-center">
                                        <span
                                            v-if="item.final_grade !== null"
                                            class="font-medium"
                                            :class="item.final_grade >= 7 ? 'text-green-600 dark:text-green-400' : 'text-destructive'"
                                        >
                                            {{ item.final_grade }}
                                        </span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>

                                    <!-- Estado -->
                                    <td class="pl-2 py-3 text-center">
                                        <Badge :variant="gradeStatusVariant(item.status)" 
                                            :class="item.status === 'aprobado' ? 'bg-green-500 text-white' : ''"
                                            class="text-xs">
                                            {{ item.status === 'aprobado' ? 'Aprobado' : item.status === 'reprobado' ? 'Reprobado' : 'En curso' }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Dialog: Acta por materia -->
        <Dialog v-model:open="actDialogOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Acta de Calificaciones</DialogTitle>
                    <DialogDescription>
                        Selecciona el período y la materia para generar el acta.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <div class="space-y-1">
                        <Label>Período académico <span class="text-destructive">*</span></Label>
                        <Select v-model="actPeriodId" @update:model-value="loadSections">
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona período" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="p in props.academicPeriods"
                                    :key="p.id"
                                    :value="String(p.id)"
                                >
                                    {{ p.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1">
                        <Label>Materia / Sección <span class="text-destructive">*</span></Label>
                        <Select v-model="actSectionId" :disabled="loadingSections || actSections.length === 0">
                            <SelectTrigger>
                                <SelectValue :placeholder="loadingSections ? 'Cargando...' : 'Selecciona materia'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="s in actSections"
                                    :key="s.id"
                                    :value="String(s.id)"
                                >
                                    {{ s.subject }} — {{ s.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="actDialogOpen = false">Cancelar</Button>
                    <Button
                        :disabled="!actSectionId || !actPeriodId"
                        @click="downloadAct"
                    >
                        <FileSpreadsheet class="mr-2 h-4 w-4" />
                        Descargar acta
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
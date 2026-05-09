<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    CheckCircle2, XCircle, Clock, AlertTriangle,
    ChevronLeft, ChevronRight, Save, BookOpen,
    ShieldCheck, ShieldX, Users, Calendar,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button }   from '@/components/ui/button';
import { Badge }    from '@/components/ui/badge';
import { Input }    from '@/components/ui/input';
import { Label }    from '@/components/ui/label';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';
import {
    Dialog, DialogContent, DialogDescription,
    DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import {
    Tabs, TabsContent, TabsList, TabsTrigger,
} from '@/components/ui/tabs';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

interface SectionInfo {
    id:             number;
    name:           string;
    subject:        string;
    career:         string;
    teacher:        string;
    period:         string;
    schedule_today: { start_time: string; end_time: string; classroom: string } | null;
}

interface StudentSummary {
    present:            number;
    late:               number;
    absent:             number;
    justified:          number;
    effective_absences: number;
    total_recorded:     number;
}

interface SheetRow {
    student_id:          number;
    full_name:           string;
    enrollment_number:   string;
    status:              'present' | 'absent' | 'late' | null;
    justified:           boolean;
    justification_note:  string | null;
    attendance_id:       number | null;
    summary:             StudentSummary;
    at_risk:             boolean;
    auto_fail:           boolean;
}

interface SummaryRow {
    student_id:        number;
    full_name:         string;
    enrollment_number: string;
    summary:           StudentSummary;
    attendance_pct:    number | null;
    absence_pct:       number | null;
    at_risk:           boolean;
    auto_fail:         boolean;
}

interface Props {
    section:        SectionInfo;
    date:           string;
    sheet:          SheetRow[];
    recordedDates:  string[];
    summary:        SummaryRow[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Horarios', href: '/schedules' },
    { title: props.section.subject, href: '#' },
    { title: 'Asistencia', href: '#' },
];

const selectedDate = ref(props.date);
const sheet        = ref<SheetRow[]>(props.sheet.map(r => ({ ...r, status: r.status ?? 'present' })));
const saving       = ref(false);
const activeTab    = ref('register');

const justifyDialog    = ref(false);
const justifyRow       = ref<SheetRow | null>(null);
const justifyNote      = ref('');
const justifyValue     = ref(false);
const savingJustify    = ref(false);

const navigateDate = (dir: -1 | 1) => {
    const d = new Date(selectedDate.value);
    d.setDate(d.getDate() + dir);
    selectedDate.value = d.toISOString().split('T')[0];
};

watch(selectedDate, async (date) => {
    router.get(`/sections/${props.section.id}/attendance`, { date }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

watch(() => props.sheet, (newSheet) => {
    sheet.value = newSheet.map(r => ({ ...r, status: r.status ?? 'present' }));
}, { deep: true });

const setStatus = (row: SheetRow, status: 'present' | 'absent' | 'late') => {
    row.status = status;
    if (status === 'present') {
        row.justified = false;
        row.justification_note = null;
    }
};

const markAllPresent = () => {
    sheet.value.forEach(r => {
        r.status    = 'present';
        r.justified = false;
        r.justification_note = null;
    });
};

const saveSheet = async () => {
    const unset = sheet.value.filter(r => r.status === null);
    if (unset.length > 0) {
        toast.error(`Faltan ${unset.length} estudiante(s) por registrar.`);
        return;
    }

    saving.value = true;
    try {
        await axios.post(`/sections/${props.section.id}/attendance`, {
            date:    selectedDate.value,
            records: sheet.value.map(r => ({
                student_id:         r.student_id,
                status:             r.status,
                justified:          r.justified,
                justification_note: r.justification_note,
            })),
        });
        toast.success('Asistencia guardada exitosamente.');
    } catch (error: any) {
        toast.error(error?.response?.data?.message ?? 'Error al guardar la asistencia.');
    } finally {
        saving.value = false;
    }
};

const openJustify = (row: SheetRow) => {
    justifyRow.value   = row;
    justifyValue.value = row.justified;
    justifyNote.value  = row.justification_note ?? '';
    justifyDialog.value = true;
};

const saveJustify = async () => {
    if (!justifyRow.value) return;
    savingJustify.value = true;

    try {
        if (justifyRow.value.attendance_id) {
            await axios.patch(`/attendances/${justifyRow.value.attendance_id}/justify`, {
                justified:          justifyValue.value,
                justification_note: justifyNote.value || null,
            });
        } else {
            justifyRow.value.justified          = justifyValue.value;
            justifyRow.value.justification_note = justifyNote.value || null;
        }

        const row = sheet.value.find(r => r.student_id === justifyRow.value!.student_id);
        if (row) {
            row.justified          = justifyValue.value;
            row.justification_note = justifyNote.value || null;
        }

        toast.success('Justificación actualizada.');
        justifyDialog.value = false;
    } catch {
        toast.error('Error al actualizar la justificación.');
    } finally {
        savingJustify.value = false;
    }
};

const sheetStats = computed(() => ({
    present: sheet.value.filter(r => r.status === 'present').length,
    absent:  sheet.value.filter(r => r.status === 'absent').length,
    late:    sheet.value.filter(r => r.status === 'late').length,
    at_risk: sheet.value.filter(r => r.at_risk).length,
    auto_fail: sheet.value.filter(r => r.auto_fail).length,
}));

const isDateRecorded = computed(() =>
    props.recordedDates.includes(selectedDate.value)
);

const statusConfig = {
    present: { label: 'Presente', icon: CheckCircle2, class: 'border-emerald-500 bg-emerald-500 text-white hover:bg-emerald-600' },
    absent:  { label: 'Ausente',  icon: XCircle,      class: 'border-red-500 bg-red-500 text-white hover:bg-red-600' },
    late:    { label: 'Tardanza', icon: Clock,         class: 'border-amber-500 bg-amber-500 text-white hover:bg-amber-600' },
};

const absencePctColor = (pct: number | null) => {
    if (pct === null) return 'text-muted-foreground';
    if (pct >= 25)    return 'text-red-600 font-bold';
    if (pct >= 18)    return 'text-amber-600 font-semibold';
    return 'text-emerald-600';
};
</script>

<template>
    <Head :title="`Asistencia — ${props.section.subject}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-md bg-primary/10 p-3 text-primary">
                        <BookOpen class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ props.section.subject }}</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ props.section.name }} · {{ props.section.career }} · {{ props.section.period }}
                        </p>
                    </div>
                </div>

                <!-- Info horario de hoy -->
                <div v-if="props.section.schedule_today" class="flex items-center gap-2 rounded-lg border px-4 py-2 text-sm">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                    <span class="font-medium">
                        {{ props.section.schedule_today.start_time }} - {{ props.section.schedule_today.end_time }}
                    </span>
                    <span class="text-muted-foreground">· {{ props.section.schedule_today.classroom }}</span>
                </div>
            </div>

            <!-- Tabs -->
            <Tabs v-model="activeTab">
                <TabsList>
                    <TabsTrigger value="register">
                        <CheckCircle2 class="mr-2 h-4 w-4" /> Registrar asistencia
                    </TabsTrigger>
                    <TabsTrigger value="summary">
                        <Users class="mr-2 h-4 w-4" /> Resumen del período
                    </TabsTrigger>
                </TabsList>

                <!-- ── TAB: Registrar ── -->
                <TabsContent value="register" class="space-y-4 mt-4">

                    <!-- Selector de fecha + stats rápidos -->
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <Button variant="outline" size="icon" @click="navigateDate(-1)">
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <Input
                                v-model="selectedDate"
                                type="date"
                                class="w-40"
                            />
                            <Button variant="outline" size="icon" @click="navigateDate(1)">
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                            <Badge v-if="isDateRecorded" variant="secondary" class="ml-2">
                                <CheckCircle2 class="mr-1 h-3 w-3" /> Ya registrada
                            </Badge>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button variant="outline" size="sm" @click="markAllPresent">
                                <CheckCircle2 class="mr-2 h-4 w-4 text-emerald-500" /> Todos presentes
                            </Button>
                            <Button size="sm" :disabled="saving" @click="saveSheet">
                                <Save class="mr-2 h-4 w-4" />
                                {{ saving ? 'Guardando...' : 'Guardar asistencia' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Stats rápidos -->
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <Card class="border-0 bg-emerald-50 dark:bg-emerald-950">
                            <CardContent class="p-3 text-center">
                                <p class="text-2xl font-bold text-emerald-600">{{ sheetStats.present }}</p>
                                <p class="text-xs text-emerald-700">Presentes</p>
                            </CardContent>
                        </Card>
                        <Card class="border-0 bg-red-50 dark:bg-red-950">
                            <CardContent class="p-3 text-center">
                                <p class="text-2xl font-bold text-red-600">{{ sheetStats.absent }}</p>
                                <p class="text-xs text-red-700">Ausentes</p>
                            </CardContent>
                        </Card>
                        <Card class="border-0 bg-amber-50 dark:bg-amber-950">
                            <CardContent class="p-3 text-center">
                                <p class="text-2xl font-bold text-amber-600">{{ sheetStats.late }}</p>
                                <p class="text-xs text-amber-700">Tardanzas</p>
                            </CardContent>
                        </Card>
                        <Card class="border-0 bg-orange-50 dark:bg-orange-950">
                            <CardContent class="p-3 text-center">
                                <p class="text-2xl font-bold text-orange-600">{{ sheetStats.at_risk + sheetStats.auto_fail }}</p>
                                <p class="text-xs text-orange-700">En riesgo</p>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Planilla -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base">
                                Lista de estudiantes — {{ sheet.length }} registros
                            </CardTitle>
                            <CardDescription>
                                Selecciona el estado de asistencia para cada estudiante.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div
                                    v-for="row in sheet"
                                    :key="row.student_id"
                                    class="flex flex-wrap items-center gap-3 rounded-lg border p-3 transition-colors"
                                    :class="{
                                        'border-red-200 bg-red-50/50 dark:bg-red-950/20':   row.auto_fail,
                                        'border-amber-200 bg-amber-50/50 dark:bg-amber-950/20': row.at_risk && !row.auto_fail,
                                    }"
                                >
                                    <!-- Info estudiante -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium text-sm truncate">{{ row.full_name }}</p>
                                            <Badge v-if="row.auto_fail" variant="destructive" class="text-[10px] px-1.5 py-0 shrink-0">
                                                <XCircle class="mr-1 h-2.5 w-2.5" /> Reprobado por faltas
                                            </Badge>
                                            <Badge v-else-if="row.at_risk" class="text-[10px] px-1.5 py-0 shrink-0 bg-amber-500 text-white">
                                                <AlertTriangle class="mr-1 h-2.5 w-2.5" /> En riesgo
                                            </Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground font-mono">{{ row.enrollment_number }}</p>
                                        <!-- Mini resumen -->
                                        <p class="text-[10px] text-muted-foreground mt-0.5">
                                            P: {{ row.summary.present }} · A: {{ row.summary.absent }} · T: {{ row.summary.late }}
                                            <span v-if="row.summary.justified > 0"> · Just: {{ row.summary.justified }}</span>
                                        </p>
                                    </div>

                                    <!-- Botones de estado -->
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <Button
                                            v-for="(cfg, key) in statusConfig"
                                            :key="key"
                                            size="sm"
                                            class="h-8 gap-1.5 text-xs transition-all"
                                            :class="row.status === key
                                                ? cfg.class
                                                : 'border bg-background text-muted-foreground hover:bg-muted'"
                                            :variant="row.status === key ? 'default' : 'outline'"
                                            @click="setStatus(row, key as any)"
                                        >
                                            <component :is="cfg.icon" class="h-3.5 w-3.5" />
                                            {{ cfg.label }}
                                        </Button>

                                        <!-- Justificar (solo ausentes o tardanzas) -->
                                        <Button
                                            v-if="row.status === 'absent' || row.status === 'late'"
                                            size="sm"
                                            variant="outline"
                                            class="h-8 text-xs"
                                            :class="row.justified ? 'border-blue-500 text-blue-600' : 'text-muted-foreground'"
                                            title="Gestionar justificación"
                                            @click="openJustify(row)"
                                        >
                                            <component :is="row.justified ? ShieldCheck : ShieldX" class="h-3.5 w-3.5 mr-1" />
                                            {{ row.justified ? 'Justificada' : 'Justificar' }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                </TabsContent>

                <!-- ── TAB: Resumen ── -->
                <TabsContent value="summary" class="mt-4">
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base">Resumen de asistencia del período</CardTitle>
                            <CardDescription>
                                {{ props.recordedDates.length }} clase(s) registrada(s) · Límite: 25% de ausencias
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="props.summary.length === 0" class="text-center py-10 text-muted-foreground text-sm">
                                Aún no hay clases registradas.
                            </div>
                            <table v-else class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-muted-foreground text-xs">
                                        <th class="py-2 text-left font-medium">Estudiante</th>
                                        <th class="py-2 text-center font-medium">Presentes</th>
                                        <th class="py-2 text-center font-medium">Tardanzas</th>
                                        <th class="py-2 text-center font-medium">Ausencias</th>
                                        <th class="py-2 text-center font-medium">Justificadas</th>
                                        <th class="py-2 text-center font-medium">% Asistencia</th>
                                        <th class="py-2 text-center font-medium">% Faltas</th>
                                        <th class="py-2 text-center font-medium">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in props.summary"
                                        :key="row.student_id"
                                        class="border-b last:border-0"
                                        :class="{
                                            'bg-red-50/50 dark:bg-red-950/20':    row.auto_fail,
                                            'bg-amber-50/50 dark:bg-amber-950/20': row.at_risk && !row.auto_fail,
                                        }"
                                    >
                                        <td class="py-2.5 pr-4">
                                            <p class="font-medium">{{ row.full_name }}</p>
                                            <p class="text-[10px] text-muted-foreground font-mono">{{ row.enrollment_number }}</p>
                                        </td>
                                        <td class="py-2.5 text-center text-emerald-600 font-medium">{{ row.summary.present }}</td>
                                        <td class="py-2.5 text-center text-amber-600 font-medium">{{ row.summary.late }}</td>
                                        <td class="py-2.5 text-center text-red-600 font-medium">{{ row.summary.absent }}</td>
                                        <td class="py-2.5 text-center text-blue-600 font-medium">{{ row.summary.justified }}</td>
                                        <td class="py-2.5 text-center font-medium">
                                            {{ row.attendance_pct !== null ? `${row.attendance_pct}%` : '—' }}
                                        </td>
                                        <td class="py-2.5 text-center font-medium" :class="absencePctColor(row.absence_pct)">
                                            {{ row.absence_pct !== null ? `${row.absence_pct}%` : '—' }}
                                        </td>
                                        <td class="py-2.5 text-center">
                                            <Badge
                                                v-if="row.auto_fail"
                                                variant="destructive"
                                                class="text-[10px]"
                                            >
                                                Reprobado
                                            </Badge>
                                            <Badge
                                                v-else-if="row.at_risk"
                                                class="text-[10px] bg-amber-500 text-white"
                                            >
                                                En riesgo
                                            </Badge>
                                            <Badge
                                                v-else
                                                variant="secondary"
                                                class="text-[10px]"
                                            >
                                                Regular
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

        </div>

        <!-- Dialog: Justificación -->
        <Dialog v-model:open="justifyDialog">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Gestionar justificación</DialogTitle>
                    <DialogDescription>
                        {{ justifyRow?.full_name }} · {{ selectedDate }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-2">
                    <div class="flex items-center gap-3">
                        <Button
                            size="sm"
                            :class="justifyValue
                                ? 'bg-blue-500 text-white hover:bg-blue-600'
                                : 'border bg-background text-muted-foreground'"
                            @click="justifyValue = true"
                        >
                            <ShieldCheck class="mr-2 h-4 w-4" /> Justificada
                        </Button>
                        <Button
                            size="sm"
                            :class="!justifyValue
                                ? 'bg-red-500 text-white hover:bg-red-600'
                                : 'border bg-background text-muted-foreground'"
                            @click="justifyValue = false"
                        >
                            <ShieldX class="mr-2 h-4 w-4" /> No justificada
                        </Button>
                    </div>

                    <div class="space-y-1">
                        <Label>Nota de justificación (opcional)</Label>
                        <Input
                            v-model="justifyNote"
                            placeholder="Ej: Certificado médico presentado"
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" :disabled="savingJustify" @click="justifyDialog = false">
                        Cancelar
                    </Button>
                    <Button :disabled="savingJustify" @click="saveJustify">
                        {{ savingJustify ? 'Guardando...' : 'Guardar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
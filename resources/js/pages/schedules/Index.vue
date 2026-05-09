<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import {
    ChevronLeft, ChevronRight, Calendar, PanelRightClose,
    PanelRightOpen, AlertTriangle, BookOpen, Plus, Clock,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
    DialogDescription, DialogFooter,
} from '@/components/ui/dialog';
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

interface AcademicPeriod { id: number; name: string; }
interface Teacher        { id: number; full_name: string; }
interface Classroom      { id: number; name: string; code: string; capacity: number; type: string; }
interface Curriculum     { id: number; subject: string; semester: string; career: string; }

interface ScheduleEvent {
    id:             number;
    section_id:     number;
    curricula_id:   number;
    title:          string;
    teacher:        string;
    teacher_id:     number;
    section_name:   string;
    classroom:      string;
    classroom_id:   number;
    classroom_type: string;
    day_of_week:    number;
    start_time:     string;
    end_time:       string;
    is_recurring:   boolean;
    specific_date:  string | null;
    recurrence_end: string | null;
    color:          string;
    quota:          number;
}

interface Conflict {
    type:    string;
    message: string;
    day:     number;
    time:    string;
}

interface Unassigned {
    curricula_id: number;
    subject:      string;
    hours:        number | null;
}

interface Props {
    academicPeriods: AcademicPeriod[];
    teachers:        Teacher[];
    classrooms:      Classroom[];
    curricula:       Curriculum[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Horarios', href: '/schedules' },
];

const selectedPeriodId = ref<string>(
    props.academicPeriods[0] ? String(props.academicPeriods[0].id) : ''
);
const currentView   = ref<'day' | 'week' | 'month'>('week');
const panelOpen     = ref(false);
const events        = ref<ScheduleEvent[]>([]);
const conflicts     = ref<Conflict[]>([]);
const unassigned    = ref<Unassigned[]>([]);
const loadingEvents = ref(false);

const today       = new Date();
const currentDate = ref(new Date());

const DAYS_LABEL = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
const DAYS_FULL  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const MONTHS     = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

const HOURS = Array.from({ length: 16 }, (_, i) => i + 7);

const weekStart = computed(() => {
    const d = new Date(currentDate.value);
    d.setDate(d.getDate() - d.getDay() + 1);
    return d;
});

const weekDays = computed(() => {
    return Array.from({ length: 6 }, (_, i) => {
        const d = new Date(weekStart.value);
        d.setDate(d.getDate() + i);
        return d;
    });
});

const weekLabel = computed(() => {
    const s = weekDays.value[0];
    const e = weekDays.value[5];
    return `Semana: ${MONTHS[s.getMonth()].slice(0,3)} ${s.getDate()} - ${e.getDate()}, ${e.getFullYear()}`;
});

const monthLabel = computed(() =>
    `${MONTHS[currentDate.value.getMonth()]} ${currentDate.value.getFullYear()}`
);

const navigate = (dir: -1 | 1) => {
    const d = new Date(currentDate.value);
    if (currentView.value === 'day')   d.setDate(d.getDate() + dir);
    if (currentView.value === 'week')  d.setDate(d.getDate() + dir * 7);
    if (currentView.value === 'month') d.setMonth(d.getMonth() + dir);
    currentDate.value = d;
};

const goToday = () => { currentDate.value = new Date(); };

const isToday = (date: Date) =>
    date.toDateString() === today.toDateString();

const loadEvents = async () => {
    if (!selectedPeriodId.value) return;
    loadingEvents.value = true;
    try {
        const [evRes, panelRes] = await Promise.all([
            axios.get('/schedules/events', { params: { academic_period_id: selectedPeriodId.value } }),
            axios.get('/schedules/panel',  { params: { academic_period_id: selectedPeriodId.value } }),
        ]);
        events.value    = evRes.data;
        conflicts.value = panelRes.data.conflicts;
        unassigned.value = panelRes.data.unassigned;
    } catch {
        toast.error('Error al cargar los horarios');
    } finally {
        loadingEvents.value = false;
    }
};

onMounted(loadEvents);

const timeToMinutes = (t: string) => {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
};

const GRID_START = 7 * 60;
const HOUR_PX    = 60;

const eventStyle = (ev: ScheduleEvent) => {
    const start  = timeToMinutes(ev.start_time) - GRID_START;
    const dur    = timeToMinutes(ev.end_time) - timeToMinutes(ev.start_time);
    const top    = (start / 60) * HOUR_PX;
    const height = (dur / 60) * HOUR_PX;
    return {
        top:             `${top}px`,
        height:          `${Math.max(height - 4, 20)}px`,
        backgroundColor: ev.color,
        opacity:         0.92,
    };
};

const eventsByDay = (dayOfWeek: number) =>
    events.value.filter(e => e.day_of_week === dayOfWeek);

const jsDateToDayOfWeek = (d: Date) => d.getDay();

const createDialogOpen = ref(false);
const creating         = ref(false);

const form = ref({
    curricula_id:    '',
    teacher_id:      '',
    section_name:    'Sección A',
    quota:           '30',
    classroom_id:    '',
    day_of_week:     '',
    start_time:      '07:00',
    end_time:        '08:00',
    is_recurring:    true,
    specific_date:   '',
    recurrence_end:  '',
});

const openCreateDialog = (dayOfWeek?: number, hour?: number) => {
    editingSchedule.value = null;
    form.value = {
        curricula_id:   '',
        teacher_id:     '',
        section_name:   'Sección A',
        quota:          '30',
        classroom_id:   '',
        day_of_week:    dayOfWeek !== undefined ? String(dayOfWeek) : '',
        start_time:     hour !== undefined ? `${String(hour).padStart(2,'0')}:00` : '07:00',
        end_time:       hour !== undefined ? `${String(hour+1).padStart(2,'0')}:00` : '08:00',
        is_recurring:   true,
        specific_date:  '',
        recurrence_end: '',
    };
    createDialogOpen.value = true;
};

const handleCreate = async () => {
    creating.value = true;
    try {
        if (dialogMode.value === 'edit' && editingSchedule.value) {
            await axios.put(`/sections/${editingSchedule.value.section_id}`, {
                curricula_id: Number(form.value.curricula_id),
                name:         form.value.section_name,
                quota:        Number(form.value.quota),
                teacher_id:   Number(form.value.teacher_id),
            });

            await axios.put(`/schedules/${editingSchedule.value.id}`, {
                classroom_id:   Number(form.value.classroom_id),
                day_of_week:    Number(form.value.day_of_week),
                start_time:     form.value.start_time,
                end_time:       form.value.end_time,
                is_recurring:   form.value.is_recurring,
                specific_date:  form.value.is_recurring ? null : form.value.specific_date,
                recurrence_end: form.value.recurrence_end || null,
            });

            toast.success('Horario actualizado exitosamente');
        } else {
            const secRes = await axios.post('/sections', {
                curricula_id:       Number(form.value.curricula_id),
                teacher_id:         Number(form.value.teacher_id),
                academic_period_id: Number(selectedPeriodId.value),
                name:               form.value.section_name,
                quota:              Number(form.value.quota),
            });

            await axios.post('/schedules', {
                section_id:     secRes.data.section.id,
                classroom_id:   Number(form.value.classroom_id),
                day_of_week:    Number(form.value.day_of_week),
                start_time:     form.value.start_time,
                end_time:       form.value.end_time,
                is_recurring:   form.value.is_recurring,
                specific_date:  form.value.is_recurring ? null : form.value.specific_date,
                recurrence_end: form.value.recurrence_end || null,
            });
            toast.success('Horario creado exitosamente');
        }

        createDialogOpen.value = false;
        editingSchedule.value  = null;
        await loadEvents();
    } catch (error: any) {
        const msg = error?.response?.data?.message
            ?? Object.values(error?.response?.data?.errors ?? {})[0] as string
            ?? 'Error al guardar el horario';
        toast.error(msg);
    } finally {
        creating.value = false;
    }
};

const editingSchedule = ref<ScheduleEvent | null>(null);
const dialogMode      = computed(() => editingSchedule.value ? 'edit' : 'create');

const openEditDialog = (ev: ScheduleEvent) => {
    editingSchedule.value = ev;
    form.value = {
        curricula_id:   String(ev.curricula_id),
        teacher_id:     String(ev.teacher_id),
        section_name:   ev.section_name,
        quota:          String(ev.quota),
        classroom_id:   String(ev.classroom_id ?? ''),
        day_of_week:    String(ev.day_of_week),
        start_time:     ev.start_time.slice(0, 5),
        end_time:       ev.end_time.slice(0, 5),
        is_recurring:   ev.is_recurring,
        specific_date:  ev.specific_date ?? '',
        recurrence_end: ev.recurrence_end ?? '',
    };
    createDialogOpen.value = true;
};

const deleteSchedule = async (scheduleId: number) => {
    try {
        await axios.delete(`/schedules/${scheduleId}`);
        toast.success('Horario eliminado');
        await loadEvents();
    } catch {
        toast.error('Error al eliminar el horario');
    }
};

const monthDays = computed(() => {
    const year  = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const first = new Date(year, month, 1);
    const last  = new Date(year, month + 1, 0);

    const days: (Date | null)[] = [];
    const startPad = (first.getDay() + 6) % 7;
    for (let i = 0; i < startPad; i++) days.push(null);
    for (let d = 1; d <= last.getDate(); d++) days.push(new Date(year, month, d));
    return days;
});

const eventsForMonthDay = (date: Date | null) => {
    if (!date) return [];
    const dow = date.getDay();
    return events.value.filter(e => e.day_of_week === dow).slice(0, 3);
};
</script>

<template>
    <Head title="Horarios" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-0">

            <!-- Toolbar -->
            <div class="flex items-center justify-between border-b px-6 py-3">
                <div class="flex items-center gap-3">
                    <!-- Período académico -->
                    <Select v-model="selectedPeriodId" @update:model-value="loadEvents">
                        <SelectTrigger class="w-56">
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

                    <!-- Navegación -->
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="icon" @click="navigate(-1)">
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button variant="outline" size="sm" @click="goToday">Hoy</Button>
                        <Button variant="outline" size="icon" @click="navigate(1)">
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Etiqueta de fecha -->
                    <span class="text-sm font-medium">
                        {{ currentView === 'month' ? monthLabel : weekLabel }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Vista -->
                    <div class="flex rounded-md border">
                        <Button
                            v-for="v in (['day','week','month'] as const)"
                            :key="v"
                            :variant="currentView === v ? 'default' : 'ghost'"
                            size="sm"
                            class="rounded-none first:rounded-l-md last:rounded-r-md"
                            @click="currentView = v"
                        >
                            {{ v === 'day' ? 'Día' : v === 'week' ? 'Semana' : 'Mes' }}
                        </Button>
                    </div>

                    <!-- Nuevo horario -->
                    <Button size="sm" @click="openCreateDialog()">
                        <Plus class="mr-2 h-4 w-4" /> Nuevo horario
                    </Button>

                    <!-- Toggle panel -->
                    <Button variant="outline" size="icon" @click="panelOpen = !panelOpen">
                        <PanelRightClose v-if="panelOpen" class="h-4 w-4" />
                        <PanelRightOpen  v-else            class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="flex flex-1 overflow-hidden">

                <!-- ── VISTA SEMANA ── -->
                <div v-if="currentView === 'week'" class="flex flex-1 flex-col">
                    <!-- Header días -->
                    <div class="grid border-b" style="grid-template-columns: 64px repeat(6, 1fr)">
                        <div class="border-r" />
                        <div
                            v-for="(day, i) in weekDays"
                            :key="i"
                            class="border-r px-2 py-2 text-center text-sm last:border-r-0"
                            :class="isToday(day) ? 'bg-primary/5 font-semibold text-primary' : 'text-muted-foreground'"
                        >
                            <div>{{ DAYS_LABEL[day.getDay()] }}</div>
                            <div
                                class="mx-auto mt-0.5 flex h-7 w-7 items-center justify-center rounded-full text-sm"
                                :class="isToday(day) ? 'bg-primary text-primary-foreground' : ''"
                            >
                                {{ day.getDate() }}
                            </div>
                        </div>
                    </div>

                    <!-- Grilla horas -->
                    <div class="flex-1">
                        <div class="relative grid" style="grid-template-columns: 64px repeat(6, 1fr)">

                            <!-- Columna horas -->
                            <div>
                                <div
                                    v-for="h in HOURS"
                                    :key="h"
                                    class="flex items-start justify-end border-b border-r pr-2 text-xs text-muted-foreground"
                                    style="height: 60px; padding-top: 4px"
                                >
                                    {{ String(h).padStart(2,'0') }}:00
                                </div>
                            </div>

                            <!-- Columnas días -->
                            <div
                                v-for="(day, di) in weekDays"
                                :key="di"
                                class="relative border-r last:border-r-0"
                                :class="isToday(day) ? 'bg-primary/5' : ''"
                                :style="{ height: `${HOURS.length * HOUR_PX}px` }"
                                @click.self="openCreateDialog(jsDateToDayOfWeek(day))"
                            >
                                <!-- Líneas de hora -->
                                <div
                                    v-for="h in HOURS"
                                    :key="h"
                                    class="absolute w-full border-b border-dashed border-border/40"
                                    :style="{ top: `${(h - 7) * HOUR_PX}px` }"
                                />

                                <!-- Línea de ahora -->
                                <div
                                    v-if="isToday(day)"
                                    class="absolute z-10 w-full border-t-2 border-red-500"
                                    :style="{
                                        top: `${((today.getHours() - 7) * 60 + today.getMinutes()) / 60 * HOUR_PX}px`
                                    }"
                                >
                                    <div class="absolute -left-1 -top-1.5 h-3 w-3 rounded-full bg-red-500" />
                                </div>

                                <!-- Eventos -->
                                <div
                                    v-for="ev in eventsByDay(jsDateToDayOfWeek(day))"
                                    :key="ev.id"
                                    class="absolute left-1 right-1 cursor-pointer overflow-hidden rounded-md px-1.5 py-1 text-white shadow-sm transition-opacity hover:opacity-100 group"
                                    :style="eventStyle(ev)"
                                    :title="`${ev.title} — ${ev.teacher} — ${ev.classroom}`"
                                >
                                    <div class="truncate text-xs font-semibold leading-tight">{{ ev.title }}</div>
                                    <div class="truncate text-[10px] opacity-90">{{ ev.teacher }}</div>
                                    <div class="truncate text-[10px] opacity-80">{{ ev.classroom }}</div>
                                    <div class="text-[10px] opacity-80">{{ ev.start_time }} - {{ ev.end_time }}</div>
                                    <!-- Acciones al hacer hover -->
                                    <div class="absolute right-1 top-1 hidden gap-0.5 group-hover:flex">
                                        <button
                                            class="rounded bg-white/20 p-0.5 hover:bg-white/40"
                                            title="Asistencia"
                                            @click.stop="router.visit(`/sections/${ev.section_id}/attendance`)"
                                        >
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                            </svg>
                                        </button>
                                        <button
                                            class="rounded bg-white/20 p-0.5 hover:bg-blue-600/60"
                                            title="Editar"
                                            @click.stop="openEditDialog(ev)"
                                        >
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/>
                                            </svg>
                                        </button>
                                        <button
                                            class="rounded bg-white/20 p-0.5 hover:bg-red-600/60"
                                            title="Eliminar"
                                            @click.stop="deleteSchedule(ev.id)"
                                        >
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── VISTA DÍA ── -->
                <div v-else-if="currentView === 'day'" class="flex flex-1 flex-col overflow-hidden">
                    <div class="border-b px-6 py-2 text-center text-sm font-medium">
                        {{ DAYS_FULL[currentDate.getDay()] }}, {{ currentDate.getDate() }} de
                        {{ MONTHS[currentDate.getMonth()] }} {{ currentDate.getFullYear() }}
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <div class="relative grid" style="grid-template-columns: 64px 1fr">
                            <div>
                                <div
                                    v-for="h in HOURS" :key="h"
                                    class="flex items-start justify-end border-b border-r pr-2 text-xs text-muted-foreground"
                                    style="height:60px;padding-top:4px"
                                >
                                    {{ String(h).padStart(2,'0') }}:00
                                </div>
                            </div>
                            <div
                                class="relative"
                                :style="{ height: `${HOURS.length * HOUR_PX}px` }"
                                @click.self="openCreateDialog(jsDateToDayOfWeek(currentDate))"
                            >
                                <div
                                    v-for="h in HOURS" :key="h"
                                    class="absolute w-full border-b border-dashed border-border/40"
                                    :style="{ top: `${(h-7)*HOUR_PX}px` }"
                                />
                                <div
                                    v-if="isToday(currentDate)"
                                    class="absolute z-10 w-full border-t-2 border-red-500"
                                    :style="{ top: `${((today.getHours()-7)*60+today.getMinutes())/60*HOUR_PX}px` }"
                                >
                                    <div class="absolute -left-1 -top-1.5 h-3 w-3 rounded-full bg-red-500" />
                                </div>
                                <div
                                    v-for="ev in eventsByDay(jsDateToDayOfWeek(currentDate))"
                                    :key="ev.id"
                                    class="absolute left-2 right-2 cursor-pointer overflow-hidden rounded-md px-2 py-1 text-white shadow-sm"
                                    :style="eventStyle(ev)"
                                >
                                    <div class="text-sm font-semibold">{{ ev.title }}</div>
                                    <div class="text-xs opacity-90">{{ ev.teacher }}</div>
                                    <div class="text-xs opacity-80">{{ ev.classroom }} · {{ ev.start_time }} - {{ ev.end_time }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── VISTA MES ── -->
                <div v-else class="flex flex-1 flex-col overflow-auto p-4">
                    <div class="grid grid-cols-7 gap-px rounded-lg border bg-border">
                        <div
                            v-for="d in ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom']"
                            :key="d"
                            class="bg-background py-2 text-center text-xs font-medium text-muted-foreground"
                        >
                            {{ d }}
                        </div>
                        <div
                            v-for="(day, i) in monthDays"
                            :key="i"
                            class="min-h-[100px] bg-background p-1"
                            :class="day && isToday(day) ? 'bg-primary/5' : ''"
                            @click="day && openCreateDialog(jsDateToDayOfWeek(day))"
                        >
                            <template v-if="day">
                                <div
                                    class="mb-1 flex h-6 w-6 items-center justify-center rounded-full text-xs"
                                    :class="isToday(day) ? 'bg-primary text-primary-foreground font-bold' : 'text-muted-foreground'"
                                >
                                    {{ day.getDate() }}
                                </div>
                                <div
                                    v-for="ev in eventsForMonthDay(day)"
                                    :key="ev.id"
                                    class="mb-0.5 truncate rounded px-1 text-[10px] text-white"
                                    :style="{ backgroundColor: ev.color }"
                                >
                                    {{ ev.title }}
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- ── PANEL DERECHO ── -->
                <aside
                    v-if="panelOpen"
                    class="flex w-72 shrink-0 flex-col gap-3 overflow-y-auto border-l p-4"
                >
                    <!-- Conflictos -->
                    <Card>
                        <CardHeader class="pb-2 pt-4">
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <AlertTriangle class="h-4 w-4 text-destructive" />
                                Conflictos
                                <Badge v-if="conflicts.length" variant="destructive" class="ml-auto">
                                    {{ conflicts.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2 pb-4">
                            <p v-if="conflicts.length === 0" class="text-xs text-muted-foreground">
                                Sin conflictos detectados.
                            </p>
                            <div
                                v-for="(c, i) in conflicts"
                                :key="i"
                                class="rounded-md border border-destructive/30 bg-destructive/5 p-2 text-xs text-destructive"
                            >
                                <div class="font-medium">{{ DAYS_FULL[c.day] }} {{ c.time }}</div>
                                <div class="mt-0.5 opacity-80">{{ c.message }}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Materias sin asignar -->
                    <Card>
                        <CardHeader class="pb-2 pt-4">
                            <CardTitle class="flex items-center gap-2 text-sm">
                                <BookOpen class="h-4 w-4 text-muted-foreground" />
                                Sin asignar
                                <Badge v-if="unassigned.length" variant="secondary" class="ml-auto">
                                    {{ unassigned.length }}
                                </Badge>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-1.5 pb-4">
                            <p v-if="unassigned.length === 0" class="text-xs text-muted-foreground">
                                Todas las materias tienen sección asignada.
                            </p>
                            <div
                                v-for="u in unassigned"
                                :key="u.curricula_id"
                                class="flex cursor-pointer items-center justify-between rounded-md border px-2 py-1.5 text-xs hover:bg-accent"
                                @click="openCreateDialog(); form.curricula_id = String(u.curricula_id)"
                            >
                                <span class="font-medium">{{ u.subject }}</span>
                                <span v-if="u.hours" class="text-muted-foreground">{{ u.hours }}h</span>
                            </div>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>

        <!-- ── DIALOG CREAR HORARIO ── -->
        <Dialog v-model:open="createDialogOpen">
            <DialogContent class="w-[90vw] max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ dialogMode === 'edit' ? 'Editar horario' : 'Nuevo horario' }}</DialogTitle>
                    <DialogDescription>
                        {{ dialogMode === 'edit' ? 'Modifica el bloque horario.' : 'Crea una sección y asígnale un bloque horario.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid grid-cols-2 gap-4 py-2">
                    <!-- Materia -->
                    <div class="col-span-2 space-y-1">
                        <Label>Materia <span class="text-destructive">*</span></Label>
                        <Select v-model="form.curricula_id">
                            <SelectTrigger><SelectValue placeholder="Selecciona materia" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="c in props.curricula"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    {{ c.subject }} — {{ c.semester }} ({{ c.career }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Docente -->
                    <div class="space-y-1">
                        <Label>Docente <span class="text-destructive">*</span></Label>
                        <Select v-model="form.teacher_id">
                            <SelectTrigger><SelectValue placeholder="Selecciona docente" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="t in props.teachers"
                                    :key="t.id"
                                    :value="String(t.id)"
                                >
                                    {{ t.full_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Aula -->
                    <div class="space-y-1">
                        <Label>Aula <span class="text-destructive">*</span></Label>
                        <Select v-model="form.classroom_id">
                            <SelectTrigger><SelectValue placeholder="Selecciona aula" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="cl in props.classrooms"
                                    :key="cl.id"
                                    :value="String(cl.id)"
                                >
                                    {{ cl.name }} ({{ cl.code }}) — Cap. {{ cl.capacity }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Nombre sección y cupo -->
                    <div class="space-y-1">
                        <Label>Nombre sección</Label>
                        <Input v-model="form.section_name" placeholder="Sección A" />
                    </div>
                    <div class="space-y-1">
                        <Label>Cupo</Label>
                        <Input v-model="form.quota" type="number" min="1" max="500" />
                    </div>

                    <!-- Día -->
                    <div class="space-y-1">
                        <Label>Día <span class="text-destructive">*</span></Label>
                        <Select v-model="form.day_of_week">
                            <SelectTrigger><SelectValue placeholder="Día de la semana" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="(d, i) in DAYS_FULL" :key="i" :value="String(i)">
                                    {{ d }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Horas -->
                    <div class="space-y-1">
                        <Label>Hora inicio <span class="text-destructive">*</span></Label>
                        <Input v-model="form.start_time" type="time" />
                    </div>
                    <div class="space-y-1">
                        <Label>Hora fin <span class="text-destructive">*</span></Label>
                        <Input v-model="form.end_time" type="time" />
                    </div>

                    <!-- Recurrente -->
                    <div class="col-span-2 flex items-center gap-3">
                        <Switch v-model:checked="form.is_recurring" />
                        <Label>Horario recurrente (se repite cada semana)</Label>
                    </div>

                    <!-- Fecha específica si no es recurrente -->
                    <div v-if="!form.is_recurring" class="space-y-1">
                        <Label>Fecha específica <span class="text-destructive">*</span></Label>
                        <Input v-model="form.specific_date" type="date" />
                    </div>

                    <!-- Fecha fin recurrencia -->
                    <div v-if="form.is_recurring" class="space-y-1">
                        <Label>Fecha fin (opcional)</Label>
                        <Input v-model="form.recurrence_end" type="date" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" :disabled="creating" @click="createDialogOpen = false">
                        Cancelar
                    </Button>
                    <Button :disabled="creating" @click="handleCreate">
                        {{ creating ? 'Guardando...' : dialogMode === 'edit' ? 'Guardar cambios' : 'Crear horario' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
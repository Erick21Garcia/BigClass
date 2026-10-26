<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlertTriangle, BookOpen, CalendarDays, CalendarPlus, CheckCircle2, ChevronLeft,
    ChevronRight, ClipboardCheck, Clock, MapPin, Pencil, PanelRightClose,
    PanelRightOpen, Plus, Repeat, Trash2, User,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
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

// "Ahora" reactivo: mueve la línea roja y marca la clase en curso.
const now         = ref(new Date());
const currentDate = ref(new Date());
let clock: ReturnType<typeof setInterval>;

const views = [
    { key: 'day',   label: 'Día' },
    { key: 'week',  label: 'Semana' },
    { key: 'month', label: 'Mes' },
] as const;

const DAYS_LABEL = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
const DAYS_FULL  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const MONTHS     = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

const HOURS      = Array.from({ length: 16 }, (_, i) => i + 7);
const GRID_START = 7 * 60;
const HOUR_PX    = 80;

// Lunes de la semana visible (antes los domingos saltaba a la semana siguiente).
const weekStart = computed(() => {
    const d = new Date(currentDate.value);
    d.setDate(d.getDate() - ((d.getDay() + 6) % 7));
    return d;
});

const weekDays = computed(() => {
    return Array.from({ length: 6 }, (_, i) => {
        const d = new Date(weekStart.value);
        d.setDate(d.getDate() + i);
        return d;
    });
});

// Columnas visibles: una en vista día, seis en vista semana.
const columns = computed(() => (currentView.value === 'day' ? [currentDate.value] : weekDays.value));
const gridCols = computed(() => ({
    gridTemplateColumns: `72px repeat(${columns.value.length}, minmax(0, 1fr))`,
}));

const dateLabel = computed(() => {
    const d = currentDate.value;
    if (currentView.value === 'month')
        return { strong: MONTHS[d.getMonth()], rest: ` ${d.getFullYear()}` };
    if (currentView.value === 'day')
        return {
            strong: `${DAYS_FULL[d.getDay()]} ${d.getDate()} de ${MONTHS[d.getMonth()].toLowerCase()}`,
            rest: ` de ${d.getFullYear()}`,
        };
    const s  = weekDays.value[0];
    const e  = weekDays.value[5];
    const ms = MONTHS[s.getMonth()].toLowerCase();
    const me = MONTHS[e.getMonth()].toLowerCase();
    return {
        strong: s.getMonth() === e.getMonth()
            ? `${s.getDate()} – ${e.getDate()} de ${me}`
            : `${s.getDate()} ${ms.slice(0, 3)} – ${e.getDate()} ${me.slice(0, 3)}`,
        rest: ` de ${e.getFullYear()}`,
    };
});

const navigate = (dir: -1 | 1) => {
    const d = new Date(currentDate.value);
    if (currentView.value === 'day')   d.setDate(d.getDate() + dir);
    if (currentView.value === 'week')  d.setDate(d.getDate() + dir * 7);
    if (currentView.value === 'month') d.setMonth(d.getMonth() + dir);
    currentDate.value = d;
};

const goToday = () => { currentDate.value = new Date(); };

const isToday = (date: Date) =>
    date.toDateString() === now.value.toDateString();

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

const timeToMinutes = (t: string) => {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
};

const hhmm = (t: string) => t.slice(0, 5);

// Posición vertical del bloque (el color ahora sale de la paleta de la marca).
const eventStyle = (ev: ScheduleEvent) => {
    const start  = timeToMinutes(ev.start_time) - GRID_START;
    const dur    = timeToMinutes(ev.end_time) - timeToMinutes(ev.start_time);
    const top    = (start / 60) * HOUR_PX;
    const height = (dur / 60) * HOUR_PX;
    return {
        top:    `${top + 3}px`,
        height: `${Math.max(height - 6, 24)}px`,
    };
};

const isCompact = (ev: ScheduleEvent) =>
    timeToMinutes(ev.end_time) - timeToMinutes(ev.start_time) <= 60;

const eventsByDay = (dayOfWeek: number) =>
    events.value.filter(e => e.day_of_week === dayOfWeek);

const jsDateToDayOfWeek = (d: Date) => d.getDay();

const classLabel = (n: number) =>
    n === 0 ? 'Sin clases' : n === 1 ? '1 clase' : `${n} clases`;

// ── Hora actual y clase en curso ─────────────────────────────────────
const nowMinutes = computed(() => now.value.getHours() * 60 + now.value.getMinutes());
const nowTop     = computed(() => ((nowMinutes.value - GRID_START) / 60) * HOUR_PX);
const nowVisible = computed(
    () => nowMinutes.value >= GRID_START && nowMinutes.value <= GRID_START + HOURS.length * 60,
);
const nowLabel = computed(
    () => `${String(now.value.getHours()).padStart(2, '0')}:${String(now.value.getMinutes()).padStart(2, '0')}`,
);

const isLive = (ev: ScheduleEvent, day: Date) =>
    isToday(day)
    && nowMinutes.value >= timeToMinutes(ev.start_time)
    && nowMinutes.value <  timeToMinutes(ev.end_time);

// ── Colores por materia (paleta de la marca) ─────────────────────────
const palette = [
    {
        card: 'bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white',
        chip: 'bg-white/20',
        tool: 'bg-white/20 hover:bg-white/35',
        pill: 'bg-[#1E3A5F] text-white',
    },
    {
        card: 'bg-gradient-to-br from-[#214EA4] to-[#3F7BE0] text-white',
        chip: 'bg-white/20',
        tool: 'bg-white/20 hover:bg-white/35',
        pill: 'bg-[#214EA4] text-white',
    },
    {
        card: 'bg-gradient-to-br from-[#5E97F0] to-[#8CB8FF] text-[#0B2545]',
        chip: 'bg-white/50',
        tool: 'bg-white/40 hover:bg-white/60',
        pill: 'bg-[#8CB8FF] text-[#0B2545]',
    },
    {
        card: 'border border-[#C9DAF7] border-l-[5px] border-l-[#214EA4] bg-[#E8EEFB] text-[#0B2545]',
        chip: 'bg-white text-[#214EA4]',
        tool: 'bg-white hover:bg-[#C9DAF7]',
        pill: 'border border-[#C9DAF7] bg-[#E8EEFB] text-[#1E3A5F]',
    },
];

const subjectOrder = computed(() => {
    const ids = [...new Set(events.value.map(e => e.curricula_id))].sort((a, b) => a - b);
    return new Map(ids.map((id, i) => [id, i]));
});

const tone = (ev: ScheduleEvent) =>
    palette[(subjectOrder.value.get(ev.curricula_id) ?? 0) % palette.length];

// ── Scroll inicial hacia la hora actual ──────────────────────────────
const scroller = ref<HTMLElement | null>(null);

const scrollToNow = async () => {
    await nextTick();
    if (!scroller.value || !nowVisible.value) return;
    scroller.value.scrollTop = Math.max(0, nowTop.value - HOUR_PX * 1.5);
};

onMounted(() => {
    clock = setInterval(() => { now.value = new Date(); }, 30_000);
    loadEvents();
    scrollToNow();
});
onUnmounted(() => clearInterval(clock));

// Hora donde se hizo clic en la grilla (para precargar el diálogo).
const hourAt = (e: MouseEvent) =>
    Math.min(21, Math.max(7, 7 + Math.floor(e.offsetY / HOUR_PX)));

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
const dialogMode = computed(() => editingSchedule.value ? 'edit' : 'create');

const selectedPeriodName = computed(
    () => props.academicPeriods.find((p) => String(p.id) === selectedPeriodId.value)?.name ?? '',
);

const fieldClass =
    'h-11 w-full rounded-xl border-[1.5px] border-transparent bg-[#E8EEFB] px-3.5 text-sm text-[#0B2545] shadow-none placeholder:text-[#7B8AA3] focus-visible:border-[#1E3A5F] focus-visible:bg-white focus-visible:ring-4 focus-visible:ring-[#1E3A5F]/10 dark:bg-muted dark:text-foreground';
const labelClass = 'text-[13px] font-semibold text-[#0B2545] dark:text-foreground';

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
    if (!window.confirm('¿Eliminar este bloque horario?')) return;
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

// Devuelve todos los del día; la vista muestra 3 y un "+N más".
const eventsForMonthDay = (date: Date | null) => {
    if (!date) return [];
    return events.value.filter(e => e.day_of_week === date.getDay());
};

// Estilos de los botones de cristal del banner.
const glassBtn =
    'grid h-[34px] min-w-[34px] place-items-center rounded-[10px] border border-white/30 bg-white/15 px-3 text-[13px] font-medium text-white transition hover:bg-white/25';
</script>

<template>
    <Head title="Horarios">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&display=swap"
            rel="stylesheet"
        />
    </Head>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 bg-[#EAF0FB] p-6 dark:bg-transparent">
            <!-- Banner con los controles -->
            <div class="relative overflow-hidden rounded-[20px] bg-[linear-gradient(120deg,#1E3A5F,#214EA4)] px-8 py-5 text-white">
                <div class="pointer-events-none absolute -right-12 -top-28 h-[210px] w-[210px] rotate-45 bg-[#2F63C9]/80"></div>
                <div class="pointer-events-none absolute -top-32 right-[420px] h-[160px] w-[160px] rotate-45 bg-[#8CB8FF]/[0.16]"></div>
                <div class="pointer-events-none absolute -bottom-28 -left-12 h-[140px] w-[140px] rotate-45 bg-[#2F63C9]/50"></div>

                <div class="relative flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="acadex-title text-[32px] leading-[1.1]">Horarios</h1>
                        <div class="mt-2.5 flex items-center gap-2.5">
                            <button type="button" :class="glassBtn" aria-label="Anterior" @click="navigate(-1)">
                                <ChevronLeft class="size-4" />
                            </button>
                            <button type="button" :class="glassBtn" @click="goToday">Hoy</button>
                            <button type="button" :class="glassBtn" aria-label="Siguiente" @click="navigate(1)">
                                <ChevronRight class="size-4" />
                            </button>
                            <span class="ml-1.5 text-[14.5px] text-[#E4EDFC]">
                                <b class="font-semibold text-white">{{ dateLabel.strong }}</b>{{ dateLabel.rest }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Período académico -->
                        <Select v-model="selectedPeriodId" @update:model-value="loadEvents">
                            <SelectTrigger
                                class="h-11 w-[290px] rounded-xl !border-white/30 !bg-white/15 !text-white focus:ring-white/40 [&_svg]:!text-white/80 [&_svg]:!opacity-100"
                            >
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

                        <!-- Vista -->
                        <div class="flex gap-1 rounded-xl border border-white/30 bg-white/15 p-1">
                            <button
                                v-for="v in views"
                                :key="v.key"
                                type="button"
                                class="h-[34px] rounded-[9px] px-4 text-[13.5px] font-medium transition"
                                :class="
                                    currentView === v.key
                                        ? 'bg-white font-semibold text-[#1E3A5F] shadow-[0_6px_14px_-8px_rgba(0,0,0,0.5)]'
                                        : 'text-[#DDEBFF] hover:bg-white/10'
                                "
                                @click="currentView = v.key"
                            >
                                {{ v.label }}
                            </button>
                        </div>

                        <!-- Nuevo horario -->
                        <button
                            type="button"
                            class="inline-flex h-11 items-center gap-2 rounded-xl bg-white px-5 text-[14.5px] font-semibold text-[#1E3A5F] shadow-[0_10px_20px_-10px_rgba(0,0,0,0.5)] transition hover:bg-[#EAF0FB]"
                            @click="openCreateDialog()"
                        >
                            <Plus class="size-[17px]" />
                            Nuevo horario
                        </button>

                        <!-- Toggle panel -->
                        <button
                            type="button"
                            class="grid size-11 place-items-center rounded-xl border border-white/30 bg-white/15 text-white transition hover:bg-white/25"
                            aria-label="Mostrar u ocultar el panel"
                            @click="panelOpen = !panelOpen"
                        >
                            <PanelRightClose v-if="panelOpen" class="size-[18px]" />
                            <PanelRightOpen v-else class="size-[18px]" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendario + panel -->
            <div class="flex min-h-0 flex-1 gap-4">
                <div
                    class="min-w-0 flex-1 overflow-hidden rounded-[18px] border border-[#D8E2F4] bg-card shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border"
                >
                    <div
                        v-if="loadingEvents"
                        class="h-0.5 animate-pulse bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF]"
                    />

                    <!-- ── DÍA / SEMANA ── -->
                    <div
                        v-if="currentView !== 'month'"
                        ref="scroller"
                        class="relative max-h-[calc(100vh-20rem)] min-h-[420px] overflow-y-auto"
                    >
                        <!-- Encabezado (se queda arriba al hacer scroll) -->
                        <div
                            class="sticky top-0 z-20 grid border-b border-[#E1E9F7] bg-[#F7F9FD] dark:border-border dark:bg-muted"
                            :style="gridCols"
                        >
                            <div />
                            <div
                                v-for="day in columns"
                                :key="day.toISOString()"
                                class="flex flex-col items-center justify-center gap-0.5 border-l border-[#EAF0FB] py-3 dark:border-border"
                                :class="isToday(day) ? 'bg-gradient-to-b from-[#E3EDFF] to-[#EEF4FF] dark:from-[#214EA4]/25 dark:to-[#214EA4]/10' : ''"
                            >
                                <span
                                    class="text-[12.5px] font-medium"
                                    :class="isToday(day) ? 'font-semibold text-[#214EA4] dark:text-[#8CB8FF]' : 'text-muted-foreground'"
                                >
                                    {{ DAYS_LABEL[day.getDay()] }}<template v-if="isToday(day)"> · Hoy</template>
                                </span>
                                <span
                                    class="grid size-[34px] place-items-center rounded-full text-base font-semibold"
                                    :class="
                                        isToday(day)
                                            ? 'bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white shadow-[0_8px_16px_-8px_rgba(33,78,164,0.8)]'
                                            : ''
                                    "
                                >
                                    {{ day.getDate() }}
                                </span>
                                <span
                                    class="text-[11px]"
                                    :class="isToday(day) ? 'font-medium text-[#214EA4] dark:text-[#8CB8FF]' : 'text-muted-foreground/80'"
                                >
                                    {{ classLabel(eventsByDay(jsDateToDayOfWeek(day)).length) }}
                                </span>
                            </div>
                        </div>

                        <!-- Cuerpo -->
                        <div class="relative grid" :style="gridCols">
                            <!-- Horas -->
                            <div class="relative" :style="{ height: `${HOURS.length * HOUR_PX}px` }">
                                <span
                                    v-for="h in HOURS"
                                    :key="h"
                                    class="absolute right-3 text-xs font-medium text-muted-foreground"
                                    :class="h === 7 ? 'translate-y-1' : '-translate-y-2'"
                                    :style="{ top: `${(h - 7) * HOUR_PX}px` }"
                                >
                                    {{ String(h).padStart(2, '0') }}:00
                                </span>

                                <span
                                    v-if="nowVisible && columns.some(isToday)"
                                    class="absolute right-1.5 z-10 -translate-y-1/2 rounded-full bg-[#E5484D] px-2 py-0.5 text-[11px] font-semibold text-white"
                                    :style="{ top: `${nowTop}px` }"
                                >
                                    {{ nowLabel }}
                                </span>
                            </div>

                            <!-- Columnas de días -->
                            <div
                                v-for="day in columns"
                                :key="day.toISOString()"
                                class="relative border-l border-[#EAF0FB] dark:border-border"
                                :class="isToday(day) ? 'bg-[#214EA4]/[0.05]' : ''"
                                :style="{ height: `${HOURS.length * HOUR_PX}px` }"
                                @click.self="openCreateDialog(jsDateToDayOfWeek(day), hourAt($event))"
                            >
                                <!-- Líneas de hora y de media hora -->
                                <div
                                    v-for="h in HOURS"
                                    :key="h"
                                    class="pointer-events-none absolute w-full border-t border-[#E4ECF9] dark:border-border/50"
                                    :style="{ top: `${(h - 7) * HOUR_PX}px` }"
                                />
                                <div
                                    v-for="h in HOURS"
                                    :key="`${h}-half`"
                                    class="pointer-events-none absolute w-full border-t border-dashed border-[#EEF3FB] dark:border-border/30"
                                    :style="{ top: `${(h - 7) * HOUR_PX + HOUR_PX / 2}px` }"
                                />

                                <!-- Línea de ahora -->
                                <div
                                    v-if="isToday(day) && nowVisible"
                                    class="pointer-events-none absolute z-[2] h-0.5 w-full bg-[#E5484D]"
                                    :style="{ top: `${nowTop}px` }"
                                >
                                    <span class="absolute -left-[5px] -top-1 size-2.5 rounded-full bg-[#E5484D]" />
                                </div>

                                <!-- Eventos -->
                                <div
                                    v-for="ev in eventsByDay(jsDateToDayOfWeek(day))"
                                    :key="ev.id"
                                    class="group absolute left-1.5 right-1.5 z-[5] overflow-hidden rounded-[14px] px-3 py-2.5 shadow-[0_12px_22px_-14px_rgba(30,58,95,0.7)] transition hover:-translate-y-px hover:shadow-[0_16px_26px_-14px_rgba(30,58,95,0.8)]"
                                    :class="tone(ev).card"
                                    :style="eventStyle(ev)"
                                    :title="`${ev.title} — ${ev.teacher} — ${ev.classroom}`"
                                >
                                    <!-- Bloque de 1 hora o menos -->
                                    <template v-if="isCompact(ev)">
                                        <div class="flex items-center gap-2">
                                            <span class="truncate text-[13.5px] font-semibold leading-tight">{{ ev.title }}</span>
                                            <span
                                                v-if="isLive(ev, day)"
                                                class="ml-auto inline-flex shrink-0 items-center gap-1 rounded-full border border-[#BFE9D3] bg-[#E3F7EC] px-2 py-px text-[10.5px] font-semibold text-[#0E7C4A]"
                                            >
                                                <span class="size-1.5 rounded-full bg-[#16A36A]" />En curso
                                            </span>
                                        </div>
                                        <p class="mt-1 truncate text-[11.5px] opacity-90">
                                            {{ hhmm(ev.start_time) }} – {{ hhmm(ev.end_time) }} · {{ ev.classroom }}
                                        </p>
                                    </template>

                                    <!-- Bloque más largo -->
                                    <template v-else>
                                        <p class="truncate text-[14.5px] font-semibold leading-tight">{{ ev.title }}</p>
                                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                            <span
                                                :class="tone(ev).chip"
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-[3px] text-xs font-semibold"
                                            >
                                                <Clock class="size-3" />
                                                {{ hhmm(ev.start_time) }} – {{ hhmm(ev.end_time) }}
                                            </span>
                                            <span
                                                v-if="isLive(ev, day)"
                                                class="inline-flex items-center gap-1 rounded-full border border-[#BFE9D3] bg-[#E3F7EC] px-2 py-0.5 text-[10.5px] font-semibold text-[#0E7C4A]"
                                            >
                                                <span class="size-1.5 rounded-full bg-[#16A36A]" />En curso
                                            </span>
                                        </div>
                                        <p class="mt-2 flex items-start gap-1.5 text-xs leading-snug opacity-95">
                                            <User class="mt-px size-3 shrink-0" />
                                            <span>{{ ev.teacher }}</span>
                                        </p>
                                        <p class="mt-1 flex items-start gap-1.5 text-xs leading-snug opacity-95">
                                            <MapPin class="mt-px size-3 shrink-0" />
                                            <span>{{ ev.classroom }}</span>
                                        </p>
                                    </template>

                                    <!-- Acciones al pasar el mouse -->
                                    <div class="absolute right-1.5 top-1.5 hidden gap-1 group-hover:flex">
                                        <button
                                            type="button"
                                            :class="tone(ev).tool"
                                            class="grid size-6 place-items-center rounded-md transition"
                                            title="Asistencia"
                                            @click.stop="router.visit(`/sections/${ev.section_id}/attendance`)"
                                        >
                                            <ClipboardCheck class="size-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            :class="tone(ev).tool"
                                            class="grid size-6 place-items-center rounded-md transition"
                                            title="Editar"
                                            @click.stop="openEditDialog(ev)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            :class="tone(ev).tool"
                                            class="grid size-6 place-items-center rounded-md transition hover:!bg-[#E5484D] hover:!text-white"
                                            title="Eliminar"
                                            @click.stop="deleteSchedule(ev.id)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── MES ── -->
                    <div v-else class="p-4">
                        <div
                            class="grid grid-cols-7 gap-px overflow-hidden rounded-xl border border-[#E1E9F7] bg-[#E1E9F7] dark:border-border dark:bg-border"
                        >
                            <div
                                v-for="d in ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom']"
                                :key="d"
                                class="bg-[#F7F9FD] py-2.5 text-center text-xs font-semibold text-muted-foreground dark:bg-muted"
                            >
                                {{ d }}
                            </div>
                            <div
                                v-for="(day, i) in monthDays"
                                :key="i"
                                class="min-h-[108px] cursor-pointer bg-card p-1.5 transition hover:bg-[#F4F7FD] dark:hover:bg-muted/40"
                                :class="day && isToday(day) ? '!bg-[#EEF4FF] dark:!bg-[#214EA4]/15' : ''"
                                @click="day && openCreateDialog(jsDateToDayOfWeek(day))"
                            >
                                <template v-if="day">
                                    <span
                                        class="mb-1 grid size-6 place-items-center rounded-full text-xs font-medium"
                                        :class="
                                            isToday(day)
                                                ? 'bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] font-bold text-white'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ day.getDate() }}
                                    </span>
                                    <div
                                        v-for="ev in eventsForMonthDay(day).slice(0, 3)"
                                        :key="ev.id"
                                        :class="tone(ev).pill"
                                        class="mb-0.5 truncate rounded-md px-1.5 py-0.5 text-[10.5px] font-medium"
                                    >
                                        {{ ev.title }}
                                    </div>
                                    <p
                                        v-if="eventsForMonthDay(day).length > 3"
                                        class="px-1 text-[10.5px] font-medium text-[#214EA4] dark:text-[#8CB8FF]"
                                    >
                                        +{{ eventsForMonthDay(day).length - 3 }} más
                                    </p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── PANEL DERECHO ── -->
                <aside v-if="panelOpen" class="flex w-72 shrink-0 flex-col gap-4 overflow-y-auto">
                    <!-- Conflictos -->
                    <div
                        class="rounded-[18px] border border-[#D8E2F4] bg-card p-5 shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br"
                                :class="conflicts.length ? 'from-[#E5484D] to-[#F07A7E]' : 'from-[#16A36A] to-[#34C58A]'"
                            >
                                <AlertTriangle v-if="conflicts.length" class="size-[19px] text-white" />
                                <CheckCircle2 v-else class="size-[19px] text-white" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-[15px] font-semibold">Conflictos</h2>
                                <p class="text-xs text-muted-foreground">
                                    {{ conflicts.length ? 'Requieren revisión' : 'Todo en orden' }}
                                </p>
                            </div>
                            <span
                                v-if="conflicts.length"
                                class="rounded-full bg-destructive px-2.5 py-0.5 text-xs font-semibold text-white"
                            >
                                {{ conflicts.length }}
                            </span>
                        </div>

                        <div class="mt-3 space-y-2">
                            <p
                                v-if="conflicts.length === 0"
                                class="rounded-xl bg-[#E3F7EC] px-3 py-2.5 text-xs text-[#0E7C4A] dark:bg-emerald-950/40 dark:text-emerald-400"
                            >
                                Sin conflictos detectados.
                            </p>
                            <div
                                v-for="(c, i) in conflicts"
                                :key="i"
                                class="rounded-xl border-l-4 border-destructive bg-destructive/10 px-3 py-2.5 text-xs"
                            >
                                <p class="font-semibold text-destructive">{{ DAYS_FULL[c.day] }} {{ c.time }}</p>
                                <p class="mt-0.5 text-muted-foreground">{{ c.message }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Materias sin asignar -->
                    <div
                        class="rounded-[18px] border border-[#D8E2F4] bg-card p-5 shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border"
                    >
                        <div class="flex items-center gap-3">
                            <span class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]">
                                <BookOpen class="size-[19px] text-white" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-[15px] font-semibold">Sin asignar</h2>
                                <p class="text-xs text-muted-foreground">Materias sin horario</p>
                            </div>
                            <span
                                v-if="unassigned.length"
                                class="rounded-full bg-[#E8EEFB] px-2.5 py-0.5 text-xs font-semibold text-[#214EA4] dark:bg-[#214EA4]/25 dark:text-[#8CB8FF]"
                            >
                                {{ unassigned.length }}
                            </span>
                        </div>

                        <div class="mt-3 space-y-2">
                            <p
                                v-if="unassigned.length === 0"
                                class="rounded-xl bg-[#E3F7EC] px-3 py-2.5 text-xs text-[#0E7C4A] dark:bg-emerald-950/40 dark:text-emerald-400"
                            >
                                Todas las materias tienen sección asignada.
                            </p>
                            <button
                                v-for="u in unassigned"
                                :key="u.curricula_id"
                                type="button"
                                class="flex w-full cursor-pointer items-center justify-between gap-2 rounded-xl border border-[#E1E9F7] bg-[#F4F7FD] px-3 py-2.5 text-left text-xs transition hover:border-[#214EA4]/40 hover:bg-[#E8EEFB] dark:border-border dark:bg-muted/40"
                                @click="openCreateDialog(); form.curricula_id = String(u.curricula_id)"
                            >
                                <span class="font-medium">{{ u.subject }}</span>
                                <span v-if="u.hours" class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-[#214EA4] dark:bg-background">
                                    {{ u.hours }}h
                                </span>
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <!-- ── DIALOG CREAR / EDITAR HORARIO ── -->
        <Dialog v-model:open="createDialogOpen">
            <DialogContent
                class="flex max-h-[90vh] w-[calc(100%-2rem)] flex-col gap-0 overflow-hidden rounded-[20px] p-0 sm:max-w-3xl [&>button]:text-white [&>button]:opacity-80 [&>button]:hover:opacity-100"
            >
                <!-- Encabezado -->
                <div class="relative shrink-0 overflow-hidden bg-[linear-gradient(120deg,#1E3A5F,#214EA4)] px-6 py-4 text-white">
                    <div class="pointer-events-none absolute -right-10 -top-16 h-[150px] w-[150px] rotate-45 bg-[#2F63C9]/80"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-8 h-[110px] w-[110px] rotate-45 bg-[#2F63C9]/55"></div>

                    <DialogHeader class="relative flex-row items-center gap-3.5 space-y-0 pr-8 text-left">
                        <span class="grid size-[46px] shrink-0 place-items-center rounded-2xl bg-white/20">
                            <Pencil v-if="dialogMode === 'edit'" class="size-[22px]" />
                            <CalendarPlus v-else class="size-[22px]" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <DialogTitle class="acadex-title text-[22px] leading-tight text-white">
                                {{ dialogMode === 'edit' ? 'Editar horario' : 'Nuevo horario' }}
                            </DialogTitle>
                            <DialogDescription class="mt-0.5 text-[13px] text-[#C9DAF7]">
                                {{ dialogMode === 'edit' ? 'Modifica el bloque horario.' : 'Crea una sección y asígnale un bloque horario.' }}
                            </DialogDescription>
                        </div>
                        <span
                            v-if="selectedPeriodName"
                            class="hidden shrink-0 items-center gap-1.5 rounded-full border border-white/30 bg-white/15 px-3 py-1 text-xs font-medium sm:inline-flex"
                        >
                            <CalendarDays class="size-3.5" />
                            {{ selectedPeriodName }}
                        </span>
                    </DialogHeader>
                </div>

                <!-- Formulario (si la pantalla es muy baja, solo esta parte hace scroll) -->
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <div class="grid grid-cols-6 gap-x-4 gap-y-3.5">
                        <!-- Materia -->
                        <div class="col-span-6 grid gap-1.5">
                            <Label :class="labelClass">Materia <span class="text-destructive">*</span></Label>
                            <Select v-model="form.curricula_id">
                                <SelectTrigger :class="fieldClass"><SelectValue placeholder="Selecciona materia" /></SelectTrigger>
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
                        <div class="col-span-6 grid gap-1.5 sm:col-span-3">
                            <Label :class="labelClass">Docente <span class="text-destructive">*</span></Label>
                            <Select v-model="form.teacher_id">
                                <SelectTrigger :class="fieldClass"><SelectValue placeholder="Selecciona docente" /></SelectTrigger>
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
                        <div class="col-span-6 grid gap-1.5 sm:col-span-3">
                            <Label :class="labelClass">Aula <span class="text-destructive">*</span></Label>
                            <Select v-model="form.classroom_id">
                                <SelectTrigger :class="fieldClass"><SelectValue placeholder="Selecciona aula" /></SelectTrigger>
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
                        <div class="col-span-6 grid gap-1.5 sm:col-span-4">
                            <Label :class="labelClass">Nombre sección</Label>
                            <Input v-model="form.section_name" placeholder="Sección A" :class="fieldClass" />
                        </div>
                        <div class="col-span-6 grid gap-1.5 sm:col-span-2">
                            <Label :class="labelClass">Cupo</Label>
                            <Input v-model="form.quota" type="number" min="1" max="500" :class="fieldClass" />
                        </div>

                        <!-- Día y horas -->
                        <div class="col-span-6 grid gap-1.5 sm:col-span-2">
                            <Label :class="labelClass">Día <span class="text-destructive">*</span></Label>
                            <Select v-model="form.day_of_week">
                                <SelectTrigger :class="fieldClass"><SelectValue placeholder="Día de la semana" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="(d, i) in DAYS_FULL" :key="i" :value="String(i)">
                                        {{ d }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="col-span-3 grid gap-1.5 sm:col-span-2">
                            <Label :class="labelClass">Hora inicio <span class="text-destructive">*</span></Label>
                            <Input v-model="form.start_time" type="time" :class="fieldClass" />
                        </div>
                        <div class="col-span-3 grid gap-1.5 sm:col-span-2">
                            <Label :class="labelClass">Hora fin <span class="text-destructive">*</span></Label>
                            <Input v-model="form.end_time" type="time" :class="fieldClass" />
                        </div>

                        <!-- Recurrencia -->
                        <div
                            class="col-span-6 flex flex-wrap items-center justify-between gap-4 rounded-[14px] border border-[#E1E9F7] bg-[#F4F7FD] px-4 py-3 dark:border-border dark:bg-muted/40"
                        >
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#214EA4] to-[#3F7BE0] text-white">
                                    <Repeat class="size-[18px]" />
                                </span>
                                <div>
                                    <Label for="schedule-recurring" :class="labelClass">Horario recurrente</Label>
                                    <p class="text-xs text-muted-foreground">Se repite cada semana</p>
                                </div>
                                <Switch id="schedule-recurring" v-model="form.is_recurring" class="ml-2" />
                            </div>

                            <div class="grid w-full gap-1.5 sm:w-52">
                                <Label :class="labelClass">
                                    {{ form.is_recurring ? 'Fecha fin' : 'Fecha específica' }}
                                    <span v-if="!form.is_recurring" class="text-destructive">*</span>
                                    <span v-else class="font-normal text-[#8A97AB]">(opcional)</span>
                                </Label>
                                <Input v-if="form.is_recurring" v-model="form.recurrence_end" type="date" :class="fieldClass" />
                                <Input v-else v-model="form.specific_date" type="date" :class="fieldClass" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones (siempre visibles) -->
                <DialogFooter class="shrink-0 gap-2 border-t border-[#E1E9F7] bg-[#F7F9FD] px-6 py-3 dark:border-border dark:bg-muted/30">
                    <Button variant="outline" class="rounded-xl" :disabled="creating" @click="createDialogOpen = false">
                        Cancelar
                    </Button>
                    <Button
                        class="rounded-xl bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] text-white shadow-[0_10px_20px_-10px_rgba(33,78,164,0.8)] hover:brightness-110"
                        :disabled="creating"
                        @click="handleCreate"
                    >
                        {{ creating ? 'Guardando...' : dialogMode === 'edit' ? 'Guardar cambios' : 'Crear horario' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
<style scoped>
    .acadex-title {
        font-family: 'Fraunces', serif;
        font-weight: 600;
    }
</style>
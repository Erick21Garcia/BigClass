<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlertTriangle, BookOpen, CheckCircle2, ChevronLeft, ChevronRight, Clock,
    Info, ListChecks, MapPin, Save, Search, ShieldCheck, ShieldX, Users, XCircle,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input }  from '@/components/ui/input';
import { Label }  from '@/components/ui/label';
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
const search       = ref('');

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

const absencePctColor = (pct: number | null) => {
    if (pct === null) return 'text-muted-foreground';
    if (pct >= 25)    return 'text-red-600 font-bold';
    if (pct >= 18)    return 'text-amber-600 font-semibold';
    return 'text-emerald-600';
};

// ── Presentación ─────────────────────────────────────────────────────
const plural = (n: number, one: string, many: string) => `${n} ${n === 1 ? one : many}`;

const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('');

const hhmm = (t: string) => t.slice(0, 5);

// Día de la semana de la fecha elegida (se usa el mediodía para evitar saltos de zona horaria).
const dateParts = computed(() => {
    const d = new Date(`${selectedDate.value}T12:00:00`);
    if (Number.isNaN(d.getTime())) return null;
    const weekday = d.toLocaleDateString('es-EC', { weekday: 'long' });
    return {
        strong: weekday.charAt(0).toUpperCase() + weekday.slice(1),
        rest: `, ${d.toLocaleDateString('es-EC', { day: 'numeric', month: 'long' })}`,
    };
});

// Anillo del banner: porcentaje de presentes del día.
// Si en tu sistema una tardanza cuenta como asistencia, suma también sheetStats.late.
const dayRate = computed(() =>
    sheet.value.length ? Math.round((sheetStats.value.present / sheet.value.length) * 100) : 0,
);
const RING = 2 * Math.PI * 42;
const ringDash = computed(() => (dayRate.value / 100) * RING);

// Tarjetas de resumen del día.
const kpis = computed(() => [
    {
        label: 'Presentes',
        value: sheetStats.value.present,
        icon: CheckCircle2,
        bar: 'bg-[#16A36A]',
        tile: 'from-[#16A36A] to-[#34C58A]',
        valueClass: 'text-[#0E7C4A] dark:text-emerald-400',
    },
    {
        label: 'Ausentes',
        value: sheetStats.value.absent,
        icon: XCircle,
        bar: 'bg-[#E5484D]',
        tile: 'from-[#E5484D] to-[#F07A7E]',
        valueClass: '',
    },
    {
        label: 'Tardanzas',
        value: sheetStats.value.late,
        icon: Clock,
        bar: 'bg-[#F2A81D]',
        tile: 'from-[#F2A81D] to-[#F5C04A]',
        valueClass: '',
    },
    {
        label: 'En riesgo',
        value: sheetStats.value.at_risk + sheetStats.value.auto_fail,
        icon: AlertTriangle,
        bar: 'bg-[#E8590C]',
        tile: 'from-[#E8590C] to-[#F58B4A]',
        valueClass: '',
    },
]);

// Búsqueda dentro de la lista (por nombre o código).
const filteredSheet = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return sheet.value;
    return sheet.value.filter(
        r => r.full_name.toLowerCase().includes(q) || r.enrollment_number.toLowerCase().includes(q),
    );
});

// Estilo de cada estado: botón seleccionado, fondo de la fila y franja lateral.
const statusConfig = {
    present: {
        label: 'Presente',
        icon: CheckCircle2,
        on: 'border-transparent bg-gradient-to-br from-[#16A36A] to-[#34C58A] text-white shadow-[0_10px_20px_-10px_rgba(22,163,106,0.8)]',
        row: 'border-[#BFE9D3] bg-gradient-to-r from-[#F0FAF5] to-[#F7F9FD] dark:border-emerald-900 dark:from-emerald-950/30 dark:to-transparent',
        bar: 'bg-[#16A36A]',
    },
    absent: {
        label: 'Ausente',
        icon: XCircle,
        on: 'border-transparent bg-gradient-to-br from-[#E5484D] to-[#F07A7E] text-white shadow-[0_10px_20px_-10px_rgba(229,72,77,0.8)]',
        row: 'border-[#F5C6C8] bg-gradient-to-r from-[#FFF3F3] to-[#F7F9FD] dark:border-red-900 dark:from-red-950/30 dark:to-transparent',
        bar: 'bg-[#E5484D]',
    },
    late: {
        label: 'Tardanza',
        icon: Clock,
        on: 'border-transparent bg-gradient-to-br from-[#F2A81D] to-[#F5C04A] text-white shadow-[0_10px_20px_-10px_rgba(242,168,29,0.8)]',
        row: 'border-[#F2D27A] bg-gradient-to-r from-[#FFF8E6] to-[#F7F9FD] dark:border-amber-900 dark:from-amber-950/30 dark:to-transparent',
        bar: 'bg-[#F2A81D]',
    },
} as const;

const rowClass = (row: SheetRow) =>
    row.status ? statusConfig[row.status].row : 'border-[#E1E9F7] bg-[#F7F9FD] dark:border-border';

const avatarClass = (row: { at_risk: boolean; auto_fail: boolean }) =>
    row.auto_fail
        ? 'from-[#E5484D] to-[#F07A7E]'
        : row.at_risk
          ? 'from-[#F2A81D] to-[#F5C04A]'
          : 'from-[#1E3A5F] to-[#214EA4]';

// Estilos comunes
const cardBase =
    'rounded-[18px] border border-[#D8E2F4] bg-card p-6 text-card-foreground shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border';
const iconTile =
    'grid size-[42px] shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white';
const fieldClass =
    'h-11 w-full rounded-xl border-[1.5px] border-transparent bg-[#E8EEFB] px-3.5 text-sm text-[#0B2545] shadow-none placeholder:text-[#7B8AA3] focus-visible:border-[#1E3A5F] focus-visible:bg-white focus-visible:ring-4 focus-visible:ring-[#1E3A5F]/10 dark:bg-muted dark:text-foreground';
const squareBtn =
    'grid size-11 shrink-0 place-items-center rounded-xl border border-[#D8E2F4] bg-white text-[#1E3A5F] transition hover:border-[#214EA4]/40 hover:bg-[#F4F7FD] dark:border-border dark:bg-card dark:text-foreground';
const tabTrigger =
    'h-[38px] flex-none gap-2 rounded-[10px] px-[18px] text-sm font-medium text-[#4A5D7E] data-[state=active]:!bg-gradient-to-br data-[state=active]:from-[#1E3A5F] data-[state=active]:to-[#214EA4] data-[state=active]:font-semibold data-[state=active]:!text-white data-[state=active]:!shadow-[0_8px_16px_-10px_rgba(33,78,164,0.8)] dark:text-muted-foreground';
</script>

<template>
    <Head :title="`Asistencia — ${props.section.subject}`">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 bg-[#EAF0FB] p-6 dark:bg-transparent">

            <!-- Banner -->
            <div class="relative overflow-hidden rounded-[20px] bg-[linear-gradient(120deg,#1E3A5F,#214EA4)] px-9 py-6 text-white">
                <div class="pointer-events-none absolute -right-14 -top-24 h-[220px] w-[220px] rotate-45 bg-[#2F63C9]/80"></div>
                <div class="pointer-events-none absolute -top-32 right-[200px] h-[170px] w-[170px] rotate-45 bg-[#8CB8FF]/20"></div>
                <div class="pointer-events-none absolute -bottom-24 -left-12 h-[150px] w-[150px] rotate-45 bg-[#2F63C9]/55"></div>

                <div class="relative flex flex-wrap items-center justify-between gap-6">
                    <div class="min-w-0">
                        <div class="flex items-center gap-4">
                            <span class="grid size-[58px] shrink-0 place-items-center rounded-2xl border border-white/30 bg-white/[0.18]">
                                <BookOpen class="size-[26px]" />
                            </span>
                            <h1 class="acadex-title text-[34px] leading-[1.1]">{{ props.section.subject }}</h1>
                        </div>

                        <div class="mt-3.5 flex flex-wrap gap-2.5">
                            <span class="rounded-full bg-white/[0.13] px-3.5 py-[7px] text-[13.5px] text-[#E4EDFC]">
                                {{ props.section.name }}
                            </span>
                            <span class="rounded-full bg-white/[0.13] px-3.5 py-[7px] text-[13.5px] text-[#E4EDFC]">
                                {{ props.section.career }}
                            </span>
                            <span class="rounded-full bg-white/[0.13] px-3.5 py-[7px] text-[13.5px] text-[#E4EDFC]">
                                {{ props.section.period }}
                            </span>
                            <template v-if="props.section.schedule_today">
                                <span class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3.5 py-[7px] text-[13.5px] font-semibold text-white">
                                    <Clock class="size-[15px]" />
                                    {{ hhmm(props.section.schedule_today.start_time) }} – {{ hhmm(props.section.schedule_today.end_time) }}
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-white/20 px-3.5 py-[7px] text-[13.5px] font-semibold text-white">
                                    <MapPin class="size-[15px]" />
                                    {{ props.section.schedule_today.classroom }}
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="text-center">
                        <svg width="104" height="104" viewBox="0 0 104 104">
                            <circle cx="52" cy="52" r="42" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="10" />
                            <circle
                                cx="52"
                                cy="52"
                                r="42"
                                fill="none"
                                stroke="#8CB8FF"
                                stroke-width="10"
                                stroke-linecap="round"
                                transform="rotate(-90 52 52)"
                                class="transition-all duration-500"
                                :style="{
                                    strokeDasharray: `${ringDash} ${RING}`,
                                    opacity: ringDash > 0 ? 1 : 0,
                                }"
                            />
                            <text x="52" y="59" text-anchor="middle" font-size="23" font-weight="600" fill="#fff">
                                {{ dayRate }}%
                            </text>
                        </svg>
                        <p class="mt-0.5 text-[13px] text-[#C9DAF7]">asistencia del día</p>
                    </div>
                </div>
            </div>

            <Tabs v-model="activeTab" class="flex flex-col gap-4">

                <!-- Pestañas + fecha + acciones -->
                <div class="flex flex-wrap items-center justify-between gap-3.5">
                    <TabsList
                        class="h-auto gap-1.5 rounded-[14px] border border-[#D8E2F4] bg-white p-1.5 dark:border-border dark:bg-card"
                    >
                        <TabsTrigger value="register" :class="tabTrigger">
                            <CheckCircle2 class="size-[17px]" /> Registrar asistencia
                        </TabsTrigger>
                        <TabsTrigger value="summary" :class="tabTrigger">
                            <Users class="size-[17px]" /> Resumen del período
                        </TabsTrigger>
                    </TabsList>

                    <template v-if="activeTab === 'register'">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" :class="squareBtn" aria-label="Día anterior" @click="navigateDate(-1)">
                                <ChevronLeft class="size-[17px]" />
                            </button>
                            <Input
                                v-model="selectedDate"
                                type="date"
                                class="h-11 w-44 rounded-xl border-[#D8E2F4] bg-white px-3.5 text-sm font-semibold shadow-none dark:bg-card"
                            />
                            <button type="button" :class="squareBtn" aria-label="Día siguiente" @click="navigateDate(1)">
                                <ChevronRight class="size-[17px]" />
                            </button>

                            <span v-if="dateParts" class="ml-1.5 text-[13.5px] text-muted-foreground">
                                <b class="font-semibold text-foreground">{{ dateParts.strong }}</b>{{ dateParts.rest }}
                            </span>
                            <span
                                v-if="isDateRecorded"
                                class="ml-1 inline-flex items-center gap-1.5 rounded-full border border-[#BFE9D3] bg-[#E3F7EC] px-3 py-1 text-xs font-semibold text-[#0E7C4A] dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-400"
                            >
                                <CheckCircle2 class="size-3.5" /> Ya registrada
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <button
                                type="button"
                                class="inline-flex h-11 items-center gap-2 rounded-xl border-[1.5px] border-[#214EA4] bg-white px-[18px] text-sm font-semibold text-[#214EA4] transition hover:bg-[#214EA4]/5 dark:bg-card"
                                @click="markAllPresent"
                            >
                                <CheckCircle2 class="size-[17px]" /> Todos presentes
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-11 items-center gap-2 rounded-xl bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] px-[22px] text-[14.5px] font-semibold text-white shadow-[0_12px_24px_-12px_rgba(33,78,164,0.8)] transition hover:brightness-110 disabled:opacity-60"
                                :disabled="saving"
                                @click="saveSheet"
                            >
                                <Save class="size-[17px]" />
                                {{ saving ? 'Guardando...' : 'Guardar asistencia' }}
                            </button>
                        </div>
                    </template>
                </div>

                <!-- ── TAB: Registrar ── -->
                <TabsContent value="register" class="mt-0 flex flex-col gap-4">

                    <!-- Resumen del día -->
                    <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
                        <div
                            v-for="k in kpis"
                            :key="k.label"
                            class="relative flex items-center gap-4 overflow-hidden rounded-[18px] border border-[#D8E2F4] bg-card py-4 pl-[22px] pr-5 shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border"
                        >
                            <span :class="[k.bar, 'absolute inset-y-0 left-0 w-[5px]']"></span>
                            <span :class="[k.tile, 'grid size-12 shrink-0 place-items-center rounded-[14px] bg-gradient-to-br text-white']">
                                <component :is="k.icon" class="size-[23px]" />
                            </span>
                            <div>
                                <p :class="[k.valueClass, 'text-[34px] font-semibold leading-none tabular-nums']">{{ k.value }}</p>
                                <p class="mt-1 text-[13px] font-medium text-muted-foreground">{{ k.label }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Planilla -->
                    <div :class="cardBase">
                        <div class="flex flex-wrap items-center gap-3.5">
                            <div :class="iconTile"><ListChecks class="size-5" /></div>
                            <div class="min-w-0">
                                <h2 class="text-[17px] font-semibold">Lista de estudiantes</h2>
                                <p class="text-[13px] text-muted-foreground">
                                    {{ plural(sheet.length, 'registro', 'registros') }} · Selecciona el estado de asistencia para cada estudiante.
                                </p>
                            </div>

                            <div class="relative ml-auto w-full sm:w-[300px]">
                                <Search class="pointer-events-none absolute left-3.5 top-1/2 size-[17px] -translate-y-1/2 text-[#7B8AA3]" />
                                <Input
                                    v-model="search"
                                    placeholder="Buscar estudiante..."
                                    :class="[fieldClass, 'h-[42px] pl-10']"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3">
                            <div
                                v-for="row in filteredSheet"
                                :key="row.student_id"
                                class="relative flex flex-wrap items-center gap-4 overflow-hidden rounded-2xl border px-[18px] py-4 transition-colors"
                                :class="rowClass(row)"
                            >
                                <span
                                    v-if="row.status"
                                    :class="[statusConfig[row.status].bar, 'absolute inset-y-0 left-0 w-1']"
                                ></span>

                                <!-- Info estudiante -->
                                <span
                                    :class="[avatarClass(row), 'grid size-12 shrink-0 place-items-center rounded-[14px] bg-gradient-to-br text-base font-semibold text-white']"
                                >
                                    {{ initials(row.full_name) }}
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-base font-semibold">{{ row.full_name }}</p>
                                        <span
                                            v-if="row.auto_fail"
                                            class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#E5484D] px-2.5 py-0.5 text-[11px] font-semibold text-white"
                                        >
                                            <XCircle class="size-3" /> Reprobado por faltas
                                        </span>
                                        <span
                                            v-else-if="row.at_risk"
                                            class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#F2A81D] px-2.5 py-0.5 text-[11px] font-semibold text-white"
                                        >
                                            <AlertTriangle class="size-3" /> En riesgo
                                        </span>
                                    </div>

                                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-[#E8EEFB] px-2.5 py-0.5 font-mono text-xs font-medium text-[#4A5D7E] dark:bg-muted dark:text-muted-foreground">
                                            {{ row.enrollment_number }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E3F7EC] px-2.5 py-0.5 text-[11.5px] font-semibold text-[#0E7C4A] dark:bg-emerald-950/40 dark:text-emerald-400">
                                            <span class="size-1.5 rounded-full bg-[#16A36A]"></span>P {{ row.summary.present }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#FDECEC] px-2.5 py-0.5 text-[11.5px] font-semibold text-[#B4262B] dark:bg-red-950/40 dark:text-red-400">
                                            <span class="size-1.5 rounded-full bg-[#E5484D]"></span>A {{ row.summary.absent }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#FFF1CC] px-2.5 py-0.5 text-[11.5px] font-semibold text-[#9A5B00] dark:bg-amber-950/40 dark:text-amber-400">
                                            <span class="size-1.5 rounded-full bg-[#F2A81D]"></span>T {{ row.summary.late }}
                                        </span>
                                        <span
                                            v-if="row.summary.justified > 0"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-[#E8EEFB] px-2.5 py-0.5 text-[11.5px] font-semibold text-[#214EA4] dark:bg-[#214EA4]/25 dark:text-[#8CB8FF]"
                                        >
                                            <ShieldCheck class="size-3" />Just {{ row.summary.justified }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Botones de estado -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        v-for="(cfg, key) in statusConfig"
                                        :key="key"
                                        type="button"
                                        class="inline-flex h-[46px] items-center gap-2 rounded-[13px] border-[1.5px] px-5 text-sm font-semibold transition"
                                        :class="
                                            row.status === key
                                                ? cfg.on
                                                : 'border-[#D8E2F4] bg-white text-[#6B7A90] hover:border-[#214EA4]/40 hover:bg-[#F4F7FD] dark:border-border dark:bg-card'
                                        "
                                        @click="setStatus(row, key as any)"
                                    >
                                        <component :is="cfg.icon" class="size-[18px]" />
                                        {{ cfg.label }}
                                    </button>

                                    <!-- Justificar (solo ausentes o tardanzas) -->
                                    <button
                                        v-if="row.status === 'absent' || row.status === 'late'"
                                        type="button"
                                        class="inline-flex h-[46px] items-center gap-2 rounded-[13px] border-[1.5px] px-4 text-sm font-semibold transition"
                                        :class="
                                            row.justified
                                                ? 'border-[#214EA4] bg-[#E8EEFB] text-[#214EA4] dark:bg-[#214EA4]/20 dark:text-[#8CB8FF]'
                                                : 'border-[#D8E2F4] bg-white text-[#6B7A90] hover:border-[#214EA4]/40 dark:border-border dark:bg-card'
                                        "
                                        title="Gestionar justificación"
                                        @click="openJustify(row)"
                                    >
                                        <component :is="row.justified ? ShieldCheck : ShieldX" class="size-[18px]" />
                                        {{ row.justified ? 'Justificada' : 'Justificar' }}
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="sheet.length === 0"
                                class="grid place-items-center gap-1.5 rounded-2xl border-2 border-dashed border-[#D8E2F4] px-4 py-10 text-center dark:border-border"
                            >
                                <Users class="size-7 text-[#B8C8E8]" />
                                <p class="text-sm text-muted-foreground">No hay estudiantes matriculados en esta sección.</p>
                            </div>
                            <p
                                v-else-if="filteredSheet.length === 0"
                                class="rounded-2xl bg-[#F4F7FD] px-4 py-8 text-center text-sm text-muted-foreground dark:bg-muted/40"
                            >
                                Sin resultados para «{{ search }}».
                            </p>
                        </div>

                        <p class="mt-4 flex items-start gap-2 text-[12.5px] text-muted-foreground">
                            <Info class="mt-px size-[15px] shrink-0" />
                            Los cambios se aplican al presionar «Guardar asistencia». P = presencias, A = ausencias,
                            T = tardanzas y Just = faltas justificadas, acumuladas en el período.
                        </p>
                    </div>
                </TabsContent>

                <!-- ── TAB: Resumen ── -->
                <TabsContent value="summary" class="mt-0">
                    <div :class="cardBase">
                        <div class="flex items-center gap-3.5">
                            <div :class="iconTile"><Users class="size-5" /></div>
                            <div>
                                <h2 class="text-[17px] font-semibold">Resumen de asistencia del período</h2>
                                <p class="text-[13px] text-muted-foreground">
                                    {{ props.recordedDates.length }} clase(s) registrada(s) · Límite: 25% de ausencias
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="props.summary.length === 0"
                            class="mt-4 grid place-items-center gap-1.5 rounded-2xl border-2 border-dashed border-[#D8E2F4] px-4 py-10 text-center dark:border-border"
                        >
                            <Clock class="size-7 text-[#B8C8E8]" />
                            <p class="text-sm text-muted-foreground">Aún no hay clases registradas.</p>
                        </div>

                        <div v-else class="mt-4 overflow-x-auto rounded-2xl border border-[#E1E9F7] dark:border-border">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-[#F4F7FD] text-xs text-muted-foreground dark:bg-muted/40">
                                        <th class="px-4 py-3 text-left font-semibold">Estudiante</th>
                                        <th class="px-3 py-3 text-center font-semibold">Presentes</th>
                                        <th class="px-3 py-3 text-center font-semibold">Tardanzas</th>
                                        <th class="px-3 py-3 text-center font-semibold">Ausencias</th>
                                        <th class="px-3 py-3 text-center font-semibold">Justificadas</th>
                                        <th class="px-3 py-3 text-center font-semibold">% Asistencia</th>
                                        <th class="px-3 py-3 text-center font-semibold">% Faltas</th>
                                        <th class="px-4 py-3 text-center font-semibold">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in props.summary"
                                        :key="row.student_id"
                                        class="border-t border-[#EAF0FB] transition-colors hover:bg-[#F7F9FD] dark:border-border dark:hover:bg-muted/30"
                                        :class="{
                                            'bg-red-50/60 dark:bg-red-950/20':     row.auto_fail,
                                            'bg-amber-50/60 dark:bg-amber-950/20': row.at_risk && !row.auto_fail,
                                        }"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <span
                                                    :class="[avatarClass(row), 'grid size-9 shrink-0 place-items-center rounded-[11px] bg-gradient-to-br text-xs font-semibold text-white']"
                                                >
                                                    {{ initials(row.full_name) }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="font-semibold">{{ row.full_name }}</p>
                                                    <p class="font-mono text-[11px] text-muted-foreground">{{ row.enrollment_number }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-center font-semibold tabular-nums text-emerald-600">{{ row.summary.present }}</td>
                                        <td class="px-3 py-3 text-center font-semibold tabular-nums text-amber-600">{{ row.summary.late }}</td>
                                        <td class="px-3 py-3 text-center font-semibold tabular-nums text-red-600">{{ row.summary.absent }}</td>
                                        <td class="px-3 py-3 text-center font-semibold tabular-nums text-[#214EA4] dark:text-[#8CB8FF]">{{ row.summary.justified }}</td>
                                        <td class="px-3 py-3 text-center font-medium tabular-nums">
                                            {{ row.attendance_pct !== null ? `${row.attendance_pct}%` : '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center font-medium tabular-nums" :class="absencePctColor(row.absence_pct)">
                                            {{ row.absence_pct !== null ? `${row.absence_pct}%` : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                v-if="row.auto_fail"
                                                class="inline-flex rounded-full bg-[#E5484D] px-3 py-0.5 text-[11.5px] font-semibold text-white"
                                            >
                                                Reprobado
                                            </span>
                                            <span
                                                v-else-if="row.at_risk"
                                                class="inline-flex rounded-full bg-[#F2A81D] px-3 py-0.5 text-[11.5px] font-semibold text-white"
                                            >
                                                En riesgo
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex rounded-full bg-[#E8EEFB] px-3 py-0.5 text-[11.5px] font-semibold text-[#214EA4] dark:bg-[#214EA4]/25 dark:text-[#8CB8FF]"
                                            >
                                                Regular
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>

        </div>

        <!-- Dialog: Justificación -->
        <Dialog v-model:open="justifyDialog">
            <DialogContent
                class="flex max-h-[90vh] w-[calc(100%-2rem)] flex-col gap-0 overflow-hidden rounded-[20px] p-0 sm:max-w-lg [&>button]:text-white [&>button]:opacity-80 [&>button]:hover:opacity-100"
            >
                <div class="relative shrink-0 overflow-hidden bg-[linear-gradient(120deg,#1E3A5F,#214EA4)] px-6 py-4 text-white">
                    <div class="pointer-events-none absolute -right-10 -top-16 h-[150px] w-[150px] rotate-45 bg-[#2F63C9]/80"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-8 h-[110px] w-[110px] rotate-45 bg-[#2F63C9]/55"></div>

                    <DialogHeader class="relative flex-row items-center gap-3.5 space-y-0 pr-8 text-left">
                        <span class="grid size-[46px] shrink-0 place-items-center rounded-2xl bg-white/20">
                            <ShieldCheck class="size-[22px]" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <DialogTitle class="acadex-title text-[22px] leading-tight text-white">
                                Gestionar justificación
                            </DialogTitle>
                            <DialogDescription class="mt-0.5 truncate text-[13px] text-[#C9DAF7]">
                                {{ justifyRow?.full_name }} · {{ selectedDate }}
                            </DialogDescription>
                        </div>
                    </DialogHeader>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-6 py-5">
                    <div class="grid grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            class="inline-flex h-12 items-center justify-center gap-2 rounded-[13px] border-[1.5px] text-sm font-semibold transition"
                            :class="
                                justifyValue
                                    ? 'border-transparent bg-gradient-to-br from-[#214EA4] to-[#3F7BE0] text-white shadow-[0_10px_20px_-10px_rgba(33,78,164,0.8)]'
                                    : 'border-[#D8E2F4] bg-white text-[#6B7A90] hover:border-[#214EA4]/40 dark:border-border dark:bg-card'
                            "
                            @click="justifyValue = true"
                        >
                            <ShieldCheck class="size-[18px]" /> Justificada
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-12 items-center justify-center gap-2 rounded-[13px] border-[1.5px] text-sm font-semibold transition"
                            :class="
                                !justifyValue
                                    ? 'border-transparent bg-gradient-to-br from-[#E5484D] to-[#F07A7E] text-white shadow-[0_10px_20px_-10px_rgba(229,72,77,0.8)]'
                                    : 'border-[#D8E2F4] bg-white text-[#6B7A90] hover:border-[#214EA4]/40 dark:border-border dark:bg-card'
                            "
                            @click="justifyValue = false"
                        >
                            <ShieldX class="size-[18px]" /> No justificada
                        </button>
                    </div>

                    <div class="grid gap-1.5">
                        <Label class="text-[13px] font-semibold">
                            Nota de justificación <span class="font-normal text-[#8A97AB]">(opcional)</span>
                        </Label>
                        <Input
                            v-model="justifyNote"
                            placeholder="Ej: Certificado médico presentado"
                            :class="fieldClass"
                        />
                    </div>
                </div>

                <DialogFooter class="shrink-0 gap-2 border-t border-[#E1E9F7] bg-[#F7F9FD] px-6 py-3 dark:border-border dark:bg-muted/30">
                    <Button variant="outline" class="rounded-xl" :disabled="savingJustify" @click="justifyDialog = false">
                        Cancelar
                    </Button>
                    <Button
                        class="rounded-xl bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] text-white shadow-[0_10px_20px_-10px_rgba(33,78,164,0.8)] hover:brightness-110"
                        :disabled="savingJustify"
                        @click="saveJustify"
                    >
                        {{ savingJustify ? 'Guardando...' : 'Guardar' }}
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
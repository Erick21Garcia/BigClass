<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    Clock,
    MapPin,
    Presentation,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    stats: {
        my_courses:     number;
        total_students: number;
        pending_grades: number;
        at_risk:        number;
    };
    myCourses:       { name: string; students: number; pending: number; avg: number }[];
    upcomingClasses: { subject: string; time: string; room: string; day: string; is_today: boolean }[];
    atRiskStudents:  { student: string; subject: string; pct: number; auto_fail: boolean }[];
    activePeriod:    string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const cardBase =
    'rounded-[18px] border border-[#D8E2F4] bg-card p-6 text-card-foreground shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border';

const today = computed(() => {
    const s = new Date().toLocaleDateString('es-EC', { weekday: 'long', day: 'numeric', month: 'short' });
    return s.charAt(0).toUpperCase() + s.slice(1);
});

// ── Métricas principales ─────────────────────────────────────────────
const kpis = computed(() => [
    {
        label: 'Mis secciones',
        value: props.stats.my_courses,
        sub: 'Período actual',
        icon: Presentation,
        cardClass: 'bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white',
        shapeClass: 'bg-white/[0.12]',
        iconBg: 'bg-white/20',
        iconClass: 'text-white',
    },
    {
        label: 'Total estudiantes',
        value: props.stats.total_students,
        sub: 'En tus secciones activas',
        icon: Users,
        cardClass: 'bg-gradient-to-br from-[#214EA4] to-[#3F7BE0] text-white',
        shapeClass: 'bg-white/[0.12]',
        iconBg: 'bg-white/20',
        iconClass: 'text-white',
    },
    {
        label: 'Notas por registrar',
        value: props.stats.pending_grades,
        sub: 'Evaluaciones pendientes',
        icon: ClipboardList,
        cardClass: 'bg-gradient-to-br from-[#5E97F0] to-[#8CB8FF] text-[#0B2545]',
        shapeClass: 'bg-white/[0.28]',
        iconBg: 'bg-white/45',
        iconClass: 'text-[#0B2545]',
    },
]);

const hasRisk = computed(() => props.stats.at_risk > 0);

// ── Secciones ────────────────────────────────────────────────────────
const rankClasses = [
    'from-[#1E3A5F] to-[#214EA4] text-white',
    'from-[#214EA4] to-[#3F7BE0] text-white',
    'from-[#3F7BE0] to-[#5E97F0] text-white',
    'from-[#5E97F0] to-[#8CB8FF] text-[#0B2545]',
    'from-[#C9DAF7] to-[#C9DAF7] text-[#1E3A5F]',
];
const rankClass = (i: number) => rankClasses[Math.min(i, rankClasses.length - 1)];

// El promedio es sobre 10: la barra mide ese promedio.
const avgWidth = (avg: number) => `${Math.min(100, Math.max(0, avg * 10))}%`;
</script>

<template>
    <Head title="Dashboard Docente">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-5 bg-[#EAF0FB] p-6 dark:bg-transparent">

            <!-- Banner -->
            <div
                class="relative overflow-hidden rounded-2xl bg-[linear-gradient(120deg,#1E3A5F,#214EA4)] px-7 py-6 text-white"
            >
                <div class="pointer-events-none absolute -right-16 -top-24 h-[230px] w-[230px] rotate-45 bg-[#2F63C9]/80"></div>
                <div class="pointer-events-none absolute -top-32 right-[150px] h-[190px] w-[190px] rotate-45 bg-[#8CB8FF]/20"></div>
                <div class="pointer-events-none absolute -bottom-28 -left-14 h-[170px] w-[170px] rotate-45 bg-[#2F63C9]/55"></div>

                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="acadex-title text-3xl tracking-tight">Mi panel docente</h1>
                        <p class="mt-1.5 flex items-center gap-2 text-sm text-[#C9DAF7]">
                            <CalendarDays class="size-4" />
                            {{ props.activePeriod }}
                        </p>
                    </div>

                    <span class="inline-flex h-[34px] items-center gap-2 rounded-full border border-white/25 bg-white/15 px-3.5 text-[12.5px] font-medium">
                        <CalendarDays class="size-3.5" />
                        {{ today }}
                    </span>
                </div>
            </div>

            <!-- Métricas principales -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="k in kpis"
                    :key="k.label"
                    :class="[k.cardClass, 'relative h-[142px] overflow-hidden rounded-[18px] p-5 shadow-[0_16px_30px_-16px_rgba(30,58,95,0.55)]']"
                >
                    <div :class="[k.shapeClass, 'absolute -bottom-9 -right-7 h-[110px] w-[110px] rotate-45']"></div>
                    <div :class="[k.iconBg, 'absolute right-[18px] top-[18px] grid size-10 place-items-center rounded-xl']">
                        <component :is="k.icon" :class="[k.iconClass, 'size-5']" />
                    </div>
                    <p class="relative text-[13px] font-medium opacity-90">{{ k.label }}</p>
                    <p class="relative mt-2 text-[42px] font-semibold leading-none">{{ k.value }}</p>
                    <p class="relative mt-2.5 text-[12.5px] opacity-90">{{ k.sub }}</p>
                </div>

                <!-- En riesgo por asistencia -->
                <div
                    class="relative h-[142px] overflow-hidden rounded-[18px] border border-[#D8E2F4] bg-card p-5 text-card-foreground shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border"
                >
                    <div class="absolute -bottom-9 -right-7 h-[110px] w-[110px] rotate-45 bg-[#E8EEFB] dark:bg-[#214EA4]/15"></div>
                    <div
                        :class="[
                            hasRisk ? 'from-[#F2A81D] to-[#F5C04A]' : 'from-[#1E3A5F] to-[#214EA4]',
                            'absolute right-[18px] top-[18px] grid size-10 place-items-center rounded-xl bg-gradient-to-br',
                        ]"
                    >
                        <AlertTriangle v-if="hasRisk" class="size-5 text-white" />
                        <CheckCircle2 v-else class="size-5 text-white" />
                    </div>
                    <p class="relative text-[13px] font-medium text-muted-foreground">En riesgo por asistencia</p>
                    <p class="relative mt-2 text-[42px] font-semibold leading-none">{{ props.stats.at_risk }}</p>
                    <span
                        v-if="hasRisk"
                        class="relative mt-2.5 inline-flex items-center rounded-full border border-amber-200 bg-amber-100 px-2.5 py-0.5 text-[11.5px] font-medium text-amber-800 dark:border-amber-900 dark:bg-amber-950/60 dark:text-amber-400"
                    >
                        Requieren seguimiento
                    </span>
                    <span
                        v-else
                        class="relative mt-2.5 inline-flex items-center rounded-full border border-emerald-200 bg-emerald-100 px-2.5 py-0.5 text-[11.5px] font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-400"
                    >
                        Sin estudiantes en riesgo
                    </span>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-5">

                <!-- Mis secciones activas -->
                <div :class="[cardBase, 'lg:col-span-3']">
                    <div class="flex items-center gap-3">
                        <div class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]">
                            <BookOpen class="size-[19px] text-white" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">Mis secciones activas</h2>
                            <p class="text-xs text-muted-foreground">Rendimiento promedio de estudiantes</p>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div
                            v-if="props.myCourses.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Sin secciones asignadas en este período.
                        </div>

                        <div
                            v-for="(course, i) in props.myCourses"
                            :key="course.name"
                            class="grid grid-cols-[30px_1fr_auto] items-center gap-3.5 border-t border-[#EAF0FB] py-3 first:border-t-0 dark:border-border"
                        >
                            <div :class="[rankClass(i), 'grid size-7 place-items-center rounded-[9px] bg-gradient-to-br text-[12.5px] font-semibold']">
                                {{ i + 1 }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center justify-between gap-3 text-sm font-medium">
                                    <span class="truncate">{{ course.name }}</span>
                                    <span class="shrink-0 rounded-full bg-[#E8EEFB] px-2.5 py-0.5 text-xs font-medium text-[#214EA4] tabular-nums dark:bg-[#214EA4]/25 dark:text-[#8CB8FF]">
                                        {{ course.students }} est.
                                    </span>
                                </div>
                                <div class="mt-2 h-[9px] w-full overflow-hidden rounded-full bg-[#E4ECF9] dark:bg-muted">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF] transition-all duration-500"
                                        :style="{ width: avgWidth(course.avg) }"
                                    />
                                </div>
                            </div>

                            <div class="min-w-[92px] text-right">
                                <p class="text-sm font-semibold tabular-nums">
                                    {{ course.avg > 0 ? course.avg : '—' }}
                                    <span v-if="course.avg > 0" class="text-xs font-normal text-muted-foreground">/10</span>
                                </p>
                                <span
                                    v-if="course.pending > 0"
                                    class="mt-1 inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-400"
                                >
                                    {{ course.pending }} pendientes
                                </span>
                                <span
                                    v-else
                                    class="mt-1 inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400"
                                >
                                    Al día
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Próximas clases -->
                <div :class="[cardBase, 'lg:col-span-2']">
                    <div class="flex items-center gap-3">
                        <div class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]">
                            <Clock class="size-[19px] text-white" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">Próximas clases</h2>
                            <p class="text-xs text-muted-foreground">Horario de esta semana</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2.5">
                        <div
                            v-if="props.upcomingClasses.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Sin horarios asignados.
                        </div>

                        <div
                            v-for="cls in props.upcomingClasses"
                            :key="cls.subject + cls.day + cls.time"
                            :class="[
                                cls.is_today
                                    ? 'border-[#214EA4] bg-[#E8EEFB] dark:bg-[#214EA4]/15'
                                    : 'border-[#C9DAF7] bg-[#F4F7FD] dark:border-border dark:bg-muted/50',
                                'rounded-[14px] border-l-4 p-3.5',
                            ]"
                        >
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <span
                                    :class="[
                                        cls.is_today
                                            ? 'bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] text-white'
                                            : 'bg-white text-[#4A5D7E] dark:bg-background dark:text-muted-foreground',
                                        'rounded-full px-2.5 py-0.5 text-[11.5px] font-semibold',
                                    ]"
                                >
                                    {{ cls.is_today ? 'Hoy' : cls.day }}
                                </span>
                                <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                    <MapPin class="size-3" />
                                    {{ cls.room }}
                                </span>
                            </div>
                            <p class="text-sm font-semibold">{{ cls.subject }}</p>
                            <p class="text-xs text-muted-foreground">{{ cls.time }}</p>
                        </div>
                    </div>
                </div>

                <!-- Estudiantes en riesgo -->
                <div v-if="props.atRiskStudents.length > 0" :class="[cardBase, 'lg:col-span-5']">
                    <div class="flex items-center gap-3">
                        <div class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#F2A81D] to-[#F5C04A]">
                            <AlertTriangle class="size-[19px] text-white" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">Estudiantes en riesgo por asistencia</h2>
                            <p class="text-xs text-muted-foreground">
                                Estudiantes con más del 18% de ausencias efectivas
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="s in props.atRiskStudents"
                            :key="s.student + s.subject"
                            :class="[
                                s.auto_fail
                                    ? 'border-red-500 bg-red-50 dark:bg-red-950/30'
                                    : 'border-[#F2A81D] bg-[#FFF4D6] dark:bg-amber-950/30',
                                'flex items-center justify-between gap-3 rounded-[14px] border-l-4 p-3.5',
                            ]"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ s.student }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ s.subject }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p
                                    class="text-sm font-bold tabular-nums"
                                    :class="s.auto_fail ? 'text-red-600 dark:text-red-400' : 'text-amber-700 dark:text-amber-400'"
                                >
                                    {{ s.pct }}% faltas
                                </p>
                                <p
                                    class="text-[11px] font-medium"
                                    :class="s.auto_fail ? 'text-red-500' : 'text-amber-600'"
                                >
                                    {{ s.auto_fail ? 'Reprobado automático' : 'En riesgo' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.acadex-title {
    font-family: 'Fraunces', serif;
    font-weight: 600;
}
</style>
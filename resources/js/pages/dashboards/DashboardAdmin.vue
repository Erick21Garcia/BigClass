<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    AlertTriangle,
    BarChart3,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    Clock,
    GraduationCap,
    Presentation,
    TrendingUp,
    Users,
} from 'lucide-vue-next';

interface Props {
    stats: {
        total_students:  number;
        total_teachers:  number;
        active_sections: number;
        approval_rate:   number | null;
        general_avg:     number | null;
        pending_grades:  number;
        conflicts:       number;
    };
    topSubjects:  { name: string; students: number; completion: number | null; avg: number }[];
    activePeriod: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const round1 = (n: number) => Math.round(n * 10) / 10;

// ── Métricas principales ─────────────────────────────────────────────
const kpis = computed(() => {
    const { total_students, total_teachers, active_sections } = props.stats;
    return [
        {
            label: 'Estudiantes activos',
            value: total_students,
            sub: 'Período actual',
            icon: GraduationCap,
            cardClass: 'bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white',
            shapeClass: 'bg-white/[0.12]',
            iconBg: 'bg-white/20',
            iconClass: 'text-white',
        },
        {
            label: 'Docentes activos',
            value: total_teachers,
            sub: total_teachers > 0
                ? `${round1(total_students / total_teachers)} estudiantes por docente`
                : 'Sin docentes activos',
            icon: Presentation,
            cardClass: 'bg-gradient-to-br from-[#214EA4] to-[#3F7BE0] text-white',
            shapeClass: 'bg-white/[0.12]',
            iconBg: 'bg-white/20',
            iconClass: 'text-white',
        },
        {
            label: 'Secciones abiertas',
            value: active_sections,
            sub: total_teachers > 0
                ? `${round1(active_sections / total_teachers)} secciones por docente`
                : 'Período actual',
            icon: BookOpen,
            cardClass: 'bg-gradient-to-br from-[#5E97F0] to-[#8CB8FF] text-[#0B2545]',
            shapeClass: 'bg-white/[0.28]',
            iconBg: 'bg-white/45',
            iconClass: 'text-[#0B2545]',
        },
    ];
});

// ── Rendimiento (aprobación + promedio) ──────────────────────────────
const approvalLabel = computed(() => (props.stats.approval_rate ? `${props.stats.approval_rate}%` : '—'));
const avgLabel      = computed(() => props.stats.general_avg ?? '—');
const noGrades      = computed(() => !props.stats.approval_rate && props.stats.general_avg == null);

// ── Materias ─────────────────────────────────────────────────────────
type Subject = Props['topSubjects'][number];

const maxStudents = computed(() => Math.max(1, ...props.topSubjects.map((s) => s.students)));
const barWidth = (s: Subject) => `${Math.max(6, (s.students / maxStudents.value) * 100)}%`;

// Un 0% (o null) se interpreta como "aún sin notas finales".
const hasApproval = (s: Subject) => s.completion !== null && s.completion > 0;
const anyApproval = computed(() => props.topSubjects.some(hasApproval));

const approvalChip = (completion: number) => {
    if (completion >= 80) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400';
    if (completion >= 60) return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400';
    return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400';
};

const rankClasses = [
    'from-[#1E3A5F] to-[#214EA4] text-white',
    'from-[#214EA4] to-[#3F7BE0] text-white',
    'from-[#3F7BE0] to-[#5E97F0] text-white',
    'from-[#5E97F0] to-[#8CB8FF] text-[#0B2545]',
    'from-[#C9DAF7] to-[#C9DAF7] text-[#1E3A5F]',
];
const rankClass = (i: number) => rankClasses[Math.min(i, rankClasses.length - 1)];

// ── Alertas ──────────────────────────────────────────────────────────
type AlertKind = 'danger' | 'warning' | 'ok';
interface AlertItem {
    icon: typeof AlertCircle;
    kind: AlertKind;
    title: string;
    text: string;
    href?: string;      // opcional: si lo defines, se muestra el enlace
    linkText?: string;
}

const alertStyles: Record<AlertKind, { wrap: string; icon: string }> = {
    danger:  { wrap: 'border-destructive bg-destructive/10', icon: 'text-destructive' },
    warning: { wrap: 'border-[#F2A81D] bg-[#FFF4D6] dark:bg-amber-950/40', icon: 'text-amber-600 dark:text-amber-400' },
    ok:      { wrap: 'border-[#16A36A] bg-[#E3F7EC] dark:bg-emerald-950/40', icon: 'text-emerald-600 dark:text-emerald-400' },
};
const alertOrder: Record<AlertKind, number> = { danger: 0, warning: 1, ok: 2 };

const alerts = computed<AlertItem[]>(() => {
    const list: AlertItem[] = [];

    list.push(
        props.stats.conflicts > 0
            ? {
                  icon: AlertCircle,
                  kind: 'danger',
                  title: `${props.stats.conflicts} conflicto(s) de horario detectado(s)`,
                  text: 'Revisa los cruces en el período actual',
              }
            : {
                  icon: CheckCircle2,
                  kind: 'ok',
                  title: 'Sin conflictos de horario',
                  text: 'No se detectaron cruces',
              },
    );

    list.push(
        props.stats.pending_grades > 0
            ? {
                  icon: Clock,
                  kind: 'warning',
                  title: `${props.stats.pending_grades} ítem(s) sin calificación final`,
                  text: 'Evaluaciones pendientes de cierre',
                  // href: '/ruta/de/pendientes', linkText: 'Revisar pendientes',
              }
            : {
                  icon: CheckCircle2,
                  kind: 'ok',
                  title: 'Sin evaluaciones pendientes',
                  text: 'Todas las notas finales están registradas',
              },
    );

    return list.sort((a, b) => alertOrder[a.kind] - alertOrder[b.kind]);
});

// ── Usuarios (dona) ──────────────────────────────────────────────────
const totalUsers = computed(() => props.stats.total_students + props.stats.total_teachers);
const CIRCUMFERENCE = 2 * Math.PI * 44;
const studentsDash = computed(() =>
    totalUsers.value ? (props.stats.total_students / totalUsers.value) * CIRCUMFERENCE : 0,
);
</script>

<template>
    <Head title="Dashboard">
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
                        <h1 class="acadex-title text-3xl tracking-tight">Panel administrativo</h1>
                        <p class="mt-1.5 flex items-center gap-2 text-sm text-[#C9DAF7]">
                            <CalendarDays class="size-4" />
                            {{ props.activePeriod }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex h-[34px] items-center gap-2 rounded-full border border-white/25 bg-white/15 px-3.5 text-[12.5px] font-medium">
                            <Users class="size-3.5" />
                            {{ totalUsers }} usuarios
                        </span>
                        <span class="inline-flex h-[34px] items-center gap-2 rounded-full border border-white/25 bg-white/15 px-3.5 text-[12.5px] font-medium">
                            <span class="size-2 rounded-full bg-[#5BE3A4] shadow-[0_0_0_3px_rgba(91,227,164,0.25)]"></span>
                            Sistema operativo
                        </span>
                    </div>
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

                <!-- Rendimiento -->
                <div
                    class="relative h-[142px] overflow-hidden rounded-[18px] border border-[#D8E2F4] bg-card p-5 text-card-foreground shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border"
                >
                    <div class="absolute -bottom-9 -right-7 h-[110px] w-[110px] rotate-45 bg-[#E8EEFB] dark:bg-[#214EA4]/15"></div>
                    <div class="absolute right-[18px] top-[18px] grid size-10 place-items-center rounded-xl bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]">
                        <TrendingUp class="size-5 text-white" />
                    </div>
                    <p class="relative text-[13px] font-medium text-muted-foreground">Rendimiento</p>
                    <div class="relative mt-2 flex gap-7">
                        <div>
                            <p :class="[noGrades ? 'text-muted-foreground/60' : 'text-foreground', 'text-[32px] font-semibold leading-none']">
                                {{ approvalLabel }}
                            </p>
                            <p class="mt-1 text-[11.5px] text-muted-foreground">aprobación</p>
                        </div>
                        <div>
                            <p :class="[noGrades ? 'text-muted-foreground/60' : 'text-foreground', 'text-[32px] font-semibold leading-none']">
                                {{ avgLabel }}
                            </p>
                            <p class="mt-1 text-[11.5px] text-muted-foreground">promedio{{ props.stats.general_avg ? ' / 10' : '' }}</p>
                        </div>
                    </div>
                    <span
                        v-if="noGrades"
                        class="relative mt-2 inline-flex items-center rounded-full border border-amber-200 bg-amber-100 px-2.5 py-0.5 text-[11.5px] font-medium text-amber-800 dark:border-amber-900 dark:bg-amber-950/60 dark:text-amber-400"
                    >
                        Aún sin notas finales
                    </span>
                </div>
            </div>

            <!-- Materias + Alertas/Usuarios -->
            <div class="grid gap-4 lg:grid-cols-3">

                <!-- Materias -->
                <div
                    class="rounded-[18px] border border-[#D8E2F4] bg-card p-6 text-card-foreground shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border lg:col-span-2"
                >
                    <div class="flex items-center gap-3">
                        <div class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]">
                            <BarChart3 class="size-[19px] text-white" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">Materias con más inscripciones</h2>
                            <p class="text-xs text-muted-foreground">Inscritos y aprobación por materia · período actual</p>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div
                            v-if="props.topSubjects.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Sin datos para el período actual.
                        </div>

                        <div
                            v-for="(subject, i) in props.topSubjects"
                            :key="subject.name"
                            class="grid grid-cols-[30px_1fr_auto] items-center gap-3.5 border-t border-[#EAF0FB] py-3 first:border-t-0 dark:border-border"
                        >
                            <div :class="[rankClass(i), 'grid size-7 place-items-center rounded-[9px] bg-gradient-to-br text-[12.5px] font-semibold']">
                                {{ i + 1 }}
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center justify-between gap-3 text-sm font-medium">
                                    <span class="truncate">{{ subject.name }}</span>
                                    <span class="shrink-0 rounded-full bg-[#E8EEFB] px-2.5 py-0.5 text-xs font-medium text-[#214EA4] tabular-nums dark:bg-[#214EA4]/25 dark:text-[#8CB8FF]">
                                        {{ subject.students }} est.
                                    </span>
                                </div>
                                <div class="mt-2 h-[9px] w-full overflow-hidden rounded-full bg-[#E4ECF9] dark:bg-muted">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF] transition-all duration-500"
                                        :style="{ width: barWidth(subject) }"
                                    />
                                </div>
                            </div>

                            <div class="min-w-[84px] text-right">
                                <span
                                    v-if="hasApproval(subject)"
                                    :class="[approvalChip(subject.completion as number), 'inline-flex rounded-full px-2.5 py-0.5 text-[11.5px] font-medium tabular-nums']"
                                >
                                    {{ subject.completion }}% aprob.
                                </span>
                                <span
                                    v-else
                                    class="inline-flex rounded-full bg-[#E8EEFB] px-2.5 py-0.5 text-[11.5px] font-medium text-[#4A5D7E] dark:bg-muted dark:text-muted-foreground"
                                >
                                    Sin notas
                                </span>
                            </div>
                        </div>
                    </div>

                    <p
                        v-if="props.topSubjects.length > 0 && !anyApproval"
                        class="mt-2 rounded-xl bg-[#EAF0FB] px-3.5 py-2.5 text-xs text-[#4A5D7E] dark:bg-muted dark:text-muted-foreground"
                    >
                        El % de aprobación aparecerá cuando se registren notas finales.
                        Colores: verde ≥ 80%, ámbar ≥ 60%, rojo por debajo.
                    </p>
                </div>

                <div class="grid content-start gap-4">
                    <!-- Requiere atención -->
                    <div
                        class="rounded-[18px] border border-[#D8E2F4] bg-card p-6 text-card-foreground shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border"
                    >
                        <div class="flex items-center gap-3">
                            <div class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#F2A81D] to-[#F5C04A]">
                                <AlertTriangle class="size-[19px] text-white" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold">Requiere atención</h2>
                                <p class="text-xs text-muted-foreground">Estado del período académico actual</p>
                            </div>
                        </div>

                        <div class="mt-3 space-y-3">
                            <div
                                v-for="alert in alerts"
                                :key="alert.title"
                                :class="[alertStyles[alert.kind].wrap, 'flex items-start gap-3 rounded-[14px] border-l-4 p-3.5']"
                            >
                                <component :is="alert.icon" :class="[alertStyles[alert.kind].icon, 'mt-0.5 size-[18px] shrink-0']" />
                                <div>
                                    <p class="text-sm font-semibold">{{ alert.title }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ alert.text }}</p>
                                    <Link
                                        v-if="alert.href"
                                        :href="alert.href"
                                        class="mt-2 inline-flex items-center gap-1 text-[12.5px] font-semibold text-[#214EA4] hover:underline dark:text-[#8CB8FF]"
                                    >
                                        {{ alert.linkText }} →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usuarios en el sistema -->
                    <div
                        class="rounded-[18px] border border-[#D8E2F4] bg-card p-6 text-card-foreground shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border"
                    >
                        <div class="flex items-center gap-3">
                            <div class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]">
                                <Users class="size-[19px] text-white" />
                            </div>
                            <h2 class="text-base font-semibold">Usuarios en el sistema</h2>
                        </div>

                        <div class="mt-4 flex items-center gap-5">
                            <svg width="118" height="118" viewBox="0 0 118 118" class="shrink-0">
                                <defs>
                                    <linearGradient id="acadex-donut" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0" stop-color="#1E3A5F" />
                                        <stop offset="1" stop-color="#214EA4" />
                                    </linearGradient>
                                </defs>
                                <circle cx="59" cy="59" r="44" fill="none" stroke="#8CB8FF" stroke-width="16" />
                                <circle
                                    cx="59"
                                    cy="59"
                                    r="44"
                                    fill="none"
                                    stroke="url(#acadex-donut)"
                                    stroke-width="16"
                                    :stroke-dasharray="`${studentsDash} ${CIRCUMFERENCE}`"
                                    transform="rotate(-90 59 59)"
                                />
                                <text x="59" y="58" text-anchor="middle" font-size="26" font-weight="600" class="fill-foreground">
                                    {{ totalUsers }}
                                </text>
                                <text x="59" y="75" text-anchor="middle" font-size="10.5" class="fill-muted-foreground">
                                    usuarios
                                </text>
                            </svg>

                            <div class="grid flex-1 gap-3 text-[13px] text-muted-foreground">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <span class="size-2.5 rounded-[4px] bg-[#214EA4]"></span>Estudiantes
                                    </span>
                                    <span class="text-lg font-semibold text-foreground">{{ props.stats.total_students }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <span class="size-2.5 rounded-[4px] bg-[#8CB8FF]"></span>Docentes
                                    </span>
                                    <span class="text-lg font-semibold text-foreground">{{ props.stats.total_teachers }}</span>
                                </div>
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
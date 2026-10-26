<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    Clock,
    GraduationCap,
    MapPin,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Grade  { name: string; score: number | null; pct: number; }
interface Subject { name: string; code: string | null; credits: number; final_grade: number | null; status: string; grades: Grade[]; }
interface Schedule { subject: string; day: string; time: string; room: string; is_today: boolean; }

interface Props {
    stats: {
        enrolled_subjects: number;
        approved:          number;
        avg:               number | null;
        semester:          string;
    };
    subjects:     Subject[];
    schedule:     Schedule[];
    activePeriod: string;
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

// ── Estado de cada materia ───────────────────────────────────────────
const statusLabel = (status: string) => ({
    aprobado: 'Aprobado', reprobado: 'Reprobado', en_curso: 'En curso',
}[status] ?? status);

const statusChip = (status: string) => ({
    aprobado:  'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
    reprobado: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
}[status] ?? 'bg-[#E8EEFB] text-[#214EA4] dark:bg-[#214EA4]/25 dark:text-[#8CB8FF]');

const statusAccent = (status: string) => ({
    aprobado:  'border-emerald-500',
    reprobado: 'border-red-500',
}[status] ?? 'border-[#214EA4]');

// Las notas son sobre 10: la barra mide esa nota.
const scoreWidth = (score: number | null) =>
    score === null ? '0%' : `${Math.min(100, Math.max(0, score * 10))}%`;

// ── Métricas principales ─────────────────────────────────────────────
const kpis = computed(() => [
    {
        label: 'Materias matriculadas',
        value: props.stats.enrolled_subjects,
        sub: 'Período actual',
        icon: BookOpen,
        cardClass: 'bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white',
        shapeClass: 'bg-white/[0.12]',
        iconBg: 'bg-white/20',
        iconClass: 'text-white',
    },
    {
        label: 'Aprobadas',
        value: props.stats.approved,
        sub: `de ${props.stats.enrolled_subjects} matriculadas`,
        icon: CheckCircle2,
        cardClass: 'bg-gradient-to-br from-[#214EA4] to-[#3F7BE0] text-white',
        shapeClass: 'bg-white/[0.12]',
        iconBg: 'bg-white/20',
        iconClass: 'text-white',
    },
    {
        label: 'Promedio actual',
        value: props.stats.avg ? `${props.stats.avg}` : '—',
        sub: props.stats.avg ? 'Sobre 10' : 'Aún sin notas finales',
        icon: BarChart3,
        cardClass: 'bg-gradient-to-br from-[#5E97F0] to-[#8CB8FF] text-[#0B2545]',
        shapeClass: 'bg-white/[0.28]',
        iconBg: 'bg-white/45',
        iconClass: 'text-[#0B2545]',
    },
]);
</script>

<template>
    <Head title="Mi Dashboard">
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
                        <h1 class="acadex-title text-3xl tracking-tight">Mi panel académico</h1>
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

                <!-- Semestre -->
                <div
                    class="relative h-[142px] overflow-hidden rounded-[18px] border border-[#D8E2F4] bg-card p-5 text-card-foreground shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-border"
                >
                    <div class="absolute -bottom-9 -right-7 h-[110px] w-[110px] rotate-45 bg-[#E8EEFB] dark:bg-[#214EA4]/15"></div>
                    <div class="absolute right-[18px] top-[18px] grid size-10 place-items-center rounded-xl bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]">
                        <GraduationCap class="size-5 text-white" />
                    </div>
                    <p class="relative text-[13px] font-medium text-muted-foreground">Semestre</p>
                    <p class="relative mt-2 max-w-[75%] truncate text-[34px] font-semibold leading-[1.2]">
                        {{ props.stats.semester }}
                    </p>
                    <p class="relative mt-1.5 text-[12.5px] text-muted-foreground">Nivel actual</p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">

                <!-- Mis materias -->
                <div :class="[cardBase, 'lg:col-span-2']">
                    <div class="flex items-center gap-3">
                        <div class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]">
                            <BookOpen class="size-[19px] text-white" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">Mis materias</h2>
                            <p class="text-xs text-muted-foreground">Calificaciones del período actual</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div
                            v-if="props.subjects.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Sin materias matriculadas en este período.
                        </div>

                        <div
                            v-for="subject in props.subjects"
                            :key="subject.name"
                            :class="[
                                statusAccent(subject.status),
                                'space-y-3 rounded-[14px] border-l-4 bg-[#F4F7FD] p-4 dark:bg-muted/40',
                            ]"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">{{ subject.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ subject.code ?? '' }}<template v-if="subject.code"> · </template>{{ subject.credits }} créditos
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-xl font-semibold leading-none tabular-nums">
                                        {{ subject.final_grade ?? '—' }}
                                        <span v-if="subject.final_grade" class="text-xs font-normal text-muted-foreground">/10</span>
                                    </p>
                                    <span
                                        :class="[statusChip(subject.status), 'mt-1.5 inline-flex rounded-full px-2.5 py-0.5 text-[11.5px] font-medium']"
                                    >
                                        {{ statusLabel(subject.status) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Notas parciales -->
                            <div
                                v-if="subject.grades.length > 0"
                                class="grid grid-cols-[repeat(auto-fit,minmax(96px,1fr))] gap-2"
                            >
                                <div
                                    v-for="grade in subject.grades"
                                    :key="grade.name"
                                    class="rounded-xl bg-white p-2.5 dark:bg-background"
                                >
                                    <p class="truncate text-[11px] text-muted-foreground">{{ grade.name }}</p>
                                    <p class="mt-0.5 text-sm font-semibold tabular-nums">{{ grade.score ?? '—' }}</p>
                                    <div class="mt-1.5 h-[5px] w-full overflow-hidden rounded-full bg-[#E4ECF9] dark:bg-muted">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF] transition-all duration-500"
                                            :style="{ width: scoreWidth(grade.score) }"
                                        />
                                    </div>
                                    <p class="mt-1 text-[10.5px] text-muted-foreground">{{ grade.pct }}% de la nota</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mi horario -->
                <div :class="cardBase">
                    <div class="flex items-center gap-3">
                        <div class="grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]">
                            <Clock class="size-[19px] text-white" />
                        </div>
                        <div>
                            <h2 class="text-base font-semibold">Mi horario</h2>
                            <p class="text-xs text-muted-foreground">Clases de esta semana</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2.5">
                        <div
                            v-if="props.schedule.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Sin horarios asignados.
                        </div>

                        <div
                            v-for="cls in props.schedule"
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
                            <p class="truncate text-sm font-semibold">{{ cls.subject }}</p>
                            <p class="text-xs text-muted-foreground">{{ cls.time }}</p>
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
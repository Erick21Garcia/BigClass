<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    GraduationCap, School, BookOpen, TrendingUp,
    AlertCircle, CheckCircle2, Users, Clock,
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
    topSubjects:  { name: string; students: number; completion: number; avg: number }[];
    activePeriod: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const stats = computed(() => [
    {
        label: 'Estudiantes activos',
        value: props.stats.total_students,
        icon: GraduationCap,
        iconClass: 'text-primary',
        iconBg: 'bg-primary/15',
        cardBg: 'bg-primary/5 dark:bg-primary/10 border-primary/15',
    },
    {
        label: 'Docentes activos',
        value: props.stats.total_teachers,
        icon: School,
        iconClass: 'text-emerald-600 dark:text-emerald-400',
        iconBg: 'bg-emerald-100 dark:bg-emerald-950',
        cardBg: 'bg-emerald-50/60 dark:bg-emerald-950/30 border-emerald-200/60 dark:border-emerald-900/60',
    },
    {
        label: 'Secciones abiertas',
        value: props.stats.active_sections,
        icon: BookOpen,
        iconClass: 'text-violet-600 dark:text-violet-400',
        iconBg: 'bg-violet-100 dark:bg-violet-950',
        cardBg: 'bg-violet-50/60 dark:bg-violet-950/30 border-violet-200/60 dark:border-violet-900/60',
    },
    {
        label: 'Tasa de aprobación',
        value: props.stats.approval_rate ? `${props.stats.approval_rate}%` : '—',
        icon: TrendingUp,
        iconClass: 'text-amber-600 dark:text-amber-400',
        iconBg: 'bg-amber-100 dark:bg-amber-950',
        cardBg: 'bg-amber-50/60 dark:bg-amber-950/30 border-amber-200/60 dark:border-amber-900/60',
    },
]);

const alerts = computed(() => {
    const list = [];
    if (props.stats.conflicts > 0)
        list.push({
            icon: AlertCircle,
            colorClass: 'text-destructive',
            wrapClass: 'bg-destructive/10 border border-destructive/20',
            text: `${props.stats.conflicts} conflicto(s) de horario detectado(s)`,
        });
    if (props.stats.pending_grades > 0)
        list.push({
            icon: Clock,
            colorClass: 'text-amber-600 dark:text-amber-400',
            wrapClass: 'bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900',
            text: `${props.stats.pending_grades} ítem(s) sin calificación final`,
        });
    if (list.length === 0)
        list.push({
            icon: CheckCircle2,
            colorClass: 'text-emerald-600 dark:text-emerald-400',
            wrapClass: 'bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-900',
            text: 'Sin alertas activas en este período',
        });
    return list;
});

// Color de barra según porcentaje de aprobación
const barColor = (completion: number) => {
    if (completion >= 80) return 'bg-emerald-500';
    if (completion >= 60) return 'bg-amber-500';
    return 'bg-destructive';
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-medium tracking-tight">Panel administrativo</h1>
                    <p class="text-sm text-muted-foreground">{{ props.activePeriod }}</p>
                </div>
                <Badge variant="outline" class="gap-1.5 border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                    Sistema operativo
                </Badge>
            </div>

            <!-- Métricas principales -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.label" :class="stat.cardBg">
                    <CardContent class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex flex-col gap-1">
                                <p class="text-xs font-medium text-muted-foreground">{{ stat.label }}</p>
                                <p class="text-3xl font-medium">{{ stat.value }}</p>
                            </div>
                            <div :class="[stat.iconBg, 'shrink-0 rounded-lg p-2.5']">
                                <component :is="stat.icon" :class="[stat.iconClass, 'size-5']" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Materias + Alertas -->
            <div class="grid gap-4 lg:grid-cols-3">

                <Card class="lg:col-span-2">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base font-medium">Materias con más inscripciones</CardTitle>
                        <CardDescription>Tasa de aprobación por materia en el período actual</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-if="props.topSubjects.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            Sin datos para el período actual.
                        </div>
                        <div
                            v-for="subject in props.topSubjects"
                            :key="subject.name"
                            class="space-y-1.5"
                        >
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ subject.name }}</span>
                                <span class="text-muted-foreground tabular-nums">
                                    {{ subject.students }} est. · {{ subject.completion }}%
                                </span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-muted">
                                <div
                                    :class="[barColor(subject.completion), 'h-1.5 rounded-full transition-all duration-500']"
                                    :style="{ width: subject.completion + '%' }"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base font-medium">Alertas del sistema</CardTitle>
                        <CardDescription>Estado del período académico actual</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            v-for="alert in alerts"
                            :key="alert.text"
                            :class="[alert.wrapClass, 'flex items-start gap-3 rounded-lg p-3']"
                        >
                            <component :is="alert.icon" :class="[alert.colorClass, 'mt-0.5 size-4 shrink-0']" />
                            <p class="text-xs leading-relaxed text-muted-foreground">{{ alert.text }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Resumen inferior -->
            <div class="grid gap-4 sm:grid-cols-3">

                <Card class="border-primary/20 bg-primary/5 dark:bg-primary/10">
                    <CardContent class="p-5">
                        <div class="mb-3 flex size-9 items-center justify-center rounded-lg bg-primary/10">
                            <Users class="size-5 text-primary" />
                        </div>
                        <p class="text-3xl font-medium">
                            {{ props.stats.total_students + props.stats.total_teachers }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">Usuarios en el sistema</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-5">
                        <div class="mb-3 flex size-9 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950/50">
                            <Clock class="size-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <p class="text-3xl font-medium">{{ props.stats.pending_grades }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">Evaluaciones pendientes</p>
                        <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">ítems sin nota final</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-5">
                        <div class="mb-3 flex size-9 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-950/50">
                            <TrendingUp class="size-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <p class="text-3xl font-medium">
                            {{ props.stats.general_avg ?? '—' }}
                            <span v-if="props.stats.general_avg" class="text-base font-normal text-muted-foreground">/ 10</span>
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">Promedio general</p>
                        <p class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">período actual</p>
                    </CardContent>
                </Card>

            </div>

        </div>
    </AppLayout>
</template>
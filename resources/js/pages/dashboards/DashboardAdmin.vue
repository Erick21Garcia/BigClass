<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    Users, GraduationCap, School, TrendingUp,
    AlertCircle, CheckCircle2, BookOpen, BarChart3,
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
    { label: 'Total Estudiantes',  value: props.stats.total_students,                      icon: GraduationCap, color: 'text-blue-500',    bg: 'bg-blue-50 dark:bg-blue-950' },
    { label: 'Docentes Activos',   value: props.stats.total_teachers,                      icon: School,        color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-950' },
    { label: 'Secciones Activas',  value: props.stats.active_sections,                     icon: BookOpen,      color: 'text-violet-500',  bg: 'bg-violet-50 dark:bg-violet-950' },
    { label: 'Tasa de Aprobación', value: props.stats.approval_rate ? `${props.stats.approval_rate}%` : '—', icon: TrendingUp, color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-950' },
]);

const alerts = computed(() => {
    const list = [];
    if (props.stats.conflicts > 0)
        list.push({ icon: AlertCircle, color: 'text-red-500', text: `${props.stats.conflicts} conflicto(s) de horario detectado(s)` });
    if (props.stats.pending_grades > 0)
        list.push({ icon: AlertCircle, color: 'text-amber-500', text: `${props.stats.pending_grades} ítems sin calificación final` });
    if (list.length === 0)
        list.push({ icon: CheckCircle2, color: 'text-emerald-500', text: 'Sin alertas activas en este período' });
    return list;
});
</script>

<template>
    <Head title="Dashboard Admin" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Panel Administrativo</h1>
                    <p class="text-sm text-muted-foreground">{{ props.activePeriod }}</p>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                    ● Sistema Operativo
                </span>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.label" class="border-0 shadow-lg dark:ring-1 dark:ring-white/10">
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ stat.label }}</p>
                                <p class="mt-1 text-3xl font-bold">{{ stat.value }}</p>
                            </div>
                            <div :class="[stat.bg, 'rounded-xl p-3']">
                                <component :is="stat.icon" :class="[stat.color, 'h-6 w-6']" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Middle -->
            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2 border-0 shadow-lg dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Materias con más inscripciones</CardTitle>
                        <CardDescription>Tasa de aprobación por materia</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="props.topSubjects.length === 0" class="text-sm text-muted-foreground text-center py-6">
                            Sin datos para el período actual.
                        </div>
                        <div v-for="subject in props.topSubjects" :key="subject.name" class="space-y-1.5">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ subject.name }}</span>
                                <span class="text-muted-foreground">{{ subject.students }} est. · {{ subject.completion }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-gradient-to-r from-violet-500 to-blue-500 transition-all duration-700"
                                    :style="{ width: subject.completion + '%' }"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 shadow-lg dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Alertas del sistema</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="alert in alerts" :key="alert.text" class="flex items-start gap-3 rounded-lg bg-muted/50 p-3">
                            <component :is="alert.icon" :class="[alert.color, 'mt-0.5 h-4 w-4 shrink-0']" />
                            <p class="text-xs leading-relaxed text-muted-foreground">{{ alert.text }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Bottom -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card class="border-0 shadow-lg dark:ring-1 dark:ring-white/10 bg-gradient-to-br from-violet-600 to-blue-600 text-white">
                    <CardContent class="p-5">
                        <Users class="h-8 w-8 opacity-80 mb-3" />
                        <p class="text-3xl font-bold">{{ props.stats.total_students + props.stats.total_teachers }}</p>
                        <p class="text-sm opacity-80 mt-1">Usuarios en el sistema</p>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-lg dark:ring-1 dark:ring-white/10">
                    <CardContent class="p-5">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Evaluaciones pendientes</p>
                        <p class="mt-1 text-3xl font-bold">{{ props.stats.pending_grades }}</p>
                        <p class="mt-1 text-xs text-amber-600 font-medium">ítems sin nota final</p>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-lg dark:ring-1 dark:ring-white/10">
                    <CardContent class="p-5">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Promedio general</p>
                        <p class="mt-1 text-3xl font-bold">
                            {{ props.stats.general_avg ?? '—' }}
                            <span v-if="props.stats.general_avg" class="text-base font-normal text-muted-foreground">/ 10</span>
                        </p>
                        <p class="mt-1 text-xs text-emerald-600 font-medium">período actual</p>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AppLayout>
</template>
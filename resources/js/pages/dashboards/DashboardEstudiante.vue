<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BookOpen, CheckCircle2, BarChart3, GraduationCap } from 'lucide-vue-next';
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

const statusVariant = (status: string) => ({
    aprobado: 'default' as const,
    reprobado: 'destructive' as const,
    en_curso: 'secondary' as const,
}[status] ?? 'secondary' as const);

const statusLabel = (status: string) => ({
    aprobado: 'Aprobado', reprobado: 'Reprobado', en_curso: 'En curso',
}[status] ?? status);

const statsCards = computed(() => [
    { label: 'Materias matriculadas', value: props.stats.enrolled_subjects, icon: BookOpen,      color: 'text-blue-500',    bg: 'bg-blue-50 dark:bg-blue-950' },
    { label: 'Aprobadas',             value: props.stats.approved,           icon: CheckCircle2,  color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-950' },
    { label: 'Promedio actual',        value: props.stats.avg ? `${props.stats.avg}` : '—',      icon: BarChart3,     color: 'text-violet-500',  bg: 'bg-violet-50 dark:bg-violet-950' },
    { label: 'Semestre',              value: props.stats.semester,           icon: GraduationCap, color: 'text-amber-500',   bg: 'bg-amber-50 dark:bg-amber-950' },
]);
</script>

<template>
    <Head title="Mi Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <div>
                <h1 class="text-2xl font-bold tracking-tight">Mi Panel Académico</h1>
                <p class="text-sm text-muted-foreground">{{ props.activePeriod }}</p>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in statsCards" :key="stat.label" class="border-0 shadow-lg dark:ring-1 dark:ring-white/10">
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

            <div class="grid gap-4 lg:grid-cols-3">

                <!-- Materias y notas -->
                <Card class="lg:col-span-2 border-0 shadow-lg dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Mis materias</CardTitle>
                        <CardDescription>Calificaciones del período actual</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-if="props.subjects.length === 0" class="text-sm text-muted-foreground text-center py-6">
                            Sin materias matriculadas en este período.
                        </div>
                        <div v-for="subject in props.subjects" :key="subject.name" class="rounded-xl border p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-sm">{{ subject.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ subject.code ?? '' }} · {{ subject.credits }} créditos</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-lg">
                                        {{ subject.final_grade ?? '—' }}
                                        <span v-if="subject.final_grade" class="text-xs font-normal text-muted-foreground">/10</span>
                                    </p>
                                    <Badge :variant="statusVariant(subject.status)"
                                           :class="subject.status === 'aprobado' ? 'bg-green-500 text-white' : ''"
                                           class="text-xs">
                                        {{ statusLabel(subject.status) }}
                                    </Badge>
                                </div>
                            </div>
                            <!-- Notas parciales -->
                            <div v-if="subject.grades.length > 0" class="flex gap-3">
                                <div v-for="grade in subject.grades" :key="grade.name" class="text-center">
                                    <p class="text-[10px] text-muted-foreground">{{ grade.name }}</p>
                                    <p class="text-xs font-semibold">{{ grade.score ?? '—' }}</p>
                                    <p class="text-[10px] text-muted-foreground">{{ grade.pct }}%</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Horario -->
                <Card class="border-0 shadow-lg dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Mi horario</CardTitle>
                        <CardDescription>Clases de esta semana</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-if="props.schedule.length === 0" class="text-sm text-muted-foreground text-center py-6">
                            Sin horarios asignados.
                        </div>
                        <div v-for="cls in props.schedule" :key="cls.subject + cls.day + cls.time"
                             class="rounded-lg p-3"
                             :class="cls.is_today ? 'bg-primary/10 border border-primary/20' : 'bg-muted/50'">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold"
                                      :class="cls.is_today ? 'text-primary' : 'text-muted-foreground'">
                                    {{ cls.is_today ? 'Hoy' : cls.day }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ cls.room }}</span>
                            </div>
                            <p class="text-sm font-medium truncate">{{ cls.subject }}</p>
                            <p class="text-xs text-muted-foreground">{{ cls.time }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AppLayout>
</template>
<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BookOpen, ClipboardList, Users, Calendar } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    stats: {
        my_courses:     number;
        total_students: number;
        pending_grades: number;
    };
    myCourses:       { name: string; students: number; pending: number; avg: number }[];
    upcomingClasses: { subject: string; time: string; room: string; day: string; is_today: boolean }[];
    activePeriod:    string;
}

const props  = defineProps<Props>();
const colors = ['bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500', 'bg-rose-500'];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const statsCards = computed(() => [
    { label: 'Mis Secciones',        value: props.stats.my_courses,     icon: BookOpen,      color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-950' },
    { label: 'Total Estudiantes',    value: props.stats.total_students,  icon: Users,         color: 'text-blue-500',    bg: 'bg-blue-50 dark:bg-blue-950' },
    { label: 'Notas por Registrar',  value: props.stats.pending_grades,  icon: ClipboardList, color: 'text-amber-500',   bg: 'bg-amber-50 dark:bg-amber-950' },
]);
</script>

<template>
    <Head title="Dashboard Docente" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Mi Panel Docente</h1>
                    <p class="text-sm text-muted-foreground">{{ props.activePeriod }}</p>
                </div>
                <div class="flex items-center gap-2 rounded-full border px-4 py-1.5">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium">{{ new Date().toLocaleDateString('es-EC', { weekday: 'long', day: 'numeric', month: 'short' }) }}</span>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
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

            <div class="grid gap-4 lg:grid-cols-5">
                <Card class="lg:col-span-3 border-0 shadow-lg dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Mis secciones activas</CardTitle>
                        <CardDescription>Rendimiento promedio de estudiantes</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-if="props.myCourses.length === 0" class="text-sm text-muted-foreground text-center py-6">
                            Sin secciones asignadas en este período.
                        </div>
                        <div v-for="(course, i) in props.myCourses" :key="course.name" class="flex items-center gap-4 rounded-xl border p-3">
                            <div :class="[colors[i % colors.length], 'h-10 w-1.5 rounded-full shrink-0']" />
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm truncate">{{ course.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ course.students }} estudiantes</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold">
                                    {{ course.avg > 0 ? course.avg : '—' }}
                                    <span v-if="course.avg > 0" class="text-xs font-normal text-muted-foreground">/10</span>
                                </p>
                                <p class="text-xs text-amber-600">{{ course.pending }} pendientes</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2 border-0 shadow-lg dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Próximas clases</CardTitle>
                        <CardDescription>Horario de esta semana</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-if="props.upcomingClasses.length === 0" class="text-sm text-muted-foreground text-center py-6">
                            Sin horarios asignados.
                        </div>
                        <div v-for="cls in props.upcomingClasses" :key="cls.subject + cls.time" class="rounded-lg bg-muted/50 p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold" :class="cls.is_today ? 'text-emerald-600' : 'text-muted-foreground'">
                                    {{ cls.is_today ? 'Hoy' : cls.day }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ cls.room }}</span>
                            </div>
                            <p class="text-sm font-medium">{{ cls.subject }}</p>
                            <p class="text-xs text-muted-foreground">{{ cls.time }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AppLayout>
</template>
<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    BookOpen,
    Trophy,
    Clock,
    TrendingUp,
    CheckCircle2,
    AlertCircle,
    Calendar,
    FileText,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const stats = [
    { label: 'Materias', value: '6', icon: BookOpen, color: 'text-blue-500', bg: 'bg-blue-50 dark:bg-blue-950' },
    { label: 'Promedio General', value: '8.2', icon: TrendingUp, color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-950' },
    { label: 'Tareas Pendientes', value: '4', icon: FileText, color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-950' },
    { label: 'Posición en clase', value: '#7', icon: Trophy, color: 'text-violet-500', bg: 'bg-violet-50 dark:bg-violet-950' },
];

const myCourses = [
    { name: 'Matemáticas Avanzadas', teacher: 'Prof. García', grade: 8.5, status: 'Al día', color: 'text-emerald-600' },
    { name: 'Física General', teacher: 'Prof. Martínez', grade: 7.8, status: 'Al día', color: 'text-emerald-600' },
    { name: 'Química Orgánica', teacher: 'Prof. López', grade: 6.4, status: 'Tarea pendiente', color: 'text-amber-600' },
    { name: 'Inglés Técnico', teacher: 'Prof. Smith', grade: 9.1, status: 'Al día', color: 'text-emerald-600' },
    { name: 'Programación I', teacher: 'Prof. Torres', grade: 8.9, status: 'Al día', color: 'text-emerald-600' },
    { name: 'Estadística', teacher: 'Prof. Ruiz', grade: 7.2, status: 'Examen próximo', color: 'text-blue-600' },
];

const upcomingDeadlines = [
    { task: 'Tarea: Química Orgánica', due: 'Hoy 23:59', urgent: true },
    { task: 'Proyecto: Programación I', due: 'Mañana 18:00', urgent: true },
    { task: 'Examen: Estadística', due: 'Miércoles 08:00', urgent: false },
    { task: 'Tarea: Física General', due: 'Viernes 23:59', urgent: false },
];

const achievements = [
    { icon: Trophy, color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-950', label: 'Top 10', desc: 'del salón' },
    { icon: CheckCircle2, color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-950', label: '94%', desc: 'asistencia' },
    { icon: TrendingUp, color: 'text-blue-500', bg: 'bg-blue-50 dark:bg-blue-950', label: '+0.4', desc: 'promedio' },
];
</script>

<template>
    <Head title="Dashboard Estudiante" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Mi Panel Estudiantil</h1>
                    <p class="text-sm text-muted-foreground">Período 2025-I · Semana 8 de 16</p>
                </div>
                <div class="flex items-center gap-2 rounded-full border px-4 py-1.5">
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium">Lunes, 24 Feb 2025</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.label" class="border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
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
            <div class="grid gap-4 lg:grid-cols-5">

                <!-- Courses -->
                <Card class="lg:col-span-3 border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Mis materias</CardTitle>
                        <CardDescription>Calificaciones y estado actual</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            v-for="course in myCourses"
                            :key="course.name"
                            class="flex items-center gap-3 rounded-xl border p-3"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm truncate">{{ course.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ course.teacher }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold">{{ course.grade }}<span class="text-xs font-normal text-muted-foreground">/10</span></p>
                                <p :class="['text-xs font-medium', course.color]">{{ course.status }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Right column -->
                <div class="lg:col-span-2 flex flex-col gap-4">

                    <!-- Deadlines -->
                    <Card class="border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10 flex-1">
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base">Próximas entregas</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <div
                                v-for="item in upcomingDeadlines"
                                :key="item.task"
                                class="flex items-start gap-3 rounded-lg p-2.5"
                                :class="item.urgent ? 'bg-red-50 dark:bg-red-950/40' : 'bg-muted/50'"
                            >
                                <component
                                    :is="item.urgent ? AlertCircle : Clock"
                                    class="mt-0.5 h-4 w-4 shrink-0"
                                    :class="item.urgent ? 'text-red-500' : 'text-muted-foreground'"
                                />
                                <div>
                                    <p class="text-xs font-medium">{{ item.task }}</p>
                                    <p class="text-xs" :class="item.urgent ? 'text-red-500 font-semibold' : 'text-muted-foreground'">
                                        {{ item.due }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Achievements -->
                    <Card class="border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base">Mis logros</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-3 gap-2">
                                <div
                                    v-for="item in achievements"
                                    :key="item.label"
                                    class="flex flex-col items-center gap-2 rounded-xl p-3"
                                    :class="item.bg"
                                >
                                    <component :is="item.icon" :class="[item.color, 'h-5 w-5']" />
                                    <p class="text-sm font-bold">{{ item.label }}</p>
                                    <p class="text-xs text-muted-foreground">{{ item.desc }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
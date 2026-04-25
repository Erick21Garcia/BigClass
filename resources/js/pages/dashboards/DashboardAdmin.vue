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
    Users,
    GraduationCap,
    School,
    TrendingUp,
    AlertCircle,
    CheckCircle2,
    Clock,
    BookOpen,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const stats = [
    { label: 'Total Estudiantes', value: '1,248', change: '+12%', icon: GraduationCap, color: 'text-blue-500', bg: 'bg-blue-50 dark:bg-blue-950' },
    { label: 'Docentes Activos', value: '87', change: '+3%', icon: School, color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-950' },
    { label: 'Cursos en Curso', value: '34', change: '+5%', icon: BookOpen, color: 'text-violet-500', bg: 'bg-violet-50 dark:bg-violet-950' },
    { label: 'Tasa de Aprobación', value: '91.4%', change: '+2.1%', icon: TrendingUp, color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-950' },
];

const recentAlerts = [
    { type: 'warning', message: '3 docentes sin asignar curso este período', icon: AlertCircle, color: 'text-amber-500' },
    { type: 'success', message: 'Cierre de período completado exitosamente', icon: CheckCircle2, color: 'text-emerald-500' },
    { type: 'info', message: 'Matrícula abierta hasta el 15 de marzo', icon: Clock, color: 'text-blue-500' },
    { type: 'warning', message: '12 estudiantes con pagos pendientes', icon: AlertCircle, color: 'text-amber-500' },
];

const topCourses = [
    { name: 'Matemáticas Avanzadas', students: 145, completion: 78 },
    { name: 'Física General', students: 132, completion: 65 },
    { name: 'Programación I', students: 118, completion: 89 },
    { name: 'Inglés Técnico', students: 201, completion: 54 },
    { name: 'Química Orgánica', students: 97, completion: 72 },
];
</script>

<template>
    <Head title="Dashboard Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Panel Administrativo</h1>
                    <p class="text-sm text-muted-foreground">Resumen general del sistema — Período 2025-I</p>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                    ● Sistema Operativo
                </span>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.label" class="border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                    <CardContent class="p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">{{ stat.label }}</p>
                                <p class="mt-1 text-3xl font-bold">{{ stat.value }}</p>
                                <p class="mt-1 text-xs font-medium text-emerald-600">{{ stat.change }} este mes</p>
                            </div>
                            <div :class="[stat.bg, 'rounded-xl p-3']">
                                <component :is="stat.icon" :class="[stat.color, 'h-6 w-6']" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Middle Row -->
            <div class="grid gap-4 lg:grid-cols-3">

                <!-- Top Courses -->
                <Card class="lg:col-span-2 border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Cursos con más inscripciones</CardTitle>
                        <CardDescription>Progreso de completitud por curso</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="course in topCourses" :key="course.name" class="space-y-1.5">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ course.name }}</span>
                                <span class="text-muted-foreground">{{ course.students }} est. · {{ course.completion }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-gradient-to-r from-violet-500 to-blue-500 transition-all duration-700"
                                    :style="{ width: course.completion + '%' }"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Alerts -->
                <Card class="border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Alertas del sistema</CardTitle>
                        <CardDescription>Notificaciones recientes</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="alert in recentAlerts"
                            :key="alert.message"
                            class="flex items-start gap-3 rounded-lg bg-muted/50 p-3"
                        >
                            <component :is="alert.icon" :class="[alert.color, 'mt-0.5 h-4 w-4 shrink-0']" />
                            <p class="text-xs leading-relaxed text-muted-foreground">{{ alert.message }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Bottom Stats -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card class="border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10 bg-gradient-to-br from-violet-600 to-blue-600 text-white">
                    <CardContent class="p-5">
                        <Users class="h-8 w-8 opacity-80 mb-3" />
                        <p class="text-3xl font-bold">342</p>
                        <p class="text-sm opacity-80 mt-1">Usuarios activos hoy</p>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                    <CardContent class="p-5">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Evaluaciones pendientes</p>
                        <p class="mt-1 text-3xl font-bold">128</p>
                        <p class="mt-1 text-xs text-amber-600 font-medium">↑ 18 sin calificar esta semana</p>
                    </CardContent>
                </Card>
                <Card class="border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                    <CardContent class="p-5">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Promedio general</p>
                        <p class="mt-1 text-3xl font-bold">7.8 <span class="text-base font-normal text-muted-foreground">/ 10</span></p>
                        <p class="mt-1 text-xs text-emerald-600 font-medium">↑ 0.3 puntos vs período anterior</p>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AppLayout>
</template>
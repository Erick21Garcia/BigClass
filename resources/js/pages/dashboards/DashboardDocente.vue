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
    ClipboardList,
    Star,
    Clock,
    CheckCircle2,
    AlertCircle,
    Calendar,
    Users,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const stats = [
    { label: 'Mis Cursos', value: '4', icon: BookOpen, color: 'text-emerald-500', bg: 'bg-emerald-50 dark:bg-emerald-950' },
    { label: 'Total Estudiantes', value: '128', icon: Users, color: 'text-blue-500', bg: 'bg-blue-50 dark:bg-blue-950' },
    { label: 'Tareas por Calificar', value: '23', icon: ClipboardList, color: 'text-amber-500', bg: 'bg-amber-50 dark:bg-amber-950' },
    { label: 'Mi Calificación', value: '4.8', icon: Star, color: 'text-violet-500', bg: 'bg-violet-50 dark:bg-violet-950' },
];

const myCourses = [
    { name: 'Matemáticas Avanzadas', students: 38, pending: 8, avg: 7.4, color: 'bg-blue-500' },
    { name: 'Cálculo Diferencial', students: 32, pending: 5, avg: 6.9, color: 'bg-emerald-500' },
    { name: 'Álgebra Lineal', students: 29, pending: 7, avg: 7.8, color: 'bg-violet-500' },
    { name: 'Estadística', students: 29, pending: 3, avg: 8.1, color: 'bg-amber-500' },
];

const upcomingClasses = [
    { subject: 'Matemáticas Avanzadas', time: '08:00 - 10:00', room: 'Aula 204', day: 'Hoy' },
    { subject: 'Álgebra Lineal', time: '10:30 - 12:30', room: 'Aula 108', day: 'Hoy' },
    { subject: 'Cálculo Diferencial', time: '14:00 - 16:00', room: 'Lab. A', day: 'Mañana' },
    { subject: 'Estadística', time: '08:00 - 10:00', room: 'Aula 301', day: 'Miércoles' },
];

const recentActivity = [
    { icon: CheckCircle2, color: 'text-emerald-500', text: 'Calificaste 12 tareas de Estadística' },
    { icon: AlertCircle, color: 'text-amber-500', text: '8 estudiantes con bajo rendimiento en Cálculo' },
    { icon: CheckCircle2, color: 'text-emerald-500', text: 'Examen parcial de Álgebra publicado' },
    { icon: Clock, color: 'text-blue-500', text: 'Entrega de notas: 3 días restantes' },
];
</script>

<template>
    <Head title="Dashboard Docente" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Mi Panel Docente</h1>
                    <p class="text-sm text-muted-foreground">Bienvenido — Período 2025-I · Semana 8 de 16</p>
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

                <!-- My courses -->
                <Card class="lg:col-span-3 border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Mis cursos activos</CardTitle>
                        <CardDescription>Rendimiento promedio de estudiantes</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="course in myCourses"
                            :key="course.name"
                            class="flex items-center gap-4 rounded-xl border p-3"
                        >
                            <div :class="[course.color, 'h-10 w-1.5 rounded-full shrink-0']" />
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm truncate">{{ course.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ course.students }} estudiantes</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold">{{ course.avg }}<span class="text-xs font-normal text-muted-foreground">/10</span></p>
                                <p class="text-xs text-amber-600">{{ course.pending }} pendientes</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Upcoming classes -->
                <Card class="lg:col-span-2 border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Próximas clases</CardTitle>
                        <CardDescription>Horario de esta semana</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            v-for="cls in upcomingClasses"
                            :key="cls.subject + cls.time"
                            class="rounded-lg bg-muted/50 p-3"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold" :class="cls.day === 'Hoy' ? 'text-emerald-600' : 'text-muted-foreground'">
                                    {{ cls.day }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ cls.room }}</span>
                            </div>
                            <p class="text-sm font-medium">{{ cls.subject }}</p>
                            <p class="text-xs text-muted-foreground">{{ cls.time }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Activity -->
            <Card class="border-0 shadow-lg dark:shadow-none dark:ring-1 dark:ring-white/10">
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">Actividad reciente</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="item in recentActivity"
                            :key="item.text"
                            class="flex items-start gap-3 rounded-xl bg-muted/50 p-3"
                        >
                            <component :is="item.icon" :class="[item.color, 'mt-0.5 h-4 w-4 shrink-0']" />
                            <p class="text-xs leading-relaxed text-muted-foreground">{{ item.text }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>
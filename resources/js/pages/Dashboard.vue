<script setup lang="ts">
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ShieldAlert, Mail, Clock, HelpCircle, RefreshCw } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

const steps = [
    {
        icon: Clock,
        color: 'text-amber-500',
        bg: 'bg-amber-50 dark:bg-amber-950',
        title: 'Cuenta en revisión',
        desc: 'Tu cuenta fue creada exitosamente. Un administrador debe asignarte un rol para acceder al sistema.',
    },
    {
        icon: Mail,
        color: 'text-blue-500',
        bg: 'bg-blue-50 dark:bg-blue-950',
        title: 'Contacta al administrador',
        desc: 'Si llevas más de 24 horas sin acceso, escribe a soporte@bigclass.edu con tu correo registrado.',
    },
    {
        icon: RefreshCw,
        color: 'text-violet-500',
        bg: 'bg-violet-50 dark:bg-violet-950',
        title: 'Recarga la página',
        desc: 'Una vez que te asignen un rol, recarga esta página y tendrás acceso completo a tu panel.',
    },
];

const faqs = [
    { q: '¿Por qué no puedo ver nada?', a: 'Aún no tienes un rol asignado. Sin rol, el sistema no sabe qué información mostrarte.' },
    { q: '¿Cuánto tiempo toma la activación?', a: 'Normalmente entre 1 y 24 horas hábiles después del registro.' },
    { q: '¿Qué roles existen?', a: 'Administrador, Docente y Estudiante. Cada uno tiene su propio panel con información relevante.' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center justify-start gap-6 p-6">

            <!-- Hero state -->
            <div class="w-full max-w-2xl">
                <Card class="border-0 shadow-sm bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
                    <CardContent class="flex flex-col items-center gap-4 py-12 text-center">
                        <div class="rounded-2xl bg-amber-100 p-5 dark:bg-amber-900/40">
                            <ShieldAlert class="h-10 w-10 text-amber-500" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">Sin rol asignado</h2>
                            <p class="mt-1 text-sm text-muted-foreground max-w-sm mx-auto">
                                Tu cuenta está activa pero todavía no tienes un rol en el sistema.
                                Contacta a tu administrador para completar la configuración.
                            </p>
                        </div>
                        <span class="rounded-full border border-amber-300 bg-amber-50 px-4 py-1.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            Acceso pendiente de activación
                        </span>
                    </CardContent>
                </Card>
            </div>

            <!-- Steps -->
            <div class="w-full max-w-2xl grid gap-3 sm:grid-cols-3">
                <Card
                    v-for="step in steps"
                    :key="step.title"
                    class="border-0 shadow-sm"
                >
                    <CardContent class="p-5 flex flex-col gap-3">
                        <div :class="[step.bg, 'w-fit rounded-xl p-2.5']">
                            <component :is="step.icon" :class="[step.color, 'h-5 w-5']" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold">{{ step.title }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">{{ step.desc }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- FAQ -->
            <div class="w-full max-w-2xl">
                <Card class="border-0 shadow-sm">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <HelpCircle class="h-4 w-4 text-muted-foreground" />
                            Preguntas frecuentes
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-for="faq in faqs"
                            :key="faq.q"
                            class="rounded-xl bg-muted/50 p-4"
                        >
                            <p class="text-sm font-semibold">{{ faq.q }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">{{ faq.a }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AppLayout>
</template>
<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, GraduationCap, ShieldCheck } from 'lucide-vue-next';
import AcadexSplitLayout from '@/layouts/AcadexSplitLayout.vue';
import acadexLogo from '@/acadex-logo-512.png';

defineOptions({ layout: AcadexSplitLayout });

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const roles = [
    { icon: ShieldCheck, title: 'Administración', text: 'Matrículas, periodos y reportes en un solo panel.' },
    { icon: BookOpen, title: 'Docentes', text: 'Calificaciones y asistencia sin papeleo.' },
    { icon: GraduationCap, title: 'Estudiantes', text: 'Tus notas, horarios y trámites al día.' },
];
</script>

<template>
    <div class="flex flex-col px-6 py-6 sm:px-10 lg:px-16 lg:py-9">
        <Head title="Bienvenido" />

        <header class="flex w-full items-center justify-between">
            <div class="flex items-center gap-2.5">
                <img :src="acadexLogo" alt="" width="36" height="36" class="h-9 w-9 rounded-lg object-contain" />
                <span class="acadex-wordmark text-xl tracking-tight lg:text-2xl">Acadex</span>
            </div>

            <Link
                v-if="$page.props.auth.user"
                :href="dashboard()"
                class="rounded-full border border-[#1E4C8A]/20 px-4 py-1.5 text-sm font-medium text-[#1E4C8A] transition-colors hover:border-[#1E4C8A]/40 hover:bg-[#1E4C8A]/5"
            >
                Ir al panel
            </Link>
        </header>

        <main class="my-auto py-10">
            <span class="inline-flex items-center gap-2 rounded-full bg-[#E8EEFB] px-3 py-1 text-xs font-medium text-[#214EA4]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#214EA4]"></span>
                Sistema de gestión académica
            </span>

            <h1 class="acadex-wordmark mt-5 text-4xl leading-[1.08] sm:text-5xl lg:text-[56px]">
                Bienvenido<br />de nuevo
            </h1>
            <p class="mt-4 max-w-md text-base leading-relaxed text-[#5B6B82] lg:text-[17px]">
                Un solo lugar para administración, docentes y estudiantes.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <Link
                    :href="login()"
                    class="group inline-flex h-[46px] items-center gap-2 rounded-full bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] px-7 text-sm font-medium text-white shadow-[0_10px_24px_-10px_rgba(33,78,164,0.7)] transition hover:-translate-y-0.5"
                >
                    Iniciar sesión
                    <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                </Link>

            </div>

            <p class="mt-5 text-sm text-[#5B6B82]">
                ¿Quieres estudiar con nosotros?
                <Link href="/admisiones/registro" class="font-medium text-[#214EA4] hover:underline">
                    Postula aquí
                </Link>
            </p>

            <div class="mt-10 grid gap-3 sm:grid-cols-3">
                <div
                    v-for="r in roles"
                    :key="r.title"
                    class="rounded-2xl border border-[#E3E8F1] bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-[0_10px_30px_-12px_rgba(30,58,95,0.25)]"
                >
                    <div class="mb-3 grid h-9 w-9 place-items-center rounded-[10px] bg-[#E8EEFB] text-[#214EA4]">
                        <component :is="r.icon" class="h-[18px] w-[18px]" />
                    </div>
                    <p class="text-sm font-medium text-[#1E3A5F]">{{ r.title }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-[#5B6B82]">{{ r.text }}</p>
                </div>
            </div>
        </main>

        <footer class="text-xs text-[#5B6B82]/70">Acadex — sistema de gestión académica</footer>
    </div>
</template>
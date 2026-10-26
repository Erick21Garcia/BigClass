<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { BookOpen, GraduationCap, ShieldCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import acadexLogo from '@/acadex-logo-512.png';

const page = usePage();

const variant = computed<'welcome' | 'login' | 'admissions'>(() => {
    const path = page.url.split('?')[0];
    if (path === '/') return 'welcome';
    if (path.startsWith('/admisiones')) return 'admissions';
    return 'login';
});

const panelOnRight = computed(() => variant.value === 'welcome');

const contentOnRight = ref(!panelOnRight.value);
function onBeforeEnter() {
    contentOnRight.value = !panelOnRight.value;
}

const roles = [
    { icon: ShieldCheck, title: 'Administración', text: 'Matrículas, periodos y reportes' },
    { icon: BookOpen, title: 'Docentes', text: 'Calificaciones y asistencia' },
    { icon: GraduationCap, title: 'Estudiantes', text: 'Notas, horarios y trámites' },
];

const steps = [
    { title: 'Crea tu cuenta', text: 'Elige la carrera a la que deseas postular' },
    { title: 'Completa tu postulación', text: 'Registra tus datos y documentos' },
    { title: 'Sigue tu proceso', text: 'Consulta el estado de tu admisión' },
];

const chips = [
    { label: 'Matrículas', class: 'left-[8%] top-[16%]' },
    { label: 'Asistencia', class: 'right-[8%] top-[30%]' },
    { label: 'Calificaciones', class: 'left-[10%] bottom-[22%]' },
    { label: 'Horarios', class: 'right-[12%] bottom-[14%]' },
];
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="acadex-scale relative overflow-hidden bg-[#F7F9FC] text-[#0B2545]">
        <!-- Panel de marca: persistente, se desliza entre izquierda y derecha -->
        <aside
            aria-hidden="true"
            class="acadex-slide absolute inset-y-0 left-0 z-10 hidden w-1/2 overflow-hidden bg-[#1E3A5F] shadow-[0_0_60px_-10px_rgba(11,37,69,0.35)] lg:block"
            :style="{ transform: panelOnRight ? 'translateX(100%)' : 'translateX(0)' }"
        >
            <!-- Franjas diagonales: se espejan junto con el panel -->
            <div
                class="acadex-shape absolute -top-28 h-[300px] w-[300px] rotate-45 bg-[#214EA4]"
                :style="{ left: panelOnRight ? 'calc(100% - 188px)' : '-112px' }"
            ></div>
            <div
                class="acadex-shape absolute -bottom-28 h-[300px] w-[300px] rotate-45 bg-[#214EA4]"
                :style="{ left: panelOnRight ? '-112px' : 'calc(100% - 188px)' }"
            ></div>
            <div
                class="acadex-shape absolute -bottom-10 h-[170px] w-[170px] rotate-45 bg-[#214EA4]/40"
                :style="{ left: panelOnRight ? 'calc(100% - 130px)' : '-40px' }"
            ></div>
            <div
                class="absolute left-1/2 top-1/2 h-[480px] w-[480px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#8CB8FF]/15 blur-3xl"
            ></div>

            <!-- Contenido del panel: capas apiladas que se cruzan -->
            <Transition name="acadex-panel">
                <!-- Inicio -->
                <div v-if="variant === 'welcome'" key="welcome" class="absolute inset-0 grid place-items-center">
                    <div class="text-center">
                        <img
                            :src="acadexLogo"
                            alt="Acadex"
                            width="512"
                            height="512"
                            class="mx-auto h-36 w-36 rounded-[30px] object-contain shadow-[0_30px_60px_-20px_rgba(0,0,0,0.55)] ring-1 ring-white/20"
                        />
                        <p class="acadex-wordmark acadex-wordmark--light mt-8 text-[34px]">Acadex</p>
                        <p class="mx-auto mt-2 max-w-[260px] text-sm leading-relaxed text-[#8CB8FF]">
                            Toda la vida académica de tu institución, ordenada.
                        </p>
                    </div>
                    <div
                        v-for="c in chips"
                        :key="c.label"
                        :class="c.class"
                        class="absolute flex items-center gap-2.5 rounded-[14px] border border-white/15 bg-white/10 px-4 py-3 text-[13px] font-medium text-white backdrop-blur"
                    >
                        <span class="h-2 w-2 rounded-full bg-[#8CB8FF]"></span>
                        {{ c.label }}
                    </div>
                </div>

                <!-- Login -->
                <div v-else-if="variant === 'login'" key="login" class="absolute inset-0 flex flex-col px-16 py-10">
                    <div class="flex items-center gap-2.5">
                        <img :src="acadexLogo" alt="" width="36" height="36" class="h-9 w-9 rounded-[9px] object-contain" />
                        <span class="acadex-wordmark acadex-wordmark--light text-[22px]">Acadex</span>
                    </div>

                    <div class="my-auto">
                        <h2 class="acadex-wordmark acadex-wordmark--light text-[42px] leading-[1.12]">
                            Toda la vida académica,<br />
                            <span class="text-[#8CB8FF]">en un solo lugar.</span>
                        </h2>
                        <p class="mt-4 max-w-[400px] text-base leading-relaxed text-[#B9CDEE]">
                            Accede a tu panel para gestionar matrículas, calificaciones, asistencia y horarios.
                        </p>
                        <div class="mt-9 grid max-w-[420px] gap-3.5">
                            <div
                                v-for="r in roles"
                                :key="r.title"
                                class="flex items-center gap-3.5 rounded-[14px] border border-white/15 bg-white/10 px-4 py-3.5"
                            >
                                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-[10px] bg-[#8CB8FF]/20 text-[#8CB8FF]">
                                    <component :is="r.icon" class="h-[18px] w-[18px]" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white">{{ r.title }}</p>
                                    <p class="mt-0.5 text-xs text-[#B9CDEE]">{{ r.text }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-[#8CB8FF]">Acadex — sistema de gestión académica</p>
                </div>

                <!-- Admisiones -->
                <div v-else key="admissions" class="absolute inset-0 flex flex-col px-16 py-10">
                    <div class="flex items-center gap-2.5">
                        <img :src="acadexLogo" alt="" width="36" height="36" class="h-9 w-9 rounded-[9px] object-contain" />
                        <span class="acadex-wordmark acadex-wordmark--light text-xl">Acadex</span>
                        <span class="ml-1.5 rounded-full border border-[#8CB8FF]/40 px-2.5 py-[3px] text-xs font-medium text-[#8CB8FF]">
                            Admisiones
                        </span>
                    </div>

                    <img
                        src="/images/admissions-graduation-light.svg"
                        alt=""
                        class="pointer-events-none absolute bottom-24 right-11 w-[220px] select-none opacity-90"
                    />

                    <div class="my-auto">
                        <h2 class="acadex-wordmark acadex-wordmark--light text-[42px] leading-[1.12]">
                            Da el primer paso<br />
                            <span class="text-[#8CB8FF]">hacia tu carrera.</span>
                        </h2>
                        <p class="mt-4 max-w-[400px] text-base leading-relaxed text-[#B9CDEE]">
                            Crea tu cuenta y empieza tu proceso de admisión en pocos minutos.
                        </p>
                        <ol class="mt-9 max-w-[380px]">
                            <li
                                v-for="(s, i) in steps"
                                :key="s.title"
                                class="relative flex gap-4 pb-6 last:pb-0"
                                :class="{
                                    'before:absolute before:bottom-0.5 before:left-[15px] before:top-[34px] before:w-[1.5px] before:bg-[#8CB8FF]/30':
                                        i < steps.length - 1,
                                }"
                            >
                                <span
                                    class="grid h-8 w-8 shrink-0 place-items-center rounded-full border-[1.5px] text-[13px] font-medium"
                                    :class="
                                        i === 0
                                            ? 'border-[#8CB8FF] bg-[#8CB8FF] text-[#1E3A5F]'
                                            : 'border-[#8CB8FF]/50 text-[#8CB8FF]'
                                    "
                                >
                                    {{ i + 1 }}
                                </span>
                                <div>
                                    <p class="mt-1 text-[14.5px] font-medium text-white">{{ s.title }}</p>
                                    <p class="mt-0.5 text-[12.5px] text-[#B9CDEE]">{{ s.text }}</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <p class="text-xs text-[#8CB8FF]">Acadex — sistema de gestión académica</p>
                </div>
            </Transition>
        </aside>

        <!-- Columna de contenido: aquí entra cada página -->
        <main
            class="relative z-0 grid w-full lg:w-1/2"
            :class="contentOnRight ? 'lg:ml-[50%]' : ''"
            style="min-height: inherit"
        >
            <Transition name="acadex-page" mode="out-in" @before-enter="onBeforeEnter">
                <slot />
            </Transition>
        </main>
    </div>
</template>

<style>
.acadex-scale {
    --scale: 0.8;
    zoom: var(--scale);
    min-height: calc(100vh / var(--scale));
}

.acadex-wordmark {
    font-family: 'Fraunces', 'Playfair Display', serif;
    font-weight: 600;
    color: #0b2545;
}

.acadex-wordmark--light {
    color: #fff;
}

/* Deslizamiento del panel y de sus franjas */
.acadex-slide {
    transition: transform 0.85s cubic-bezier(0.77, 0, 0.18, 1);
}
.acadex-shape {
    transition: left 0.85s cubic-bezier(0.77, 0, 0.18, 1);
}

/* Contenido del panel: sale rápido, entra cuando el panel ya casi llegó */
.acadex-panel-enter-active {
    transition: opacity 0.45s ease-out 0.35s;
}
.acadex-panel-leave-active {
    transition: opacity 0.4s ease-in;
}
.acadex-panel-enter-from,
.acadex-panel-leave-to {
    opacity: 0;
}

/* Contenido de la página */
.acadex-page-enter-active {
    transition:
        opacity 0.45s ease-out,
        transform 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}
.acadex-page-leave-active {
    transition:
        opacity 0.22s ease-in,
        transform 0.22s ease-in;
}
.acadex-page-enter-from {
    opacity: 0;
    transform: translateY(16px);
}
.acadex-page-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

@media (prefers-reduced-motion: reduce) {
    .acadex-slide,
    .acadex-shape,
    .acadex-panel-enter-active,
    .acadex-panel-leave-active,
    .acadex-page-enter-active,
    .acadex-page-leave-active {
        transition-duration: 0.01ms !important;
        transition-delay: 0s !important;
    }
}
</style>
<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, LayoutDashboard, LogOut, User } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard, logout } from '@/routes';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Spinner } from '@/components/ui/spinner';
import { clearLmsBanner, lmsBanner, type LmsTone } from '@/composables/useLmsBanner';
import acadexLogo from '@/acadex-logo-512.png';

const props = withDefaults(
    defineProps<{
        title?: string;
        userInitials?: string;
    }>(),
    {
        title: undefined,
        userInitials: '',
    },
);

const page = usePage();
const banner = lmsBanner;

const handleLogout = () => {
    router.flushAll();
};

const initialsOf = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('');

const userInitials = computed(() => {
    if (props.userInitials) return props.userInitials;
    const name = (page.props as { auth?: { user?: { name?: string } } }).auth?.user?.name;
    return name ? initialsOf(name) : '';
});

const depth = (url: string) => {
    const path = url.split('?')[0];
    if (path.includes('mis-materias')) return 0;
    if (path.includes('quiz-attempts')) return 2;
    return 1;
};

const direction = ref<'forward' | 'back'>('forward');
watch(
    () => page.url,
    (to, from) => {
        direction.value = depth(to) >= depth(from) ? 'forward' : 'back';
    },
);

const inner = ref<HTMLElement | null>(null);
const bannerHeight = ref<number | null>(null);
let observer: ResizeObserver | null = null;

onMounted(() => {
    observer = new ResizeObserver(([entry]) => {
        const h = entry.borderBoxSize?.[0]?.blockSize ?? (entry.target as HTMLElement).offsetHeight;
        if (h > 40) bannerHeight.value = h;
    });
    watch(
        inner,
        (el) => {
            observer?.disconnect();
            if (el) observer?.observe(el);
        },
        { immediate: true },
    );
});

onBeforeUnmount(() => {
    observer?.disconnect();
    clearLmsBanner();
});

const bannerStyle = computed(() => {
    if (!banner.value) return { height: '0px' };
    return { height: bannerHeight.value !== null ? `${bannerHeight.value}px` : 'auto' };
});

const chipTone: Record<LmsTone, string> = {
    plain: 'bg-white/[0.13] text-[#E4EDFC]',
    green: 'border border-[#5BE3A4]/40 bg-[#5BE3A4]/20 text-[#C8F7E1]',
    amber: 'border border-[#F2A81D]/50 bg-[#F2A81D]/25 text-[#FFE3A6]',
    red: 'border border-red-300/50 bg-red-400/25 text-red-100',
    blue: 'border border-[#8CB8FF]/50 bg-[#8CB8FF]/25 text-[#DDEBFF]',
};

const RING = 2 * Math.PI * 42;
const ringDash = (progress: number) => (Math.min(100, Math.max(0, progress)) / 100) * RING;
</script>

<template>
    <Head :title="title">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="lms-shell min-h-screen bg-[#1E3A5F]" :data-dir="direction">
        <header class="flex h-14 items-center justify-between px-7 text-white">
            <div class="flex items-center gap-3">
                <img :src="acadexLogo" alt="" width="30" height="30" class="h-[30px] w-[30px] rounded-lg object-contain" />
                <span class="lms-wordmark text-[15px]">Acadex — Aula Virtual</span>
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        aria-label="Menú de usuario"
                        class="grid size-[30px] cursor-pointer place-items-center rounded-full border border-white/30 bg-white/[0.18] text-[11.5px] font-semibold text-white transition hover:bg-white/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#8CB8FF] data-[state=open]:bg-white/30"
                    >
                        <template v-if="userInitials">{{ userInitials }}</template>
                        <User v-else class="size-4" />
                    </button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="min-w-52 rounded-xl border-[#D8E2F4] p-1.5">
                    <DropdownMenuItem
                        :as-child="true"
                        class="cursor-pointer rounded-lg text-[#1E3A5F] focus:bg-[#E8EEFB] focus:text-[#1E3A5F]"
                    >
                        <Link class="flex w-full items-center gap-2" :href="dashboard()" as="button">
                            <LayoutDashboard class="size-4" />
                            Volver a mi dashboard
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        :as-child="true"
                        class="cursor-pointer rounded-lg text-[#1E3A5F] focus:bg-[#E8EEFB] focus:text-[#1E3A5F]"
                    >
                        <Link
                            class="flex w-full items-center gap-2"
                            :href="logout()"
                            as="button"
                            data-test="logout-button"
                            @click="handleLogout"
                        >
                            <LogOut class="size-4" />
                            Cerrar sesión
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </header>

        <div class="min-h-[calc(100vh-3.5rem)] rounded-t-[18px] bg-[#EAF0FB] px-4 py-6 sm:px-8">
            <div
                class="overflow-hidden transition-[height,margin,opacity] duration-500 ease-[cubic-bezier(0.77,0,0.18,1)]"
                :class="banner?.back ? 'mb-3.5 h-9 opacity-100' : 'mb-0 h-0 opacity-0'"
                :aria-hidden="!banner?.back"
            >
                <Link
                    href="/lms/mis-materias"
                    :tabindex="banner?.back ? 0 : -1"
                    class="inline-flex h-9 items-center gap-2 rounded-full border border-[#D8E2F4] bg-white px-4 text-[13.5px] font-medium text-[#1E3A5F] transition hover:border-[#214EA4]/40"
                >
                    <ArrowLeft class="size-[15px]" />
                    Volver a mis materias
                </Link>
            </div>

            <div
                class="lms-banner-box relative overflow-hidden rounded-[20px] bg-[linear-gradient(120deg,#1E3A5F,#214EA4)] text-white"
                :class="banner ? 'mb-4' : 'mb-0'"
                :style="bannerStyle"
            >
                <div class="pointer-events-none absolute -right-14 -top-24 h-[220px] w-[220px] rotate-45 bg-[#2F63C9]/80"></div>
                <div class="pointer-events-none absolute -top-32 right-[190px] h-[170px] w-[170px] rotate-45 bg-[#8CB8FF]/20"></div>
                <div class="pointer-events-none absolute -bottom-24 -left-12 h-[150px] w-[150px] rotate-45 bg-[#2F63C9]/55"></div>

                <div ref="inner" class="relative">
                    <Transition name="lms-banner" mode="out-in">
                        <div
                            v-if="banner"
                            :key="banner.title"
                            class="flex flex-wrap items-center justify-between gap-6 px-10 py-6"
                        >
                            <div class="min-w-0">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full border border-white/30 bg-white/15 px-3 py-1 text-xs font-medium"
                                >
                                    <component :is="banner.tag.icon" class="size-3.5" />
                                    {{ banner.tag.label }}
                                </span>

                                <h1 class="lms-wordmark mt-2.5 text-[40px] leading-[1.1]">{{ banner.title }}</h1>

                                <div v-if="banner.teacher" class="mt-3 flex items-center gap-2.5">
                                    <span
                                        class="grid size-[42px] shrink-0 place-items-center rounded-full bg-[#8CB8FF] text-sm font-semibold text-[#0B2545]"
                                    >
                                        {{ initialsOf(banner.teacher) }}
                                    </span>
                                    <div>
                                        <p class="text-base font-medium">{{ banner.teacher }}</p>
                                        <p class="text-[13px] text-[#C9DAF7]">Docente</p>
                                    </div>
                                </div>

                                <div v-if="banner.chips?.length" class="mt-3.5 flex flex-wrap gap-2.5">
                                    <span
                                        v-for="chip in banner.chips"
                                        :key="chip.label"
                                        :class="[
                                            chipTone[chip.tone ?? 'plain'],
                                            'inline-flex items-center gap-2 rounded-full px-3.5 py-[7px] text-[13.5px]',
                                        ]"
                                    >
                                        <component :is="chip.icon" v-if="chip.icon" class="size-[15px]" />
                                        {{ chip.label }}
                                    </span>
                                </div>

                                <button
                                    v-if="banner.action"
                                    type="button"
                                    class="mt-4 inline-flex h-11 cursor-pointer items-center gap-2 rounded-xl bg-white px-6 text-[15px] font-semibold text-[#1E3A5F] shadow-[0_10px_20px_-10px_rgba(0,0,0,0.5)] transition hover:bg-[#EAF0FB] disabled:opacity-60"
                                    :disabled="banner.action.loading"
                                    @click="banner.action.onClick()"
                                >
                                    <Spinner v-if="banner.action.loading" />
                                    {{ banner.action.label }}
                                    <ArrowRight v-if="!banner.action.loading" class="size-4" />
                                </button>
                            </div>

                            <div v-if="banner.ring" class="text-center">
                                <svg width="112" height="112" viewBox="0 0 104 104">
                                    <circle cx="52" cy="52" r="42" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="10" />
                                    <circle
                                        cx="52"
                                        cy="52"
                                        r="42"
                                        fill="none"
                                        stroke="#8CB8FF"
                                        stroke-width="10"
                                        stroke-linecap="round"
                                        transform="rotate(-90 52 52)"
                                        class="lms-ring"
                                        :style="{
                                            strokeDasharray: `${ringDash(banner.ring.progress)} ${RING}`,
                                            opacity: banner.ring.progress > 0 ? 1 : 0,
                                        }"
                                    />
                                    <text
                                        x="52"
                                        y="59"
                                        text-anchor="middle"
                                        font-weight="600"
                                        fill="#fff"
                                        :font-size="banner.ring.text.length > 4 ? 20 : 24"
                                    >
                                        {{ banner.ring.text }}
                                    </text>
                                </svg>
                                <p class="mt-1 max-w-[140px] text-[13px] leading-snug text-[#C9DAF7]">
                                    {{ banner.ring.caption }}
                                </p>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <Transition name="lms-page" mode="out-in">
                <slot />
            </Transition>
        </div>
    </div>
</template>

<style>
.lms-wordmark {
    font-family: 'Fraunces', serif;
    font-weight: 600;
}

/* Altura y margen del banner */
.lms-banner-box {
    transition:
        height 0.55s cubic-bezier(0.77, 0, 0.18, 1),
        margin 0.55s cubic-bezier(0.77, 0, 0.18, 1);
}
.lms-ring {
    transition:
        stroke-dasharray 0.6s ease,
        opacity 0.3s ease;
}

/* Contenido del banner: sale rápido y entra después */
.lms-banner-enter-active {
    transition:
        opacity 0.4s ease-out,
        transform 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}
.lms-banner-leave-active {
    transition:
        opacity 0.2s ease-in,
        transform 0.2s ease-in;
}
.lms-banner-enter-from {
    opacity: 0;
    transform: translateX(30px);
}
.lms-banner-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

/* Contenido de la vista: entra con una pequeña espera, para coincidir con el banner */
.lms-page-enter-active {
    transition:
        opacity 0.45s ease-out 0.18s,
        transform 0.45s cubic-bezier(0.22, 1, 0.36, 1) 0.18s;
}
.lms-page-leave-active {
    transition:
        opacity 0.22s ease-in,
        transform 0.22s ease-in;
}
.lms-page-enter-from {
    opacity: 0;
    transform: translateX(44px) translateY(14px);
}
.lms-page-leave-to {
    opacity: 0;
    transform: translateX(-44px);
}

/* Al volver, todo se mueve en sentido contrario */
[data-dir='back'] .lms-banner-enter-from {
    transform: translateX(-30px);
}
[data-dir='back'] .lms-banner-leave-to {
    transform: translateX(30px);
}
[data-dir='back'] .lms-page-enter-from {
    transform: translateX(-44px) translateY(14px);
}
[data-dir='back'] .lms-page-leave-to {
    transform: translateX(44px);
}

@media (prefers-reduced-motion: reduce) {
    .lms-banner-box,
    .lms-ring,
    .lms-banner-enter-active,
    .lms-banner-leave-active,
    .lms-page-enter-active,
    .lms-page-leave-active {
        transition-duration: 0.01ms !important;
        transition-delay: 0s !important;
    }
}
</style>
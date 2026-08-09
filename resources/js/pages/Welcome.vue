<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import acadexLogo from '@/acadex-logo-512.png';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Bienvenido">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="acadex-welcome flex min-h-screen flex-col bg-[#F7F9FC] px-6 py-6 text-[#0B2545] lg:px-10 lg:py-8">
        <!-- Wordmark -->
        <header class="flex w-full items-center justify-between">
            <span class="acadex-wordmark text-xl tracking-tight lg:text-2xl">Acadex</span>

            <Link
                v-if="$page.props.auth.user"
                :href="dashboard()"
                class="rounded-full border border-[#1E4C8A]/20 px-4 py-1.5 text-sm font-medium text-[#1E4C8A] transition-colors hover:border-[#1E4C8A]/40 hover:bg-[#1E4C8A]/5"
            >
                Ir al panel
            </Link>
        </header>

        <!-- Centered stage -->
        <main class="flex flex-1 flex-col items-center justify-center gap-8 text-center">
            <!-- Logo importado como asset de Vite -->
            <img
                :src="acadexLogo"
                alt="Acadex"
                width="512"
                height="512"
                class="acadex-mark h-28 w-28 rounded-3xl object-contain shadow-[0_16px_40px_-12px_rgba(30,58,95,0.45)] lg:h-32 lg:w-32"
            />

            <div class="flex flex-col items-center gap-3">
                <h1 class="acadex-wordmark text-3xl leading-tight lg:text-4xl">
                    Bienvenido de nuevo
                </h1>
                <p class="max-w-sm text-[15px] leading-relaxed text-[#5B6B82]">
                    Un solo lugar para administración, docentes y estudiantes.
                </p>
            </div>

            <div class="flex flex-col items-center gap-3">
                <Link
                    :href="login()"
                    class="acadex-cta inline-flex items-center gap-2 rounded-full px-7 py-3 text-[15px] font-semibold text-white shadow-[0_8px_24px_-8px_rgba(30,76,138,0.6)] transition-transform hover:-translate-y-0.5"
                >
                    Iniciar sesión
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </Link>

                <Link
                    v-if="canRegister"
                    :href="register()"
                    class="text-sm font-medium text-[#5B6B82] underline-offset-4 transition-colors hover:text-[#1E4C8A] hover:underline"
                >
                    Crear cuenta
                </Link>
            </div>
        </main>

        <footer class="pb-2 text-center text-xs text-[#5B6B82]/70">
            Acadex — sistema de gestión académica
        </footer>
    </div>
</template>

<style scoped>
.acadex-wordmark {
    font-family: 'Fraunces', serif;
    font-weight: 600;
    color: #0b2545;
}

.acadex-cta {
    background: linear-gradient(135deg, #1e4c8a, #4c7fc2);
}

.acadex-mark {
    animation: acadex-rise 700ms ease-out both;
}

@keyframes acadex-rise {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.94);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .acadex-mark {
        animation: none;
        opacity: 1;
    }
}
</style>
<script setup lang="ts">
import { home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import acadexLogo from '@/acadex-logo-512.png';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <img
                            :src="acadexLogo"
                            alt="Acadex"
                            width="512"
                            height="512"
                            class="acadex-mark h-16 w-16 rounded-2xl object-contain shadow-[0_10px_28px_-10px_rgba(30,58,95,0.45)]"
                        />
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="acadex-wordmark text-xl">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
.acadex-wordmark {
    font-family: 'Fraunces', serif;
    font-weight: 600;
    color: #1e3a5f;
}

.acadex-mark {
    animation: acadex-rise 600ms ease-out both;
}

@keyframes acadex-rise {
    from {
        opacity: 0;
        transform: translateY(8px) scale(0.94);
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
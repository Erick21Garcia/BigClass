<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        title?: string;
    }>(),
    {
        title: 'Admisiones',
    },
);
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

    <div class="admissions-shell min-h-screen bg-[#F7F9FC]">
        <header class="flex items-center gap-2.5 bg-[#1E3A5F] px-6 py-3">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-[11px] font-medium text-[#1E3A5F]">
                AX
            </div>
            <span class="admissions-wordmark text-sm text-white">Acadex — Admisiones</span>
        </header>

        <main class="mx-auto max-w-2xl px-6 py-8">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.admissions-wordmark {
    font-family: 'Fraunces', serif;
    font-weight: 500;
}
</style>
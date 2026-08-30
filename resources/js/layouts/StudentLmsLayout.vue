<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        title?: string;
        userInitials?: string;
    }>(),
    {
        title: 'Aula Virtual',
        userInitials: '',
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

    <div class="lms-shell min-h-screen bg-[#F7F9FC]">
        <header class="flex items-center justify-between bg-[#1E3A5F] px-6 py-3">
            <div class="flex items-center gap-2.5">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-[11px] font-medium text-[#1E3A5F]">
                    AX
                </div>
                <span class="lms-wordmark text-sm text-white">Acadex — Aula Virtual</span>
            </div>
            <div
                v-if="userInitials"
                class="flex h-7 w-7 items-center justify-center rounded-full bg-[#93C5FD] text-[11px] font-medium text-[#1E3A5F]"
            >
                {{ userInitials }}
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-6">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.lms-wordmark {
    font-family: 'Fraunces', serif;
    font-weight: 500;
}
</style>
<script setup lang="ts">
import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'vue';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <thead :class="cn('[&_tr]:border-b', props.class)">
        <slot />
    </thead>
</template>
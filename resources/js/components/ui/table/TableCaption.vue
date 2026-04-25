<script setup lang="ts">
import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'vue';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <caption :class="cn('mt-4 text-sm text-muted-foreground', props.class)">
        <slot />
    </caption>
</template>
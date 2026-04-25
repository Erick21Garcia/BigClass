<script setup lang="ts">
import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'vue';

interface Props {
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <tbody :class="cn('[&_tr:last-child]:border-0', props.class)">
        <slot />
    </tbody>
</template>
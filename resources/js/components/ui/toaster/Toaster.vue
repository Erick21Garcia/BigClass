<script setup lang="ts">
import { Toaster as Sonner } from 'vue-sonner';

type ToasterProps = {
    position?: 
        | 'top-left'
        | 'top-right'
        | 'bottom-left'
        | 'bottom-right'
        | 'top-center'
        | 'bottom-center';
    richColors?: boolean;
    duration?: number;
    closeButton?: boolean;
    theme?: 'light' | 'dark' | 'system';
};

const props = withDefaults(defineProps<ToasterProps>(), {
    position: 'top-center',
    richColors: true,
    duration: 3000,
    closeButton: true,
    theme: 'system',
});

</script>

<template>
    <Sonner
        :position="position"
        :rich-colors="richColors"
        :duration="duration"
        :close-button="closeButton"
        :theme="theme"
    />
</template>
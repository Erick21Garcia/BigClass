<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    settings: { abandon_after_days: number; retention_after_months: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Configuración de admisiones', href: '/admisiones/configuracion' }];

const form = useForm({ ...props.settings });

function submit() {
    form.put('/admisiones/configuracion');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-md p-6">
            <h1 class="mb-4 text-xl font-medium text-[#1E3A5F]">Configuración de admisiones</h1>

            <div class="rounded-xl border border-[#EAEAE5] bg-white p-5">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="abandon_after_days">Días de inactividad antes de marcar como abandonada</Label>
                        <Input id="abandon_after_days" type="number" min="1" v-model.number="form.abandon_after_days" />
                        <p v-if="form.errors.abandon_after_days" class="text-xs text-[#D85A30]">
                            {{ form.errors.abandon_after_days }}
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="retention_after_months">Meses de retención antes de eliminar</Label>
                        <Input id="retention_after_months" type="number" min="1" v-model.number="form.retention_after_months" />
                        <p v-if="form.errors.retention_after_months" class="text-xs text-[#D85A30]">
                            {{ form.errors.retention_after_months }}
                        </p>
                    </div>

                    <Button
                        class="mt-1 w-fit bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Guardar
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
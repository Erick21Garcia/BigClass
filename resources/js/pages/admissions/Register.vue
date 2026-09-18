<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AdmissionsLayout from '@/layouts/AdmissionsLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    careers: { id: number; name: string }[];
}>();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    career_id: '',
});

function submit() {
    form.post('/admisiones/registro');
}
</script>

<template>
    <AdmissionsLayout title="Regístrate para postular">
        <div class="rounded-xl border border-[#EAEAE5] bg-white p-6">
            <p class="admissions-wordmark mb-1 text-lg text-[#1E3A5F]">Regístrate para postular</p>
            <p class="mb-5 text-sm text-[#5B6B82]">
                Crea tu cuenta para empezar tu proceso de admisión.
            </p>

            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="name">Nombre completo</Label>
                    <Input id="name" v-model="form.name" />
                    <p v-if="form.errors.name" class="text-xs text-[#D85A30]">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-2">
                    <Label for="email">Correo electrónico</Label>
                    <Input id="email" type="email" v-model="form.email" />
                    <p v-if="form.errors.email" class="text-xs text-[#D85A30]">{{ form.errors.email }}</p>
                </div>

                <div class="grid gap-2">
                    <Label for="career">Carrera a la que deseas postular</Label>
                    <select id="career" v-model="form.career_id" class="rounded-md border px-2 py-1.5 text-sm">
                        <option value="" disabled>Selecciona una carrera</option>
                        <option v-for="c in careers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <p v-if="form.errors.career_id" class="text-xs text-[#D85A30]">{{ form.errors.career_id }}</p>
                </div>

                <div class="grid gap-2">
                    <Label for="password">Contraseña</Label>
                    <Input id="password" type="password" v-model="form.password" />
                    <p v-if="form.errors.password" class="text-xs text-[#D85A30]">{{ form.errors.password }}</p>
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirmar contraseña</Label>
                    <Input id="password_confirmation" type="password" v-model="form.password_confirmation" />
                </div>

                <Button
                    class="mt-1 w-full bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Crear cuenta y empezar
                </Button>
            </div>
        </div>
    </AdmissionsLayout>
</template>

<style scoped>
.admissions-wordmark {
    font-family: 'Fraunces', serif;
    font-weight: 500;
}
</style>
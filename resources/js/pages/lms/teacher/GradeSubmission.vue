<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    submission: {
        id: number;
        student_name: string;
        assignment_title: string;
        submitted_at: string;
        is_late: boolean;
        grade: number | null;
        feedback: string | null;
        files: { id: number; original_name: string }[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mis materias', href: '/lms/mis-materias-docente' },
    { title: props.submission.assignment_title, href: `/lms/submissions/${props.submission.id}/calificar` },
];

const form = useForm({
    grade: props.submission.grade ?? '',
    feedback: props.submission.feedback ?? '',
});

function submit() {
    form.post(`/lms/submissions/${props.submission.id}/grade`);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <p class="text-xs text-[#5B6B82]">{{ submission.assignment_title }}</p>
            <h1 class="mb-4 text-xl font-medium text-[#1E3A5F]">{{ submission.student_name }}</h1>

            <div class="rounded-xl border border-[#EAEAE5] bg-white p-4">
                <p class="text-xs" :class="submission.is_late ? 'text-[#D85A30]' : 'text-[#3B8A5A]'">
                    {{ submission.is_late ? 'Entregado con retraso' : 'Entregado' }}
                    — {{ new Date(submission.submitted_at).toLocaleString() }}
                </p>

                <ul class="mt-3 flex flex-col gap-1">
                    <li v-for="f in submission.files" :key="f.id" class="text-sm text-[#1E3A5F]">
                        <i class="ti ti-paperclip" /> {{ f.original_name }}
                    </li>
                </ul>

                <div class="mt-5 grid gap-3 border-t border-[#EAEAE5] pt-4">
                    <div class="grid gap-2">
                        <Label for="grade">Nota</Label>
                        <Input id="grade" type="number" min="0" max="10" step="0.1" v-model="form.grade" />
                        <p v-if="form.errors.grade" class="text-xs text-[#D85A30]">{{ form.errors.grade }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="feedback">Retroalimentación</Label>
                        <textarea
                            id="feedback"
                            v-model="form.feedback"
                            rows="3"
                            class="rounded-md border border-[#EAEAE5] p-2 text-sm"
                        />
                    </div>
                    <Button
                        class="w-fit bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Guardar calificación
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<script setup lang="ts">
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem } from '@/types';

interface EssayAnswer {
    question_id: number;
    question: string;
    written_answer: string | null;
    max_points: number;
}

const props = defineProps<{
    attempt: { id: number; student_name: string; quiz_title: string; auto_score: number };
    essay_answers: EssayAnswer[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mis materias', href: '/lms/mis-materias-docente' },
    { title: props.attempt.quiz_title, href: `/lms/quiz-attempts/${props.attempt.id}/calificar` },
];

const points = reactive<Record<number, number>>(
    Object.fromEntries(props.essay_answers.map((a) => [a.question_id, 0])),
);

const form = useForm({ points: {} as typeof points });

function submit() {
    form.points = points;
    form.post(`/lms/quiz-attempts/${props.attempt.id}/grade-essay`);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <p class="text-xs text-[#5B6B82]">{{ attempt.quiz_title }}</p>
            <h1 class="mb-1 text-xl font-medium text-[#1E3A5F]">{{ attempt.student_name }}</h1>
            <p class="mb-4 text-xs text-[#8A8A85]">Puntos de opción múltiple (automático): {{ attempt.auto_score }}</p>

            <div class="flex flex-col gap-3">
                <div
                    v-for="a in essay_answers"
                    :key="a.question_id"
                    class="rounded-xl border border-[#EAEAE5] bg-white p-4"
                >
                    <p class="text-sm font-medium text-[#1E3A5F]">{{ a.question }}</p>
                    <p class="mt-2 rounded-md bg-[#F7F9FC] p-2.5 text-sm text-[#5B6B82]">
                        {{ a.written_answer || '(sin respuesta)' }}
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <Input
                            type="number"
                            min="0"
                            :max="a.max_points"
                            step="0.5"
                            class="w-24"
                            v-model.number="points[a.question_id]"
                        />
                        <span class="text-xs text-[#8A8A85]">/ {{ a.max_points }} pts</span>
                    </div>
                </div>
            </div>

            <Button
                class="mt-5 bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                :disabled="form.processing"
                @click="submit"
            >
                Guardar calificación
            </Button>
        </div>
    </AppLayout>
</template>
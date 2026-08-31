<script setup lang="ts">
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem } from '@/types';

interface McAnswer {
    question: string;
    selected_text: string | null;
    correct_text: string | null;
    is_correct: boolean;
    points_awarded: number;
    max_points: number;
}

interface EssayAnswer {
    question_id: number;
    question: string;
    written_answer: string | null;
    max_points: number;
}

const props = defineProps<{
    attempt: { id: number; student_name: string; quiz_title: string; auto_score: number };
    multiple_choice_answers: McAnswer[];
    essay_answers: EssayAnswer[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mis materias', href: '/lms/mis-materias-docente' },
    { title: props.attempt.quiz_title, href: `/lms/quiz-attempts/${props.attempt.id}/calificar` },
];

const mcMaxTotal = props.multiple_choice_answers.reduce((sum, a) => sum + Number(a.max_points), 0);
const mcCorrectCount = props.multiple_choice_answers.filter((a) => a.is_correct).length;

// Muestra "3" en vez de "3.00" cuando el número es entero, pero conserva
// el decimal si de verdad lo tiene (ej: "2.5").
function formatPoints(value: number): string {
    const n = Number(value);
    return Number.isInteger(n) ? String(n) : n.toFixed(1);
}

// undefined = todavía no la toca el docente (para no confundir "en blanco"
// con "calificada con 0" sin querer). Se usa undefined y no null porque
// el componente Input solo acepta string | number | undefined.
const points = reactive<Record<number, number | undefined>>(
    Object.fromEntries(props.essay_answers.map((a) => [a.question_id, undefined])),
);

const allGraded = () =>
    props.essay_answers.every((a) => points[a.question_id] !== undefined);

const form = useForm({ points: {} as Record<number, number> });

function submit() {
    form.points = points as Record<number, number>;
    form.post(`/lms/quiz-attempts/${props.attempt.id}/grade-essay`);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <p class="text-xs text-[#5B6B82]">{{ attempt.quiz_title }}</p>
            <h1 class="mb-6 text-xl font-medium text-[#1E3A5F]">{{ attempt.student_name }}</h1>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_420px]">
                <!-- Detalle de opción múltiple (autocalificado) -->
                <div>
                    <div class="mb-4 grid grid-cols-3 gap-3">
                        <div class="rounded-lg border border-[#EAEAE5] bg-white p-3 text-center">
                            <p class="text-lg font-medium text-[#1E3A5F]">{{ mcCorrectCount }}/{{ multiple_choice_answers.length }}</p>
                            <p class="text-[11px] text-[#8A8A85]">Correctas</p>
                        </div>
                        <div class="rounded-lg border border-[#EAEAE5] bg-white p-3 text-center">
                            <p class="text-lg font-medium text-[#1E3A5F]">{{ formatPoints(attempt.auto_score) }}/{{ formatPoints(mcMaxTotal) }}</p>
                            <p class="text-[11px] text-[#8A8A85]">Puntos automáticos</p>
                        </div>
                        <div class="rounded-lg border border-[#EAEAE5] bg-white p-3 text-center">
                            <p class="text-lg font-medium text-[#1E3A5F]">{{ essay_answers.length }}</p>
                            <p class="text-[11px] text-[#8A8A85]">Por calificar</p>
                        </div>
                    </div>

                    <p v-if="multiple_choice_answers.length" class="mb-2 text-xs font-medium text-[#1E3A5F]">
                        Opción múltiple (automático)
                    </p>
                    <div class="flex flex-col gap-2">
                        <div
                            v-for="(a, i) in multiple_choice_answers"
                            :key="i"
                            class="rounded-lg border bg-white p-3"
                            :class="a.is_correct ? 'border-[#3B8A5A]/30' : 'border-[#D85A30]/30'"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm text-[#1E3A5F]">{{ a.question }}</p>
                                <i
                                    :class="a.is_correct ? 'ti ti-circle-check text-[#3B8A5A]' : 'ti ti-circle-x text-[#D85A30]'"
                                    class="mt-0.5 shrink-0 text-[16px]"
                                />
                            </div>
                            <p class="mt-1.5 text-xs text-[#5B6B82]">
                                Respondió: <span class="font-medium">{{ a.selected_text ?? '(sin responder)' }}</span>
                            </p>
                            <p v-if="!a.is_correct" class="text-xs text-[#3B8A5A]">
                                Correcta: <span class="font-medium">{{ a.correct_text }}</span>
                            </p>
                            <p class="mt-1 text-[11px] text-[#8A8A85]">{{ formatPoints(a.points_awarded) }}/{{ formatPoints(a.max_points) }} pts</p>
                        </div>
                    </div>
                </div>

                <!-- Calificar ensayos -->
                <div class="h-fit rounded-lg border border-[#EAEAE5] bg-white p-4 lg:sticky lg:top-6">
                    <p class="mb-3 text-sm font-medium text-[#1E3A5F]">Calificar respuestas escritas</p>

                    <div class="flex flex-col gap-3">
                        <div v-for="a in essay_answers" :key="a.question_id">
                            <p class="text-sm font-medium text-[#1E3A5F]">{{ a.question }}</p>
                            <p class="mt-1 rounded-md bg-[#F7F9FC] p-2.5 text-sm text-[#5B6B82]">
                                {{ a.written_answer || '(sin respuesta)' }}
                            </p>
                            <div class="mt-2 flex items-center gap-2">
                                <Input
                                    type="number"
                                    min="0"
                                    :max="a.max_points"
                                    step="0.5"
                                    class="w-24"
                                    placeholder="—"
                                    v-model.number="points[a.question_id]"
                                />
                                <span class="text-xs text-[#8A8A85]">/ {{ formatPoints(a.max_points) }} pts</span>
                                <span v-if="points[a.question_id] === undefined" class="text-[11px] text-[#D8A030]">
                                    Sin calificar
                                </span>
                            </div>
                        </div>

                        <p v-if="essay_answers.length === 0" class="text-sm text-[#8A8A85]">
                            Este intento no tiene respuestas de ensayo por calificar.
                        </p>

                        <p v-else-if="!allGraded()" class="text-xs text-[#D8A030]">
                            Falta calificar {{ essay_answers.filter((a) => points[a.question_id] === undefined).length }}
                            respuesta(s) antes de guardar.
                        </p>

                        <Button
                            class="mt-1 w-full bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                            :disabled="form.processing || !allGraded()"
                            @click="submit"
                        >
                            Guardar calificación
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
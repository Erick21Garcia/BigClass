<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import StudentLmsLayout from '@/layouts/StudentLmsLayout.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    virtual_course_id: number;
    quiz: { id: number; title: string; description: string | null; max_attempts: number };
    attempts_used: number;
    in_progress_attempt_id: number | null;
    attempts: { attempt_number: number; status: string; final_score: number | null }[];
}>();

const statusLabel: Record<string, string> = {
    in_progress: 'En progreso',
    pending_review: 'En revisión',
    graded: 'Calificado',
};

function startAttempt() {
    router.post(`/lms/quizzes/${props.quiz.id}/attempts`);
}

function continueAttempt() {
    router.get(`/lms/quiz-attempts/${props.in_progress_attempt_id}/resolver`);
}

const canStart = props.attempts_used < props.quiz.max_attempts && !props.in_progress_attempt_id;
</script>

<template>
    <StudentLmsLayout :title="quiz.title">
        <div class="mx-auto max-w-2xl">
            <a :href="`/lms/mis-materias`" class="mb-4 inline-block text-xs text-[#5B6B82] hover:underline">
                ← Volver a mis materias
            </a>

            <div class="rounded-xl border border-[#EAEAE5] bg-white p-5">
                <p class="lms-wordmark text-lg text-[#1E3A5F]">{{ quiz.title }}</p>
                <p class="mt-1 text-xs text-[#8A8A85]">
                    {{ attempts_used }} de {{ quiz.max_attempts }} intento(s) usados
                </p>
                <p v-if="quiz.description" class="mt-3 text-sm text-[#5B6B82]">{{ quiz.description }}</p>

                <div v-if="attempts.length" class="mt-4 flex flex-col gap-1.5 border-t border-[#EAEAE5] pt-4">
                    <div
                        v-for="a in attempts"
                        :key="a.attempt_number"
                        class="flex items-center justify-between text-xs"
                    >
                        <span class="text-[#5B6B82]">Intento {{ a.attempt_number }}</span>
                        <span class="text-[#1E3A5F]">
                            {{ a.final_score !== null ? `Nota: ${a.final_score}` : statusLabel[a.status] }}
                        </span>
                    </div>
                </div>

                <Button
                    v-if="in_progress_attempt_id"
                    class="mt-5 bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                    @click="continueAttempt"
                >
                    Continuar intento
                </Button>
                <Button
                    v-else-if="canStart"
                    class="mt-5 bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                    @click="startAttempt"
                >
                    Iniciar intento
                </Button>
                <p v-else class="mt-5 text-xs text-[#8A8A85]">Ya usaste todos tus intentos.</p>
            </div>
        </div>
    </StudentLmsLayout>
</template>

<style scoped>
.lms-wordmark {
    font-family: 'Fraunces', serif;
    font-weight: 500;
}
</style>
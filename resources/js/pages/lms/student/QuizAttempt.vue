<script setup lang="ts">
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import StudentLmsLayout from '@/layouts/StudentLmsLayout.vue';
import { Button } from '@/components/ui/button';

interface Option {
    id: number;
    option_text: string;
}

interface Question {
    id: number;
    type: 'multiple_choice' | 'essay';
    question: string;
    options: Option[];
}

const props = defineProps<{
    virtual_course_id: number;
    attempt: { id: number };
    quiz: { id: number; title: string };
    questions: Question[];
}>();

// answers[question_id] = { selected_option_id } | { written_answer }
const answers = reactive<Record<number, { selected_option_id?: number; written_answer?: string }>>(
    Object.fromEntries(props.questions.map((q) => [q.id, {}])),
);

const form = useForm({ answers: {} as typeof answers });

function submit() {
    form.answers = answers;
    form.post(`/lms/quiz-attempts/${props.attempt.id}/submit`);
}
</script>

<template>
    <StudentLmsLayout :title="quiz.title">
        <div class="mx-auto max-w-2xl">
            <p class="lms-wordmark mb-5 text-lg text-[#1E3A5F]">{{ quiz.title }}</p>

            <div class="flex flex-col gap-4">
                <div
                    v-for="(q, i) in questions"
                    :key="q.id"
                    class="rounded-xl border border-[#EAEAE5] bg-white p-4"
                >
                    <p class="mb-3 text-sm font-medium text-[#1E3A5F]">{{ i + 1 }}. {{ q.question }}</p>

                    <div v-if="q.type === 'multiple_choice'" class="flex flex-col gap-2">
                        <label
                            v-for="opt in q.options"
                            :key="opt.id"
                            class="flex items-center gap-2 text-sm text-[#5B6B82]"
                        >
                            <input
                                type="radio"
                                :name="`q-${q.id}`"
                                :value="opt.id"
                                @change="answers[q.id].selected_option_id = opt.id"
                            />
                            {{ opt.option_text }}
                        </label>
                    </div>

                    <textarea
                        v-else
                        rows="4"
                        class="w-full rounded-md border border-[#EAEAE5] p-2 text-sm"
                        placeholder="Escribe tu respuesta..."
                        @input="answers[q.id].written_answer = ($event.target as HTMLTextAreaElement).value"
                    />
                </div>
            </div>

            <Button
                class="mt-5 bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                :disabled="form.processing"
                @click="submit"
            >
                Entregar cuestionario
            </Button>
        </div>
    </StudentLmsLayout>
</template>

<style scoped>
.lms-wordmark {
    font-family: 'Fraunces', serif;
    font-weight: 500;
}
</style>
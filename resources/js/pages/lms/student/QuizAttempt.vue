<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useLmsBanner } from '@/composables/useLmsBanner';
import { computed, reactive } from 'vue';
import {
    ArrowRight,
    CheckCircle2,
    HelpCircle,
    ListChecks,
    PenLine,
    TriangleAlert,
} from 'lucide-vue-next';
import StudentLmsLayout from '@/layouts/StudentLmsLayout.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

defineOptions({ layout: StudentLmsLayout });

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

const answers = reactive<Record<number, { selected_option_id?: number; written_answer?: string }>>(
    Object.fromEntries(props.questions.map((q) => [q.id, {}])),
);

const form = useForm({ answers: {} as typeof answers });

function isAnswered(q: Question) {
    const a = answers[q.id];
    return q.type === 'multiple_choice' ? a.selected_option_id !== undefined : !!a.written_answer?.trim();
}

const total = computed(() => props.questions.length);
const answeredCount = computed(() => props.questions.filter(isAnswered).length);
const unanswered = computed(() => total.value - answeredCount.value);

const plural = (n: number, one: string, many: string) => `${n} ${n === 1 ? one : many}`;

const choiceCount = computed(() => props.questions.filter((q) => q.type === 'multiple_choice').length);
const essayCount = computed(() => total.value - choiceCount.value);

function selectOption(q: Question, optionId: number) {
    answers[q.id].selected_option_id = optionId;
}

function onWritten(q: Question, event: Event) {
    answers[q.id].written_answer = (event.target as HTMLTextAreaElement).value;
}

function goTo(q: Question) {
    document.getElementById(`question-${q.id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function submit() {
    if (
        unanswered.value > 0 &&
        !window.confirm(
            `Tienes ${plural(unanswered.value, 'pregunta sin responder', 'preguntas sin responder')}. ¿Entregar de todos modos?`,
        )
    ) {
        return;
    }
    form.answers = answers;
    form.post(`/lms/quiz-attempts/${props.attempt.id}/submit`);
}

const firstError = computed(() => Object.values(form.errors)[0] ?? null);

const cardBase =
    'rounded-[18px] border border-[#D8E2F4] bg-white shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)]';

useLmsBanner(() => ({
    tag: { label: 'Cuestionario en curso', icon: HelpCircle },
    title: props.quiz.title,
    chips: [
        { label: plural(total.value, 'pregunta', 'preguntas'), icon: ListChecks },
        ...(choiceCount.value > 0 && essayCount.value > 0
            ? [{ label: `${choiceCount.value} de opción múltiple · ${essayCount.value} de desarrollo` }]
            : []),
    ],
    ring: {
        text: `${answeredCount.value}/${total.value}`,
        caption: 'respondidas',
        progress: total.value ? (answeredCount.value / total.value) * 100 : 0,
    },
}));

</script>

<template>
    <div class="w-full">
        <Head :title="quiz.title" />
        <div class="grid gap-4 lg:grid-cols-[1fr_320px]">
            <!-- Preguntas -->
            <div class="flex flex-col gap-4">
                <div
                    v-for="(q, i) in questions"
                    :id="`question-${q.id}`"
                    :key="q.id"
                    :class="[
                        cardBase,
                        'scroll-mt-6 border-l-4 px-7 py-6 transition-colors',
                        isAnswered(q) ? 'border-l-[#16A36A]' : 'border-l-[#C9DAF7]',
                    ]"
                >
                    <div class="flex items-start gap-4">
                        <span
                            :class="[
                                isAnswered(q)
                                    ? 'from-[#16A36A] to-[#34C58A]'
                                    : 'from-[#1E3A5F] to-[#214EA4]',
                                'grid size-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br text-base font-semibold text-white transition-colors',
                            ]"
                        >
                            <CheckCircle2 v-if="isAnswered(q)" class="size-5" />
                            <template v-else>{{ i + 1 }}</template>
                        </span>

                        <div class="min-w-0 flex-1">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-[#E8EEFB] px-2.5 py-0.5 text-[11.5px] font-medium text-[#214EA4]"
                            >
                                <ListChecks v-if="q.type === 'multiple_choice'" class="size-3.5" />
                                <PenLine v-else class="size-3.5" />
                                {{ q.type === 'multiple_choice' ? 'Opción múltiple' : 'Desarrollo' }}
                            </span>
                            <p class="mt-2 text-[17px] font-semibold leading-snug text-[#0B2545]">
                                {{ q.question }}
                            </p>
                        </div>
                    </div>

                    <!-- Opción múltiple -->
                    <div v-if="q.type === 'multiple_choice'" class="mt-5 flex flex-col gap-2.5 sm:pl-[60px]">
                        <label
                            v-for="opt in q.options"
                            :key="opt.id"
                            :class="[
                                answers[q.id].selected_option_id === opt.id
                                    ? 'border-[#214EA4] bg-[#E8EEFB]'
                                    : 'border-[#E1E9F7] bg-[#F4F7FD] hover:border-[#214EA4]/40',
                                'flex cursor-pointer items-center gap-3.5 rounded-[14px] border-[1.5px] px-4 py-3.5 transition focus-within:ring-4 focus-within:ring-[#214EA4]/15',
                            ]"
                        >
                            <input
                                type="radio"
                                class="sr-only"
                                :name="`q-${q.id}`"
                                :value="opt.id"
                                :checked="answers[q.id].selected_option_id === opt.id"
                                @change="selectOption(q, opt.id)"
                            />
                            <span
                                :class="[
                                    answers[q.id].selected_option_id === opt.id
                                        ? 'border-[#214EA4]'
                                        : 'border-[#B8C8E8]',
                                    'grid size-5 shrink-0 place-items-center rounded-full border-2 bg-white',
                                ]"
                            >
                                <span
                                    v-if="answers[q.id].selected_option_id === opt.id"
                                    class="size-2.5 rounded-full bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]"
                                />
                            </span>
                            <span class="text-[15px] text-[#0B2545]">{{ opt.option_text }}</span>
                        </label>
                    </div>

                    <!-- Desarrollo -->
                    <div v-else class="mt-5 sm:pl-[60px]">
                        <textarea
                            rows="5"
                            :value="answers[q.id].written_answer ?? ''"
                            class="w-full resize-y rounded-[14px] border-[1.5px] border-transparent bg-[#E8EEFB] px-4 py-3 text-[15px] text-[#0B2545] outline-none placeholder:text-[#7B8AA3] focus:border-[#1E3A5F] focus:bg-white focus:ring-4 focus:ring-[#1E3A5F]/12"
                            placeholder="Escribe tu respuesta..."
                            @input="onWritten(q, $event)"
                        />
                        <p class="mt-1.5 text-right text-xs text-[#8A97AB]">
                            {{ (answers[q.id].written_answer ?? '').length }} caracteres
                        </p>
                    </div>
                </div>
            </div>

            <!-- Panel lateral -->
            <aside class="lg:sticky lg:top-4 lg:self-start">
                <div :class="[cardBase, 'px-6 py-5']">
                    <h2 class="text-[17px] font-semibold text-[#0B2545]">Tu progreso</h2>
                    <p class="mt-0.5 text-[13px] text-[#5B6B82]">
                        {{ answeredCount }} de {{ total }} respondidas
                    </p>

                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-[#E4ECF9]">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF] transition-all duration-500"
                            :style="{ width: `${total ? (answeredCount / total) * 100 : 0}%` }"
                        />
                    </div>

                    <div class="mt-4 grid grid-cols-5 gap-2">
                        <button
                            v-for="(q, i) in questions"
                            :key="q.id"
                            type="button"
                            :aria-label="`Ir a la pregunta ${i + 1}`"
                            :class="[
                                isAnswered(q)
                                    ? 'bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white'
                                    : 'bg-[#E8EEFB] text-[#214EA4] hover:bg-[#DCE6F8]',
                                'h-10 rounded-xl text-sm font-semibold transition',
                            ]"
                            @click="goTo(q)"
                        >
                            {{ i + 1 }}
                        </button>
                    </div>

                    <div class="mt-3 flex gap-4 text-xs text-[#6B7A90]">
                        <span class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-[4px] bg-gradient-to-br from-[#1E3A5F] to-[#214EA4]"></span>
                            Respondida
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-[4px] bg-[#E8EEFB]"></span>
                            Pendiente
                        </span>
                    </div>

                    <div
                        :class="[
                            unanswered === 0
                                ? 'bg-[#E3F7EC] text-[#0E7C4A]'
                                : 'bg-[#FFF4D6] text-[#9A5B00]',
                            'mt-5 flex items-start gap-2.5 rounded-xl px-3.5 py-3 text-[13px]',
                        ]"
                    >
                        <CheckCircle2 v-if="unanswered === 0" class="mt-px size-4 shrink-0" />
                        <TriangleAlert v-else class="mt-px size-4 shrink-0" />
                        <span>
                            {{
                                unanswered === 0
                                    ? 'Respondiste todas las preguntas. Revisa tus respuestas antes de entregar.'
                                    : `Te ${unanswered === 1 ? 'falta' : 'faltan'} ${plural(unanswered, 'pregunta', 'preguntas')} por responder.`
                            }}
                        </span>
                    </div>

                    <p v-if="firstError" class="mt-3 text-sm text-[#D85A30]">{{ firstError }}</p>

                    <Button
                        type="button"
                        class="mt-4 h-12 w-full rounded-[13px] bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] text-[15px] font-medium text-white shadow-[0_10px_20px_-10px_rgba(33,78,164,0.8)] hover:brightness-110 disabled:opacity-45"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <Spinner v-if="form.processing" />
                        {{ form.processing ? 'Enviando...' : 'Entregar cuestionario' }}
                        <ArrowRight v-if="!form.processing" class="size-[17px]" />
                    </Button>
                </div>
            </aside>
        </div>
    </div>
</template>
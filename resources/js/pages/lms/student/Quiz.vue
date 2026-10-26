<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useLmsBanner } from '@/composables/useLmsBanner';
import {
    CheckCircle2,
    HelpCircle,
    Info,
    ListChecks,
    Lock,
    Play,
    Repeat,
    Star,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import StudentLmsLayout from '@/layouts/StudentLmsLayout.vue';

defineOptions({ layout: StudentLmsLayout });

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

const starting = ref(false);

function startAttempt() {
    router.post(
        `/lms/quizzes/${props.quiz.id}/attempts`,
        {},
        {
            onStart: () => (starting.value = true),
            onFinish: () => (starting.value = false),
        },
    );
}

function continueAttempt() {
    router.get(`/lms/quiz-attempts/${props.in_progress_attempt_id}/resolver`);
}

const remaining = computed(() => Math.max(0, props.quiz.max_attempts - props.attempts_used));
const canStart = computed(() => remaining.value > 0 && !props.in_progress_attempt_id);

const plural = (n: number, one: string, many: string) => `${n} ${n === 1 ? one : many}`;

const segmented = computed(() => props.quiz.max_attempts <= 10);
const usedPct = computed(() =>
    props.quiz.max_attempts > 0 ? Math.min(100, (props.attempts_used / props.quiz.max_attempts) * 100) : 0,
);

const best = computed(() => {
    const graded = props.attempts
        .filter((a) => a.final_score !== null)
        .map((a) => ({ attempt: a.attempt_number, score: Number(a.final_score) }));
    return graded.length ? graded.reduce((m, s) => (s.score > m.score ? s : m)) : null;
});
const gradedCount = computed(() => props.attempts.filter((a) => a.final_score !== null).length);

const fmt = (n: number) => n.toFixed(2);
const bestLabel = computed(() => (best.value ? fmt(best.value.score) : '—'));
const pct = (score: number) => Math.min(100, Math.max(0, score * 10));

type Tone = 'green' | 'amber' | 'blue';

const state = computed<{ label: string; tone: Tone }>(() => {
    if (props.in_progress_attempt_id) return { label: 'Intento en progreso', tone: 'blue' };
    if (canStart.value)
        return {
            label: `${plural(remaining.value, 'intento disponible', 'intentos disponibles')}`,
            tone: 'green',
        };
    return { label: 'Sin intentos disponibles', tone: 'amber' };
});

const attemptChip = (status: string) =>
    ({
        in_progress: 'bg-[#E8EEFB] text-[#214EA4]',
        pending_review: 'border border-[#F2D27A] bg-[#FFF1CC] text-[#9A5B00]',
        graded: 'bg-emerald-100 text-emerald-700',
    })[status] ?? 'bg-[#E8EEFB] text-[#4A5D7E]';

const cardBase =
    'rounded-[18px] border border-[#D8E2F4] bg-white px-[26px] py-5 shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)]';
const iconTile =
    'grid size-[42px] shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white';

useLmsBanner(() => ({
    tag: { label: 'Cuestionario', icon: HelpCircle },
    title: props.quiz.title,
    back: true,
    chips: [
        {
            label: `${props.attempts_used} de ${props.quiz.max_attempts} ${
                props.quiz.max_attempts === 1 ? 'intento usado' : 'intentos usados'
            }`,
            icon: Repeat,
        },
        {
            label: state.value.label,
            tone: state.value.tone,
            icon: state.value.tone === 'amber' ? Lock : state.value.tone === 'blue' ? Play : CheckCircle2,
        },
    ],
    action: props.in_progress_attempt_id
        ? { label: 'Continuar intento', onClick: continueAttempt }
        : canStart.value
          ? { label: 'Iniciar intento', loading: starting.value, onClick: startAttempt }
          : null,
    ring: {
        text: bestLabel.value,
        caption: best.value ? (gradedCount.value > 1 ? 'Mejor nota sobre 10' : 'Nota sobre 10') : 'Sin calificar',
        progress: best.value ? pct(best.value.score) : 0,
    },
}));

</script>

<template>
    <div class="w-full">
        <Head :title="quiz.title" />

        <div class="grid gap-4 lg:grid-cols-[1fr_400px]">
            <!-- Columna principal -->
            <div class="grid content-start gap-4">
                <!-- Instrucciones -->
                <div v-if="quiz.description" :class="cardBase">
                    <div class="flex items-center gap-3.5">
                        <div :class="iconTile"><Info class="size-5" /></div>
                        <h2 class="text-[17px] font-semibold text-[#0B2545]">Instrucciones</h2>
                    </div>
                    <p class="mt-3.5 text-sm leading-relaxed text-[#5B6B82]">{{ quiz.description }}</p>
                </div>

                <!-- Tus intentos -->
                <div :class="cardBase">
                    <div class="flex items-center gap-3.5">
                        <div :class="iconTile"><ListChecks class="size-5" /></div>
                        <div>
                            <h2 class="text-[17px] font-semibold text-[#0B2545]">Tus intentos</h2>
                            <p class="text-[13px] text-[#5B6B82]">Historial de resultados de este cuestionario</p>
                        </div>
                    </div>

                    <div v-if="attempts.length" class="mt-3.5 flex flex-col gap-2.5">
                        <div
                            v-for="a in attempts"
                            :key="a.attempt_number"
                            class="flex flex-wrap items-center gap-4 rounded-[15px] border border-[#E1E9F7] bg-[#F4F7FD] px-[18px] py-4"
                        >
                            <span
                                class="grid size-12 shrink-0 place-items-center rounded-[13px] bg-gradient-to-br from-[#214EA4] to-[#3F7BE0] text-[17px] font-semibold text-white"
                            >
                                {{ a.attempt_number }}
                            </span>

                            <div class="min-w-0">
                                <p class="text-base font-semibold text-[#0B2545]">Intento {{ a.attempt_number }}</p>
                                <p class="mt-0.5 text-[12.5px] text-[#6B7A90]">
                                    {{ statusLabel[a.status] ?? a.status }}
                                </p>
                            </div>

                            <div class="ml-auto flex items-center gap-4">
                                <template v-if="a.final_score !== null">
                                    <div class="hidden h-[9px] w-[260px] overflow-hidden rounded-full bg-[#E4ECF9] md:block">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF] transition-all duration-500"
                                            :style="{ width: `${pct(Number(a.final_score))}%` }"
                                        />
                                    </div>
                                    <span
                                        class="rounded-full bg-[#E8EEFB] px-3.5 py-[5px] text-[13.5px] font-semibold tabular-nums text-[#214EA4]"
                                    >
                                        {{ fmt(Number(a.final_score)) }} / 10
                                    </span>
                                </template>
                                <span
                                    v-else
                                    :class="[attemptChip(a.status), 'rounded-full px-3.5 py-[5px] text-[13px] font-medium']"
                                >
                                    {{ statusLabel[a.status] ?? a.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <p v-else class="mt-3.5 rounded-[15px] bg-[#F4F7FD] px-5 py-6 text-center text-sm text-[#6B7A90]">
                        Aún no has realizado intentos.
                    </p>

                    <!-- Sin intentos disponibles -->
                    <div
                        v-if="!in_progress_attempt_id && !canStart"
                        class="mt-4 flex items-center gap-4 rounded-2xl border-2 border-dashed border-[#C9DAF7] bg-[#F6F9FE] px-[22px] py-5"
                    >
                        <span class="grid size-12 shrink-0 place-items-center rounded-[13px] bg-[#E4ECF9] text-[#8A97AB]">
                            <Lock class="size-[22px]" />
                        </span>
                        <div>
                            <p class="text-[15.5px] font-semibold text-[#0B2545]">Ya usaste todos tus intentos</p>
                            <p class="mt-0.5 text-[13px] text-[#5B6B82]">
                                No quedan intentos disponibles para este cuestionario.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha -->
            <div class="grid content-start gap-4">
                <!-- Calificación -->
                <div :class="cardBase">
                    <div class="flex items-center gap-3.5">
                        <div :class="iconTile"><Star class="size-5" /></div>
                        <h2 class="text-[17px] font-semibold text-[#0B2545]">Calificación</h2>
                    </div>

                    <template v-if="best">
                        <div class="mt-3.5 flex items-baseline gap-2">
                            <span class="text-[52px] font-semibold leading-none tabular-nums text-[#0B2545]">{{ bestLabel }}</span>
                            <span class="text-lg text-[#6B7A90]">/ 10</span>
                        </div>
                        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-[#E4ECF9]">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF] transition-all duration-500"
                                :style="{ width: `${pct(best.score)}%` }"
                            />
                        </div>
                        <p v-if="gradedCount > 1" class="mt-2.5 text-[12.5px] text-[#6B7A90]">
                            Mejor resultado: intento {{ best.attempt }}
                        </p>
                    </template>
                    <p v-else class="mt-3.5 text-sm text-[#6B7A90]">Aún sin calificar.</p>
                </div>

                <!-- Intentos -->
                <div :class="cardBase">
                    <h2 class="text-[17px] font-semibold text-[#0B2545]">Intentos</h2>

                    <div v-if="segmented" class="mt-4 flex gap-2">
                        <span
                            v-for="n in quiz.max_attempts"
                            :key="n"
                            :class="[
                                n <= attempts_used ? 'bg-gradient-to-r from-[#1E3A5F] to-[#214EA4]' : 'bg-[#E4ECF9]',
                                'h-3 flex-1 rounded-full',
                            ]"
                        />
                    </div>
                    <div v-else class="mt-4 h-3 overflow-hidden rounded-full bg-[#E4ECF9]">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] transition-all duration-500"
                            :style="{ width: `${usedPct}%` }"
                        />
                    </div>

                    <div class="mt-3 flex justify-between text-[13px] text-[#5B6B82]">
                        <span><b class="font-semibold text-[#0B2545]">{{ attempts_used }}</b> {{ attempts_used === 1 ? 'usado' : 'usados' }}</span>
                        <span><b class="font-semibold text-[#0B2545]">{{ remaining }}</b> {{ remaining === 1 ? 'disponible' : 'disponibles' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
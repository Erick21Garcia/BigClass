<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useLmsBanner } from '@/composables/useLmsBanner';
import { computed, ref } from 'vue';
import {
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    Clock,
    FileText,
    Info,
    MessageSquare,
    Paperclip,
    Star,
    Upload,
    X,
} from 'lucide-vue-next';
import StudentLmsLayout from '@/layouts/StudentLmsLayout.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

defineOptions({ layout: StudentLmsLayout });

interface SubmissionFile {
    id: number;
    original_name: string;
}

const props = defineProps<{
    virtual_course_id: number;
    assignment: { id: number; title: string; description: string | null; due_date: string };
    submission: {
        submitted_at: string;
        is_late: boolean;
        grade: number | null;
        feedback: string | null;
        files: SubmissionFile[];
    } | null;
}>();

const form = useForm({ files: [] as File[] });
const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

function triggerFilePicker() {
    fileInput.value?.click();
}

function onFilesChange(event: Event) {
    const input = event.target as HTMLInputElement;
    form.files = input.files ? Array.from(input.files) : [];
}

function onDrop(event: DragEvent) {
    isDragging.value = false;
    const dropped = event.dataTransfer?.files;
    if (dropped && dropped.length > 0) form.files = Array.from(dropped);
}

function removeFile(index: number) {
    form.files = form.files.filter((_, i) => i !== index);
    if (fileInput.value) fileInput.value.value = '';
}

function submit() {
    form.post(`/lms/assignments/${props.assignment.id}/submit`, { preserveScroll: true });
}

const dateFormat: Intl.DateTimeFormatOptions = {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
};
const formatDate = (iso: string) => new Date(iso).toLocaleString(undefined, dateFormat);

const isOverdue = computed(
    () => !props.submission && new Date(props.assignment.due_date).getTime() < Date.now(),
);

const graded = computed(() => props.submission !== null && props.submission.grade !== null);
const gradeNumber = computed(() => (graded.value ? Number(props.submission!.grade) : null));
const gradeLabel = computed(() => (gradeNumber.value === null ? '—' : gradeNumber.value.toFixed(2)));

const gradeWidth = computed(() =>
    gradeNumber.value === null ? '0%' : `${Math.min(100, Math.max(0, gradeNumber.value * 10))}%`,
);

type Tone = 'green' | 'amber' | 'red';

const toneChip: Record<Tone, string> = {
    green: 'border-[#BFE9D3] bg-[#E3F7EC] text-[#0E7C4A]',
    amber: 'border-[#F2D27A] bg-[#FFF1CC] text-[#9A5B00]',
    red: 'border-red-200 bg-red-50 text-red-700',
};

const status = computed<{ label: string; tone: Tone }>(() => {
    if (!props.submission)
        return isOverdue.value
            ? { label: 'Vencida', tone: 'red' }
            : { label: 'Pendiente de entrega', tone: 'amber' };
    if (graded.value) return { label: 'Calificada', tone: 'green' };
    return props.submission.is_late
        ? { label: 'Entregada con retraso', tone: 'amber' }
        : { label: 'Entregada', tone: 'green' };
});

const steps = computed(() => {
    const sub = props.submission;
    return [
        {
            key: 'sent',
            title: 'Entregada',
            done: sub !== null,
            text: sub ? formatDate(sub.submitted_at) : 'Aún no has entregado',
            badge: sub
                ? sub.is_late
                    ? { text: 'Con retraso', cls: 'bg-[#FFF1CC] text-[#9A5B00]' }
                    : { text: 'A tiempo', cls: 'bg-[#E3F7EC] text-[#0E7C4A]' }
                : null,
        },
        {
            key: 'graded',
            title: 'Calificada',
            done: graded.value,
            text: graded.value
                ? `Nota ${gradeLabel.value} sobre 10`
                : sub
                  ? 'Pendiente de calificación'
                  : 'Disponible tras entregar',
            badge: null,
        },
        {
            key: 'due',
            title: 'Fecha límite',
            done: false,
            text: formatDate(props.assignment.due_date),
            badge: isOverdue.value ? { text: 'Vencida', cls: 'bg-red-50 text-red-700' } : null,
        },
    ];
});

const fileExt = (name: string) => (name.includes('.') ? name.split('.').pop()!.toUpperCase() : 'Archivo');

const cardBase =
    'rounded-[18px] border border-[#D8E2F4] bg-white px-[26px] py-5 shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)]';
const iconTile =
    'grid size-[42px] shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white';

useLmsBanner(() => ({
    tag: { label: 'Tarea', icon: ClipboardList },
    title: props.assignment.title,
    back: true,
    chips: [
        { label: `Entrega: ${formatDate(props.assignment.due_date)}`, icon: CalendarDays },
        {
            label: status.value.label,
            icon: status.value.tone === 'green' ? CheckCircle2 : Clock,
            tone: status.value.tone,
        },
    ],
    ring: {
        text: gradeLabel.value,
        caption: graded.value ? 'Nota sobre 10' : 'Sin calificar',
        progress: gradeNumber.value === null ? 0 : gradeNumber.value * 10,
    },
}));

</script>

<template>
    <div class="w-full">
        <Head :title="assignment.title" />
        <div class="grid gap-4 lg:grid-cols-[1fr_400px]">
            <!-- Columna principal -->
            <div class="grid content-start gap-4">
                <!-- Instrucciones -->
                <div v-if="assignment.description" :class="cardBase">
                    <div class="flex items-center gap-3.5">
                        <div :class="iconTile"><Info class="size-5" /></div>
                        <h2 class="text-[17px] font-semibold text-[#0B2545]">Instrucciones</h2>
                    </div>
                    <p class="mt-3.5 text-sm leading-relaxed text-[#5B6B82]">{{ assignment.description }}</p>
                </div>

                <!-- Tu entrega -->
                <div v-if="submission" :class="cardBase">
                    <div class="flex flex-wrap items-center gap-3.5">
                        <div :class="iconTile"><FileText class="size-5" /></div>
                        <div class="min-w-0">
                            <h2 class="text-[17px] font-semibold text-[#0B2545]">Tu entrega</h2>
                            <p class="text-[13px] text-[#5B6B82]">
                                Entregado el {{ formatDate(submission.submitted_at) }}
                            </p>
                        </div>
                        <span
                            :class="[
                                submission.is_late ? toneChip.amber : toneChip.green,
                                'ml-auto inline-flex items-center gap-1.5 rounded-full border px-3.5 py-[5px] text-[12.5px] font-medium',
                            ]"
                        >
                            <CheckCircle2 v-if="!submission.is_late" class="size-3.5" />
                            <Clock v-else class="size-3.5" />
                            {{ submission.is_late ? 'Entregado con retraso' : 'Entregado' }}
                        </span>
                    </div>

                    <ul class="mt-3.5 flex flex-col gap-2.5">
                        <li
                            v-for="f in submission.files"
                            :key="f.id"
                            class="flex items-center gap-4 rounded-[15px] border border-[#E1E9F7] bg-[#F4F7FD] px-[18px] py-3.5"
                        >
                            <span
                                class="grid size-12 shrink-0 place-items-center rounded-[13px] bg-gradient-to-br from-[#214EA4] to-[#3F7BE0] text-white"
                            >
                                <FileText class="size-[22px]" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-[15.5px] font-semibold text-[#0B2545]">{{ f.original_name }}</p>
                                <p class="mt-0.5 text-[12.5px] text-[#6B7A90]">
                                    Archivo entregado · {{ fileExt(f.original_name) }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Formulario de entrega (nueva o reemplazo) -->
                <div :class="cardBase">
                    <div class="flex items-center gap-3.5">
                        <div :class="iconTile"><Upload class="size-5" /></div>
                        <div>
                            <h2 class="text-[17px] font-semibold text-[#0B2545]">
                                {{ submission ? 'Reemplazar entrega' : 'Entregar tarea' }}
                            </h2>
                            <p class="text-[13px] text-[#5B6B82]">
                                {{
                                    submission
                                        ? 'Sube un nuevo archivo para sustituir el anterior'
                                        : 'Sube tu archivo para completar la tarea'
                                }}
                            </p>
                        </div>
                    </div>

                    <input ref="fileInput" type="file" multiple class="hidden" @change="onFilesChange" />

                    <div
                        :class="[
                            isDragging ? 'border-[#214EA4] bg-[#E8EEFB]' : 'border-[#B8C8E8] bg-[#F4F7FD]',
                            'mt-3.5 flex flex-wrap items-center gap-5 rounded-2xl border-2 border-dashed px-6 py-[22px] transition-colors',
                        ]"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDrop"
                    >
                        <span class="grid size-[54px] shrink-0 place-items-center rounded-full bg-[#E8EEFB] text-[#214EA4]">
                            <Upload class="size-6" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-[15px] font-semibold text-[#0B2545]">Arrastra tu archivo aquí</p>
                            <p class="mt-0.5 text-[12.5px] text-[#6B7A90]">o elige uno desde tu equipo</p>
                        </div>

                        <div class="ml-auto flex items-center gap-3.5">
                            <span class="text-[13px] text-[#8A97AB]">
                                {{
                                    form.files.length > 0
                                        ? `${form.files.length} archivo(s) elegido(s)`
                                        : 'Ningún archivo elegido'
                                }}
                            </span>
                            <button
                                type="button"
                                class="h-[42px] rounded-xl border-[1.5px] border-[#214EA4] bg-white px-5 text-sm font-medium text-[#214EA4] transition hover:bg-[#214EA4]/5"
                                @click="triggerFilePicker"
                            >
                                Seleccionar archivos
                            </button>
                        </div>
                    </div>

                    <ul v-if="form.files.length > 0" class="mt-3 flex flex-col gap-2">
                        <li
                            v-for="(file, i) in form.files"
                            :key="`${file.name}-${i}`"
                            class="flex items-center justify-between gap-3 rounded-xl border border-[#E1E9F7] bg-white px-3.5 py-2.5 text-sm text-[#0B2545]"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <Paperclip class="size-4 shrink-0 text-[#214EA4]" />
                                <span class="truncate">{{ file.name }}</span>
                            </span>
                            <button
                                type="button"
                                class="grid size-7 shrink-0 place-items-center rounded-lg text-[#D85A30] transition hover:bg-red-50"
                                aria-label="Quitar archivo"
                                @click="removeFile(i)"
                            >
                                <X class="size-4" />
                            </button>
                        </li>
                    </ul>

                    <p v-if="form.errors.files" class="mt-2 text-sm text-[#D85A30]">{{ form.errors.files }}</p>

                    <div class="mt-3.5 flex flex-wrap items-center justify-between gap-3">
                        <p v-if="submission" class="flex items-center gap-2 text-[12.5px] text-[#6B7A90]">
                            <Info class="size-[15px]" />
                            Al entregar, tu archivo actual será reemplazado.
                        </p>
                        <span v-else></span>

                        <Button
                            type="button"
                            class="h-12 rounded-[13px] bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] px-6 text-[15px] font-medium text-white shadow-[0_10px_20px_-10px_rgba(33,78,164,0.8)] hover:brightness-110 disabled:opacity-45"
                            :disabled="form.processing || form.files.length === 0"
                            @click="submit"
                        >
                            <Spinner v-if="form.processing" />
                            {{ form.processing ? 'Enviando...' : 'Entregar' }}
                            <ArrowRight v-if="!form.processing" class="size-[17px]" />
                        </Button>
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

                    <template v-if="graded">
                        <div class="mt-3.5 flex items-baseline gap-2">
                            <span class="text-[52px] font-semibold leading-none tabular-nums text-[#0B2545]">{{ gradeLabel }}</span>
                            <span class="text-lg text-[#6B7A90]">/ 10</span>
                        </div>
                        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-[#E4ECF9]">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF] transition-all duration-500"
                                :style="{ width: gradeWidth }"
                            />
                        </div>
                    </template>
                    <p v-else class="mt-3.5 text-sm text-[#6B7A90]">Aún sin calificar.</p>

                    <div
                        v-if="submission?.feedback"
                        class="mt-4 rounded-[14px] border-l-4 border-[#214EA4] bg-[#EAF0FB] px-4 py-3.5"
                    >
                        <p class="flex items-center gap-[7px] text-[11.5px] font-semibold uppercase tracking-[0.06em] text-[#214EA4]">
                            <MessageSquare class="size-3.5" />
                            Comentario
                        </p>
                        <p class="mt-1.5 text-[15.5px] text-[#0B2545]">{{ submission.feedback }}</p>
                    </div>
                </div>

                <!-- Estado de la tarea -->
                <div :class="cardBase">
                    <h2 class="text-[17px] font-semibold text-[#0B2545]">Estado de la tarea</h2>

                    <ol class="mt-3.5">
                        <li
                            v-for="(s, i) in steps"
                            :key="s.key"
                            class="relative flex gap-3.5 pb-4 last:pb-0"
                            :class="{
                                'before:absolute before:bottom-0.5 before:left-[15px] before:top-[34px] before:w-0.5 before:bg-[#C9DAF7]':
                                    i < steps.length - 1,
                            }"
                        >
                            <span
                                :class="[
                                    s.done ? 'bg-gradient-to-br from-[#16A36A] to-[#34C58A] text-white' : 'bg-[#E4ECF9] text-[#6B7A90]',
                                    'grid size-8 shrink-0 place-items-center rounded-full',
                                ]"
                            >
                                <CheckCircle2 v-if="s.done" class="size-[15px]" />
                                <Clock v-else class="size-[15px]" />
                            </span>
                            <div>
                                <p class="mt-px text-[14.5px] font-semibold text-[#0B2545]">
                                    {{ s.title }}
                                    <span
                                        v-if="s.badge"
                                        :class="[s.badge.cls, 'ml-2 inline-block rounded-full px-[9px] py-0.5 align-[1px] text-[11px] font-semibold']"
                                    >
                                        {{ s.badge.text }}
                                    </span>
                                </p>
                                <p class="mt-0.5 text-[12.5px] text-[#5B6B82]">{{ s.text }}</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</template>
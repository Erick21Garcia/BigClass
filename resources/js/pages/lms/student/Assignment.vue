<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import StudentLmsLayout from '@/layouts/StudentLmsLayout.vue';
import { Button } from '@/components/ui/button';

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

function triggerFilePicker() {
    fileInput.value?.click();
}

function onFilesChange(event: Event) {
    const input = event.target as HTMLInputElement;
    form.files = input.files ? Array.from(input.files) : [];
}

function removeFile(index: number) {
    form.files = form.files.filter((_, i) => i !== index);
    if (fileInput.value) fileInput.value.value = '';
}

function submit() {
    form.post(`/lms/assignments/${props.assignment.id}/submit`, { preserveScroll: true });
}
</script>

<template>
    <StudentLmsLayout :title="assignment.title">
        <div class="mx-auto max-w-2xl">
            <a :href="`/lms/mis-materias`" class="mb-4 inline-block text-xs text-[#5B6B82] hover:underline">
                ← Volver a mis materias
            </a>

            <div class="rounded-xl border border-[#EAEAE5] bg-white p-5">
                <p class="lms-wordmark text-lg text-[#1E3A5F]">{{ assignment.title }}</p>
                <p class="mt-1 text-xs text-[#8A8A85]">
                    Entrega: {{ new Date(assignment.due_date).toLocaleString() }}
                </p>
                <p v-if="assignment.description" class="mt-3 text-sm text-[#5B6B82]">
                    {{ assignment.description }}
                </p>

                <!-- Ya entregado -->
                <div v-if="submission" class="mt-5 rounded-lg bg-[#F7F9FC] p-4">
                    <p class="text-xs font-medium" :class="submission.is_late ? 'text-[#D85A30]' : 'text-[#3B8A5A]'">
                        {{ submission.is_late ? 'Entregado con retraso' : 'Entregado' }}
                        — {{ new Date(submission.submitted_at).toLocaleString() }}
                    </p>

                    <ul class="mt-2 flex flex-col gap-1">
                        <li v-for="f in submission.files" :key="f.id" class="text-xs text-[#1E3A5F]">
                            <i class="ti ti-paperclip" /> {{ f.original_name }}
                        </li>
                    </ul>

                    <div v-if="submission.grade !== null" class="mt-3 border-t border-[#EAEAE5] pt-3">
                        <p class="text-sm font-medium text-[#1E3A5F]">Nota: {{ submission.grade }}</p>
                        <p v-if="submission.feedback" class="mt-1 text-xs text-[#5B6B82]">{{ submission.feedback }}</p>
                    </div>
                    <p v-else class="mt-2 text-xs text-[#8A8A85]">Aún sin calificar.</p>
                </div>

                <!-- Formulario de entrega (nueva o reemplazo) -->
                <div class="mt-5 border-t border-[#EAEAE5] pt-4">
                    <p class="mb-3 text-xs font-medium text-[#1E3A5F]">
                        {{ submission ? 'Reemplazar entrega' : 'Entregar tarea' }}
                    </p>

                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        class="hidden"
                        @change="onFilesChange"
                    />

                    <div class="flex items-center justify-between gap-3 rounded-lg border border-dashed border-[#C7D2E0] bg-[#F7F9FC] px-4 py-3">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-md border border-[#214EA4]/30 bg-white px-3 py-1.5 text-xs font-medium text-[#214EA4] hover:bg-[#214EA4]/5"
                            @click="triggerFilePicker"
                        >
                            <i class="ti ti-upload text-[14px]" />
                            Seleccionar archivos
                        </button>

                        <span class="text-xs text-[#8A8A85]">
                            {{ form.files.length > 0 ? `${form.files.length} archivo(s) elegido(s)` : 'Ningún archivo elegido' }}
                        </span>
                    </div>

                    <ul v-if="form.files.length > 0" class="mt-2 flex flex-col gap-1">
                        <li
                            v-for="(file, i) in form.files"
                            :key="`${file.name}-${i}`"
                            class="flex items-center justify-between rounded-md bg-white px-2.5 py-1.5 text-xs text-[#1E3A5F]"
                        >
                            <span class="flex items-center gap-1.5"><i class="ti ti-paperclip text-[#214EA4]" /> {{ file.name }}</span>
                            <button type="button" class="text-[#D85A30]" @click="removeFile(i)">
                                <i class="ti ti-x text-[13px]" />
                            </button>
                        </li>
                    </ul>

                    <p v-if="form.errors.files" class="mt-1 text-xs text-[#D85A30]">{{ form.errors.files }}</p>

                    <div class="mt-3 flex justify-end">
                        <Button
                            class="bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                            :disabled="form.processing || form.files.length === 0"
                            @click="submit"
                        >
                            {{ form.processing ? 'Enviando...' : 'Entregar' }}
                        </Button>
                    </div>
                </div>
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
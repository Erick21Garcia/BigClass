<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { type BreadcrumbItem } from '@/types';

interface Option {
    id: number;
    option_text: string;
    is_correct: boolean;
}

interface Question {
    id: number;
    type: 'multiple_choice' | 'essay';
    question: string;
    points: number;
    options: Option[];
}

const props = defineProps<{
    quiz: { id: number; title: string; max_attempts: number };
    virtual_course_id: number;
    questions: Question[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mis materias', href: '/lms/mis-materias-docente' },
    { title: props.quiz.title, href: `/lms/quizzes/${props.quiz.id}/edit` },
];

const typeLabel = { multiple_choice: 'Opción múltiple', essay: 'Respuesta escrita' };

const form = useForm({
    type: 'essay' as 'multiple_choice' | 'essay',
    question: '',
    points: 1,
    options: [] as { option_text: string; is_correct: boolean }[],
});

function addOption() {
    form.options.push({ option_text: '', is_correct: false });
}
function removeOption(index: number) {
    form.options.splice(index, 1);
}

// Si cambia a "essay", se limpian las opciones — de lo contrario el backend
// las rechaza en silencio (option_text vacío es inválido) y el error queda
// oculto porque el bloque de opciones no se muestra en modo essay.
watch(
    () => form.type,
    (type) => {
        form.options = type === 'multiple_choice'
            ? [
                  { option_text: '', is_correct: false },
                  { option_text: '', is_correct: false },
              ]
            : [];
    },
);

function submitQuestion() {
    form.post(`/lms/quizzes/${props.quiz.id}/questions`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.options = form.type === 'multiple_choice'
                ? [
                      { option_text: '', is_correct: false },
                      { option_text: '', is_correct: false },
                  ]
                : [];
        },
    });
}

function deleteQuestion(id: number) {
    router.delete(`/lms/quiz-questions/${id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="mb-1 text-xl font-medium text-[#1E3A5F]">{{ quiz.title }}</h1>
            <p class="mb-6 text-xs text-[#5B6B82]">{{ quiz.max_attempts }} intento(s) permitido(s)</p>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
                <!-- Preguntas existentes -->
                <div class="flex flex-col gap-3">
                    <div
                        v-for="(q, i) in questions"
                        :key="q.id"
                        class="rounded-lg border border-[#EAEAE5] bg-white p-4"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-medium text-[#214EA4]">
                                    Pregunta {{ i + 1 }} — {{ typeLabel[q.type] }} ({{ q.points }} pts)
                                </p>
                                <p class="mt-1 text-sm text-[#1E3A5F]">{{ q.question }}</p>
                            </div>
                            <button class="text-xs text-[#D85A30]" @click="deleteQuestion(q.id)">Eliminar</button>
                        </div>

                        <ul v-if="q.type === 'multiple_choice'" class="mt-3 flex flex-col gap-1 pl-1">
                            <li
                                v-for="opt in q.options"
                                :key="opt.id"
                                class="flex items-center gap-2 text-xs"
                                :class="opt.is_correct ? 'text-[#3B8A5A] font-medium' : 'text-[#5B6B82]'"
                            >
                                <i :class="opt.is_correct ? 'ti ti-circle-check' : 'ti ti-circle'" />
                                {{ opt.option_text }}
                            </li>
                        </ul>
                    </div>

                    <p v-if="questions.length === 0" class="text-sm text-[#8A8A85]">
                        Todavía no hay preguntas — agrégalas desde el panel de la derecha.
                    </p>

                    <a
                        :href="`/lms/mis-materias-docente/${virtual_course_id}`"
                        class="mt-2 inline-block w-fit text-xs text-[#5B6B82] hover:underline"
                    >
                        ← Volver a la materia
                    </a>
                </div>

                <!-- Nueva pregunta -->
                <div class="h-fit rounded-lg border border-[#EAEAE5] bg-white p-4 lg:sticky lg:top-6">
                    <p class="mb-3 text-sm font-medium text-[#1E3A5F]">Agregar pregunta</p>

                    <div class="grid gap-3">
                        <div class="grid gap-2">
                            <Label for="q-type">Tipo</Label>
                            <select id="q-type" v-model="form.type" class="rounded-md border px-2 py-1.5 text-sm">
                                <option value="multiple_choice">Opción múltiple</option>
                                <option value="essay">Respuesta escrita</option>
                            </select>
                        </div>

                        <div class="grid gap-2">
                            <Label for="q-text">Pregunta</Label>
                            <Input id="q-text" v-model="form.question" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="q-points">Puntos</Label>
                            <Input id="q-points" type="number" min="0.5" step="0.5" v-model.number="form.points" />
                        </div>

                        <div v-if="form.type === 'multiple_choice'" class="grid gap-2">
                            <Label>Opciones</Label>
                            <div v-for="(opt, i) in form.options" :key="i" class="flex items-center gap-2">
                                <Checkbox :checked="opt.is_correct" @update:checked="(v) => (opt.is_correct = v)" />
                                <Input v-model="opt.option_text" :placeholder="`Opción ${i + 1}`" class="flex-1" />
                                <button v-if="form.options.length > 2" class="text-xs text-[#D85A30]" @click="removeOption(i)">
                                    Quitar
                                </button>
                            </div>
                            <button type="button" class="w-fit text-xs text-[#214EA4]" @click="addOption">
                                + Agregar opción
                            </button>
                        </div>
                        <p v-if="form.errors.options" class="text-xs text-[#D85A30]">{{ form.errors.options }}</p>
                        <p v-if="form.errors.question" class="text-xs text-[#D85A30]">{{ form.errors.question }}</p>
                        <p v-if="form.errors.type" class="text-xs text-[#D85A30]">{{ form.errors.type }}</p>

                        <Button
                            class="mt-1 w-full bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                            :disabled="form.processing"
                            @click="submitQuestion"
                        >
                            Agregar pregunta
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
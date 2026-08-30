<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Column {
    type: 'assignment' | 'quiz';
    id: number;
    title: string;
}

interface Cell {
    type: 'assignment' | 'quiz';
    id: number;
    grade: number | null;
    status: 'pending' | 'submitted' | 'in_review' | 'graded';
    submission_id?: number;
    attempt_id?: number;
}

interface Row {
    student_id: number;
    student_name: string;
    cells: Cell[];
}

const props = defineProps<{
    virtual_course_id: number;
    subject_name: string;
    columns: Column[];
    rows: Row[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mis materias', href: '/lms/mis-materias-docente' },
    { title: props.subject_name, href: `/lms/virtual-courses/${props.virtual_course_id}/gradebook` },
];

const statusColor: Record<Cell['status'], string> = {
    pending: 'text-[#8A8A85]',
    submitted: 'text-[#D8A030]',
    in_review: 'text-[#D8A030]',
    graded: 'text-[#3B8A5A]',
};

const statusLabel: Record<Cell['status'], string> = {
    pending: '—',
    submitted: 'Sin calificar',
    in_review: 'En revisión',
    graded: '',
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="mb-4 text-xl font-medium text-[#1E3A5F]">{{ subject_name }} — Calificaciones</h1>

            <div class="overflow-x-auto rounded-xl border border-[#EAEAE5] bg-white">
                <table class="w-full border-collapse text-left text-[13px]">
                    <thead>
                        <tr class="bg-[#F7F9FC]">
                            <th class="sticky left-0 bg-[#F7F9FC] px-3 py-2.5 font-medium text-[#1E3A5F]">
                                Estudiante
                            </th>
                            <th
                                v-for="col in columns"
                                :key="`${col.type}-${col.id}`"
                                class="px-3 py-2.5 text-center font-medium text-[#1E3A5F]"
                            >
                                {{ col.title }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in rows"
                            :key="row.student_id"
                            class="border-t border-[#EAEAE5]"
                        >
                            <td class="sticky left-0 bg-white px-3 py-2 font-medium text-[#1E3A5F]">
                                {{ row.student_name }}
                            </td>
                            <td
                                v-for="cell in row.cells"
                                :key="`${cell.type}-${cell.id}`"
                                class="px-3 py-2 text-center"
                            >
                                <span v-if="cell.grade !== null" class="font-medium text-[#1E3A5F]">
                                    {{ cell.grade }}
                                </span>
                                <a
                                    v-else-if="cell.type === 'assignment' && cell.submission_id"
                                    :href="`/lms/submissions/${cell.submission_id}/calificar`"
                                    class="text-xs font-medium text-[#214EA4] hover:underline"
                                >
                                    Calificar
                                </a>
                                <a
                                    v-else-if="cell.type === 'quiz' && cell.attempt_id"
                                    :href="`/lms/quiz-attempts/${cell.attempt_id}/calificar`"
                                    class="text-xs font-medium text-[#214EA4] hover:underline"
                                >
                                    Calificar
                                </a>
                                <span v-else class="text-xs" :class="statusColor[cell.status]">
                                    {{ statusLabel[cell.status] }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
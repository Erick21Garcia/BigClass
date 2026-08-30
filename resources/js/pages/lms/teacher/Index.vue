<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Subject {
    section_id: number;
    virtual_course_id: number | null;
    subject_name: string;
    has_virtual_course: boolean;
    is_published: boolean;
    students_count: number;
    pending_count: number;
}

defineProps<{
    subjects: Subject[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mis materias', href: '/lms/mis-materias-docente' }];

function createVirtualCourse(sectionId: number) {
    router.post(`/lms/sections/${sectionId}/virtual-course`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="mb-4 text-xl font-medium text-[#1E3A5F]">Mis materias</h1>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="subject in subjects"
                    :key="subject.section_id"
                    class="overflow-hidden rounded-xl border border-[#EAEAE5] bg-white"
                >
                    <div class="h-2 bg-[#214EA4]" />
                    <div class="p-4">
                        <p class="text-[15px] font-medium text-[#1E3A5F]">{{ subject.subject_name }}</p>
                        <p class="mt-1 text-xs text-[#5B6B82]">
                            {{ subject.students_count }} estudiante{{ subject.students_count === 1 ? '' : 's' }}
                        </p>

                        <div v-if="!subject.has_virtual_course" class="mt-4">
                            <button
                                type="button"
                                class="w-full rounded-lg bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] px-3 py-2 text-xs font-medium text-white"
                                @click="createVirtualCourse(subject.section_id)"
                            >
                                Crear Aula Virtual
                            </button>
                        </div>

                        <div v-else class="mt-4 flex items-center justify-between">
                            <a
                                :href="`/lms/mis-materias-docente/${subject.virtual_course_id}`"
                                class="text-xs font-medium text-[#214EA4] hover:underline"
                            >
                                Gestionar
                            </a>

                            <a
                                v-if="subject.pending_count > 0"
                                :href="`/lms/virtual-courses/${subject.virtual_course_id}/gradebook`"
                                class="rounded-full bg-[#D85A30]/10 px-2.5 py-1 text-[11px] font-medium text-[#D85A30]"
                            >
                                {{ subject.pending_count }} pendiente{{ subject.pending_count === 1 ? '' : 's' }}
                            </a>
                            <a
                                v-else
                                :href="`/lms/virtual-courses/${subject.virtual_course_id}/gradebook`"
                                class="text-[11px] text-[#5B6B82] hover:underline"
                            >
                                Ver notas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
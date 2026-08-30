<script setup lang="ts">
import { computed, ref } from 'vue';
import axios from 'axios';
import StudentLmsLayout from '@/layouts/StudentLmsLayout.vue';

interface Subject {
    section_id: number;
    virtual_course_id: number | null;
    subject_name: string;
    teacher_name: string | null;
    enabled: boolean;
    disabled_message: string | null;
    progress: number | null;
}

interface UnitItem {
    type: 'resource' | 'assignment' | 'quiz';
    id: number;
    title: string;
    status: string;
    download_url?: string;
}

interface UnitDetail {
    id: number;
    name: string;
    progress: number;
    items: UnitItem[];
}

interface CourseDetail {
    virtual_course_id: number;
    course_progress: number;
    units: UnitDetail[];
}

const props = defineProps<{
    subjects: Subject[];
}>();

const activeSubjectIndex = ref<number>(
    Math.max(props.subjects.findIndex((s) => s.enabled), 0),
);

const activeSubject = computed(() => props.subjects[activeSubjectIndex.value]);

const courseDetail = ref<CourseDetail | null>(null);
const loadingDetail = ref(false);
const openUnitIds = ref<Set<number>>(new Set());

async function selectSubject(index: number) {
    const subject = props.subjects[index];
    activeSubjectIndex.value = index;
    courseDetail.value = null;

    if (!subject.enabled || !subject.virtual_course_id) {
        return;
    }

    loadingDetail.value = true;
    try {
        const { data } = await axios.get<CourseDetail>(
            `/lms/mis-materias/${subject.virtual_course_id}`,
        );
        courseDetail.value = data;
        // Primera unidad abierta por defecto, igual que el boceto aprobado.
        openUnitIds.value = new Set(data.units.length ? [data.units[0].id] : []);
    } finally {
        loadingDetail.value = false;
    }
}

function toggleUnit(unitId: number) {
    const next = new Set(openUnitIds.value);
    next.has(unitId) ? next.delete(unitId) : next.add(unitId);
    openUnitIds.value = next;
}

async function openResource(item: UnitItem) {
    if (!item.download_url) return;

    // Se marca como leído (Punto de progreso) y se abre el archivo a la vez.
    axios.post(`/lms/resources/${item.id}/mark-viewed`).catch(() => {});
    window.open(item.download_url, '_blank');

    // Refleja el cambio de inmediato en la UI sin esperar recargar.
    if (courseDetail.value) {
        const unit = courseDetail.value.units.find((u) =>
            u.items.some((i) => i.type === 'resource' && i.id === item.id),
        );
        const target = unit?.items.find((i) => i.type === 'resource' && i.id === item.id);
        if (target) target.status = 'read';
    }
}

const itemIcon: Record<UnitItem['type'], string> = {
    resource: 'ti-file',
    assignment: 'ti-clipboard-text',
    quiz: 'ti-help-circle',
};

const statusLabel: Record<string, string> = {
    read: 'Leído',
    pending: 'Pendiente',
    submitted: 'Entregado',
    graded: 'Calificado',
    in_review: 'En revisión',
};

const statusColor: Record<string, string> = {
    read: 'text-[#8A8A85]',
    pending: 'text-[#D85A30]',
    submitted: 'text-[#3B8A5A]',
    graded: 'text-[#3B8A5A]',
    in_review: 'text-[#D8A030]',
};

// Carga inicial: si ya hay una materia habilitada, trae su detalle de una vez.
if (activeSubject.value?.enabled) {
    selectSubject(activeSubjectIndex.value);
}
</script>

<template>
    <StudentLmsLayout title="Mis materias">
        <div class="grid grid-cols-[220px_1fr] gap-6">
            <!-- Sidebar de materias -->
            <div class="flex flex-col gap-1.5">
                <button
                    v-for="(subject, index) in subjects"
                    :key="subject.section_id"
                    type="button"
                    :disabled="!subject.enabled"
                    class="rounded-lg px-3 py-2.5 text-left transition-colors disabled:cursor-not-allowed"
                    :class="
                        index === activeSubjectIndex
                            ? 'bg-[#1E3A5F]'
                            : subject.enabled
                              ? 'bg-white hover:bg-[#F0F3F8]'
                              : 'bg-white/60'
                    "
                    @click="subject.enabled && selectSubject(index)"
                >
                    <p
                        class="text-[13px] font-medium"
                        :class="index === activeSubjectIndex ? 'text-white' : 'text-[#1E3A5F]'"
                    >
                        {{ subject.subject_name }}
                    </p>

                    <div
                        v-if="subject.enabled"
                        class="mt-1.5 h-1 rounded-full"
                        :class="index === activeSubjectIndex ? 'bg-white/25' : 'bg-[#EEF1F6]'"
                    >
                        <div
                            class="h-full rounded-full"
                            :class="index === activeSubjectIndex ? 'bg-[#93C5FD]' : 'bg-[#214EA4]'"
                            :style="{ width: `${subject.progress ?? 0}%` }"
                        />
                    </div>
                    <p v-else class="mt-1 text-[11px] text-[#8A8A85]">No disponible</p>
                </button>
            </div>

            <!-- Panel principal -->
            <div v-if="activeSubject">
                <div
                    v-if="!activeSubject.enabled"
                    class="rounded-xl border border-[#EAEAE5] bg-white p-4 text-sm text-[#5B6B82]"
                >
                    {{ activeSubject.disabled_message }}
                </div>

                <template v-else>
                    <div class="mb-3.5 rounded-xl border border-[#EAEAE5] bg-white p-4">
                        <p class="lms-wordmark text-[17px] text-[#1E3A5F]">
                            {{ activeSubject.subject_name }}
                        </p>
                        <p class="mb-2.5 text-xs text-[#5B6B82]">{{ activeSubject.teacher_name }}</p>
                        <div class="h-1.5 rounded-full bg-[#EEF1F6]">
                            <div
                                class="h-full rounded-full bg-[#214EA4]"
                                :style="{ width: `${courseDetail?.course_progress ?? activeSubject.progress ?? 0}%` }"
                            />
                        </div>
                        <p class="mt-1.5 text-[11px] text-[#8A8A85]">
                            {{ courseDetail?.course_progress ?? activeSubject.progress ?? 0 }}% completado del curso
                        </p>
                    </div>

                    <p v-if="loadingDetail" class="text-sm text-[#8A8A85]">Cargando…</p>

                    <div v-else class="flex flex-col gap-2">
                        <div
                            v-for="unit in courseDetail?.units ?? []"
                            :key="unit.id"
                            class="overflow-hidden rounded-lg border border-[#EAEAE5] bg-white"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between px-3.5 py-2.5"
                                @click="toggleUnit(unit.id)"
                            >
                                <span class="text-[13px] font-medium text-[#1E3A5F]">{{ unit.name }}</span>
                                <span class="flex items-center gap-2">
                                    <span class="text-[11px] text-[#8A8A85]">{{ unit.progress }}%</span>
                                    <i
                                        class="ti ti-chevron-down text-[16px] text-[#214EA4] transition-transform"
                                        :class="{ 'rotate-180': openUnitIds.has(unit.id) }"
                                    />
                                </span>
                            </button>

                            <div v-if="openUnitIds.has(unit.id)" class="px-3.5 pb-2.5">
                                <a
                                    v-for="item in unit.items"
                                    :key="`${item.type}-${item.id}`"
                                    :href="
                                        item.type === 'assignment'
                                            ? `/lms/assignments/${item.id}/entrega`
                                            : item.type === 'quiz'
                                              ? `/lms/quizzes/${item.id}/resolver`
                                              : undefined
                                    "
                                    class="flex items-center gap-2.5 border-t border-[#EAEAE5] py-1.5 hover:bg-[#F7F9FC]"
                                    @click="item.type === 'resource' ? ($event.preventDefault(), openResource(item)) : null"
                                >
                                    <i :class="['ti', itemIcon[item.type], 'text-[15px] text-[#214EA4]']" />
                                    <span class="flex-1 text-[12.5px] text-[#1E3A5F]">{{ item.title }}</span>
                                    <span class="text-[10.5px]" :class="statusColor[item.status]">
                                        {{ statusLabel[item.status] }}
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
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
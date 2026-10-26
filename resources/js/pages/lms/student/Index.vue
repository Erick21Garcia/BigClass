<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useLmsBanner } from '@/composables/useLmsBanner';
import axios from 'axios';
import {
    ArrowRight,
    BookOpen,
    ChevronDown,
    ClipboardList,
    FileText,
    HelpCircle,
    Lock,
} from 'lucide-vue-next';
import { computed, ref, type Component } from 'vue';
import StudentLmsLayout from '@/layouts/StudentLmsLayout.vue';

defineOptions({ layout: StudentLmsLayout });

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

    axios.post(`/lms/resources/${item.id}/mark-viewed`).catch(() => {});
    window.open(item.download_url, '_blank');

    if (courseDetail.value) {
        const unit = courseDetail.value.units.find((u) =>
            u.items.some((i) => i.type === 'resource' && i.id === item.id),
        );
        const target = unit?.items.find((i) => i.type === 'resource' && i.id === item.id);
        if (target) target.status = 'read';
    }
}

function itemHref(item: UnitItem): string | undefined {
    if (item.type === 'assignment') return `/lms/assignments/${item.id}/entrega`;
    if (item.type === 'quiz') return `/lms/quizzes/${item.id}/resolver`;
    return undefined;
}

function onItemClick(event: MouseEvent, item: UnitItem) {
    event.preventDefault();
    if (item.type === 'resource') {
        openResource(item);
        return;
    }
    const href = itemHref(item);
    if (href) router.visit(href);
}

const itemIcon: Record<UnitItem['type'], Component> = {
    resource: FileText,
    assignment: ClipboardList,
    quiz: HelpCircle,
};

const typeLabel: Record<UnitItem['type'], string> = {
    resource: 'Recurso',
    assignment: 'Tarea',
    quiz: 'Cuestionario',
};

const typeTile: Record<UnitItem['type'], string> = {
    resource: 'from-[#1E3A5F] to-[#214EA4] text-white',
    assignment: 'from-[#214EA4] to-[#3F7BE0] text-white',
    quiz: 'from-[#5E97F0] to-[#8CB8FF] text-[#0B2545]',
};

const pendingHint: Record<UnitItem['type'], string> = {
    resource: 'Pendiente de lectura',
    assignment: 'Pendiente de entrega',
    quiz: 'Pendiente de resolver',
};

const statusLabel: Record<string, string> = {
    read: 'Leído',
    pending: 'Pendiente',
    submitted: 'Entregado',
    graded: 'Calificado',
    in_review: 'En revisión',
};

const statusChip: Record<string, string> = {
    read: 'bg-[#E8EEFB] text-[#4A5D7E]',
    pending: 'border border-[#F2D27A] bg-[#FFF1CC] text-[#9A5B00]',
    submitted: 'bg-emerald-100 text-emerald-700',
    graded: 'bg-emerald-100 text-emerald-700',
    in_review: 'bg-[#E8EEFB] text-[#214EA4]',
};

const units = computed(() => courseDetail.value?.units ?? []);

const courseProgress = computed(
    () => courseDetail.value?.course_progress ?? activeSubject.value?.progress ?? 0,
);

const allItems = computed(() =>
    units.value.flatMap((unit) => unit.items.map((item) => ({ item, unit }))),
);
const pendingCount = computed(() => allItems.value.filter(({ item }) => item.status === 'pending').length);
const nextPending = computed(() => allItems.value.find(({ item }) => item.status === 'pending') ?? null);

const completedCount = (unit: UnitDetail) => unit.items.filter((i) => i.status !== 'pending').length;

const plural = (n: number, one: string, many: string) => `${n} ${n === 1 ? one : many}`;

function subjectClass(subject: Subject, index: number) {
    if (index === activeSubjectIndex.value)
        return 'border-transparent bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white shadow-[0_14px_26px_-14px_rgba(33,78,164,0.8)]';
    if (subject.enabled)
        return 'border-[#D8E2F4] bg-white text-[#0B2545] hover:border-[#214EA4]/40 hover:shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)]';
    return 'border-dashed border-[#D8E2F4] bg-[#F6F9FE] text-[#6B7A90]';
}

function subjectTileClass(subject: Subject, index: number) {
    if (index === activeSubjectIndex.value) return 'bg-white/20 text-white';
    if (subject.enabled) return 'bg-[#E8EEFB] text-[#214EA4]';
    return 'bg-[#E4ECF9] text-[#8A97AB]';
}

if (activeSubject.value?.enabled) {
    selectSubject(activeSubjectIndex.value);
}

useLmsBanner(() =>
    activeSubject.value
        ? {
              tag: { label: 'Curso', icon: BookOpen },
              title: activeSubject.value.subject_name,
              teacher: activeSubject.value.enabled ? activeSubject.value.teacher_name : null,
              chips:
                  activeSubject.value.enabled && courseDetail.value
                      ? [
                            { label: plural(units.value.length, 'unidad', 'unidades') },
                            { label: plural(allItems.value.length, 'actividad', 'actividades') },
                            { label: plural(pendingCount.value, 'pendiente', 'pendientes') },
                        ]
                      : [],
              ring: activeSubject.value.enabled
                  ? {
                        text: `${courseProgress.value}%`,
                        caption: 'completado del curso',
                        progress: courseProgress.value,
                    }
                  : null,
          }
        : null,
);

</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[310px_1fr]">
        <Head title="Mis materias" />
        <!-- Sidebar de materias -->
        <div>
            <p class="mb-2.5 ml-1 text-xs font-semibold uppercase tracking-[0.08em] text-[#6B7A90]">Mis cursos</p>

            <div class="flex flex-col gap-3">
                <button
                    v-for="(subject, index) in subjects"
                    :key="subject.section_id"
                    type="button"
                    :disabled="!subject.enabled"
                    :title="!subject.enabled ? (subject.disabled_message ?? undefined) : undefined"
                    class="flex w-full items-center gap-3 rounded-[18px] border px-4 py-4 text-left transition disabled:cursor-not-allowed"
                    :class="subjectClass(subject, index)"
                    @click="subject.enabled && selectSubject(index)"
                >
                    <span
                        class="grid size-[46px] shrink-0 place-items-center rounded-[13px]"
                        :class="subjectTileClass(subject, index)"
                    >
                        <BookOpen v-if="subject.enabled" class="size-5" />
                        <Lock v-else class="size-[18px]" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block text-[15px] font-semibold leading-tight">
                            {{ subject.subject_name }}
                        </span>

                        <template v-if="subject.enabled">
                            <span
                                class="mt-2 block h-[5px] overflow-hidden rounded-full"
                                :class="index === activeSubjectIndex ? 'bg-white/25' : 'bg-[#E4ECF9]'"
                            >
                                <span
                                    class="block h-full rounded-full transition-all duration-500"
                                    :class="
                                        index === activeSubjectIndex
                                            ? 'bg-[#8CB8FF]'
                                            : 'bg-gradient-to-r from-[#1E3A5F] to-[#214EA4]'
                                    "
                                    :style="{ width: `${subject.progress ?? 0}%` }"
                                />
                            </span>
                            <span
                                class="mt-1.5 block text-[12.5px]"
                                :class="index === activeSubjectIndex ? 'text-[#B9CDEE]' : 'text-[#6B7A90]'"
                            >
                                {{ subject.progress ?? 0 }}% completado
                            </span>
                        </template>

                        <span
                            v-else
                            class="mt-1.5 inline-flex rounded-full bg-[#E8EEFB] px-2.5 py-0.5 text-[11px] font-medium text-[#6B7A90]"
                        >
                            No disponible
                        </span>
                    </span>
                </button>
            </div>
        </div>

        <!-- Panel principal -->
        <div v-if="activeSubject" class="min-w-0 [&>*:first-child]:!mt-0">
            <!-- Curso no disponible -->
            <div
                v-if="!activeSubject.enabled"
                class="flex items-center gap-4 rounded-[18px] border border-dashed border-[#D8E2F4] bg-white p-7"
            >
                <span class="grid size-[46px] shrink-0 place-items-center rounded-[13px] bg-[#E4ECF9] text-[#8A97AB]">
                    <Lock class="size-5" />
                </span>
                <div>
                    <p class="text-base font-semibold text-[#0B2545]">Curso no disponible</p>
                    <p class="mt-0.5 text-sm text-[#5B6B82]">
                        {{ activeSubject.disabled_message ?? 'Este curso aún no está disponible.' }}
                    </p>
                </div>
            </div>

            <template v-else>

                <!-- Cargando -->
                <div v-if="loadingDetail" class="mt-[18px] space-y-3">
                    <div class="h-[92px] animate-pulse rounded-[18px] bg-white/70"></div>
                    <div class="h-[200px] animate-pulse rounded-[18px] bg-white/70"></div>
                </div>

                <template v-else>
                    <!-- Siguiente actividad -->
                    <div
                        v-if="nextPending"
                        class="mt-[18px] flex flex-wrap items-center gap-[18px] rounded-[18px] border border-l-[5px] border-[#D8E2F4] border-l-[#F2A81D] bg-white px-7 py-5 shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)]"
                    >
                        <span
                            class="grid size-[54px] shrink-0 place-items-center rounded-[15px] bg-gradient-to-br from-[#F2A81D] to-[#F5C04A] text-white"
                        >
                            <component :is="itemIcon[nextPending.item.type]" class="size-6" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.06em] text-[#B26E00]">
                                Siguiente actividad
                            </p>
                            <p class="mt-0.5 truncate text-[18.5px] font-semibold text-[#0B2545]">
                                {{ nextPending.item.title }}
                            </p>
                            <p class="mt-0.5 text-sm text-[#5B6B82]">
                                {{ nextPending.unit.name }} · {{ pendingHint[nextPending.item.type] }}
                            </p>
                        </div>
                        <a
                            :href="itemHref(nextPending.item)"
                            class="inline-flex h-12 cursor-pointer items-center gap-2 rounded-[13px] bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] px-6 text-[15px] font-medium text-white shadow-[0_10px_20px_-10px_rgba(33,78,164,0.8)] transition hover:brightness-110"
                            @click="onItemClick($event, nextPending.item)"
                        >
                            Continuar
                            <ArrowRight class="size-4" />
                        </a>
                    </div>

                    <!-- Unidades -->
                    <div class="mt-[18px] flex flex-col gap-4">
                        <div
                            v-if="units.length === 0"
                            class="rounded-[18px] border border-[#D8E2F4] bg-white p-7 text-sm text-[#5B6B82]"
                        >
                            Este curso aún no tiene unidades publicadas.
                        </div>

                        <div
                            v-for="(unit, ui) in units"
                            :key="unit.id"
                            class="overflow-hidden rounded-[18px] border border-[#D8E2F4] bg-white shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)]"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center gap-4 px-7 py-5 text-left"
                                @click="toggleUnit(unit.id)"
                            >
                                <span
                                    class="grid size-[46px] shrink-0 place-items-center rounded-[13px] bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-[15px] font-semibold text-white"
                                >
                                    {{ ui + 1 }}
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-lg font-semibold text-[#0B2545]">{{ unit.name }}</span>
                                    <span class="mt-0.5 block text-[13.5px] text-[#5B6B82]">
                                        {{ plural(unit.items.length, 'actividad', 'actividades') }} ·
                                        {{ plural(completedCount(unit), 'completada', 'completadas') }}
                                    </span>
                                </span>

                                <span class="hidden h-[9px] w-[260px] overflow-hidden rounded-full bg-[#E4ECF9] md:block">
                                    <span
                                        class="block h-full rounded-full bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF] transition-all duration-500"
                                        :style="{ width: `${unit.progress}%` }"
                                    />
                                </span>
                                <span class="rounded-full bg-[#E8EEFB] px-2.5 py-[3px] text-[13px] font-semibold tabular-nums text-[#214EA4]">
                                    {{ unit.progress }}%
                                </span>
                                <ChevronDown
                                    class="size-[18px] shrink-0 text-[#6B7A90] transition-transform"
                                    :class="{ 'rotate-180': openUnitIds.has(unit.id) }"
                                />
                            </button>

                            <div v-if="openUnitIds.has(unit.id)">
                                <a
                                    v-for="item in unit.items"
                                    :key="`${item.type}-${item.id}`"
                                    :href="itemHref(item)"
                                    class="flex cursor-pointer items-center gap-4 border-t border-[#EAF0FB] px-7 py-[18px] transition hover:bg-[#F4F7FD]"
                                    @click="onItemClick($event, item)"
                                >
                                    <span
                                        :class="[typeTile[item.type], 'grid size-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br']"
                                    >
                                        <component :is="itemIcon[item.type]" class="size-[18px]" />
                                    </span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-base font-medium text-[#0B2545]">{{ item.title }}</span>
                                        <span class="mt-0.5 block text-[13px] text-[#6B7A90]">{{ typeLabel[item.type] }}</span>
                                    </span>

                                    <span
                                        :class="[statusChip[item.status] ?? 'bg-[#E8EEFB] text-[#4A5D7E]', 'rounded-full px-3.5 py-1 text-[13px] font-medium']"
                                    >
                                        {{ statusLabel[item.status] ?? item.status }}
                                    </span>

                                    <span class="grid size-9 shrink-0 place-items-center rounded-[10px] bg-[#E8EEFB]">
                                        <ArrowRight class="size-[15px] text-[#214EA4]" />
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </div>
    </div>
</template>
<script setup lang="ts">
import { computed, ref, type Component } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CalendarDays,
    CheckCircle2,
    Clock,
    Download,
    FileText,
    GraduationCap,
    GripVertical,
    Inbox,
    Search,
    Users,
    XCircle,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type BreadcrumbItem } from '@/types';

interface Document {
    type: string;
    original_name: string;
    download_url: string;
}

interface ApplicantCard {
    id: number;
    full_name: string;
    career_name: string;
    submitted_at: string | null;
    documents: Document[];
}

interface PendingMatricula {
    student_id: number;
    student_name: string;
    career_name: string;
    enroll_url: string | null;
}

interface Column {
    status: string;
    applicants: ApplicantCard[];
}

const props = defineProps<{
    columns: Column[];
    pending_matriculas: PendingMatricula[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Revisión de postulaciones', href: '/admisiones/revision' }];

const page = usePage();
const statusError = computed(() => (page.props.errors as Record<string, string>)?.status);

const columnLabel: Record<string, string> = {
    recibida: 'Recibida',
    en_revision: 'En revisión',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
};

const documentLabel: Record<string, string> = {
    cedula: 'Cédula',
    titulo_bachiller: 'Título de bachiller',
    foto: 'Foto',
    comprobante_pago: 'Comprobante de pago',
};

const DOC_TYPES = Object.keys(documentLabel);
const DOC_TOTAL = DOC_TYPES.length;

// ── Estilos por columna ──────────────────────────────────────────────
interface ColumnStyle {
    icon: Component;
    bar: string;
    tile: string;
    pill: string;
}

const columnStyles: Record<string, ColumnStyle> = {
    recibida: {
        icon: Inbox,
        bar: 'bg-[#8CB8FF]',
        tile: 'from-[#5E97F0] to-[#8CB8FF] text-[#0B2545]',
        pill: 'bg-[#E8EEFB] text-[#214EA4] dark:bg-[#214EA4]/25 dark:text-[#8CB8FF]',
    },
    en_revision: {
        icon: Search,
        bar: 'bg-[#214EA4]',
        tile: 'from-[#214EA4] to-[#3F7BE0] text-white',
        pill: 'bg-[#E8EEFB] text-[#214EA4] dark:bg-[#214EA4]/25 dark:text-[#8CB8FF]',
    },
    aprobada: {
        icon: CheckCircle2,
        bar: 'bg-[#16A36A]',
        tile: 'from-[#16A36A] to-[#34C58A] text-white',
        pill: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
    },
    rechazada: {
        icon: XCircle,
        bar: 'bg-[#E5484D]',
        tile: 'from-[#E5484D] to-[#F07A7E] text-white',
        pill: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400',
    },
};

const colStyle = (status: string): ColumnStyle =>
    columnStyles[status] ?? {
        icon: Inbox,
        bar: 'bg-[#C9DAF7]',
        tile: 'from-[#1E3A5F] to-[#214EA4] text-white',
        pill: 'bg-[#E8EEFB] text-[#214EA4]',
    };

// ── Resumen del banner ───────────────────────────────────────────────
const plural = (n: number, one: string, many: string) => `${n} ${n === 1 ? one : many}`;

const countOf = (status: string) => props.columns.find((c) => c.status === status)?.applicants.length ?? 0;
const total = computed(() => props.columns.reduce((n, c) => n + c.applicants.length, 0));
const toReview = computed(() => countOf('recibida') + countOf('en_revision'));
const approved = computed(() => countOf('aprobada'));
const resolved = computed(() => approved.value + countOf('rechazada'));

const RING = 2 * Math.PI * 42;
const ringDash = computed(() => (total.value ? (resolved.value / total.value) * RING : 0));

// ── Utilidades de presentación ───────────────────────────────────────
const initials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('');

const formatDate = (iso: string) =>
    new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });

// ── Drag and drop nativo (sin librería externa) ──────────────────────
const draggingId = ref<number | null>(null);
const draggingFrom = ref<string | null>(null);
const dragOver = ref<string | null>(null);

function onDragStart(event: DragEvent, applicantId: number, status: string) {
    draggingId.value = applicantId;
    draggingFrom.value = status;
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        // Firefox no inicia el arrastre si no se define algún dato.
        event.dataTransfer.setData('text/plain', String(applicantId));
    }
}

function onDragEnd() {
    draggingId.value = null;
    draggingFrom.value = null;
    dragOver.value = null;
}

function onDragLeave(event: DragEvent, status: string) {
    const zone = event.currentTarget as HTMLElement;
    if (!zone.contains(event.relatedTarget as Node) && dragOver.value === status) dragOver.value = null;
}

function onDrop(newStatus: string) {
    const id = draggingId.value;
    const from = draggingFrom.value;
    onDragEnd();
    // Soltar en la misma columna no hace nada.
    if (id === null || from === newStatus) return;
    moveApplicant(id, newStatus, null);
}

// ── Modal de detalle / decisión ──────────────────────────────────────
const selected = ref<ApplicantCard | null>(null);
const selectedColumn = ref<string>('');
const decisionNotes = ref('');

const docFor = (type: string) => selected.value?.documents.find((d) => d.type === type) ?? null;

function openDetail(applicant: ApplicantCard, status: string) {
    selected.value = applicant;
    selectedColumn.value = status;
    decisionNotes.value = '';
}

function moveApplicant(applicantId: number, newStatus: string, notes: string | null) {
    router.post(
        `/admisiones/revision/${applicantId}/mover`,
        { status: newStatus, notes },
        { preserveScroll: true, onSuccess: () => { selected.value = null; } },
    );
}

function confirmDecision(newStatus: string) {
    if (!selected.value) return;
    moveApplicant(selected.value.id, newStatus, decisionNotes.value || null);
}
</script>

<template>
    <Head title="Revisión de postulaciones">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 bg-[#EAF0FB] p-6 dark:bg-transparent">

            <!-- Banner -->
            <div
                class="relative overflow-hidden rounded-[20px] bg-[linear-gradient(120deg,#1E3A5F,#214EA4)] px-9 py-6 text-white"
            >
                <div class="pointer-events-none absolute -right-14 -top-24 h-[220px] w-[220px] rotate-45 bg-[#2F63C9]/80"></div>
                <div class="pointer-events-none absolute -top-32 right-[190px] h-[170px] w-[170px] rotate-45 bg-[#8CB8FF]/20"></div>
                <div class="pointer-events-none absolute -bottom-24 -left-12 h-[150px] w-[150px] rotate-45 bg-[#2F63C9]/55"></div>

                <div class="relative flex flex-wrap items-center justify-between gap-6">
                    <div class="min-w-0">
                        <h1 class="acadex-title text-[34px] leading-[1.1]">Revisión de postulaciones</h1>
                        <p class="mt-1.5 text-sm text-[#C9DAF7]">
                            Arrastra una postulación a otra columna o ábrela para decidir.
                        </p>

                        <div class="mt-3.5 flex flex-wrap gap-2.5">
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/[0.13] px-3.5 py-[7px] text-[13.5px] text-[#E4EDFC]">
                                <Users class="size-[15px]" />
                                {{ plural(total, 'postulación', 'postulaciones') }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-[#F2A81D]/50 bg-[#F2A81D]/25 px-3.5 py-[7px] text-[13.5px] text-[#FFE3A6]">
                                <Search class="size-[15px]" />
                                {{ toReview }} por revisar
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-[#5BE3A4]/40 bg-[#5BE3A4]/20 px-3.5 py-[7px] text-[13.5px] text-[#C8F7E1]">
                                <CheckCircle2 class="size-[15px]" />
                                {{ plural(approved, 'aprobada', 'aprobadas') }}
                            </span>
                        </div>
                    </div>

                    <div class="text-center">
                        <svg width="104" height="104" viewBox="0 0 104 104">
                            <circle cx="52" cy="52" r="42" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="10" />
                            <circle
                                cx="52"
                                cy="52"
                                r="42"
                                fill="none"
                                stroke="#8CB8FF"
                                stroke-width="10"
                                stroke-linecap="round"
                                transform="rotate(-90 52 52)"
                                class="transition-all duration-500"
                                :style="{
                                    strokeDasharray: `${ringDash} ${RING}`,
                                    opacity: ringDash > 0 ? 1 : 0,
                                }"
                            />
                            <text x="52" y="58" text-anchor="middle" font-size="22" font-weight="600" fill="#fff">
                                {{ resolved }}/{{ total }}
                            </text>
                        </svg>
                        <p class="mt-1 text-[13px] text-[#C9DAF7]">resueltas</p>
                    </div>
                </div>
            </div>

            <!-- Error al mover una postulación -->
            <div
                v-if="statusError"
                class="flex items-start gap-3 rounded-[14px] border-l-4 border-[#E5484D] bg-red-50 px-4 py-3 text-sm text-[#8A2A2D] dark:bg-red-950/30 dark:text-red-300"
            >
                <AlertCircle class="mt-0.5 size-[18px] shrink-0" />
                <span>No se pudo actualizar la postulación: {{ statusError }}</span>
            </div>

            <!-- Aprobados pendientes de matrícula -->
            <div
                v-if="pending_matriculas.length"
                class="rounded-[18px] border border-[#F2D27A] border-l-[5px] border-l-[#F2A81D] bg-[#FFF8E6] p-5 shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)] dark:border-amber-900 dark:border-l-[#F2A81D] dark:bg-amber-950/30"
            >
                <div class="flex items-center gap-3.5">
                    <span class="grid size-[42px] shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#F2A81D] to-[#F5C04A] text-white">
                        <GraduationCap class="size-5" />
                    </span>
                    <div>
                        <p class="text-[15.5px] font-semibold text-[#0B2545] dark:text-foreground">
                            {{ pending_matriculas.length }} aprobado(s) pendiente(s) de matrícula
                        </p>
                        <p class="mt-0.5 text-[13px] text-[#5B6B82] dark:text-muted-foreground">
                            Matricúlalos en su primer semestre para que puedan ver sus materias.
                        </p>
                    </div>
                </div>

                <ul class="mt-4 grid gap-2.5 lg:grid-cols-2">
                    <li
                        v-for="s in pending_matriculas"
                        :key="s.student_id"
                        class="flex items-center gap-3 rounded-[14px] border border-[#F2E3B5] bg-white px-3.5 py-3 dark:border-border dark:bg-card"
                    >
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-sm font-semibold text-white">
                            {{ initials(s.student_name) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-[#0B2545] dark:text-foreground">{{ s.student_name }}</p>
                            <p class="truncate text-xs text-[#6B7A90] dark:text-muted-foreground">{{ s.career_name }}</p>
                        </div>
                        <a
                            v-if="s.enroll_url"
                            :href="s.enroll_url"
                            class="inline-flex h-9 shrink-0 items-center rounded-[10px] bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] px-4 text-[13px] font-medium text-white shadow-[0_8px_16px_-8px_rgba(33,78,164,0.8)] transition hover:brightness-110"
                        >
                            Matricular
                        </a>
                        <span v-else class="max-w-[170px] text-right text-xs leading-snug text-[#D85A30]">
                            La carrera no tiene Semestre 1 configurado
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Tablero -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="column in columns"
                    :key="column.status"
                    class="relative min-h-[320px] overflow-hidden rounded-[18px] border bg-white/70 p-3.5 pt-5 transition dark:bg-card/60"
                    :class="
                        dragOver === column.status && draggingFrom !== column.status
                            ? 'border-2 border-dashed border-[#214EA4] bg-[#E8EEFB] dark:bg-[#214EA4]/15'
                            : 'border-[#D8E2F4] dark:border-border'
                    "
                    @dragover.prevent="dragOver = column.status"
                    @dragleave="onDragLeave($event, column.status)"
                    @drop.prevent="onDrop(column.status)"
                >
                    <div :class="[colStyle(column.status).bar, 'absolute inset-x-0 top-0 h-1']"></div>

                    <div class="mb-3.5 flex items-center gap-3">
                        <span
                            :class="[colStyle(column.status).tile, 'grid size-[38px] shrink-0 place-items-center rounded-[11px] bg-gradient-to-br']"
                        >
                            <component :is="colStyle(column.status).icon" class="size-[19px]" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-[15px] font-semibold">{{ columnLabel[column.status] ?? column.status }}</h2>
                            <p class="text-xs text-muted-foreground">
                                {{ plural(column.applicants.length, 'postulación', 'postulaciones') }}
                            </p>
                        </div>
                        <span
                            :class="[colStyle(column.status).pill, 'rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums']"
                        >
                            {{ column.applicants.length }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-2.5">
                        <div
                            v-for="applicant in column.applicants"
                            :key="applicant.id"
                            draggable="true"
                            class="group cursor-grab rounded-[14px] border border-[#E1E9F7] bg-white p-3.5 shadow-[0_8px_18px_-14px_rgba(30,58,95,0.6)] transition hover:-translate-y-px hover:border-[#214EA4]/40 hover:shadow-[0_14px_24px_-14px_rgba(30,58,95,0.7)] active:cursor-grabbing dark:border-border dark:bg-card"
                            :class="draggingId === applicant.id ? 'opacity-40' : ''"
                            @dragstart="onDragStart($event, applicant.id, column.status)"
                            @dragend="onDragEnd"
                            @click="openDetail(applicant, column.status)"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="grid size-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-sm font-semibold text-white"
                                >
                                    {{ initials(applicant.full_name) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[14.5px] font-semibold leading-snug text-[#0B2545] dark:text-foreground">
                                        {{ applicant.full_name }}
                                    </p>
                                    <p class="mt-0.5 text-xs leading-snug text-[#5B6B82] dark:text-muted-foreground">
                                        {{ applicant.career_name }}
                                    </p>
                                </div>
                                <GripVertical
                                    class="size-4 shrink-0 text-[#B8C8E8] opacity-0 transition group-hover:opacity-100"
                                />
                            </div>

                            <div class="mt-3 flex gap-1">
                                <span
                                    v-for="n in DOC_TOTAL"
                                    :key="n"
                                    :class="[
                                        n <= applicant.documents.length
                                            ? 'bg-gradient-to-r from-[#1E3A5F] to-[#214EA4]'
                                            : 'bg-[#E4ECF9] dark:bg-muted',
                                        'h-1.5 flex-1 rounded-full',
                                    ]"
                                />
                            </div>

                            <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2">
                                <span
                                    :class="[
                                        applicant.documents.length >= DOC_TOTAL
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400'
                                            : 'border border-[#F2D27A] bg-[#FFF1CC] text-[#9A5B00]',
                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11.5px] font-medium',
                                    ]"
                                >
                                    <FileText class="size-3" />
                                    {{ applicant.documents.length }}/{{ DOC_TOTAL }} documentos
                                </span>
                                <span
                                    v-if="applicant.submitted_at"
                                    class="inline-flex items-center gap-1 text-[11.5px] text-[#8A97AB]"
                                >
                                    <CalendarDays class="size-3" />
                                    {{ formatDate(applicant.submitted_at) }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="column.applicants.length === 0"
                            class="grid place-items-center gap-1.5 rounded-[14px] border-2 border-dashed border-[#D8E2F4] px-3 py-8 text-center dark:border-border"
                        >
                            <component :is="colStyle(column.status).icon" class="size-6 text-[#B8C8E8]" />
                            <p class="text-xs text-[#8A97AB]">Sin postulaciones aquí.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle / decisión -->
        <Dialog :open="!!selected" @update:open="(v) => !v && (selected = null)">
            <DialogContent
                class="flex max-h-[90vh] w-[calc(100%-2rem)] flex-col gap-0 overflow-hidden rounded-[20px] p-0 sm:max-w-2xl [&>button]:text-white [&>button]:opacity-80 [&>button]:hover:opacity-100"
            >
                <!-- Encabezado compacto -->
                <div class="relative shrink-0 overflow-hidden bg-[linear-gradient(120deg,#1E3A5F,#214EA4)] px-6 py-4 text-white">
                    <div class="pointer-events-none absolute -right-10 -top-16 h-[150px] w-[150px] rotate-45 bg-[#2F63C9]/80"></div>
                    <div class="pointer-events-none absolute -bottom-16 -left-8 h-[110px] w-[110px] rotate-45 bg-[#2F63C9]/55"></div>

                    <DialogHeader class="relative flex-row items-center gap-3.5 space-y-0 pr-8 text-left">
                        <span
                            class="grid size-[46px] shrink-0 place-items-center rounded-2xl bg-[#8CB8FF] text-base font-semibold text-[#0B2545]"
                        >
                            {{ selected ? initials(selected.full_name) : '' }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <DialogTitle class="acadex-title truncate text-xl leading-tight text-white">
                                {{ selected?.full_name }}
                            </DialogTitle>
                            <DialogDescription class="mt-0.5 truncate text-[13px] text-[#C9DAF7]">
                                {{ selected?.career_name }}
                            </DialogDescription>
                        </div>
                        <span
                            v-if="selected"
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-white/30 bg-white/15 px-3 py-1 text-xs font-medium"
                        >
                            <component :is="colStyle(selectedColumn).icon" class="size-3.5" />
                            {{ columnLabel[selectedColumn] ?? selectedColumn }}
                        </span>
                    </DialogHeader>
                </div>

                <!-- Cuerpo (si la pantalla es muy baja, solo esta parte hace scroll) -->
                <div v-if="selected" class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-6 py-4">
                    <div>
                        <p class="mb-2 text-[11.5px] font-semibold uppercase tracking-[0.06em] text-[#6B7A90]">
                            Documentos · {{ selected.documents.length }}/{{ DOC_TOTAL }}
                        </p>

                        <div class="grid gap-2.5 sm:grid-cols-2">
                            <template v-for="type in DOC_TYPES" :key="type">
                                <a
                                    v-if="docFor(type)"
                                    :href="docFor(type)!.download_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="group flex items-center gap-3 rounded-[14px] border border-[#E1E9F7] bg-[#F4F7FD] px-3 py-2.5 transition hover:border-[#214EA4]/40 hover:bg-[#E8EEFB] dark:border-border dark:bg-muted/40"
                                >
                                    <span class="grid size-9 shrink-0 place-items-center rounded-[11px] bg-gradient-to-br from-[#214EA4] to-[#3F7BE0] text-white">
                                        <FileText class="size-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13.5px] font-semibold text-[#0B2545] dark:text-foreground">{{ documentLabel[type] }}</p>
                                        <p class="truncate text-[11.5px] text-[#6B7A90]">{{ docFor(type)!.original_name }}</p>
                                    </div>
                                    <Download class="size-4 shrink-0 text-[#214EA4] transition group-hover:translate-y-px" />
                                </a>

                                <div
                                    v-else
                                    class="flex items-center gap-3 rounded-[14px] border-2 border-dashed border-[#D8E2F4] px-3 py-2.5 dark:border-border"
                                >
                                    <span class="grid size-9 shrink-0 place-items-center rounded-[11px] bg-[#E4ECF9] text-[#8A97AB] dark:bg-muted">
                                        <Clock class="size-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13.5px] font-semibold text-[#6B7A90]">{{ documentLabel[type] }}</p>
                                        <p class="text-[11.5px] text-[#8A97AB]">Aún no subido</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="grid gap-1.5">
                        <label for="decision-notes" class="text-[13px] font-semibold text-[#0B2545] dark:text-foreground">
                            Notas de la decisión
                            <span class="font-normal text-[#8A97AB]">(opcional)</span>
                        </label>
                        <textarea
                            id="decision-notes"
                            v-model="decisionNotes"
                            rows="2"
                            class="w-full resize-none rounded-[14px] border-[1.5px] border-transparent bg-[#E8EEFB] px-4 py-2.5 text-sm text-[#0B2545] outline-none placeholder:text-[#7B8AA3] focus:border-[#1E3A5F] focus:bg-white focus:ring-4 focus:ring-[#1E3A5F]/10 dark:bg-muted dark:text-foreground"
                            placeholder="Escribe un comentario para esta decisión..."
                        />
                    </div>

                    <div
                        v-if="statusError"
                        class="flex items-start gap-2.5 rounded-xl border-l-4 border-[#E5484D] bg-red-50 px-3.5 py-2.5 text-[13px] text-[#8A2A2D] dark:bg-red-950/30 dark:text-red-300"
                    >
                        <AlertCircle class="mt-px size-4 shrink-0" />
                        {{ statusError }}
                    </div>
                </div>

                <!-- Acciones (siempre visibles) -->
                <DialogFooter class="shrink-0 gap-2 border-t border-[#E1E9F7] bg-[#F7F9FD] px-6 py-3 dark:border-border dark:bg-muted/30">
                    <Button variant="outline" class="rounded-xl" @click="confirmDecision('en_revision')">
                        En revisión
                    </Button>
                    <Button variant="destructive" class="rounded-xl" @click="confirmDecision('rechazada')">
                        Rechazar
                    </Button>
                    <Button
                        class="rounded-xl bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] text-white shadow-[0_10px_20px_-10px_rgba(33,78,164,0.8)] hover:brightness-110"
                        @click="confirmDecision('aprobada')"
                    >
                        Aprobar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.acadex-title {
    font-family: 'Fraunces', serif;
    font-weight: 600;
}
</style>
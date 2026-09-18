<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
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

interface Column {
    status: string;
    applicants: ApplicantCard[];
}

const props = defineProps<{
    columns: Column[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Revisión de admisiones', href: '/admisiones/revision' }];

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

// ── Drag and drop nativo (sin librería externa) ──────────────────────
const draggingId = ref<number | null>(null);

function onDragStart(applicantId: number) {
    draggingId.value = applicantId;
}

function onDrop(newStatus: string) {
    if (draggingId.value === null) return;
    moveApplicant(draggingId.value, newStatus, null);
    draggingId.value = null;
}

// ── Modal de detalle / decisión ──────────────────────────────────────
const selected = ref<ApplicantCard | null>(null);
const selectedColumn = ref<string>('');
const decisionNotes = ref('');

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
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="mb-4 text-xl font-medium text-[#1E3A5F]">Revisión de postulaciones</h1>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div
                    v-for="column in columns"
                    :key="column.status"
                    class="rounded-xl bg-[#F7F9FC] p-3"
                    @dragover.prevent
                    @drop="onDrop(column.status)"
                >
                    <p class="mb-3 text-xs font-medium text-[#1E3A5F]">
                        {{ columnLabel[column.status] }}
                        <span class="text-[#8A8A85]">({{ column.applicants.length }})</span>
                    </p>

                    <div class="flex flex-col gap-2">
                        <div
                            v-for="applicant in column.applicants"
                            :key="applicant.id"
                            draggable="true"
                            class="cursor-pointer rounded-lg border border-[#EAEAE5] bg-white p-3"
                            @dragstart="onDragStart(applicant.id)"
                            @click="openDetail(applicant, column.status)"
                        >
                            <p class="text-sm font-medium text-[#1E3A5F]">{{ applicant.full_name }}</p>
                            <p class="text-xs text-[#5B6B82]">{{ applicant.career_name }}</p>
                            <p class="mt-1.5 text-[11px] text-[#8A8A85]">
                                {{ applicant.documents.length }}/4 documentos
                            </p>
                        </div>

                        <p v-if="column.applicants.length === 0" class="text-xs text-[#8A8A85]">
                            Sin postulaciones aquí.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <Dialog :open="!!selected" @update:open="(v) => !v && (selected = null)">
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ selected?.full_name }}</DialogTitle>
                </DialogHeader>

                <div v-if="selected" class="flex flex-col gap-4">
                    <p class="text-sm text-[#5B6B82]">{{ selected.career_name }}</p>

                    <div class="flex flex-col gap-1.5">
                        <a
                            v-for="doc in selected.documents"
                            :key="doc.type"
                            :href="doc.download_url"
                            target="_blank"
                            class="flex items-center gap-2 text-sm text-[#214EA4] hover:underline"
                        >
                            <i class="ti ti-file" /> {{ documentLabel[doc.type] }} — {{ doc.original_name }}
                        </a>
                        <p v-if="selected.documents.length === 0" class="text-xs text-[#8A8A85]">
                            Sin documentos subidos todavía.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-xs font-medium text-[#1E3A5F]">Notas de la decisión (opcional)</label>
                        <textarea
                            v-model="decisionNotes"
                            rows="3"
                            class="rounded-md border border-[#EAEAE5] p-2 text-sm"
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="confirmDecision('en_revision')">En revisión</Button>
                    <Button variant="destructive" @click="confirmDecision('rechazada')">Rechazar</Button>
                    <Button class="bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white" @click="confirmDecision('aprobada')">
                        Aprobar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
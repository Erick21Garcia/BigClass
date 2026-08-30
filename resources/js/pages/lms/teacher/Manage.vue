<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type BreadcrumbItem } from '@/types';

interface Item {
    id: number;
    title: string;
}

interface UnitData {
    id: number;
    name: string;
    resources: Item[];
    assignments: Item[];
    quizzes: Item[];
}

const props = defineProps<{
    virtual_course_id: number;
    subject_name: string;
    is_published: boolean;
    evaluation_parameters: { id: number; name: string }[];
    units: UnitData[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mis materias', href: '/lms/mis-materias-docente' },
    { title: props.subject_name, href: `/lms/mis-materias-docente/${props.virtual_course_id}` },
];

function togglePublish() {
    const action = props.is_published ? 'unpublish' : 'publish';
    router.post(`/lms/virtual-courses/${props.virtual_course_id}/${action}`, {}, { preserveScroll: true });
}

const openUnitIds = ref<Set<number>>(new Set(props.units.length ? [props.units[0].id] : []));
function toggleUnit(id: number) {
    const next = new Set(openUnitIds.value);
    next.has(id) ? next.delete(id) : next.add(id);
    openUnitIds.value = next;
}

// ── Modal: nueva Unidad ─────────────────────────────────────────────
const unitModalOpen = ref(false);
const unitForm = useForm({ name: '' });
function submitUnit() {
    unitForm.post(`/lms/virtual-courses/${props.virtual_course_id}/units`, {
        preserveScroll: true,
        onSuccess: () => { unitModalOpen.value = false; unitForm.reset(); },
    });
}

// ── Modal: nuevo Material ───────────────────────────────────────────
const resourceModalOpen = ref(false);
const resourceUnitId = ref<number | null>(null);
const resourceForm = useForm({ title: '', file: null as File | null });
function openResourceModal(unitId: number) {
    resourceUnitId.value = unitId;
    resourceModalOpen.value = true;
}
function submitResource() {
    resourceForm.post(`/lms/units/${resourceUnitId.value}/resources`, {
        preserveScroll: true,
        onSuccess: () => { resourceModalOpen.value = false; resourceForm.reset(); },
    });
}

// ── Modal: nueva Tarea ───────────────────────────────────────────────
const assignmentModalOpen = ref(false);
const assignmentUnitId = ref<number | null>(null);
const assignmentForm = useForm({
    title: '', description: '', due_date: '', evaluation_parameter_id: '',
});
function openAssignmentModal(unitId: number) {
    assignmentUnitId.value = unitId;
    assignmentModalOpen.value = true;
}
function submitAssignment() {
    assignmentForm.post(`/lms/units/${assignmentUnitId.value}/assignments`, {
        preserveScroll: true,
        onSuccess: () => { assignmentModalOpen.value = false; assignmentForm.reset(); },
    });
}

// ── Modal: nuevo Cuestionario ─────────────────────────────────────────
const quizModalOpen = ref(false);
const quizUnitId = ref<number | null>(null);
const quizForm = useForm({
    title: '', description: '', max_attempts: 1, evaluation_parameter_id: '',
});
function openQuizModal(unitId: number) {
    quizUnitId.value = unitId;
    quizModalOpen.value = true;
}
function submitQuiz() {
    quizForm.post(`/lms/units/${quizUnitId.value}/quizzes`, {
        preserveScroll: true,
        onSuccess: () => { quizModalOpen.value = false; quizForm.reset(); },
        // El backend no devuelve el ID del quiz creado por Inertia directamente;
        // el docente entra al editor desde la lista de la Unidad una vez creado.
    });
}

function deleteResource(id: number) {
    router.delete(`/lms/resources/${id}`, { preserveScroll: true });
}
function deleteAssignment(id: number) {
    router.delete(`/lms/assignments/${id}`, { preserveScroll: true });
}
function deleteQuiz(id: number) {
    router.delete(`/lms/quizzes/${id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-medium text-[#1E3A5F]">{{ subject_name }}</h1>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                        :class="is_published ? 'bg-[#3B8A5A]/10 text-[#3B8A5A]' : 'bg-[#D8A030]/10 text-[#D8A030]'"
                    >
                        {{ is_published ? 'Publicada' : 'Sin publicar' }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="togglePublish">
                        {{ is_published ? 'Despublicar' : 'Publicar' }}
                    </Button>
                    <Button class="bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white" @click="unitModalOpen = true">
                        + Nueva Unidad
                    </Button>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <div v-for="unit in units" :key="unit.id" class="overflow-hidden rounded-lg border border-[#EAEAE5] bg-white">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-4 py-3"
                        @click="toggleUnit(unit.id)"
                    >
                        <span class="text-sm font-medium text-[#1E3A5F]">{{ unit.name }}</span>
                        <i
                            class="ti ti-chevron-down text-[16px] text-[#214EA4] transition-transform"
                            :class="{ 'rotate-180': openUnitIds.has(unit.id) }"
                        />
                    </button>

                    <div v-if="openUnitIds.has(unit.id)" class="border-t border-[#EAEAE5] px-4 py-3">
                        <div class="mb-3 flex gap-2">
                            <Button size="sm" variant="outline" @click="openResourceModal(unit.id)">+ Material</Button>
                            <Button size="sm" variant="outline" @click="openAssignmentModal(unit.id)">+ Tarea</Button>
                            <Button size="sm" variant="outline" @click="openQuizModal(unit.id)">+ Cuestionario</Button>
                        </div>

                        <div class="flex flex-col gap-1 text-[13px]">
                            <div v-for="r in unit.resources" :key="`r-${r.id}`" class="flex items-center justify-between py-1">
                                <span><i class="ti ti-file text-[#214EA4]" /> {{ r.title }}</span>
                                <button class="text-xs text-[#D85A30]" @click="deleteResource(r.id)">Eliminar</button>
                            </div>
                            <div v-for="a in unit.assignments" :key="`a-${a.id}`" class="flex items-center justify-between py-1">
                                <span><i class="ti ti-clipboard-text text-[#214EA4]" /> {{ a.title }}</span>
                                <button class="text-xs text-[#D85A30]" @click="deleteAssignment(a.id)">Eliminar</button>
                            </div>
                            <div v-for="q in unit.quizzes" :key="`q-${q.id}`" class="flex items-center justify-between py-1">
                                <span><i class="ti ti-help-circle text-[#214EA4]" /> {{ q.title }}</span>
                                <div class="flex items-center gap-3">
                                    <a :href="`/lms/quizzes/${q.id}/edit`" class="text-xs text-[#214EA4]">Editar preguntas</a>
                                    <button class="text-xs text-[#D85A30]" @click="deleteQuiz(q.id)">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Unidad -->
        <Dialog v-model:open="unitModalOpen">
            <DialogContent>
                <DialogHeader><DialogTitle>Nueva Unidad</DialogTitle></DialogHeader>
                <div class="grid gap-2">
                    <Label for="unit-name">Nombre</Label>
                    <Input id="unit-name" v-model="unitForm.name" placeholder="Unidad 1: ..." />
                </div>
                <DialogFooter>
                    <Button :disabled="unitForm.processing" @click="submitUnit">Crear</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal: Material -->
        <Dialog v-model:open="resourceModalOpen">
            <DialogContent>
                <DialogHeader><DialogTitle>Nuevo material</DialogTitle></DialogHeader>
                <div class="grid gap-3">
                    <div class="grid gap-2">
                        <Label for="res-title">Título</Label>
                        <Input id="res-title" v-model="resourceForm.title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="res-file">Archivo (PDF o Word)</Label>
                        <input
                            id="res-file"
                            type="file"
                            accept=".pdf,.doc,.docx"
                            @change="resourceForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button :disabled="resourceForm.processing" @click="submitResource">Subir</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal: Tarea -->
        <Dialog v-model:open="assignmentModalOpen">
            <DialogContent>
                <DialogHeader><DialogTitle>Nueva tarea</DialogTitle></DialogHeader>
                <div class="grid gap-3">
                    <div class="grid gap-2">
                        <Label for="as-title">Título</Label>
                        <Input id="as-title" v-model="assignmentForm.title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="as-due">Fecha límite</Label>
                        <Input id="as-due" type="datetime-local" v-model="assignmentForm.due_date" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="as-param">Parámetro de evaluación</Label>
                        <select id="as-param" v-model="assignmentForm.evaluation_parameter_id" class="rounded-md border px-2 py-1.5 text-sm">
                            <option value="" disabled>Selecciona uno</option>
                            <option v-for="p in evaluation_parameters" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button :disabled="assignmentForm.processing" @click="submitAssignment">Crear</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Modal: Cuestionario -->
        <Dialog v-model:open="quizModalOpen">
            <DialogContent>
                <DialogHeader><DialogTitle>Nuevo cuestionario</DialogTitle></DialogHeader>
                <div class="grid gap-3">
                    <div class="grid gap-2">
                        <Label for="qz-title">Título</Label>
                        <Input id="qz-title" v-model="quizForm.title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="qz-attempts">Intentos permitidos</Label>
                        <Input id="qz-attempts" type="number" min="1" v-model.number="quizForm.max_attempts" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="qz-param">Parámetro de evaluación</Label>
                        <select id="qz-param" v-model="quizForm.evaluation_parameter_id" class="rounded-md border px-2 py-1.5 text-sm">
                            <option value="" disabled>Selecciona uno</option>
                            <option v-for="p in evaluation_parameters" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button :disabled="quizForm.processing" @click="submitQuiz">Crear y luego editar preguntas</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
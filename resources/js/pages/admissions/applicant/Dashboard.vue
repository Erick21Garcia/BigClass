<script setup lang="ts">
import { computed, ref, type Component } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle, ArrowRight, CheckCircle2, ChevronDown, Clock, Contact, FileText,
    GraduationCap, ImageIcon, Info, Mail, MessageSquare, Pencil, Receipt, Save,
    Search, Upload, User, XCircle,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import acadexLogo from '@/acadex-logo-512.png';

const page = usePage();
const pageErrors = computed(() => page.props.errors as Record<string, string>);
const submitError = computed(() => pageErrors.value?.submit);
const profileError = computed(() => pageErrors.value?.profile);
const documentError = computed(() => pageErrors.value?.document);

interface Catalog {
    id: number;
    name: string;
}

interface DocumentSlot {
    type: string;
    uploaded: boolean;
    document: { id: number; original_name: string } | null;
}

const props = defineProps<{
    applicant: {
        status: string;
        career_name: string;
        submitted_at: string | null;
        decision_notes: string | null;
        profile: Record<string, string | number | null>;
    };
    documents: DocumentSlot[];
    can_edit: boolean;
    email: string;
    email_verified: boolean;
    catalogs: {
        marital_statuses: Catalog[];
        type_identifications: Catalog[];
        sexes: Catalog[];
        nationalities: Catalog[];
        education_levels: Catalog[];
        countries: Catalog[];
        provinces: Catalog[];
        cities: Catalog[];
    };
}>();

// ── Estados de la postulación ────────────────────────────────────────
type Tone = 'amber' | 'blue' | 'green' | 'red' | 'gray';

interface StatusInfo {
    title: string;
    description: string;
    badge: string;
    icon: Component;
    tone: Tone;
}

const statusInfo: Record<string, StatusInfo> = {
    borrador: {
        title: 'Postulación en borrador',
        description: 'Completa tus datos personales, sube los 4 documentos y envía tu postulación.',
        badge: 'Borrador',
        icon: Pencil,
        tone: 'amber',
    },
    recibida: {
        title: 'Postulación enviada',
        description: 'Recibimos tu postulación. Ya no necesitas hacer nada más: te avisaremos por correo cuando haya una decisión.',
        badge: 'Enviada',
        icon: CheckCircle2,
        tone: 'blue',
    },
    en_revision: {
        title: 'Postulación en revisión',
        description: 'Nuestro equipo está revisando tu postulación. Te notificaremos por correo con la decisión.',
        badge: 'En revisión',
        icon: Search,
        tone: 'blue',
    },
    aprobada: {
        title: '¡Postulación aprobada!',
        description: 'Felicidades. En los próximos días recibirás información sobre los siguientes pasos.',
        badge: 'Aprobada',
        icon: CheckCircle2,
        tone: 'green',
    },
    rechazada: {
        title: 'Postulación no aprobada',
        description: 'Tu postulación no fue aprobada en esta ocasión.',
        badge: 'No aprobada',
        icon: XCircle,
        tone: 'red',
    },
    abandonada: {
        title: 'Postulación cerrada por inactividad',
        description: 'Tu postulación se cerró automáticamente por falta de actividad.',
        badge: 'Cerrada',
        icon: Clock,
        tone: 'gray',
    },
};

// Clases completas y estáticas a propósito, para que Tailwind las detecte.
const toneChip: Record<Tone, string> = {
    amber: 'border-[#F2A81D]/50 bg-[#F2A81D]/25 text-[#FFE3A6]',
    blue: 'border-[#8CB8FF]/50 bg-[#8CB8FF]/25 text-[#DDEBFF]',
    green: 'border-[#5BE3A4]/40 bg-[#5BE3A4]/20 text-[#C8F7E1]',
    red: 'border-red-300/50 bg-red-400/25 text-red-100',
    gray: 'border-white/30 bg-white/15 text-white/80',
};

const toneSoft: Record<Tone, string> = {
    amber: 'bg-[#FFF1CC] text-[#9A5B00]',
    blue: 'bg-[#E8EEFB] text-[#214EA4]',
    green: 'bg-[#E3F7EC] text-[#0E7C4A]',
    red: 'bg-red-50 text-red-700',
    gray: 'bg-[#F0F3F8] text-[#5B6B82]',
};

const currentStatus = computed(() => statusInfo[props.applicant.status] ?? statusInfo.borrador);

const documentLabel: Record<string, string> = {
    cedula: 'Cédula',
    titulo_bachiller: 'Título de bachiller',
    foto: 'Foto tipo carnet',
    comprobante_pago: 'Comprobante de pago',
};

const documentIcon: Record<string, Component> = {
    cedula: Contact,
    titulo_bachiller: GraduationCap,
    foto: ImageIcon,
    comprobante_pago: Receipt,
};

// ── Datos personales ─────────────────────────────────────────────────
type CatalogKey = keyof typeof props.catalogs;

interface Field {
    key: string;
    label: string;
    type?: 'text' | 'date';
    options?: CatalogKey; // si existe, el campo es un select con ese catálogo
    span2?: boolean;
    optional?: boolean;   // no cuenta para saber si los datos están completos
}

const groups: { title: string; fields: Field[] }[] = [
    {
        title: 'Identificación',
        fields: [
            { key: 'first_name', label: 'Primer nombre' },
            { key: 'second_name', label: 'Segundo nombre' },
            { key: 'first_surname', label: 'Primer apellido' },
            { key: 'second_surname', label: 'Segundo apellido' },
            { key: 'identification_number', label: 'Cédula' },
            { key: 'type_identification_id', label: 'Tipo de identificación', options: 'type_identifications' },
        ],
    },
    {
        title: 'Contacto y nacimiento',
        fields: [
            { key: 'cellphone', label: 'Celular' },
            { key: 'phone', label: 'Teléfono' },
            { key: 'birthdate', label: 'Fecha de nacimiento', type: 'date' },
            { key: 'place_birth', label: 'Lugar de nacimiento' },
        ],
    },
    {
        title: 'Perfil',
        fields: [
            { key: 'sex_id', label: 'Sexo', options: 'sexes' },
            { key: 'marital_status_id', label: 'Estado civil', options: 'marital_statuses' },
            { key: 'nationality_id', label: 'Nacionalidad', options: 'nationalities' },
            { key: 'education_level_id', label: 'Nivel de educación', options: 'education_levels' },
        ],
    },
    {
        title: 'Domicilio',
        fields: [
            { key: 'countries_id', label: 'País', options: 'countries' },
            { key: 'provinces_id', label: 'Provincia', options: 'provinces' },
            { key: 'cities_id', label: 'Ciudad', options: 'cities' },
            { key: 'main_street', label: 'Dirección (calle principal)', span2: true },
            { key: 'secondary_street', label: 'Calle secundaria' },
            { key: 'neighborhood', label: 'Barrio/sector' },
        ],
    },
];

// El Input no acepta null en su v-model — los campos que el aspirante
// aún no ha llenado vienen como null desde el backend, así que se
// convierten a '' antes de pasarlos al form.
const sanitizedProfile = Object.fromEntries(
    Object.entries(props.applicant.profile).map(([key, value]) => [key, value ?? '']),
);

const profileForm = useForm({ ...sanitizedProfile });

function saveProfile() {
    profileForm.put('/admisiones/mi-postulacion/perfil', { preserveScroll: true });
}

// ── Documentos ───────────────────────────────────────────────────────
const uploadingType = ref<string | null>(null);

function uploadDocument(type: string, event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    router.post(
        '/admisiones/mi-postulacion/documentos',
        { type, file },
        {
            preserveScroll: true,
            forceFormData: true,
            onStart: () => { uploadingType.value = type; },
            onFinish: () => {
                uploadingType.value = null;
                input.value = ''; // permite volver a elegir el mismo archivo
            },
        },
    );
}

const allDocumentsUploaded = computed(() => props.documents.every((d) => d.uploaded));
const uploadedCount = computed(() => props.documents.filter((d) => d.uploaded).length);
const docTotal = computed(() => props.documents.length);

function resendVerification() {
    router.post('/admisiones/verificacion/reenviar', {}, { preserveScroll: true });
}

const submitting = ref(false);

function submitApplication() {
    // preserveScroll en false a propósito: al enviar, el botón desaparece y
    // el usuario debe ver arriba el nuevo estado, no quedarse mirando un
    // final de página vacío.
    router.post('/admisiones/mi-postulacion/enviar', {}, {
        preserveScroll: false,
        onStart: () => { submitting.value = true; },
        onFinish: () => { submitting.value = false; },
        onSuccess: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
    });
}

// ── Progreso ─────────────────────────────────────────────────────────
const requiredKeys = groups.flatMap((g) => g.fields.filter((f) => !f.optional).map((f) => f.key));
const filledCount = computed(
    () => requiredKeys.filter((k) => String(profileForm[k] ?? '').trim() !== '').length,
);
const allFieldsFilled = computed(() => filledCount.value === requiredKeys.length);

const overall = computed(() => {
    const f = requiredKeys.length ? filledCount.value / requiredKeys.length : 1;
    const d = docTotal.value ? uploadedCount.value / docTotal.value : 1;
    return Math.round(((f + d) / 2) * 100);
});

const checklist = computed(() => [
    {
        label: 'Datos personales',
        done: allFieldsFilled.value,
        note: allFieldsFilled.value ? 'Completos' : `${filledCount.value} de ${requiredKeys.length}`,
    },
    ...props.documents.map((d) => ({
        label: documentLabel[d.type] ?? d.type,
        done: d.uploaded,
        note: d.uploaded ? 'Subido' : 'Pendiente',
    })),
]);

// Pasos del banner: en borrador son los de la postulación; después, los del proceso.
type StepState = 'done' | 'current' | 'todo' | 'bad';

const steps = computed<{ label: string; state: StepState }[]>(() => {
    if (props.can_edit) {
        const active = !allFieldsFilled.value ? 0 : !allDocumentsUploaded.value ? 1 : 2;
        return ['Datos personales', 'Documentos', 'Enviar'].map((label, i) => ({
            label,
            state: i < active ? 'done' : i === active ? 'current' : 'todo',
        }));
    }

    const s = props.applicant.status;
    if (s === 'abandonada') return [];
    const decided = s === 'aprobada' || s === 'rechazada';

    return [
        { label: 'Enviada', state: 'done' },
        { label: 'En revisión', state: decided ? 'done' : 'current' },
        {
            label: s === 'aprobada' ? 'Aprobada' : s === 'rechazada' ? 'No aprobada' : 'Decisión',
            state: s === 'rechazada' ? 'bad' : decided ? 'done' : 'todo',
        },
    ];
});

const stepPill: Record<StepState, string> = {
    current: 'bg-white font-semibold text-[#1E3A5F] shadow-[0_10px_20px_-10px_rgba(0,0,0,0.5)]',
    done: 'bg-white/[0.12] text-[#DDEBFF]',
    todo: 'bg-white/[0.12] text-[#DDEBFF]/80',
    bad: 'bg-red-400/25 text-red-100',
};

const stepDot: Record<StepState, string> = {
    current: 'bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white',
    done: 'bg-[#5BE3A4] text-[#0B2545]',
    todo: 'bg-white/20 text-white',
    bad: 'bg-[#E5484D] text-white',
};

// ── Presentación ─────────────────────────────────────────────────────
const initialsOf = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('');

const userInitials = computed(() => {
    const p = props.applicant.profile;
    const name = `${p.first_name ?? ''} ${p.first_surname ?? ''}`.trim()
        || (page.props as { auth?: { user?: { name?: string } } }).auth?.user?.name
        || '';
    return name ? initialsOf(name) : '';
});

const formatDate = (iso: string) =>
    new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'long', year: 'numeric' });

const cardBase =
    'rounded-[18px] border border-[#D8E2F4] bg-white p-6 shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)]';
const iconTile =
    'grid size-[42px] shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white';
const fieldClass =
    'h-[46px] w-full rounded-xl border-[1.5px] border-transparent bg-[#E8EEFB] px-4 text-[14.5px] text-[#0B2545] shadow-none placeholder:text-[#7B8AA3] focus-visible:border-[#1E3A5F] focus-visible:bg-white focus-visible:ring-4 focus-visible:ring-[#1E3A5F]/10 disabled:cursor-not-allowed disabled:opacity-70';
const selectClass =
    'h-[46px] w-full appearance-none rounded-xl border-[1.5px] border-transparent bg-[#E8EEFB] pl-4 pr-10 text-[14.5px] text-[#0B2545] outline-none focus:border-[#1E3A5F] focus:bg-white focus:ring-4 focus:ring-[#1E3A5F]/10 disabled:cursor-not-allowed disabled:opacity-70';
</script>

<template>
    <Head title="Mi postulación">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="min-h-screen bg-[#1E3A5F]">
        <header class="flex h-14 items-center justify-between px-7 text-white">
            <div class="flex items-center gap-3">
                <img :src="acadexLogo" alt="" width="30" height="30" class="h-[30px] w-[30px] rounded-lg object-contain" />
                <span class="acadex-title text-[15px]">Acadex — Admisiones</span>
            </div>
            <div
                v-if="userInitials"
                class="grid size-[30px] place-items-center rounded-full border border-white/30 bg-white/[0.18] text-[11.5px] font-semibold"
            >
                {{ userInitials }}
            </div>
        </header>

        <div class="min-h-[calc(100vh-3.5rem)] rounded-t-[18px] bg-[#EAF0FB] px-4 py-6 sm:px-8">
            <div class="mx-auto flex max-w-[1400px] flex-col gap-4">

                <!-- Banner -->
                <div class="relative overflow-hidden rounded-[20px] bg-[linear-gradient(120deg,#1E3A5F,#214EA4)] px-6 py-6 text-white sm:px-10">
                    <div class="pointer-events-none absolute -right-14 -top-24 h-[230px] w-[230px] rotate-45 bg-[#2F63C9]/80"></div>
                    <div class="pointer-events-none absolute -top-32 right-[230px] h-[170px] w-[170px] rotate-45 bg-[#8CB8FF]/[0.18]"></div>
                    <div class="pointer-events-none absolute -bottom-24 -left-12 h-[150px] w-[150px] rotate-45 bg-[#2F63C9]/55"></div>

                    <div class="relative">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/15 px-3 py-1 text-xs font-medium">
                                <GraduationCap class="size-3.5" />
                                {{ applicant.career_name }}
                            </span>
                            <span
                                :class="[toneChip[currentStatus.tone], 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium']"
                            >
                                <component :is="currentStatus.icon" class="size-3.5" />
                                {{ currentStatus.badge }}
                            </span>
                            <span
                                v-if="applicant.submitted_at"
                                class="inline-flex items-center rounded-full bg-white/[0.13] px-3 py-1 text-xs text-[#E4EDFC]"
                            >
                                Enviada el {{ formatDate(applicant.submitted_at) }}
                            </span>
                        </div>

                        <h1 class="acadex-title mt-3 text-[34px] leading-[1.1] sm:text-[40px]">{{ currentStatus.title }}</h1>
                        <p class="mt-2 max-w-3xl text-[15px] text-[#C9DAF7]">{{ currentStatus.description }}</p>

                        <div v-if="steps.length" class="mt-5 flex flex-wrap items-center gap-2.5">
                            <template v-for="(s, i) in steps" :key="s.label">
                                <span v-if="i > 0" class="hidden h-0.5 w-7 rounded bg-white/30 sm:block"></span>
                                <span
                                    :class="[stepPill[s.state], 'inline-flex h-10 items-center gap-2.5 rounded-full pl-2 pr-4 text-[13.5px]']"
                                >
                                    <span :class="[stepDot[s.state], 'grid size-[26px] place-items-center rounded-full text-[12.5px] font-semibold']">
                                        <CheckCircle2 v-if="s.state === 'done'" class="size-3.5" />
                                        <XCircle v-else-if="s.state === 'bad'" class="size-3.5" />
                                        <template v-else>{{ i + 1 }}</template>
                                    </span>
                                    {{ s.label }}
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Verificación de correo -->
                <div
                    v-if="can_edit && !email_verified"
                    class="flex flex-wrap items-center gap-4 rounded-[18px] border border-[#F2D27A] border-l-[5px] border-l-[#F2A81D] bg-[#FFF8E6] p-5 shadow-[0_10px_24px_-18px_rgba(30,58,95,0.5)]"
                >
                    <span class="grid size-[42px] shrink-0 place-items-center rounded-xl bg-gradient-to-br from-[#F2A81D] to-[#F5C04A] text-white">
                        <Mail class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[15px] font-semibold text-[#0B2545]">Verifica tu correo electrónico</p>
                        <p class="mt-0.5 text-[13px] text-[#5B6B82]">
                            Te enviamos un enlace a <span class="font-semibold">{{ email }}</span>. Necesitas verificarlo
                            para subir documentos y enviar tu postulación. Mientras tanto, puedes ir llenando tus datos.
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        class="h-10 shrink-0 rounded-xl border-[1.5px] border-[#214EA4] bg-white font-semibold text-[#214EA4] hover:bg-[#214EA4]/5"
                        @click="resendVerification"
                    >
                        Reenviar correo
                    </Button>
                </div>

                <div class="grid gap-4 lg:grid-cols-[1fr_350px]">
                    <!-- Columna principal -->
                    <div class="flex min-w-0 flex-col gap-4">

                        <!-- Datos personales -->
                        <div :class="cardBase">
                            <div class="flex items-center gap-3.5">
                                <div :class="iconTile"><User class="size-5" /></div>
                                <div>
                                    <h2 class="text-[17px] font-semibold text-[#0B2545]">Datos personales</h2>
                                    <p class="text-[13px] text-[#5B6B82]">
                                        {{ can_edit ? 'Revisa que tu información sea correcta' : 'Información registrada en tu postulación' }}
                                    </p>
                                </div>
                            </div>

                            <div v-for="g in groups" :key="g.title" class="mt-6">
                                <p class="mb-3.5 flex items-center gap-3 text-[11.5px] font-semibold uppercase tracking-[0.06em] text-[#6B7A90]">
                                    {{ g.title }}
                                    <span class="h-px flex-1 bg-[#E1E9F7]"></span>
                                </p>

                                <div class="grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2">
                                    <div
                                        v-for="f in g.fields"
                                        :key="f.key"
                                        class="grid gap-1.5"
                                        :class="f.span2 ? 'sm:col-span-2' : ''"
                                    >
                                        <Label :for="f.key" class="text-[13px] font-semibold text-[#0B2545]">{{ f.label }}</Label>

                                        <div v-if="f.options" class="relative">
                                            <select
                                                :id="f.key"
                                                v-model="profileForm[f.key]"
                                                :disabled="!can_edit"
                                                :class="selectClass"
                                            >
                                                <option value="" disabled>Selecciona</option>
                                                <option v-for="c in catalogs[f.options]" :key="c.id" :value="c.id">{{ c.name }}</option>
                                            </select>
                                            <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-[#1F2A3D]" />
                                        </div>

                                        <Input
                                            v-else
                                            :id="f.key"
                                            :type="f.type ?? 'text'"
                                            v-model="profileForm[f.key]"
                                            :disabled="!can_edit"
                                            :class="fieldClass"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="profileError"
                                class="mt-5 flex items-start gap-2.5 rounded-xl border-l-4 border-[#E5484D] bg-red-50 px-3.5 py-2.5 text-[13px] text-[#8A2A2D]"
                            >
                                <AlertCircle class="mt-px size-4 shrink-0" />
                                {{ profileError }}
                            </div>

                            <!-- En pantallas pequeñas el panel lateral queda abajo: se repite el botón aquí -->
                            <div v-if="can_edit" class="mt-5 flex justify-end lg:hidden">
                                <Button
                                    class="h-11 rounded-xl bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] px-6 font-semibold text-white shadow-[0_10px_20px_-10px_rgba(33,78,164,0.8)] hover:brightness-110"
                                    :disabled="profileForm.processing"
                                    @click="saveProfile"
                                >
                                    <Spinner v-if="profileForm.processing" />
                                    <Save v-else class="size-4" />
                                    Guardar datos
                                </Button>
                            </div>
                        </div>

                        <!-- Documentos -->
                        <div :class="cardBase">
                            <div class="flex flex-wrap items-center gap-3.5">
                                <div :class="iconTile"><FileText class="size-5" /></div>
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-[17px] font-semibold text-[#0B2545]">Documentos</h2>
                                    <p class="text-[13px] text-[#5B6B82]">
                                        {{ can_edit ? `Sube los ${docTotal} documentos requeridos (PDF, JPG o PNG)` : 'Documentos de tu postulación' }}
                                    </p>
                                </div>
                                <span
                                    :class="[
                                        allDocumentsUploaded
                                            ? 'border-[#BFE9D3] bg-[#E3F7EC] text-[#0E7C4A]'
                                            : 'border-[#F2D27A] bg-[#FFF1CC] text-[#9A5B00]',
                                        'rounded-full border px-3 py-1 text-xs font-semibold tabular-nums',
                                    ]"
                                >
                                    {{ uploadedCount }} de {{ docTotal }}
                                </span>
                            </div>

                            <div class="mt-5 grid gap-3.5 sm:grid-cols-2">
                                <div
                                    v-for="doc in documents"
                                    :key="doc.type"
                                    class="flex flex-wrap items-center gap-3.5 rounded-2xl p-4 transition"
                                    :class="
                                        doc.uploaded
                                            ? 'border border-[#BFE9D3] bg-gradient-to-r from-[#F0FAF5] to-[#F7F9FD]'
                                            : 'border-2 border-dashed border-[#C9DAF7] bg-[#F7F9FD]'
                                    "
                                >
                                    <span
                                        class="grid size-[46px] shrink-0 place-items-center rounded-[13px]"
                                        :class="
                                            doc.uploaded
                                                ? 'bg-gradient-to-br from-[#16A36A] to-[#34C58A] text-white'
                                                : 'bg-[#E4ECF9] text-[#8A97AB]'
                                        "
                                    >
                                        <CheckCircle2 v-if="doc.uploaded" class="size-[22px]" />
                                        <component :is="documentIcon[doc.type] ?? FileText" v-else class="size-[22px]" />
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[15px] font-semibold text-[#0B2545]">
                                            {{ documentLabel[doc.type] ?? doc.type }}
                                        </p>
                                        <p
                                            class="truncate text-[12.5px]"
                                            :class="doc.uploaded ? 'text-[#4A5D7E]' : 'text-[#8A97AB]'"
                                        >
                                            {{ doc.document ? doc.document.original_name : 'Aún no subido' }}
                                        </p>
                                    </div>

                                    <label
                                        v-if="can_edit"
                                        class="inline-flex h-[38px] cursor-pointer items-center gap-2 rounded-[11px] border-[1.5px] border-[#214EA4] bg-white px-4 text-[13.5px] font-semibold text-[#214EA4] transition hover:bg-[#214EA4]/5"
                                        :class="{ 'pointer-events-none opacity-50': !email_verified || uploadingType === doc.type }"
                                    >
                                        <Spinner v-if="uploadingType === doc.type" class="size-[15px]" />
                                        <Upload v-else class="size-[15px]" />
                                        {{ doc.uploaded ? 'Reemplazar' : 'Subir' }}
                                        <input
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            class="hidden"
                                            :disabled="!email_verified"
                                            @change="uploadDocument(doc.type, $event)"
                                        />
                                    </label>
                                </div>
                            </div>

                            <div
                                v-if="documentError"
                                class="mt-4 flex items-start gap-2.5 rounded-xl border-l-4 border-[#E5484D] bg-red-50 px-3.5 py-2.5 text-[13px] text-[#8A2A2D]"
                            >
                                <AlertCircle class="mt-px size-4 shrink-0" />
                                {{ documentError }}
                            </div>
                        </div>
                    </div>

                    <!-- Panel lateral -->
                    <aside class="min-w-0 lg:sticky lg:top-6 lg:self-start">
                        <!-- Borrador: progreso y acciones -->
                        <div v-if="can_edit" :class="cardBase">
                            <h2 class="text-[17px] font-semibold text-[#0B2545]">Tu progreso</h2>
                            <p class="mt-0.5 text-[13px] text-[#5B6B82]">{{ overall }}% completado</p>

                            <div class="mt-3.5 h-[9px] overflow-hidden rounded-full bg-[#E4ECF9]">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-[#1E3A5F] via-[#214EA4] to-[#8CB8FF] transition-all duration-500"
                                    :style="{ width: `${overall}%` }"
                                />
                            </div>

                            <ul class="mt-4">
                                <li
                                    v-for="item in checklist"
                                    :key="item.label"
                                    class="flex items-center gap-3 border-t border-[#EAF0FB] py-2.5 text-sm first:border-t-0"
                                >
                                    <span
                                        class="grid size-6 shrink-0 place-items-center rounded-full"
                                        :class="item.done ? 'bg-gradient-to-br from-[#16A36A] to-[#34C58A] text-white' : 'bg-[#E4ECF9]'"
                                    >
                                        <CheckCircle2 v-if="item.done" class="size-3.5" />
                                    </span>
                                    <span class="min-w-0 flex-1 truncate text-[#0B2545]">{{ item.label }}</span>
                                    <span class="shrink-0 text-xs text-[#8A97AB]">{{ item.note }}</span>
                                </li>
                            </ul>

                            <Button
                                variant="outline"
                                class="mt-4 h-[46px] w-full rounded-[13px] border-[1.5px] border-[#214EA4] bg-white text-[14.5px] font-semibold text-[#214EA4] hover:bg-[#214EA4]/5"
                                :disabled="profileForm.processing"
                                @click="saveProfile"
                            >
                                <Spinner v-if="profileForm.processing" />
                                <Save v-else class="size-[17px]" />
                                Guardar datos
                            </Button>

                            <Button
                                class="mt-2.5 h-12 w-full rounded-[13px] bg-gradient-to-r from-[#1E3A5F] to-[#214EA4] text-[15px] font-semibold text-white shadow-[0_10px_20px_-10px_rgba(33,78,164,0.8)] hover:brightness-110 disabled:opacity-45"
                                :disabled="!allDocumentsUploaded || !email_verified || submitting"
                                @click="submitApplication"
                            >
                                <Spinner v-if="submitting" />
                                Enviar postulación
                                <ArrowRight v-if="!submitting" class="size-[17px]" />
                            </Button>

                            <p v-if="!email_verified" class="mt-3 flex items-start gap-2 text-[12.5px] leading-snug text-[#9A5B00]">
                                <Info class="mt-px size-[15px] shrink-0" />
                                Verifica tu correo para subir documentos y enviar tu postulación.
                            </p>
                            <p v-else-if="!allDocumentsUploaded" class="mt-3 flex items-start gap-2 text-[12.5px] leading-snug text-[#9A5B00]">
                                <Info class="mt-px size-[15px] shrink-0" />
                                Sube los {{ docTotal }} documentos antes de enviar tu postulación.
                            </p>

                            <div
                                v-if="submitError"
                                class="mt-3 flex items-start gap-2.5 rounded-xl border-l-4 border-[#E5484D] bg-red-50 px-3.5 py-2.5 text-[13px] text-[#8A2A2D]"
                            >
                                <AlertCircle class="mt-px size-4 shrink-0" />
                                {{ submitError }}
                            </div>
                        </div>

                        <!-- Ya enviada: resumen -->
                        <div v-else :class="cardBase">
                            <h2 class="text-[17px] font-semibold text-[#0B2545]">Resumen de tu postulación</h2>

                            <dl class="mt-4 text-sm">
                                <div class="flex items-start justify-between gap-3 border-t border-[#EAF0FB] py-2.5 first:border-t-0">
                                    <dt class="text-[#6B7A90]">Carrera</dt>
                                    <dd class="text-right font-medium text-[#0B2545]">{{ applicant.career_name }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3 border-t border-[#EAF0FB] py-2.5">
                                    <dt class="text-[#6B7A90]">Estado</dt>
                                    <dd>
                                        <span :class="[toneSoft[currentStatus.tone], 'inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-xs font-semibold']">
                                            <component :is="currentStatus.icon" class="size-3.5" />
                                            {{ currentStatus.badge }}
                                        </span>
                                    </dd>
                                </div>
                                <div v-if="applicant.submitted_at" class="flex items-center justify-between gap-3 border-t border-[#EAF0FB] py-2.5">
                                    <dt class="text-[#6B7A90]">Enviada el</dt>
                                    <dd class="font-medium text-[#0B2545]">{{ formatDate(applicant.submitted_at) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3 border-t border-[#EAF0FB] py-2.5">
                                    <dt class="text-[#6B7A90]">Documentos</dt>
                                    <dd class="font-medium tabular-nums text-[#0B2545]">{{ uploadedCount }} de {{ docTotal }}</dd>
                                </div>
                            </dl>

                            <div
                                v-if="applicant.decision_notes"
                                class="mt-4 rounded-[14px] border-l-4 border-[#214EA4] bg-[#EAF0FB] px-4 py-3.5"
                            >
                                <p class="flex items-center gap-[7px] text-[11.5px] font-semibold uppercase tracking-[0.06em] text-[#214EA4]">
                                    <MessageSquare class="size-3.5" />
                                    Comentarios de la decisión
                                </p>
                                <p class="mt-1.5 text-[14.5px] text-[#0B2545]">{{ applicant.decision_notes }}</p>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.acadex-title {
    font-family: 'Fraunces', serif;
    font-weight: 600;
}
</style>
<script setup lang="ts">
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdmissionsLayout from '@/layouts/AdmissionsLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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

const statusLabel: Record<string, string> = {
    borrador: 'Borrador — completa tu postulación',
    recibida: 'Recibida — en espera de revisión',
    en_revision: 'En revisión',
    aprobada: 'Aprobada',
    rechazada: 'Rechazada',
    abandonada: 'Abandonada por inactividad',
};

const documentLabel: Record<string, string> = {
    cedula: 'Cédula',
    titulo_bachiller: 'Título de bachiller',
    foto: 'Foto tipo carnet',
    comprobante_pago: 'Comprobante de pago',
};

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

function uploadDocument(type: string, event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    router.post(
        '/admisiones/mi-postulacion/documentos',
        { type, file },
        { preserveScroll: true, forceFormData: true },
    );
}

const allDocumentsUploaded = computed(() => props.documents.every((d) => d.uploaded));

function submitApplication() {
    router.post('/admisiones/mi-postulacion/enviar', {}, { preserveScroll: true });
}
</script>

<template>
    <AdmissionsLayout title="Mi postulación">
        <div class="mb-5 rounded-xl border border-[#EAEAE5] bg-white p-4">
            <p class="text-xs text-[#5B6B82]">{{ applicant.career_name }}</p>
            <p class="text-sm font-medium text-[#1E3A5F]">{{ statusLabel[applicant.status] }}</p>
            <p v-if="applicant.decision_notes" class="mt-2 text-sm text-[#5B6B82]">{{ applicant.decision_notes }}</p>
        </div>

        <!-- Datos personales -->
        <div class="mb-5 rounded-xl border border-[#EAEAE5] bg-white p-5">
            <p class="admissions-wordmark mb-4 text-base text-[#1E3A5F]">Datos personales</p>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="first_name">Primer nombre</Label>
                    <Input id="first_name" v-model="profileForm.first_name" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="second_name">Segundo nombre</Label>
                    <Input id="second_name" v-model="profileForm.second_name" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="first_surname">Primer apellido</Label>
                    <Input id="first_surname" v-model="profileForm.first_surname" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="second_surname">Segundo apellido</Label>
                    <Input id="second_surname" v-model="profileForm.second_surname" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="identification_number">Cédula</Label>
                    <Input id="identification_number" v-model="profileForm.identification_number" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="type_identification_id">Tipo de identificación</Label>
                    <select id="type_identification_id" v-model="profileForm.type_identification_id" :disabled="!can_edit" class="rounded-md border px-2 py-1.5 text-sm">
                        <option value="" disabled>Selecciona</option>
                        <option v-for="c in catalogs.type_identifications" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <Label for="cellphone">Celular</Label>
                    <Input id="cellphone" v-model="profileForm.cellphone" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="phone">Teléfono</Label>
                    <Input id="phone" v-model="profileForm.phone" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="birthdate">Fecha de nacimiento</Label>
                    <Input id="birthdate" type="date" v-model="profileForm.birthdate" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="place_birth">Lugar de nacimiento</Label>
                    <Input id="place_birth" v-model="profileForm.place_birth" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="sex_id">Sexo</Label>
                    <select id="sex_id" v-model="profileForm.sex_id" :disabled="!can_edit" class="rounded-md border px-2 py-1.5 text-sm">
                        <option value="" disabled>Selecciona</option>
                        <option v-for="c in catalogs.sexes" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <Label for="marital_status_id">Estado civil</Label>
                    <select id="marital_status_id" v-model="profileForm.marital_status_id" :disabled="!can_edit" class="rounded-md border px-2 py-1.5 text-sm">
                        <option value="" disabled>Selecciona</option>
                        <option v-for="c in catalogs.marital_statuses" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <Label for="nationality_id">Nacionalidad</Label>
                    <select id="nationality_id" v-model="profileForm.nationality_id" :disabled="!can_edit" class="rounded-md border px-2 py-1.5 text-sm">
                        <option value="" disabled>Selecciona</option>
                        <option v-for="c in catalogs.nationalities" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <Label for="education_level_id">Nivel de educación</Label>
                    <select id="education_level_id" v-model="profileForm.education_level_id" :disabled="!can_edit" class="rounded-md border px-2 py-1.5 text-sm">
                        <option value="" disabled>Selecciona</option>
                        <option v-for="c in catalogs.education_levels" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <Label for="countries_id">País</Label>
                    <select id="countries_id" v-model="profileForm.countries_id" :disabled="!can_edit" class="rounded-md border px-2 py-1.5 text-sm">
                        <option value="" disabled>Selecciona</option>
                        <option v-for="c in catalogs.countries" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <Label for="provinces_id">Provincia</Label>
                    <select id="provinces_id" v-model="profileForm.provinces_id" :disabled="!can_edit" class="rounded-md border px-2 py-1.5 text-sm">
                        <option value="" disabled>Selecciona</option>
                        <option v-for="c in catalogs.provinces" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <Label for="cities_id">Ciudad</Label>
                    <select id="cities_id" v-model="profileForm.cities_id" :disabled="!can_edit" class="rounded-md border px-2 py-1.5 text-sm">
                        <option value="" disabled>Selecciona</option>
                        <option v-for="c in catalogs.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="grid gap-1.5 sm:col-span-2">
                    <Label for="main_street">Dirección (calle principal)</Label>
                    <Input id="main_street" v-model="profileForm.main_street" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="secondary_street">Calle secundaria</Label>
                    <Input id="secondary_street" v-model="profileForm.secondary_street" :disabled="!can_edit" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="neighborhood">Barrio/sector</Label>
                    <Input id="neighborhood" v-model="profileForm.neighborhood" :disabled="!can_edit" />
                </div>
            </div>

            <Button
                v-if="can_edit"
                class="mt-4 bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                :disabled="profileForm.processing"
                @click="saveProfile"
            >
                Guardar datos
            </Button>
        </div>

        <!-- Documentos -->
        <div class="mb-5 rounded-xl border border-[#EAEAE5] bg-white p-5">
            <p class="admissions-wordmark mb-4 text-base text-[#1E3A5F]">Documentos</p>

            <div class="flex flex-col gap-3">
                <div
                    v-for="doc in documents"
                    :key="doc.type"
                    class="flex items-center justify-between rounded-lg border border-[#EAEAE5] p-3"
                >
                    <div class="flex items-center gap-2.5">
                        <i
                            :class="doc.uploaded ? 'ti ti-circle-check text-[#3B8A5A]' : 'ti ti-circle text-[#8A8A85]'"
                            class="text-[18px]"
                        />
                        <div>
                            <p class="text-sm text-[#1E3A5F]">{{ documentLabel[doc.type] }}</p>
                            <p v-if="doc.document" class="text-xs text-[#8A8A85]">{{ doc.document.original_name }}</p>
                        </div>
                    </div>

                    <label v-if="can_edit" class="cursor-pointer rounded-md border border-[#214EA4]/30 bg-white px-3 py-1.5 text-xs font-medium text-[#214EA4] hover:bg-[#214EA4]/5">
                        {{ doc.uploaded ? 'Reemplazar' : 'Subir' }}
                        <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" @change="uploadDocument(doc.type, $event)" />
                    </label>
                </div>
            </div>
        </div>

        <!-- Enviar postulación -->
        <div v-if="can_edit" class="rounded-xl border border-[#EAEAE5] bg-white p-5">
            <p v-if="!allDocumentsUploaded" class="mb-3 text-xs text-[#D8A030]">
                Sube los 4 documentos antes de enviar tu postulación.
            </p>
            <Button
                class="w-full bg-gradient-to-br from-[#1E3A5F] to-[#214EA4] text-white"
                :disabled="!allDocumentsUploaded"
                @click="submitApplication"
            >
                Enviar postulación
            </Button>
        </div>
    </AdmissionsLayout>
</template>

<style scoped>
.admissions-wordmark {
    font-family: 'Fraunces', serif;
    font-weight: 500;
}
</style>
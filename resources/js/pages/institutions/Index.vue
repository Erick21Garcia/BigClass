<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Building2, MapPin, Mail, Phone } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

export interface Institution {
    id: number;
    name: string;
    type: string;
    code: string | null;
    acronym: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    province: string | null;
    country: string | null;
    active: boolean;
    created_at: string;
}

interface Props {
    institutions: Institution[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Instituciones', href: '/institutions' },
];

const INSTITUTION_TYPES = [
    { value: 'university', label: 'Universidad' },
    { value: 'institute',  label: 'Instituto'   },
    { value: 'college',    label: 'Colegio'     },
    { value: 'school',     label: 'Escuela'     },
    { value: 'other',      label: 'Otro'        },
];

const getTypeLabel = (value: string) =>
    INSTITUTION_TYPES.find(t => t.value === value)?.label ?? value;

const createDialogOpen = ref(false);

const form = useForm({
    name:     '',
    type:     '',
    code:     '',
    acronym:  '',
    email:    '',
    phone:    '',
    address:  '',
    city:     '',
    province: '',
    country:  '',
    active:   true,
});

const handleCreate = () => {
    form.post('/institutions', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            createDialogOpen.value = false;
            form.reset();
            toast.success('Institución creada exitosamente');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] as string;
            toast.error(firstError || 'Error al crear la institución');
        },
    });
};

const goToShow = (id: number) => {
    router.visit(`/institutions/${id}`);
};
</script>

<template>
    <Head title="Instituciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Instituciones</h2>
                    <p class="text-muted-foreground">
                        {{ props.institutions.length }} institución{{ props.institutions.length !== 1 ? 'es' : '' }} registrada{{ props.institutions.length !== 1 ? 's' : '' }}
                    </p>
                </div>
                <Button @click="createDialogOpen = true">
                    <Plus class="mr-2 h-4 w-4" />
                    Nueva Institución
                </Button>
            </div>

            <div
                v-if="props.institutions.length === 0"
                class="flex flex-col items-center justify-center py-24 text-center text-muted-foreground"
            >
                <Building2 class="mb-4 h-12 w-12 opacity-30" />
                <p class="text-lg font-medium">No hay instituciones registradas</p>
                <p class="mt-1 text-sm">Crea la primera usando el botón de arriba</p>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <Card
                    v-for="institution in props.institutions"
                    :key="institution.id"
                    class="group cursor-pointer transition-all duration-200 hover:border-primary/50 hover:shadow-md"
                    @click="goToShow(institution.id)"
                >
                    <CardHeader class="pb-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="rounded-md bg-primary/10 p-2 text-primary transition-colors group-hover:bg-primary/20">
                                    <Building2 class="h-4 w-4" />
                                </div>
                                <div>
                                    <CardTitle class="text-base leading-tight">
                                        {{ institution.name }}
                                    </CardTitle>
                                    <span v-if="institution.acronym" class="font-mono text-xs text-muted-foreground">
                                        {{ institution.acronym }}
                                    </span>
                                </div>
                            </div>
                            <Badge
                                :variant="institution.active ? 'default' : 'secondary'"
                                class="shrink-0 text-xs"
                            >
                                {{ institution.active ? 'Activa' : 'Inactiva' }}
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-1.5">
                        <CardDescription v-if="institution.code" class="font-mono text-xs">
                            Código: {{ institution.code }}
                        </CardDescription>
                        <CardDescription v-if="institution.type" class="text-xs capitalize">
                            {{ getTypeLabel(institution.type) }}
                        </CardDescription>
                        <div
                            v-if="institution.city || institution.country"
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <MapPin class="h-3 w-3 shrink-0" />
                            <span>{{ [institution.city, institution.country].filter(Boolean).join(', ') }}</span>
                        </div>
                        <div
                            v-if="institution.email"
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Mail class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{ institution.email }}</span>
                        </div>
                        <div
                            v-if="institution.phone"
                            class="flex items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Phone class="h-3 w-3 shrink-0" />
                            <span>{{ institution.phone }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="createDialogOpen">
            <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Nueva Institución</DialogTitle>
                    <DialogDescription>
                        Completa los datos para registrar una nueva institución.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4 mt-2" @submit.prevent="handleCreate">
                    <div class="grid grid-cols-2 gap-4">

                        <div class="col-span-2 space-y-1">
                            <Label for="name">Nombre <span class="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Ej. Universidad Central"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label>Tipo <span class="text-destructive">*</span></Label>
                            <Select v-model="form.type">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                                    <SelectValue placeholder="Seleccionar tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in INSTITUTION_TYPES" :key="t.value" :value="t.value">
                                        {{ t.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="code">Código</Label>
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="Ej. UC-001"
                                :class="{ 'border-destructive': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="acronym">Acrónimo</Label>
                            <Input
                                id="acronym"
                                v-model="form.acronym"
                                placeholder="Ej. UC"
                                :class="{ 'border-destructive': form.errors.acronym }"
                            />
                            <p v-if="form.errors.acronym" class="text-xs text-destructive">{{ form.errors.acronym }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="email">Correo electrónico</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="contacto@institucion.edu"
                                :class="{ 'border-destructive': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="phone">Teléfono</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="+593 99 999 9999"
                                :class="{ 'border-destructive': form.errors.phone }"
                            />
                            <p v-if="form.errors.phone" class="text-xs text-destructive">{{ form.errors.phone }}</p>
                        </div>

                        <div class="col-span-2 space-y-1">
                            <Label for="address">Dirección</Label>
                            <Input
                                id="address"
                                v-model="form.address"
                                placeholder="Av. Principal 123"
                                :class="{ 'border-destructive': form.errors.address }"
                            />
                            <p v-if="form.errors.address" class="text-xs text-destructive">{{ form.errors.address }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="city">Ciudad</Label>
                            <Input
                                id="city"
                                v-model="form.city"
                                placeholder="Quito"
                                :class="{ 'border-destructive': form.errors.city }"
                            />
                            <p v-if="form.errors.city" class="text-xs text-destructive">{{ form.errors.city }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="province">Provincia</Label>
                            <Input
                                id="province"
                                v-model="form.province"
                                placeholder="Pichincha"
                                :class="{ 'border-destructive': form.errors.province }"
                            />
                            <p v-if="form.errors.province" class="text-xs text-destructive">{{ form.errors.province }}</p>
                        </div>

                        <div class="space-y-1">
                            <Label for="country">País</Label>
                            <Input
                                id="country"
                                v-model="form.country"
                                placeholder="Ecuador"
                                :class="{ 'border-destructive': form.errors.country }"
                            />
                            <p v-if="form.errors.country" class="text-xs text-destructive">{{ form.errors.country }}</p>
                        </div>

                        <div class="col-span-2 flex items-center gap-3">
                            <Switch v-model="form.active" />
                            <Label>Institución activa</Label>
                        </div>

                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="createDialogOpen = false"
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Guardando...' : 'Crear Institución' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
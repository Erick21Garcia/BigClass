<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Search, Filter, RefreshCw, ClipboardList,
    Pencil, Plus, Trash2, Eye,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

interface Causer {
    id:   number;
    name: string;
}

interface LogEntry {
    id:           number;
    description:  string;
    subject_type: string;
    subject_id:   number;
    causer:       Causer | null;
    properties: {
        old:        Record<string, any> | null;
        attributes: Record<string, any> | null;
    };
    created_at: string;
}

interface PaginationLink {
    url:    string | null;
    label:  string;
    active: boolean;
}

interface Paginator {
    data:          LogEntry[];
    current_page:  number;
    last_page:     number;
    per_page:      number;
    total:         number;
    links:         PaginationLink[];
}

interface Props {
    logs:    Paginator;
    filters: {
        subject_type?: string;
        causer_id?:    string;
        date_from?:    string;
        date_to?:      string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Log de Auditoría', href: '/activity-log' },
];

// 'all' es el valor interno que representa "sin filtro"
const ALL = 'all';

const filters = ref({
    subject_type: props.filters.subject_type ?? ALL,
    causer_id:    props.filters.causer_id    ?? '',
    date_from:    props.filters.date_from    ?? '',
    date_to:      props.filters.date_to      ?? '',
});

const applyFilters = () => {
    router.get('/activity-log', {
        // Convierte 'all' de vuelta a vacío para no enviarlo al servidor
        ...Object.fromEntries(
            Object.entries(filters.value)
                .map(([k, v]) => [k, v === ALL ? '' : v])
                .filter(([, v]) => v !== '')
        ),
    }, { preserveScroll: true, replace: true });
};

const clearFilters = () => {
    filters.value = { subject_type: ALL, causer_id: '', date_from: '', date_to: '' };
    router.get('/activity-log', {}, { preserveScroll: true, replace: true });
};

const subjectTypes = [
    { value: 'Grade',          label: 'Notas' },
    { value: 'Enrollment',     label: 'Matrículas' },
    { value: 'EnrollmentItem', label: 'Ítems de matrícula' },
    { value: 'Section',        label: 'Secciones' },
    { value: 'Schedule',       label: 'Horarios' },
    { value: 'Attendance',     label: 'Asistencia' },
];

const descriptionConfig = (description: string) => {
    if (description.includes('creada') || description.includes('registrada') || description.includes('creado'))
        return { icon: Plus,    class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' };
    if (description.includes('modificada') || description.includes('modificado') || description.includes('actualizada'))
        return { icon: Pencil,  class: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400' };
    if (description.includes('eliminada') || description.includes('eliminado'))
        return { icon: Trash2,  class: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-400' };
    return { icon: Eye,         class: 'bg-muted text-muted-foreground' };
};

const formatProperties = (props: Record<string, any> | null): string => {
    if (!props) return '—';
    return Object.entries(props)
        .map(([k, v]) => `${k}: ${JSON.stringify(v)}`)
        .join(' · ');
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.visit(url, { preserveScroll: true });
};
</script>

<template>
    <Head title="Log de Auditoría" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-md bg-primary/10 p-3 text-primary">
                        <ClipboardList class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Log de Auditoría</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ props.logs.total }} registro(s) encontrado(s)
                        </p>
                    </div>
                </div>
                <Button variant="outline" size="sm" @click="clearFilters">
                    <RefreshCw class="mr-2 h-4 w-4" /> Limpiar filtros
                </Button>
            </div>

            <!-- Filtros -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2 text-sm">
                        <Filter class="h-4 w-4" /> Filtros
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <Label>Tipo de registro</Label>
                            <Select v-model="filters.subject_type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <!-- 'all' en vez de '' — SelectItem no acepta value vacío -->
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem
                                        v-for="t in subjectTypes"
                                        :key="t.value"
                                        :value="t.value"
                                    >
                                        {{ t.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1">
                            <Label>Desde</Label>
                            <Input v-model="filters.date_from" type="date" />
                        </div>

                        <div class="space-y-1">
                            <Label>Hasta</Label>
                            <Input v-model="filters.date_to" type="date" />
                        </div>

                        <div class="flex items-end">
                            <Button class="w-full" @click="applyFilters">
                                <Search class="mr-2 h-4 w-4" /> Buscar
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabla de logs -->
            <Card>
                <CardContent class="p-0">
                    <div v-if="props.logs.data.length === 0" class="flex flex-col items-center justify-center py-16 text-muted-foreground">
                        <ClipboardList class="mb-3 h-10 w-10 opacity-30" />
                        <p class="font-medium">Sin registros de auditoría</p>
                        <p class="mt-1 text-sm">Las acciones sobre notas, matrículas y horarios aparecerán aquí.</p>
                    </div>

                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40 text-xs text-muted-foreground">
                                <th class="px-4 py-3 text-left font-medium">Acción</th>
                                <th class="px-4 py-3 text-left font-medium">Módulo</th>
                                <th class="px-4 py-3 text-left font-medium">Registro #</th>
                                <th class="px-4 py-3 text-left font-medium">Usuario</th>
                                <th class="px-4 py-3 text-left font-medium">Cambios</th>
                                <th class="px-4 py-3 text-left font-medium">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="log in props.logs.data"
                                :key="log.id"
                                class="border-b last:border-0 hover:bg-muted/30 transition-colors"
                            >
                                <!-- Acción -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div :class="['rounded-md p-1.5', descriptionConfig(log.description).class]">
                                            <component
                                                :is="descriptionConfig(log.description).icon"
                                                class="h-3.5 w-3.5"
                                            />
                                        </div>
                                        <span class="font-medium">{{ log.description }}</span>
                                    </div>
                                </td>

                                <!-- Módulo -->
                                <td class="px-4 py-3">
                                    <Badge variant="secondary" class="text-xs">
                                        {{ subjectTypes.find(t => t.value === log.subject_type)?.label ?? log.subject_type }}
                                    </Badge>
                                </td>

                                <!-- ID del registro -->
                                <td class="px-4 py-3 font-mono text-muted-foreground">
                                    #{{ log.subject_id }}
                                </td>

                                <!-- Usuario -->
                                <td class="px-4 py-3">
                                    <span v-if="log.causer" class="font-medium">{{ log.causer.name }}</span>
                                    <span v-else class="text-muted-foreground italic">Sistema</span>
                                </td>

                                <!-- Cambios -->
                                <td class="px-4 py-3 max-w-xs">
                                    <div v-if="log.properties.old || log.properties.attributes" class="space-y-1">
                                        <div v-if="log.properties.old" class="text-[10px] text-red-600 truncate">
                                            Antes: {{ formatProperties(log.properties.old) }}
                                        </div>
                                        <div v-if="log.properties.attributes" class="text-[10px] text-emerald-600 truncate">
                                            Después: {{ formatProperties(log.properties.attributes) }}
                                        </div>
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>

                                <!-- Fecha -->
                                <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">
                                    {{ log.created_at }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <!-- Paginación -->
            <div v-if="props.logs.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Página {{ props.logs.current_page }} de {{ props.logs.last_page }}
                    · {{ props.logs.total }} registros
                </p>
                <div class="flex gap-1">
                    <Button
                        v-for="link in props.logs.links"
                        :key="link.label"
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        :disabled="!link.url"
                        class="min-w-8 text-xs"
                        @click="goToPage(link.url)"
                        v-html="link.label"
                    />
                </div>
            </div>

        </div>
    </AppLayout>
</template>
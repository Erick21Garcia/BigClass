<script setup lang="ts">
import { computed } from 'vue';
import { AlertCircle } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';

export interface SubjectItem {
    curriculum_id: number;
    subject_id: number;
    subject_name: string;
    subject_code: string | null;
    credits: number;
    is_mandatory: boolean;
    semester: { id: number; name: string; number: number } | null;
    can_enroll: boolean;
    already_enrolled: boolean;
    missing_prerequisites: { id: number; name: string; code: string | null }[];
}

const props = defineProps<{
    subject: SubjectItem;
    selected: boolean;
}>();

const emit = defineEmits<{ toggle: [] }>();

const isDisabled = computed(() =>
    !props.subject.can_enroll || props.subject.already_enrolled
);

const rowClass = computed(() => ({
    'flex items-start gap-3 rounded-lg border p-3 transition-colors': true,
    'cursor-pointer hover:bg-muted/50':  !isDisabled.value,
    'opacity-60 cursor-not-allowed':      isDisabled.value,
    'border-primary bg-primary/5':        props.selected,
}));

const handleToggle = () => {
    if (!isDisabled.value) emit('toggle');
};
</script>

<template>
    <div :class="rowClass" @click="handleToggle">
        <Checkbox
            :checked="props.selected"
            :disabled="isDisabled"
            class="mt-0.5 shrink-0"
            @click.stop
            @update:checked="handleToggle"
        />
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium leading-none">{{ props.subject.subject_name }}</span>
                <span v-if="props.subject.subject_code" class="font-mono text-xs text-muted-foreground">
                    {{ props.subject.subject_code }}
                </span>
                <Badge variant="outline" class="text-xs">
                    {{ props.subject.credits }} crédito{{ props.subject.credits !== 1 ? 's' : '' }}
                </Badge>
                <Badge v-if="!props.subject.is_mandatory" variant="secondary" class="text-xs">
                    Optativa
                </Badge>
                <Badge v-if="props.subject.already_enrolled" variant="secondary" class="text-xs">
                    Ya cursando
                </Badge>
                <Badge v-if="props.subject.semester" variant="outline" class="text-xs text-muted-foreground">
                    {{ props.subject.semester.name }}
                </Badge>
            </div>
            <div
                v-if="!props.subject.can_enroll && props.subject.missing_prerequisites.length > 0"
                class="mt-1.5 flex items-start gap-1 text-xs text-destructive"
            >
                <AlertCircle class="mt-0.5 h-3 w-3 shrink-0" />
                <span>
                    Prerequisito{{ props.subject.missing_prerequisites.length !== 1 ? 's' : '' }} pendiente{{ props.subject.missing_prerequisites.length !== 1 ? 's' : '' }}:
                    {{ props.subject.missing_prerequisites.map(p => p.name).join(', ') }}
                </span>
            </div>
        </div>
    </div>
</template>
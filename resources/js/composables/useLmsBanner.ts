import { shallowRef, watchEffect, type Component } from 'vue';

export type LmsTone = 'plain' | 'green' | 'amber' | 'red' | 'blue';

export interface LmsChip {
    label: string;
    icon?: Component;
    tone?: LmsTone;
}

export interface LmsBanner {
    tag: { label: string; icon: Component };
    title: string;
    back?: boolean;
    teacher?: string | null;
    chips?: LmsChip[];
    action?: { label: string; loading?: boolean; onClick: () => void } | null;
    ring?: { text: string; caption: string; progress: number } | null;
}

const banner = shallowRef<LmsBanner | null>(null);

export const lmsBanner = banner;

export function clearLmsBanner() {
    banner.value = null;
}

export function useLmsBanner(source: () => LmsBanner | null) {
    watchEffect(() => {
        banner.value = source();
    });
}
<script setup lang="ts">
import { computed, ref } from 'vue';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
    BreadcrumbEllipsis,
} from '@/components/ui/breadcrumb';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Link } from '@inertiajs/vue3';

interface BreadcrumbItemType {
    title: string;
    href?: string;
}

const props = defineProps<{
    breadcrumbs: BreadcrumbItemType[];
}>();

// Máximo de ítems visibles antes de colapsar (sin contar el último, que siempre se ve)
const MAX_VISIBLE = 3;

const isCollapsed = computed(() => props.breadcrumbs.length > MAX_VISIBLE);

// Cuando colapsa: muestra el primero, "...", y los últimos 2
const firstItem = computed(() => props.breadcrumbs[0]);
const hiddenItems = computed(() =>
    isCollapsed.value ? props.breadcrumbs.slice(1, -2) : []
);
const lastVisibleItems = computed(() =>
    isCollapsed.value ? props.breadcrumbs.slice(-2) : props.breadcrumbs.slice(1)
);
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList class="flex-nowrap overflow-hidden whitespace-nowrap">

            <template v-if="!isCollapsed">
                <!-- Sin colapsar: render normal -->
                <template v-for="(item, index) in breadcrumbs" :key="index">
                    <BreadcrumbItem>
                        <template v-if="index === breadcrumbs.length - 1">
                            <BreadcrumbPage class="truncate">{{ item.title }}</BreadcrumbPage>
                        </template>
                        <template v-else>
                            <BreadcrumbLink as-child>
                                <Link :href="item.href ?? '#'" class="truncate">{{ item.title }}</Link>
                            </BreadcrumbLink>
                        </template>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                </template>
            </template>

            <template v-else>
                <!-- Colapsado: primero + ... + últimos 2 -->
                <BreadcrumbItem>
                    <BreadcrumbLink as-child>
                        <Link :href="firstItem.href ?? '#'" class="truncate">{{ firstItem.title }}</Link>
                    </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />

                <BreadcrumbItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger class="flex items-center gap-1 hover:text-foreground">
                            <BreadcrumbEllipsis class="size-4" />
                            <span class="sr-only">Mostrar ruta completa</span>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start">
                            <DropdownMenuItem
                                v-for="(item, index) in hiddenItems"
                                :key="index"
                                as-child
                            >
                                <Link :href="item.href ?? '#'">{{ item.title }}</Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </BreadcrumbItem>
                <BreadcrumbSeparator />

                <template v-for="(item, index) in lastVisibleItems" :key="`last-${index}`">
                    <BreadcrumbItem>
                        <template v-if="index === lastVisibleItems.length - 1">
                            <BreadcrumbPage class="truncate">{{ item.title }}</BreadcrumbPage>
                        </template>
                        <template v-else>
                            <BreadcrumbLink as-child>
                                <Link :href="item.href ?? '#'" class="truncate">{{ item.title }}</Link>
                            </BreadcrumbLink>
                        </template>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator v-if="index !== lastVisibleItems.length - 1" />
                </template>
            </template>

        </BreadcrumbList>
    </Breadcrumb>
</template>
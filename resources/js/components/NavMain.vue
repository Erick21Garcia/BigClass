<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar'
import {
    Collapsible,
    CollapsibleTrigger,
    CollapsibleContent,
} from '@/components/ui/collapsible'
import { urlIsActive } from '@/lib/utils'
import { type NavItem } from '@/types'
import { Link, usePage } from '@inertiajs/vue3'
import { ChevronRight } from 'lucide-vue-next'
import { ref } from 'vue'

export interface NavGroup {
    label: string
    items: NavItem[]
}

defineProps<{
    groups: NavGroup[]
}>()

const page = usePage()
const openItems = ref<Set<string>>(new Set())

const hasActiveChild = (item: NavItem) => {
    if (!item.children) return false
    return item.children.some(child => urlIsActive(child.href ?? '', page.url))
}
</script>

<template>
    <SidebarGroup
        v-for="group in groups"
        :key="group.label"
        class="px-2 py-0"
    >
        <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>

        <SidebarMenu>
            <template v-for="item in group.items" :key="item.title">

                <!-- Ítem con hijos (collapsible) -->
                <Collapsible
                    v-if="item.children"
                    :open="openItems.has(item.title) || hasActiveChild(item)"
                    @update:open="(open) => open
                        ? openItems.add(item.title)
                        : openItems.delete(item.title)"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton>
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200"
                                    :class="openItems.has(item.title) || hasActiveChild(item) ? 'rotate-90' : ''"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>

                        <CollapsibleContent class="ml-6 space-y-1">
                            <SidebarMenuItem
                                v-for="child in item.children"
                                :key="child.title"
                            >
                                <SidebarMenuButton
                                    as-child
                                    :is-active="urlIsActive(child.href ?? '', page.url)"
                                >
                                    <Link :href="child.href ?? ''">
                                        <component :is="child.icon" />
                                        <span>{{ child.title }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <!-- Ítem simple -->
                <SidebarMenuItem v-else>
                    <SidebarMenuButton
                        as-child
                        :is-active="urlIsActive(item.href ?? '', page.url)"
                        :tooltip="item.title"
                    >
                        <Link :href="item.href ?? ''">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>

            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
<script setup lang="ts">
import NavMain from '@/components/NavMain.vue'
import NavUser from '@/components/NavUser.vue'
import type { NavGroup } from '@/components/NavMain.vue'
import users from '@/routes/users'
import permissions from '@/routes/permissions'
import roles from '@/routes/roles'
import people from '@/routes/people'
import students from '@/routes/students'
import teachers from '@/routes/teachers'
import admins from '@/routes/admins'
import institutions from '@/routes/institutions'
import academicPeriods from '@/routes/academic-periods'
import subjects from '@/routes/subjects'
import schedules from '@/routes/schedules'
import enrollments from '@/routes/enrollments'
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar'
import { dashboard } from '@/routes'
import { Link } from '@inertiajs/vue3'
import {
    LayoutGrid,
    Calendar, BookMarked, CalendarDays, BookCopy,
    Users, GraduationCap, BookOpen, Briefcase, User,
    School, Settings, UserCog, Key, ClipboardList,
} from 'lucide-vue-next'
import AppLogo from './AppLogo.vue'
import { computed } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { can, hasAnyRole } = useAuth()

type NavItemWithMeta = {
    title: string
    href?: string
    icon?: any
    permission?: string
    role?: string[]
    children?: NavItemWithMeta[]
}

function isVisible(item: NavItemWithMeta): boolean {
    if (hasAnyRole('admin', 'super-admin')) return true
    if (item.role && !hasAnyRole(...item.role)) return false
    if (item.permission && !can(item.permission)) return false
    return true
}

function filterItems(items: NavItemWithMeta[]): NavItemWithMeta[] {
    return items
        .filter(isVisible)
        .map(item => {
            if (!item.children) return item
            const visibleChildren = item.children.filter(isVisible)
            if (visibleChildren.length === 0) return null
            return { ...item, children: visibleChildren }
        })
        .filter(Boolean) as NavItemWithMeta[]
}

const navGroups = computed<NavGroup[]>(() => {
    const groups: { label: string; items: NavItemWithMeta[] }[] = [
        {
            label: 'Principal',
            items: [
                {
                    title: 'Dashboard',
                    href: dashboard().url,
                    icon: LayoutGrid,
                },
            ],
        },
        {
            label: 'Académico',
            items: [
                {
                    title: 'Períodos académicos',
                    href: academicPeriods.index().url,
                    icon: Calendar,
                    role: ['admin', 'super-admin'],
                },
                {
                    title: 'Horarios',
                    href: schedules.index().url,
                    icon: CalendarDays,
                    role: ['admin', 'super-admin'],
                },
                {
                    title: 'Materias',
                    href: subjects.index().url,
                    icon: BookCopy,
                    role: ['admin', 'super-admin'],
                },
                {
                    title: 'Institución',
                    href: institutions.index().url,
                    icon: School,
                    role: ['admin', 'super-admin'],
                },
            ],
        },
        {
            label: 'Sistema',
            items: [
                {
                    title: 'Personas',
                    icon: Users,
                    children: [
                        {
                            title: 'Estudiantes',
                            href: students.index().url,
                            icon: GraduationCap,
                            permission: 'students.index',
                        },
                        {
                            title: 'Docentes',
                            href: teachers.index().url,
                            icon: BookOpen,
                            permission: 'teachers.index',
                        },
                        {
                            title: 'Administrativos',
                            href: admins.index().url,
                            icon: Briefcase,
                            permission: 'staff.index',
                        },
                        {
                            title: 'Personas',
                            href: people.index().url,
                            icon: User,
                            permission: 'persons.index',
                        },
                    ],
                },
                {
                    title: 'Configuración',
                    icon: Settings,
                    role: ['admin', 'super-admin'],
                    children: [
                        {
                            title: 'Usuarios',
                            href: users.index().url,
                            icon: Users,
                            permission: 'users.index',
                        },
                        {
                            title: 'Roles',
                            href: roles.index().url,
                            icon: UserCog,
                            permission: 'roles.index',
                        },
                        {
                            title: 'Permisos',
                            href: permissions.index().url,
                            icon: Key,
                            permission: 'permissions.index',
                        },
                    ],
                },
                {
                    title: 'Log de auditoría',
                    href: '/activity-log',
                    icon: ClipboardList,
                    role: ['admin', 'super-admin'],
                },
            ],
        },
    ]

    // Filtrar ítems de cada grupo y eliminar grupos vacíos
    return groups
        .map(group => ({ ...group, items: filterItems(group.items) }))
        .filter(group => group.items.length > 0)
})
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard().url">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
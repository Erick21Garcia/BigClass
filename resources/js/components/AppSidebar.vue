<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import users from '@/routes/users';
import permissions from '@/routes/permissions';
import roles from '@/routes/roles';
import people from '@/routes/people';
import students from '@/routes/students';
import teachers from '@/routes/teachers';
import admins from '@/routes/admins';
import institutions from '@/routes/institutions';
import academicPeriods from '@/routes/academic-periods';
import subjects from '@/routes/subjects';
import schedules from '@/routes/schedules';
import enrollments from '@/routes/enrollments';
import activityLog from '@/routes/activity-log';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    UserCog, Settings, Key, Folder, BookOpen, LayoutGrid,
    Users, User, GraduationCap, School, Briefcase, Book,
    BookCopy, Calendar, ClipboardList, CalendarDays
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';
import { useAuth } from '@/composables/useAuth';

const { can, hasAnyRole } = useAuth();

const allNavItems: (NavItem & { permission?: string; role?: string[] })[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Administración',
        icon: Users,
        children: [
            {
                title: 'Personas',
                href: people.index().url,
                icon: User,
                permission: 'persons.index',
            },
            {
                title: 'Estudiantes',
                href: students.index().url,
                icon: GraduationCap,
                permission: 'students.index',
            },
            {
                title: 'Docentes',
                href: teachers.index().url,
                icon: Book,
                permission: 'teachers.index',
            },
            {
                title: 'Administrativos',
                href: admins.index().url,
                icon: Briefcase,
                permission: 'staff.index',
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
        title: 'Institución',
        href: institutions.index().url,
        icon: School,
        role: ['admin', 'super-admin'],
    },
    {
        title: 'Periodos Académicos',
        href: academicPeriods.index().url,
        icon: Calendar,
        role: ['admin', 'super-admin'],
    },
    {
        title: 'Materias',
        href: subjects.index().url,
        icon: BookCopy,
        role: ['admin', 'super-admin'],
    },
    {
        title: 'Horarios',
        href: schedules.index().url,
        icon: CalendarDays,
        role: ['admin', 'super-admin'],
    },
    {
        title: 'Log de Auditoría',
        href: '/activity-log',
        icon: ClipboardList,
        role: ['admin', 'super-admin'],
    },
];

function isVisible(item: NavItem & { permission?: string; role?: string[] }): boolean {
    if (hasAnyRole('admin')) return true;
    
    if (item.role && !hasAnyRole(...item.role)) return false;
    if (item.permission && !can(item.permission)) return false;
    return true;
}

const mainNavItems = computed<NavItem[]>(() => {
    return allNavItems
        .filter(isVisible)
        .map(item => {
            if (!item.children) return item;

            const visibleChildren = item.children.filter(child =>
                isVisible(child as NavItem & { permission?: string; role?: string[] })
            );

            if (visibleChildren.length === 0) return null;

            return { ...item, children: visibleChildren };
        })
        .filter(Boolean) as NavItem[];
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
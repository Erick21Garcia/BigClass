import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useAuth() {
    const page = usePage();

    const user = computed(() => page.props.auth.user);
    const roles = computed(() => (page.props.auth as any).roles as string[]);
    const permissions = computed(() => (page.props.auth as any).permissions as string[]);

    const hasRole = (role: string): boolean =>
        roles.value.includes(role);

    const hasAnyRole = (...roleList: string[]): boolean =>
        roleList.some(r => roles.value.includes(r));

    const can = (permission: string): boolean =>
        permissions.value.includes(permission);

    const canAny = (...permissionList: string[]): boolean =>
        permissionList.some(p => permissions.value.includes(p));

    return { user, roles, permissions, hasRole, hasAnyRole, can, canAny };
}
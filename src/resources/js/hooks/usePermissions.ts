import { usePage } from '@inertiajs/react';

type PageProps = {
    auth?: {
        permissions?: string[];
    };
};

export function usePermissions() {
    const { auth } = usePage<PageProps>().props;

    const permissions = auth?.permissions ?? [];

    const can = (permission: string): boolean => {
        return permissions.includes(permission);
    };

    const canAny = (items: string[]): boolean => {
        return items.some((permission) => permissions.includes(permission));
    };

    const canAll = (items: string[]): boolean => {
        return items.every((permission) => permissions.includes(permission));
    };

    return {
        permissions,
        can,
        canAny,
        canAll,
    };
}

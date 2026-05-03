import RoleForm from './Partials/RoleForm'
import { route } from 'ziggy-js'
import type { RoleData, RolePermissionPanel } from '@/types/role'

type Props = {
    readonly role: RoleData
    readonly permissionPanels: RolePermissionPanel[]
}

export default function Edit({ role, permissionPanels }: Props) {
    return (
        <RoleForm
            mode="edit"
            type={role.type}
            permissionPanels={permissionPanels}
            submitUrl={route('admin.roles.update', role.id)}
            initialData={{
                name: role.name ?? '',
                label: role.label ?? '',
                description: role.description ?? '',
                type: role.type,
                is_default: role.is_default,
                permission_ids: role.permission_ids ?? [],
            }}
            isSystem={role.is_system}
        />
    )
}

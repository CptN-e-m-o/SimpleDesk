import RoleForm from './Partials/RoleForm'
import { route } from 'ziggy-js'
import type { RolePermissionPanel, RoleType } from '@/types/role'

type Props = {
    readonly type: RoleType
    readonly permissionPanels: RolePermissionPanel[]
}

export default function Create({ type, permissionPanels }: Props) {
    return (
        <RoleForm
            mode="create"
            type={type}
            permissionPanels={permissionPanels}
            submitUrl={route('admin.roles.store')}
            initialData={{
                name: '',
                label: '',
                description: '',
                type,
                is_default: false,
                permission_ids: [],
            }}
        />
    )
}

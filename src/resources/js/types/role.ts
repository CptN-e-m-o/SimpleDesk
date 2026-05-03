export type RoleType = 'user' | 'agent'
export type RolePanelKey = 'admin' | 'agent' | 'client' | 'general'

export type RolePermission = {
    id: number
    key: string
    label: string
    type: RoleType
    ui_type: 'checkbox' | 'radio'
    description?: string | null
    parent_id?: number | null
    children: RolePermission[]
}

export type RolePermissionGroup = {
    id: number
    key: string
    label: string
    panel: RolePanelKey
    type: RoleType
    permissions: RolePermission[]
}

export type RolePermissionPanel = {
    key: RolePanelKey
    label: string
    groups: RolePermissionGroup[]
}

export type RoleData = {
    id: number
    name: string
    label: string
    description?: string | null
    type: RoleType
    is_system: boolean
    is_default: boolean
    permission_ids: number[]
}

export type RoleFormData = {
    name: string
    label: string
    description: string
    type: RoleType
    is_default: boolean
    permission_ids: number[]
}

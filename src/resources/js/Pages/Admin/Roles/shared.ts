export type Role = {
    readonly id: number
    readonly name: string
    readonly label: string
    readonly description?: string | null
    readonly is_system: boolean
    readonly is_deleted: boolean
    readonly deleted_at?: string | null
    readonly updated_at?: string | null
}

export function getRoleStatusLabel(role: Pick<Role, 'is_deleted' | 'is_system'>) {
    if (role.is_deleted) return 'Deleted'
    if (role.is_system) return 'System'
    return 'Custom'
}

export function getRoleStatusClasses(role: Pick<Role, 'is_deleted' | 'is_system'>) {
    if (role.is_deleted) return 'bg-rose-50 text-rose-700 ring-rose-200'
    if (role.is_system) return 'bg-sky-50 text-sky-700 ring-sky-200'
    return 'bg-emerald-50 text-emerald-700 ring-emerald-200'
}

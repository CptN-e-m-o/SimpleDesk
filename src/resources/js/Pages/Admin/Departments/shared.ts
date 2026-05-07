export type DepartmentManager = {
    id: number
    name: string
}

export type DepartmentStatus = {
    id?: number
    name: string
    code: string
    color?: string | null
} | null

export type Department = {
    id: number
    name: string
    type: string
    is_default: boolean
    is_deleted?: boolean
    deleted_at?: string | null
    managers?: DepartmentManager[]
    status?: DepartmentStatus
}

export function formatDepartmentType(type: string) {
    if (!type) return '—'

    return type
        .split(/[_\s-]+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ')
}

export function getDepartmentStatusClasses(department: Pick<Department, 'is_deleted' | 'is_default'>) {
    if (department.is_deleted) return 'bg-rose-50 text-rose-700 ring-rose-200'
    if (department.is_default) return 'bg-sky-50 text-sky-700 ring-sky-200'
    return 'bg-emerald-50 text-emerald-700 ring-emerald-200'
}

export function getDepartmentStatusLabel(department: Pick<Department, 'is_deleted' | 'is_default'>) {
    if (department.is_deleted) return 'Deleted'
    if (department.is_default) return 'Default'
    return 'Active'
}

export function getDepartmentSearchStatus(department: Pick<Department, 'is_deleted' | 'is_default'>) {
    if (department.is_deleted) return 'deleted'
    if (department.is_default) return 'default'
    return 'active'
}

export function getDepartmentTypeClasses(type: string) {
    return type === 'private'
        ? 'bg-violet-50 text-violet-700 ring-violet-200'
        : 'bg-sky-50 text-sky-700 ring-sky-200'
}

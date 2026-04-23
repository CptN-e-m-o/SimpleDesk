import type { UserOption } from '@/types/team'

export type TeamOption = {
    id: number
    name: string
}

export type DepartmentStatusOption = {
    id: number
    name: string
    code: string
    color?: string | null
}

export type DepartmentManager = {
    id: number
    name: string
    email: string
}

export type DepartmentTeam = {
    id: number
    name: string
}

export type DepartmentData = {
    id: number
    name: string
    slug: string
    type: 'public' | 'private'
    business_hours: string | null
    outgoing_email: string | null
    department_status_id: number | null
    signature: string | null
    is_default: boolean
    managers: DepartmentManager[]
    teams: DepartmentTeam[]
}

export type DepartmentFormData = {
    name: string
    type: 'public' | 'private'
    business_hours: string
    outgoing_email: string
    department_status_id: number | ''
    signature: string
    is_default: boolean
    manager_ids: number[]
    team_ids: number[]
}

export type DepartmentFormOptions = {
    users: UserOption[]
    teams: TeamOption[]
    statuses: DepartmentStatusOption[]
}

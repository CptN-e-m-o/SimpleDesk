export type DepartmentOption = {
    id: number
    name: string
}

export type TeamDepartment = {
    id: number
    name: string
    slug: string
}

export type UserOption = {
    id: number
    name: string
    email: string
}

export type TeamData = {
    id: number
    name: string
    departments: TeamDepartment[]
    is_active: boolean
    admin_notes: string | null
    member_ids: number[]
    lead_user_id: number | null
}

export type TeamFormData = {
    name: string
    departments: number[]
    is_active: boolean
    admin_notes: string
    member_ids: number[]
    lead_user_id: number | ''
}

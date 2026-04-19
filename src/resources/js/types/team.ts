export type DepartmentOption = {
    id: string
    name: string
}

export type UserOption = {
    id: number
    name: string
    email: string
}

export type TeamData = {
    id: number
    name: string
    departments: string[]
    is_active: boolean
    admin_notes: string | null
    member_ids: number[]
    lead_user_id: number | null
}

export type TeamFormData = {
    name: string
    departments: string[]
    is_active: boolean
    admin_notes: string
    member_ids: number[]
    lead_user_id: number | ''
}

import type { Agent } from '@/Pages/Admin/Agents/shared'

export type AgentRoleOption = {
    id: number
    name: string
    label: string
    type: 'agent' | 'user'
}

export type AgentDepartmentOption = {
    id: number
    name: string
}

export type AgentTeamOption = {
    id: number
    name: string
}

export type AgentTimezoneOption = {
    id: string
    name: string
}

export type AgentFormOptions = {
    readonly departments: AgentDepartmentOption[]
    readonly roles: AgentRoleOption[]
    readonly teams: AgentTeamOption[]
    readonly timezones: AgentTimezoneOption[]
}

export type AgentFormData = {
    email: string
    username: string
    first_name: string
    last_name: string
    location: string

    phone_country_iso2: string
    phone_country_code: string
    phone_number: string
    phone_ext: string

    mobile_country_iso2: string
    mobile_country_code: string
    mobile_number: string

    timezone: string
    signature: string
    is_active: boolean

    password: string
    password_confirmation: string

    role_ids: number[]
    department_ids: number[]
    team_ids: number[]
}

export type AgentFormAgent = Agent & {
    role_ids?: number[]
    department_ids?: number[]
    team_ids?: number[]
}

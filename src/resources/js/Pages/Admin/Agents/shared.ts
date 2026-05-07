export type AgentRole = {
    id: number
    name: string
    label: string
    type: 'agent' | 'user'
}

export type Agent = {
    id: number
    email: string
    username: string
    first_name: string
    last_name?: string | null
    name: string
    location?: string | null
    phone_country_code?: string | null
    phone_number?: string | null
    phone_ext?: string | null
    mobile_country_code?: string | null
    mobile_number?: string | null
    timezone: string
    is_active: boolean
    is_deleted: boolean
    deleted_at?: string | null
    email_verified_at?: string | null
    roles: AgentRole[]
    two_factor_confirmed_at?: string | null
    signature?: string | null
}

export function getPhone(agent: Agent) {
    const phone = [agent.phone_country_code, agent.phone_number]
        .filter(Boolean)
        .join(' ')

    if (!phone) return '—'

    return agent.phone_ext ? `${phone} ext. ${agent.phone_ext}` : phone
}

export function getMobile(agent: Agent) {
    return [agent.mobile_country_code, agent.mobile_number]
        .filter(Boolean)
        .join(' ') || '—'
}

export function getStatusLabel(agent: Agent) {
    if (agent.is_deleted) return 'Deleted'
    if (!agent.is_active) return 'Inactive'
    return 'Active'
}

export function getStatusClasses(agent: Agent) {
    if (agent.is_deleted) return 'bg-rose-50 text-rose-700 ring-rose-200'
    if (!agent.is_active) return 'bg-amber-50 text-amber-700 ring-amber-200'

    return 'bg-emerald-50 text-emerald-700 ring-emerald-200'
}

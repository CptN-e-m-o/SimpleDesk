export type TeamLead = {
    id: number
    name: string
    email?: string
}

export type Team = {
    id: number
    name: string
    members_count: number
    is_active: boolean
    is_deleted?: boolean
    deleted_at?: string | null
    lead?: TeamLead | null
}

export function getTeamStatusLabel(team: Pick<Team, 'is_active' | 'is_deleted'>) {
    if (team.is_deleted) return 'Deleted'
    return team.is_active ? 'Active' : 'Inactive'
}

export function getTeamStatusClasses(team: Pick<Team, 'is_active' | 'is_deleted'>) {
    if (team.is_deleted) return 'bg-rose-50 text-rose-700 ring-rose-200'

    return team.is_active
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
        : 'bg-amber-50 text-amber-700 ring-amber-200'
}

export function getTeamSearchStatus(team: Pick<Team, 'is_active' | 'is_deleted'>) {
    if (team.is_deleted) return 'deleted'
    if (team.is_active) return 'active'
    return 'inactive'
}

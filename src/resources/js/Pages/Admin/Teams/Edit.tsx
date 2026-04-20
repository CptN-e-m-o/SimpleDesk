import TeamForm from './Partials/TeamForm'
import { route } from 'ziggy-js'
import type { DepartmentOption, UserOption, TeamData } from '@/types/team'

type Props = {
    readonly team: TeamData
    readonly departments: DepartmentOption[]
    readonly users: UserOption[]
}

export default function Edit({ team, departments, users }: Props) {
    return (
        <TeamForm
            mode="edit"
            submitUrl={route('admin.teams.update', team.id)}
            departments={departments}
            users={users}
            initialData={{
                name: team.name ?? '',
                departments: team.departments.map((department) => department.id),
                is_active: team.is_active,
                admin_notes: team.admin_notes ?? '',
                member_ids: team.member_ids ?? [],
                lead_user_id: team.lead_user_id ?? '',
            }}
        />
    )
}

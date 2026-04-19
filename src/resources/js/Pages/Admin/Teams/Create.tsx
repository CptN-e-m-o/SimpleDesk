import TeamForm from './Partials/TeamForm'
import { route } from 'ziggy-js'
import type { DepartmentOption, UserOption } from '@/types/team'

type Props = {
    readonly departments: DepartmentOption[]
    readonly users: UserOption[]
}

export default function Create({ departments, users }: Props) {
    return (
        <TeamForm
            mode="create"
            departments={departments}
            users={users}
            submitUrl={route('admin.teams.store')}
            initialData={{
                name: '',
                departments: [],
                is_active: true,
                admin_notes: '',
                member_ids: [],
                lead_user_id: '',
            }}
        />
    )
}

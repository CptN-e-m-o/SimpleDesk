import DepartmentForm from './Partials/DepartmentForm'
import { route } from 'ziggy-js'
import type { DepartmentFormOptions } from '@/types/department'

type Props = DepartmentFormOptions

export default function Create({ users, teams, statuses }: Props) {
    return (
        <DepartmentForm
            mode="create"
            users={users}
            teams={teams}
            statuses={statuses}
            submitUrl={route('admin.departments.store')}
            initialData={{
                name: '',
                type: 'public',
                business_hours: '',
                outgoing_email: '',
                department_status_id: '',
                signature: '',
                is_default: false,
                manager_ids: [],
                team_ids: [],
            }}
        />
    )
}

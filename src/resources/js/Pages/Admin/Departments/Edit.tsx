import DepartmentForm from './Partials/DepartmentForm'
import { route } from 'ziggy-js'
import type {
    DepartmentData,
    DepartmentFormOptions,
} from '@/types/department'

type Props = DepartmentFormOptions & {
    readonly department: DepartmentData
}

export default function Edit({ department, users, teams, statuses }: Props) {
    return (
        <DepartmentForm
            mode="edit"
            users={users}
            teams={teams}
            statuses={statuses}
            submitUrl={route('admin.departments.update', department.id)}
            initialData={{
                name: department.name ?? '',
                type: department.type ?? 'public',
                business_hours: department.business_hours ?? '',
                outgoing_email: department.outgoing_email ?? '',
                department_status_id: department.department_status_id ?? '',
                signature: department.signature ?? '',
                is_default: department.is_default,
                manager_ids: department.managers.map((manager) => manager.id),
                team_ids: department.teams.map((team) => team.id),
            }}
        />
    )
}

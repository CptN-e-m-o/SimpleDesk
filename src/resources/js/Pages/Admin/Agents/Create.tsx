import AgentForm from './Partials/AgentForm'
import { route } from 'ziggy-js'
import type { AgentFormOptions } from '@/types/agent'

type Props = Readonly<AgentFormOptions>

export default function Create({
                                   departments,
                                   roles,
                                   teams,
                                   timezones,
                               }: Props) {
    return (
        <AgentForm
            mode="create"
            departments={departments}
            roles={roles}
            teams={teams}
            timezones={timezones}
            submitUrl={route('admin.agents.store')}
            initialData={{
                email: '',
                username: '',
                first_name: '',
                last_name: '',
                location: '',

                phone_country_iso2: 'DE',
                phone_country_code: '+49',
                phone_number: '',
                phone_ext: '',

                mobile_country_iso2: 'DE',
                mobile_country_code: '+49',
                mobile_number: '',

                timezone: 'Europe/Berlin',
                signature: '',
                is_active: true,

                password: '',
                password_confirmation: '',

                role_ids: [],
                department_ids: [],
                team_ids: [],
            }}
        />
    )
}

import AgentForm from './Partials/AgentForm'
import { route } from 'ziggy-js'
import type {
    AgentFormAgent,
    AgentFormOptions,
} from '@/types/agent'

type Props = AgentFormOptions & {
    readonly agent: AgentFormAgent
}

export default function Edit({
                                 agent,
                                 departments,
                                 roles,
                                 teams,
                                 timezones,
                             }: Props) {
    return (
        <AgentForm
            mode="edit"
            departments={departments}
            roles={roles}
            teams={teams}
            timezones={timezones}
            submitUrl={route('admin.agents.update', agent.id)}
            initialData={{
                email: agent.email ?? '',
                username: agent.username ?? '',
                first_name: agent.first_name ?? '',
                last_name: agent.last_name ?? '',
                location: agent.location ?? '',

                phone_country_iso2: agent.phone_country_iso2 ?? 'DE',
                phone_country_code: agent.phone_country_code ?? '+49',
                phone_number: agent.phone_number ?? '',
                phone_ext: agent.phone_ext ?? '',

                mobile_country_iso2: agent.mobile_country_iso2 ?? 'DE',
                mobile_country_code: agent.mobile_country_code ?? '+49',
                mobile_number: agent.mobile_number ?? '',

                timezone: agent.timezone ?? 'Europe/Berlin',
                signature: agent.signature ?? '',
                is_active: agent.is_active,

                password: '',
                password_confirmation: '',

                role_ids: agent.role_ids ?? agent.roles.map((role) => role.id),
                department_ids: agent.department_ids ?? [],
                team_ids: agent.team_ids ?? [],
            }}
        />
    )
}

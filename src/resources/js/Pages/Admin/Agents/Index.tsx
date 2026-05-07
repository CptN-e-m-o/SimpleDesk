import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Eye,
    Mail,
    MapPin,
    Pencil,
    Phone,
    Plus,
    RotateCcw,
    Search,
    ShieldAlert,
    Smartphone,
    Trash2,
    UserRound,
    Users,
} from 'lucide-react'
import {JSX, useMemo, useState} from 'react'
import { route } from 'ziggy-js'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/Components/ui/alert-dialog'

type AgentRole = {
    id: number
    name: string
    label: string
    type: 'agent' | 'user'
}

type Agent = {
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
}

type Props = {
    readonly agents?: Agent[]
}

type AgentAction =
    | {
    type: 'delete' | 'restore' | 'force-delete'
    agent: Agent
}
    | null

type SortField = 'name' | 'username' | 'email'
type SortDirection = 'asc' | 'desc'

const dialogTitles: Record<string, string> = {
    restore: 'Restore agent?',
    'force-delete': 'Delete agent permanently?',
    delete: 'Delete agent?',
}

function getPhone(agent: Agent) {
    const phone = [agent.phone_country_code, agent.phone_number]
        .filter(Boolean)
        .join(' ')

    if (!phone) return '—'

    return agent.phone_ext ? `${phone} ext. ${agent.phone_ext}` : phone
}

function getMobile(agent: Agent) {
    return [agent.mobile_country_code, agent.mobile_number]
        .filter(Boolean)
        .join(' ') || '—'
}

function getStatusLabel(agent: Agent) {
    if (agent.is_deleted) return 'Deleted'
    if (!agent.is_active) return 'Inactive'
    return 'Active'
}

function getStatusClasses(agent: Agent) {
    if (agent.is_deleted) return 'bg-rose-50 text-rose-700 ring-rose-200'
    if (!agent.is_active) return 'bg-amber-50 text-amber-700 ring-amber-200'

    return 'bg-emerald-50 text-emerald-700 ring-emerald-200'
}

function getDialogDescription(action?: AgentAction) {
    if (!action) return null

    const name = action.agent.name || action.agent.username

    if (action.type === 'restore') {
        return (
            <>
                Agent <span className="font-semibold text-gray-900">{name}</span>{' '}
                will be restored and returned to the list.
            </>
        )
    }

    if (action.type === 'force-delete') {
        return (
            <>
                Agent <span className="font-semibold text-gray-900">{name}</span>{' '}
                will be permanently deleted. This action cannot be undone.
            </>
        )
    }

    return (
        <>
            Agent <span className="font-semibold text-gray-900">{name}</span>{' '}
            will be soft deleted and can be restored later.
        </>
    )
}

export default function Index({ agents = [] }: Props) {
    const [search, setSearch] = useState('')
    const [agentAction, setAgentAction] = useState<AgentAction>(null)
    const [processing, setProcessing] = useState(false)
    const [sortField, setSortField] = useState<SortField>('name')
    const [sortDirection, setSortDirection] = useState<SortDirection>('asc')

    const activeAgentsCount = useMemo(() => {
        return agents.filter((agent) => !agent.is_deleted && agent.is_active).length
    }, [agents])

    const inactiveAgentsCount = useMemo(() => {
        return agents.filter((agent) => !agent.is_deleted && !agent.is_active).length
    }, [agents])

    const deletedAgentsCount = useMemo(() => {
        return agents.filter((agent) => agent.is_deleted).length
    }, [agents])

    const filteredAgents = useMemo(() => {
        const query = search.trim().toLowerCase()

        if (!query) return agents

        return agents.filter((agent) => {
            const status = getStatusLabel(agent).toLowerCase()
            const phone = getPhone(agent).toLowerCase()
            const mobile = getMobile(agent).toLowerCase()

            return (
                agent.name.toLowerCase().includes(query) ||
                agent.username.toLowerCase().includes(query) ||
                agent.email.toLowerCase().includes(query) ||
                status.includes(query) ||
                phone.includes(query) ||
                mobile.includes(query) ||
                agent.timezone.toLowerCase().includes(query) ||
                (agent.location ?? '').toLowerCase().includes(query)
            )
        })
    }, [agents, search])

    const sortedAgents = useMemo(() => {
        const result = [...filteredAgents]

        result.sort((a, b) => {
            const aValue = String(a[sortField] ?? '').toLowerCase()
            const bValue = String(b[sortField] ?? '').toLowerCase()
            const compare = aValue.localeCompare(bValue)

            return sortDirection === 'asc' ? compare : -compare
        })

        return result
    }, [filteredAgents, sortField, sortDirection])

    const isRestore = agentAction?.type === 'restore'

    const buttonText = processing
        ? 'Processing...'
        : agentAction?.type === 'restore'
            ? 'Restore Agent'
            : agentAction?.type === 'force-delete'
                ? 'Delete Permanently'
                : 'Delete Agent'

    const buttonClass = isRestore
        ? 'bg-emerald-600 hover:bg-emerald-700'
        : 'bg-rose-600 hover:bg-rose-700'

    function handleSort(field: SortField) {
        if (sortField === field) {
            setSortDirection((prev) => (prev === 'asc' ? 'desc' : 'asc'))
            return
        }

        setSortField(field)
        setSortDirection('asc')
    }

    function renderSortIcon(field: SortField) {
        if (sortField !== field) {
            return <ArrowUpDown className="h-4 w-4 text-gray-400" />
        }

        return sortDirection === 'asc' ? (
            <ArrowUp className="h-4 w-4 text-sky-600" />
        ) : (
            <ArrowDown className="h-4 w-4 text-sky-600" />
        )
    }

    function openActionDialog(
        type: 'delete' | 'restore' | 'force-delete',
        agent: Agent,
    ) {
        setAgentAction({ type, agent })
    }

    function closeActionDialog() {
        if (processing) return
        setAgentAction(null)
    }

    function handleAction() {
        if (!agentAction) return

        setProcessing(true)

        if (agentAction.type === 'delete') {
            router.delete(route('admin.agents.destroy', agentAction.agent.id), {
                preserveScroll: true,
                onSuccess: () => setAgentAction(null),
                onFinish: () => setProcessing(false),
            })

            return
        }

        if (agentAction.type === 'restore') {
            router.patch(
                route('admin.agents.restore', agentAction.agent.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => setAgentAction(null),
                    onFinish: () => setProcessing(false),
                },
            )

            return
        }

        router.delete(route('admin.agents.force-delete', agentAction.agent.id), {
            preserveScroll: true,
            onSuccess: () => setAgentAction(null),
            onFinish: () => setProcessing(false),
        })
    }

    return (
        <AdminLayout title="Agents">
            <Head title="Agents" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <Users className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Agents
                                    </h1>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Manage support agents, account status,
                                        contact details, and access.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div className="relative w-full sm:w-80">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Search agents, email, username, phone..."
                                        className="h-11 w-full rounded-2xl border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                    />
                                </div>

                                <Link
                                    href={route('admin.agents.create')}
                                    className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                >
                                    <Plus className="h-4 w-4" />
                                    New Agent
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                        <div className="grid gap-3 sm:grid-cols-4">
                            <Stat label="Total agents" value={agents.length} />
                            <Stat label="Active agents" value={activeAgentsCount} />
                            <Stat label="Inactive agents" value={inactiveAgentsCount} />
                            <Stat label="Deleted agents" value={deletedAgentsCount} />
                        </div>
                    </div>

                    <div className="p-6">
                        {sortedAgents.length > 0 ? (
                            <>
                                <div className="hidden overflow-hidden rounded-[24px] border border-gray-200 xl:block">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                        <tr>
                                            <SortableTh
                                                label="Name"
                                                field="name"
                                                onSort={handleSort}
                                                renderIcon={renderSortIcon}
                                            />

                                            <SortableTh
                                                label="User name"
                                                field="username"
                                                onSort={handleSort}
                                                renderIcon={renderSortIcon}
                                            />

                                            <SortableTh
                                                label="Email"
                                                field="email"
                                                onSort={handleSort}
                                                renderIcon={renderSortIcon}
                                            />

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Phone
                                            </th>

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Role
                                            </th>

                                            <th className="w-56 px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Status
                                            </th>

                                            <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Actions
                                            </th>
                                        </tr>
                                        </thead>

                                        <tbody className="divide-y divide-gray-100 bg-white">
                                        {sortedAgents.map((agent) => (
                                            <tr
                                                key={agent.id}
                                                className={`transition ${
                                                    agent.is_deleted
                                                        ? 'bg-rose-50/30 hover:bg-rose-50/50'
                                                        : 'hover:bg-sky-50/40'
                                                }`}
                                            >
                                                <td className="px-6 py-5">
                                                    <div>
                                                        <p
                                                            className={`font-semibold ${
                                                                agent.is_deleted
                                                                    ? 'text-gray-500 line-through'
                                                                    : 'text-gray-900'
                                                            }`}
                                                        >
                                                            {agent.name || '—'}
                                                        </p>
                                                        <p className="mt-1 text-sm text-gray-500">
                                                            Agent #{agent.id}
                                                        </p>
                                                    </div>
                                                </td>

                                                <td className="px-6 py-5 text-sm font-medium text-gray-700">
                                                    {agent.username}
                                                </td>

                                                <td className="px-6 py-5">
                                                    <div className="inline-flex items-center gap-2 text-sm text-gray-600">
                                                        <Mail className="h-4 w-4 text-gray-400" />
                                                        {agent.email}
                                                    </div>
                                                </td>

                                                <td className="px-6 py-5 text-sm text-gray-600">
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            <Phone className="h-4 w-4 text-gray-400" />
                                                            {getPhone(agent)}
                                                        </div>
                                                        <div className="flex items-center gap-2">
                                                            <Smartphone className="h-4 w-4 text-gray-400" />
                                                            {getMobile(agent)}
                                                        </div>
                                                    </div>
                                                </td>

                                                <td className="px-6 py-5">
                                                    <div className="flex flex-wrap gap-2">
                                                        {agent.roles.length > 0 ? (
                                                            agent.roles.map((role) => (
                                                                <span
                                                                    key={role.id}
                                                                    className="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200"
                                                                >
                                                                    {role.label}
                                                                </span>
                                                            ))
                                                        ) : (
                                                            <span className="text-sm text-gray-400">—</span>
                                                        )}
                                                    </div>
                                                </td>

                                                <td className="px-6 py-5">
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                            <span
                                                                className={`inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getStatusClasses(agent)}`}
                                                            >
                                                                {getStatusLabel(agent)}
                                                            </span>

                                                        {agent.email_verified_at ? (
                                                            <span className="inline-flex whitespace-nowrap rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                                    Email verified
                                                                </span>
                                                        ) : (
                                                            <span className="inline-flex whitespace-nowrap rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-200">
                                                                    Email not verified
                                                                </span>
                                                        )}
                                                    </div>
                                                </td>

                                                <td className="px-6 py-5">
                                                    <AgentActions
                                                        agent={agent}
                                                        onAction={openActionDialog}
                                                    />
                                                </td>
                                            </tr>
                                        ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="grid gap-4 xl:hidden">
                                    {sortedAgents.map((agent) => (
                                        <div
                                            key={agent.id}
                                            className={`rounded-[24px] border p-5 ${
                                                agent.is_deleted
                                                    ? 'border-rose-200 bg-rose-50/50'
                                                    : 'border-gray-200 bg-gray-50'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="min-w-0">
                                                    <h3
                                                        className={`text-base font-semibold ${
                                                            agent.is_deleted
                                                                ? 'text-gray-500 line-through'
                                                                : 'text-gray-900'
                                                        }`}
                                                    >
                                                        {agent.name || '—'}
                                                    </h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        @{agent.username}
                                                    </p>
                                                </div>

                                                <span
                                                    className={`inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getStatusClasses(agent)}`}
                                                >
                                                    {getStatusLabel(agent)}
                                                </span>
                                            </div>

                                            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                                <InfoCard icon={Mail} label="Email" value={agent.email} />
                                                <InfoCard icon={Phone} label="Phone" value={getPhone(agent)} />
                                                <InfoCard icon={Smartphone} label="Mobile" value={getMobile(agent)} />
                                                <InfoCard icon={MapPin} label="Location" value={agent.location || '—'} />
                                            </div>

                                            <div className="mt-4">
                                                <AgentActions
                                                    agent={agent}
                                                    onAction={openActionDialog}
                                                    mobile
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-4 flex items-center justify-between px-1 text-sm text-gray-500">
                                    <span>
                                        Showing {sortedAgents.length} of {agents.length}{' '}
                                        agents
                                    </span>
                                </div>
                            </>
                        ) : (
                            <div className="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-sm ring-1 ring-inset ring-gray-200">
                                    <UserRound className="h-8 w-8 text-gray-400" />
                                </div>

                                <h3 className="mt-5 text-lg font-semibold text-gray-900">
                                    No agents found
                                </h3>

                                <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
                                    {search
                                        ? 'Try changing your search query or clear the search field.'
                                        : 'Create your first agent to start managing support access.'}
                                </p>

                                <div className="mt-6">
                                    <Link
                                        href={route('admin.agents.create')}
                                        className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Create Agent
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </section>
            </div>

            <AlertDialog
                open={agentAction !== null}
                onOpenChange={(open) => {
                    if (!open) closeActionDialog()
                }}
            >
                <AlertDialogContent className="rounded-[28px] border border-gray-200 bg-white p-0 shadow-xl">
                    <div
                        className={`border-b border-gray-100 px-6 py-5 ${
                            agentAction?.type === 'restore'
                                ? 'bg-gradient-to-r from-emerald-50 to-white'
                                : 'bg-gradient-to-r from-rose-50 to-white'
                        }`}
                    >
                        <AlertDialogHeader>
                            <AlertDialogTitle className="text-xl font-semibold tracking-tight text-gray-900">
                                {dialogTitles[agentAction?.type ?? 'delete']}
                            </AlertDialogTitle>

                            <AlertDialogDescription className="mt-2 text-sm leading-6 text-gray-500">
                                {getDialogDescription(agentAction)}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                    </div>

                    <div className="px-6 py-5">
                        <AlertDialogFooter className="gap-3">
                            <AlertDialogCancel
                                disabled={processing}
                                className="mt-0 cursor-pointer rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            >
                                Cancel
                            </AlertDialogCancel>

                            <AlertDialogAction
                                onClick={(e) => {
                                    e.preventDefault()
                                    handleAction()
                                }}
                                disabled={processing}
                                className={`cursor-pointer rounded-2xl px-4 text-sm font-medium text-white transition disabled:cursor-not-allowed disabled:opacity-70 ${buttonClass}`}
                            >
                                {buttonText}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </div>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    )
}

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
            <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                {label}
            </p>
            <p className="mt-2 text-2xl font-semibold text-gray-900">{value}</p>
        </div>
    )
}

function SortableTh({
                        label,
                        field,
                        onSort,
                        renderIcon,
                    }: {
    label: string
    field: SortField
    onSort: (field: SortField) => void
    renderIcon: (field: SortField) => JSX.Element
}) {
    return (
        <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
            <button
                type="button"
                onClick={() => onSort(field)}
                className="inline-flex cursor-pointer items-center gap-2 transition hover:text-sky-700"
            >
                {label}
                {renderIcon(field)}
            </button>
        </th>
    )
}

function InfoCard({
                      icon: Icon,
                      label,
                      value,
                  }: {
    icon: typeof Mail
    label: string
    value: string
}) {
    return (
        <div className="rounded-2xl bg-white px-4 py-3 ring-1 ring-inset ring-gray-200">
            <p className="flex items-center gap-2 text-xs uppercase tracking-wide text-gray-400">
                <Icon className="h-3.5 w-3.5" />
                {label}
            </p>
            <p className="mt-1 break-words text-sm font-medium text-gray-900">
                {value}
            </p>
        </div>
    )
}

function AgentActions({
                         agent,
                         onAction,
                         mobile = false,
                     }: {
    agent: Agent
    onAction: (
        type: 'delete' | 'restore' | 'force-delete',
        agent: Agent,
    ) => void
    mobile?: boolean
}) {
    if (agent.is_deleted) {
        return (
            <div className={mobile ? 'grid gap-2 sm:grid-cols-2' : 'flex items-center justify-end gap-2'}>
                <button
                    type="button"
                    onClick={() => onAction('restore', agent)}
                    className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
                >
                    <RotateCcw className="h-4 w-4" />
                    Restore
                </button>

                <button
                    type="button"
                    onClick={() => onAction('force-delete', agent)}
                    className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                >
                    <ShieldAlert className="h-4 w-4" />
                    Delete permanently
                </button>
            </div>
        )
    }

    return (
        <div className={mobile ? 'flex items-center gap-2' : 'flex items-center justify-end gap-2'}>
            <Link
                href={route('admin.agents.show', agent.id)}
                className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
            >
                <Eye className="h-4 w-4" />
            </Link>

            <Link
                href={route('admin.agents.edit', agent.id)}
                className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
            >
                <Pencil className="h-4 w-4" />
            </Link>

            <button
                type="button"
                onClick={() => onAction('delete', agent)}
                className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
            >
                <Trash2 className="h-4 w-4" />
            </button>
        </div>
    )
}

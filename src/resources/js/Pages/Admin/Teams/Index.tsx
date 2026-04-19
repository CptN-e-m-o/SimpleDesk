import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    ArrowUpDown,
    ArrowDown,
    ArrowUp,
    Eye,
    Pencil,
    Plus,
    Search,
    Trash2,
    UsersRound,
    RotateCcw,
    ShieldAlert,
} from 'lucide-react'
import { useMemo, useState } from 'react'
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

type Team = {
    id: number
    name: string
    members_count: number
    is_active: boolean
    is_deleted: boolean
    deleted_at?: string | null
    lead?: {
        id: number
        name: string
    } | null
}

type Props = {
    teams?: Team[]
}

type TeamAction =
    | {
    type: 'delete' | 'restore' | 'force-delete'
    team: Team
}
    | null

type SortField = 'name' | 'members_count'
type SortDirection = 'asc' | 'desc'

function getStatusClasses(team: Team) {
    if (team.is_deleted) {
        return 'bg-rose-50 text-rose-700 ring-rose-200'
    }

    return team.is_active
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
        : 'bg-amber-50 text-amber-700 ring-amber-200'
}

function getStatusLabel(team: Team) {
    if (team.is_deleted) return 'Deleted'
    return team.is_active ? 'Active' : 'Inactive'
}

export default function Index({ teams = [] }: Props) {
    const [search, setSearch] = useState('')
    const [teamAction, setTeamAction] = useState<TeamAction>(null)
    const [processing, setProcessing] = useState(false)
    const [sortField, setSortField] = useState<SortField>('name')
    const [sortDirection, setSortDirection] = useState<SortDirection>('asc')

    const filteredTeams = useMemo(() => {
        const query = search.trim().toLowerCase()

        if (!query) return teams

        return teams.filter((team) => {
            const leadName = team.lead?.name?.toLowerCase() ?? ''
            const status = team.is_deleted
                ? 'deleted'
                : team.is_active
                    ? 'active'
                    : 'inactive'

            return (
                team.name.toLowerCase().includes(query) ||
                leadName.includes(query) ||
                status.includes(query)
            )
        })
    }, [teams, search])

    const sortedTeams = useMemo(() => {
        const result = [...filteredTeams]

        result.sort((a, b) => {
            if (sortField === 'name') {
                const compare = a.name.localeCompare(b.name)
                return sortDirection === 'asc' ? compare : -compare
            }

            if (sortField === 'members_count') {
                const compare = a.members_count - b.members_count
                return sortDirection === 'asc' ? compare : -compare
            }

            return 0
        })

        return result
    }, [filteredTeams, sortField, sortDirection])

    const activeTeamsCount = useMemo(() => {
        return teams.filter((team) => !team.is_deleted && team.is_active).length
    }, [teams])

    const deletedTeamsCount = useMemo(() => {
        return teams.filter((team) => team.is_deleted).length
    }, [teams])

    const totalAgentsInActiveTeams = useMemo(() => {
        return teams
            .filter((team) => !team.is_deleted)
            .reduce((sum, team) => sum + team.members_count, 0)
    }, [teams])

    function openActionDialog(
        type: 'delete' | 'restore' | 'force-delete',
        team: Team,
    ) {
        setTeamAction({ type, team })
    }

    function closeActionDialog() {
        if (processing) return
        setTeamAction(null)
    }

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

    function handleAction() {
        if (!teamAction) return

        setProcessing(true)

        if (teamAction.type === 'delete') {
            router.delete(route('admin.teams.destroy', teamAction.team.id), {
                preserveScroll: true,
                onSuccess: () => {
                    setTeamAction(null)
                },
                onFinish: () => {
                    setProcessing(false)
                },
            })

            return
        }

        if (teamAction.type === 'restore') {
            router.post(
                route('admin.teams.restore', teamAction.team.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setTeamAction(null)
                    },
                    onFinish: () => {
                        setProcessing(false)
                    },
                },
            )

            return
        }

        router.delete(route('admin.teams.force-delete', teamAction.team.id), {
            preserveScroll: true,
            onSuccess: () => {
                setTeamAction(null)
            },
            onFinish: () => {
                setProcessing(false)
            },
        })
    }

    return (
        <AdminLayout title="Teams">
            <Head title="Teams" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="min-w-0">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                        <UsersRound className="h-6 w-6 text-sky-600" />
                                    </div>

                                    <div>
                                        <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Teams
                                        </h1>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Group agents into teams for routing,
                                            ownership, and collaboration.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div className="relative w-full sm:w-80">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={(e) =>
                                            setSearch(e.target.value)
                                        }
                                        placeholder="Search teams, lead, or status..."
                                        className="h-11 w-full rounded-2xl border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                    />
                                </div>

                                <Link
                                    href={route('admin.teams.create')}
                                    className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                >
                                    <Plus className="h-4 w-4" />
                                    New Team
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                    Total teams
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900">
                                    {teams.length}
                                </p>
                            </div>

                            <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                    Active teams
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900">
                                    {activeTeamsCount}
                                </p>
                            </div>

                            <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                    Deleted teams
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900">
                                    {deletedTeamsCount}
                                </p>
                            </div>
                        </div>

                        <div className="mt-3">
                            <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                    Total agents in active teams
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900">
                                    {totalAgentsInActiveTeams}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        {sortedTeams.length > 0 ? (
                            <>
                                <div className="hidden overflow-hidden rounded-[24px] border border-gray-200 lg:block">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleSort('name')
                                                    }
                                                    className="inline-flex cursor-pointer items-center gap-2 transition hover:text-sky-700"
                                                >
                                                    Name
                                                    {renderSortIcon('name')}
                                                </button>
                                            </th>

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleSort(
                                                            'members_count',
                                                        )
                                                    }
                                                    className="inline-flex cursor-pointer items-center gap-2 transition hover:text-sky-700"
                                                >
                                                    Team Size
                                                    {renderSortIcon(
                                                        'members_count',
                                                    )}
                                                </button>
                                            </th>

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Status
                                            </th>

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Lead
                                            </th>

                                            <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Actions
                                            </th>
                                        </tr>
                                        </thead>

                                        <tbody className="divide-y divide-gray-100 bg-white">
                                        {sortedTeams.map((team) => (
                                            <tr
                                                key={team.id}
                                                className={`transition ${
                                                    team.is_deleted
                                                        ? 'bg-rose-50/30 hover:bg-rose-50/50'
                                                        : 'hover:bg-sky-50/40'
                                                }`}
                                            >
                                                <td className="px-6 py-5">
                                                    <div>
                                                        <p
                                                            className={`font-semibold ${
                                                                team.is_deleted
                                                                    ? 'text-gray-500 line-through'
                                                                    : 'text-gray-900'
                                                            }`}
                                                        >
                                                            {team.name}
                                                        </p>
                                                        <p className="mt-1 text-sm text-gray-500">
                                                            Team #{team.id}
                                                        </p>
                                                    </div>
                                                </td>

                                                <td className="px-6 py-5">
                                                    <div className="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700">
                                                        <UsersRound className="h-4 w-4 text-gray-500" />
                                                        {team.members_count}{' '}
                                                        members
                                                    </div>
                                                </td>

                                                <td className="px-6 py-5">
                                                        <span
                                                            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getStatusClasses(team)}`}
                                                        >
                                                            {getStatusLabel(team)}
                                                        </span>
                                                </td>

                                                <td className="px-6 py-5 text-sm text-gray-600">
                                                    {team.lead?.name ?? '—'}
                                                </td>

                                                <td className="px-6 py-5">
                                                    {!team.is_deleted ? (
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Link
                                                                href={route(
                                                                    'admin.teams.show',
                                                                    team.id,
                                                                )}
                                                                className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Link>

                                                            <Link
                                                                href={route(
                                                                    'admin.teams.edit',
                                                                    team.id,
                                                                )}
                                                                className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>

                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    openActionDialog(
                                                                        'delete',
                                                                        team,
                                                                    )
                                                                }
                                                                className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        <div className="flex items-center justify-end gap-2">
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    openActionDialog(
                                                                        'restore',
                                                                        team,
                                                                    )
                                                                }
                                                                className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
                                                            >
                                                                <RotateCcw className="h-4 w-4" />
                                                                Restore
                                                            </button>

                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    openActionDialog(
                                                                        'force-delete',
                                                                        team,
                                                                    )
                                                                }
                                                                className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                                                            >
                                                                <ShieldAlert className="h-4 w-4" />
                                                                Delete
                                                                permanently
                                                            </button>
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="grid gap-4 lg:hidden">
                                    {sortedTeams.map((team) => (
                                        <div
                                            key={team.id}
                                            className={`rounded-[24px] border p-5 ${
                                                team.is_deleted
                                                    ? 'border-rose-200 bg-rose-50/50'
                                                    : 'border-gray-200 bg-gray-50'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="min-w-0">
                                                    <h3
                                                        className={`text-base font-semibold ${
                                                            team.is_deleted
                                                                ? 'text-gray-500 line-through'
                                                                : 'text-gray-900'
                                                        }`}
                                                    >
                                                        {team.name}
                                                    </h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        Team #{team.id}
                                                    </p>
                                                </div>

                                                <span
                                                    className={`inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getStatusClasses(team)}`}
                                                >
                                                    {getStatusLabel(team)}
                                                </span>
                                            </div>

                                            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                                <div className="rounded-2xl bg-white px-4 py-3 ring-1 ring-inset ring-gray-200">
                                                    <p className="text-xs uppercase tracking-wide text-gray-400">
                                                        Team Size
                                                    </p>
                                                    <p className="mt-1 text-sm font-medium text-gray-900">
                                                        {team.members_count}{' '}
                                                        members
                                                    </p>
                                                </div>

                                                <div className="rounded-2xl bg-white px-4 py-3 ring-1 ring-inset ring-gray-200">
                                                    <p className="text-xs uppercase tracking-wide text-gray-400">
                                                        Lead
                                                    </p>
                                                    <p className="mt-1 text-sm font-medium text-gray-900">
                                                        {team.lead?.name ?? '—'}
                                                    </p>
                                                </div>
                                            </div>

                                            {!team.is_deleted ? (
                                                <div className="mt-4 flex items-center gap-2">
                                                    <Link
                                                        href={route(
                                                            'admin.teams.show',
                                                            team.id,
                                                        )}
                                                        className="inline-flex h-10 flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                        View
                                                    </Link>

                                                    <Link
                                                        href={route(
                                                            'admin.teams.edit',
                                                            team.id,
                                                        )}
                                                        className="inline-flex h-10 flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                        Edit
                                                    </Link>

                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            openActionDialog(
                                                                'delete',
                                                                team,
                                                            )
                                                        }
                                                        className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            ) : (
                                                <div className="mt-4 grid gap-2 sm:grid-cols-2">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            openActionDialog(
                                                                'restore',
                                                                team,
                                                            )
                                                        }
                                                        className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
                                                    >
                                                        <RotateCcw className="h-4 w-4" />
                                                        Restore
                                                    </button>

                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            openActionDialog(
                                                                'force-delete',
                                                                team,
                                                            )
                                                        }
                                                        className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                                                    >
                                                        <ShieldAlert className="h-4 w-4" />
                                                        Delete permanently
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-4 flex items-center justify-between px-1 text-sm text-gray-500">
                                    <span>
                                        Showing {sortedTeams.length} of{' '}
                                        {teams.length} teams
                                    </span>
                                </div>
                            </>
                        ) : (
                            <div className="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-sm ring-1 ring-inset ring-gray-200">
                                    <UsersRound className="h-8 w-8 text-gray-400" />
                                </div>

                                <h3 className="mt-5 text-lg font-semibold text-gray-900">
                                    No teams found
                                </h3>

                                <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
                                    {search
                                        ? 'Try changing your search query or clear the search field.'
                                        : 'Create your first team to start grouping agents for routing and collaboration.'}
                                </p>

                                <div className="mt-6">
                                    <Link
                                        href={route('admin.teams.create')}
                                        className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Create Team
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </section>
            </div>

            <AlertDialog
                open={teamAction !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        closeActionDialog()
                    }
                }}
            >
                <AlertDialogContent className="rounded-[28px] border border-gray-200 bg-white p-0 shadow-xl">
                    <div
                        className={`border-b border-gray-100 px-6 py-5 ${
                            teamAction?.type === 'restore'
                                ? 'bg-gradient-to-r from-emerald-50 to-white'
                                : 'bg-gradient-to-r from-rose-50 to-white'
                        }`}
                    >
                        <AlertDialogHeader>
                            <AlertDialogTitle className="text-xl font-semibold tracking-tight text-gray-900">
                                {teamAction?.type === 'restore'
                                    ? 'Restore team?'
                                    : teamAction?.type === 'force-delete'
                                        ? 'Delete team permanently?'
                                        : 'Delete team?'}
                            </AlertDialogTitle>

                            <AlertDialogDescription className="mt-2 text-sm leading-6 text-gray-500">
                                {teamAction?.type === 'restore' ? (
                                    <>
                                        Team{' '}
                                        <span className="font-semibold text-gray-900">
                                            {teamAction.team.name}
                                        </span>{' '}
                                        will be restored and returned to the
                                        list.
                                    </>
                                ) : teamAction?.type === 'force-delete' ? (
                                    <>
                                        Team{' '}
                                        <span className="font-semibold text-gray-900">
                                            {teamAction?.team.name}
                                        </span>{' '}
                                        will be permanently deleted. This action
                                        cannot be undone.
                                    </>
                                ) : (
                                    <>
                                        Team{' '}
                                        <span className="font-semibold text-gray-900">
                                            {teamAction?.team.name}
                                        </span>{' '}
                                        will be soft deleted and can be restored
                                        later.
                                    </>
                                )}
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
                                className={`cursor-pointer rounded-2xl px-4 text-sm font-medium text-white transition disabled:cursor-not-allowed disabled:opacity-70 ${
                                    teamAction?.type === 'restore'
                                        ? 'bg-emerald-600 hover:bg-emerald-700'
                                        : 'bg-rose-600 hover:bg-rose-700'
                                }`}
                            >
                                {processing
                                    ? 'Processing...'
                                    : teamAction?.type === 'restore'
                                        ? 'Restore Team'
                                        : teamAction?.type === 'force-delete'
                                            ? 'Delete Permanently'
                                            : 'Delete Team'}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </div>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    )
}

import {
    useEffect,
    useRef,
    useState,
} from 'react'
import type {
    FormEvent,
    ReactNode,
} from 'react'
import type { LucideIcon } from 'lucide-react'
import {
    Activity,
    Archive,
    Ban,
    Check,
    CheckCircle2,
    ChevronDown,
    CircleDot,
    CircleSlash,
    Clock3,
    Copy,
    Filter,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    ShieldCheck,
    SlidersHorizontal,
    Star,
    Tag,
    Trash2,
    UserRoundCheck,
    UsersRound,
    X,
} from 'lucide-react'
import {
    Head,
    Link,
    router,
} from '@inertiajs/react'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout'
import { resolveAgentStatusIcon } from './statusIcons'

type Status = {
    id: number
    name: string
    description?: string | null
    availability: string
    routing_eligibility: string
    icon: string
    color: string
    default_duration_minutes?: number | null
    is_system: boolean
    is_default: boolean
    is_active: boolean
    is_selectable?: boolean
    deleted_at?: string | null
    currently_used_periods_count: number
    periods_count: number
    revert_periods_count: number
    updated_at: string
}

type PaginationLink = {
    url?: string | null
    label: string
    active: boolean
}

type PaginatedStatuses = {
    data: Status[]
    links: PaginationLink[]
    current_page?: number
    last_page?: number
    from?: number | null
    to?: number | null
    total?: number
}

type Filters = {
    search?: string
    availability?: string
    routing?: string
    type?: string
    state?: string
}

type Props = {
    statuses: PaginatedStatuses
    filters?: Filters
    permissions?: string[]
}

type ActionPermissions = {
    canUpdate: boolean
    canArchive: boolean
    canDelete: boolean
}

type FilterOption = {
    value: string
    label: string
    description?: string
    icon?: LucideIcon
    badgeClassName?: string
}

const availabilityOptions: FilterOption[] = [
    {
        value: '',
        label: 'All availability',
        description: 'Any availability level',
        icon: Activity,
    },
    {
        value: 'available',
        label: 'Available',
        description: 'Ready to work normally',
        icon: CheckCircle2,
        badgeClassName:
            'bg-emerald-500',
    },
    {
        value: 'limited',
        label: 'Limited',
        description: 'Reduced availability',
        icon: CircleDot,
        badgeClassName:
            'bg-amber-500',
    },
    {
        value: 'unavailable',
        label: 'Unavailable',
        description: 'Not available for work',
        icon: Ban,
        badgeClassName:
            'bg-rose-500',
    },
]

const routingOptions: FilterOption[] = [
    {
        value: '',
        label: 'All routing',
        description: 'Any routing policy',
        icon: Activity,
    },
    {
        value: 'eligible',
        label: 'Eligible',
        description: 'Can receive new work',
        icon: CheckCircle2,
        badgeClassName:
            'bg-sky-500',
    },
    {
        value: 'fallback',
        label: 'Fallback',
        description: 'Used as a reserve candidate',
        icon: CircleDot,
        badgeClassName:
            'bg-violet-500',
    },
    {
        value: 'blocked',
        label: 'Blocked',
        description: 'Cannot receive new work',
        icon: CircleSlash,
        badgeClassName:
            'bg-gray-500',
    },
]

const typeOptions: FilterOption[] = [
    {
        value: '',
        label: 'All types',
        description: 'System and custom statuses',
        icon: Tag,
    },
    {
        value: 'system',
        label: 'System',
        description: 'Protected system status',
        icon: ShieldCheck,
        badgeClassName:
            'bg-indigo-500',
    },
    {
        value: 'custom',
        label: 'Custom',
        description: 'Administrator-created status',
        icon: Tag,
        badgeClassName:
            'bg-gray-500',
    },
]

const stateOptions: FilterOption[] = [
    {
        value: 'active',
        label: 'Active',
        description: 'Available for selection',
        icon: CheckCircle2,
        badgeClassName:
            'bg-emerald-500',
    },
    {
        value: 'inactive',
        label: 'Inactive',
        description: 'Temporarily disabled',
        icon: CircleDot,
        badgeClassName:
            'bg-amber-500',
    },
    {
        value: 'archived',
        label: 'Archived',
        description: 'Soft-deleted statuses',
        icon: Archive,
        badgeClassName:
            'bg-rose-500',
    },
]

export default function Index({
                                  statuses,
                                  filters = {},
                                  permissions = [],
                              }: Props) {
    const [search, setSearch] = useState(
        filters.search ?? '',
    )

    const [availability, setAvailability] =
        useState(
            filters.availability ?? '',
        )

    const [routing, setRouting] = useState(
        filters.routing ?? '',
    )

    const [type, setType] = useState(
        filters.type ?? '',
    )

    const [state, setState] = useState(
        filters.state ?? 'active',
    )

    const canCreate = permissions.includes(
        'admin.staff.agent_statuses.create',
    )

    const canUpdate = permissions.includes(
        'admin.staff.agent_statuses.update',
    )

    const canArchive = permissions.includes(
        'admin.staff.agent_statuses.archive',
    )

    const canDelete = permissions.includes(
        'admin.staff.agent_statuses.delete',
    )

    const total =
        statuses.total ?? statuses.data.length

    const systemCount = statuses.data.filter(
        (status) => status.is_system,
    ).length

    const currentlyUsedCount =
        statuses.data.reduce(
            (count, status) =>
                count +
                status.currently_used_periods_count,
            0,
        )

    const hasActiveFilters =
        search.trim() !== '' ||
        availability !== '' ||
        routing !== '' ||
        type !== '' ||
        state !== 'active'

    const submitFilters = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        router.get(
            route(
                'admin.agent-statuses.index',
            ),
            {
                search: search.trim(),
                availability,
                routing,
                type,
                state,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const resetFilters = () => {
        setSearch('')
        setAvailability('')
        setRouting('')
        setType('')
        setState('active')

        router.get(
            route(
                'admin.agent-statuses.index',
            ),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    return (
        <AdminLayout title="Agent Statuses">
            <Head title="Agent Statuses" />

            <div className="space-y-6 p-4 sm:p-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white p-6 sm:flex-row sm:items-center">
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <Activity className="h-6 w-6 text-sky-700" />
                            </div>

                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    Agent Statuses
                                </h1>

                                <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                    Configure agent
                                    availability, routing
                                    eligibility, temporary
                                    states, and the default
                                    working status.
                                </p>
                            </div>
                        </div>

                        {canCreate ? (
                            <Link
                                href={route(
                                    'admin.agent-statuses.create',
                                )}
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200"
                            >
                                <Plus className="h-4 w-4" />
                                Create status
                            </Link>
                        ) : null}
                    </div>

                    <div className="grid border-t border-gray-200 sm:grid-cols-3">
                        <Metric
                            label="Matching statuses"
                            value={String(total)}
                            icon={Tag}
                        />

                        <Metric
                            label="System on this page"
                            value={String(systemCount)}
                            icon={ShieldCheck}
                        />

                        <Metric
                            label="Agents using shown statuses"
                            value={String(
                                currentlyUsedCount,
                            )}
                            icon={UsersRound}
                        />
                    </div>
                </header>

                <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="rounded-t-[28px] border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                        <div className="flex items-start gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                                <SlidersHorizontal className="h-5 w-5 text-sky-600" />
                            </div>

                            <div>
                                <h2 className="font-semibold text-gray-900">
                                    Filters
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Find statuses by name,
                                    availability, routing
                                    policy, type, or state.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form
                        onSubmit={submitFilters}
                        className="grid gap-4 rounded-b-[28px] p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-[minmax(230px,1.4fr)_repeat(4,minmax(155px,1fr))_auto]"
                    >
                        <label className="block">
                            <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Search
                            </span>

                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                <input
                                    type="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(
                                            event.target
                                                .value,
                                        )
                                    }
                                    placeholder="Search statuses..."
                                    className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                />

                                {search !== '' ? (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setSearch('')
                                        }
                                        aria-label="Clear search"
                                        className="absolute right-3 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                    </button>
                                ) : null}
                            </div>
                        </label>

                        <FilterCombobox
                            label="Availability"
                            value={availability}
                            options={
                                availabilityOptions
                            }
                            onChange={setAvailability}
                        />

                        <FilterCombobox
                            label="Routing"
                            value={routing}
                            options={routingOptions}
                            onChange={setRouting}
                        />

                        <FilterCombobox
                            label="Type"
                            value={type}
                            options={typeOptions}
                            onChange={setType}
                        />

                        <FilterCombobox
                            label="State"
                            value={state}
                            options={stateOptions}
                            onChange={setState}
                        />

                        <div className="flex items-end gap-2 md:col-span-2 xl:col-span-1">
                            <button
                                type="submit"
                                className="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200 xl:flex-none"
                            >
                                <Filter className="h-4 w-4" />
                                Apply
                            </button>

                            {hasActiveFilters ? (
                                <button
                                    type="button"
                                    onClick={resetFilters}
                                    title="Reset filters"
                                    aria-label="Reset filters"
                                    className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            ) : null}
                        </div>
                    </form>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <div>
                            <h2 className="font-semibold text-gray-900">
                                Status catalog
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                {total === 1
                                    ? '1 status matches the current filters'
                                    : `${total} statuses match the current filters`}
                            </p>
                        </div>

                        {statuses.from &&
                        statuses.to ? (
                            <span className="text-sm text-gray-400">
                                Showing {statuses.from}–
                                {statuses.to}
                            </span>
                        ) : null}
                    </div>

                    {statuses.data.length === 0 ? (
                        <EmptyState
                            canCreate={canCreate}
                            filtered={
                                hasActiveFilters
                            }
                            onReset={resetFilters}
                        />
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table className="w-full min-w-[900px] table-fixed text-left text-sm">
                                    <colgroup>
                                        <col className="w-[31%]" />
                                        <col className="w-[19%]" />
                                        <col className="w-[18%]" />
                                        <col className="w-[12%]" />
                                        <col className="w-[11%]" />
                                        <col className="w-[9%]" />
                                    </colgroup>

                                    <thead>
                                    <tr className="border-b border-gray-200 bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <th className="px-5 py-4">
                                            Status
                                        </th>

                                        <th className="px-4 py-4">
                                            Work policy
                                        </th>

                                        <th className="px-4 py-4">
                                            Behavior
                                        </th>

                                        <th className="px-4 py-4">
                                            Usage
                                        </th>

                                        <th className="px-4 py-4">
                                            Updated
                                        </th>

                                        <th className="sticky right-0 z-20 border-l border-gray-200 bg-gray-50 px-4 py-4 text-right shadow-[-8px_0_18px_-16px_rgba(15,23,42,0.45)]">
                                            Actions
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-100">
                                    {statuses.data.map(
                                        (status) => (
                                            <StatusRow
                                                key={
                                                    status.id
                                                }
                                                status={
                                                    status
                                                }
                                                permissions={{
                                                    canUpdate,
                                                    canArchive,
                                                    canDelete,
                                                }}
                                            />
                                        ),
                                    )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="grid gap-4 p-4 lg:hidden">
                                {statuses.data.map(
                                    (status) => (
                                        <StatusCard
                                            key={status.id}
                                            status={
                                                status
                                            }
                                            permissions={{
                                                canUpdate,
                                                canArchive,
                                                canDelete,
                                            }}
                                        />
                                    ),
                                )}
                            </div>
                        </>
                    )}
                </section>

                {statuses.links.length > 3 ? (
                    <Pagination
                        links={statuses.links}
                    />
                ) : null}
            </div>
        </AdminLayout>
    )
}

function StatusRow({
                       status,
                       permissions,
                   }: {
    status: Status
    permissions: ActionPermissions
}) {
    const archived = Boolean(status.deleted_at)

    return (
        <tr
            className={`group ${
                archived
                    ? 'bg-rose-50/30 hover:bg-rose-50/60'
                    : 'hover:bg-gray-50/80'
            } transition`}
        >
            <td className="px-5 py-4 align-top">
                <StatusIdentity status={status} />
            </td>

            <td className="px-4 py-4 align-top">
                <div className="flex flex-col items-start gap-2">
                    <AvailabilityBadge
                        value={
                            status.availability
                        }
                    />

                    <RoutingBadge
                        value={
                            status.routing_eligibility
                        }
                    />
                </div>
            </td>

            <td className="px-4 py-4 align-top">
                <StatusBehavior
                    status={status}
                />
            </td>

            <td className="px-4 py-4 align-top">
                <div className="flex items-center gap-2 text-gray-700">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-100">
                        <UsersRound className="h-4 w-4 text-gray-500" />
                    </span>

                    <div>
                        <div className="font-semibold text-gray-900">
                            {
                                status.currently_used_periods_count
                            }
                        </div>

                        <div className="text-xs text-gray-400">
                            {status.currently_used_periods_count ===
                            1
                                ? 'agent'
                                : 'agents'}
                        </div>
                    </div>
                </div>
            </td>

            <td className="px-4 py-4 align-top">
                <span className="whitespace-nowrap text-sm text-gray-500">
                    {formatDate(
                        status.updated_at,
                    )}
                </span>
            </td>

            <td
                className={`sticky right-0 z-10 border-l border-gray-100 px-4 py-4 align-top shadow-[-8px_0_18px_-16px_rgba(15,23,42,0.45)] transition ${
                    archived
                        ? 'bg-rose-50 group-hover:bg-rose-50'
                        : 'bg-white group-hover:bg-gray-50'
                }`}
            >
                <StatusActions
                    status={status}
                    permissions={permissions}
                />
            </td>
        </tr>
    )
}

function StatusCard({
                        status,
                        permissions,
                    }: {
    status: Status
    permissions: ActionPermissions
}) {
    return (
        <article
            className={
                status.deleted_at
                    ? 'rounded-2xl border border-rose-200 bg-rose-50/40 p-4'
                    : 'rounded-2xl border border-gray-200 bg-white p-4'
            }
        >
            <StatusIdentity status={status} />

            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                <div className="rounded-2xl bg-gray-50 p-4">
                    <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Work policy
                    </div>

                    <div className="mt-3 flex flex-wrap gap-2">
                        <AvailabilityBadge
                            value={
                                status.availability
                            }
                        />

                        <RoutingBadge
                            value={
                                status.routing_eligibility
                            }
                        />
                    </div>
                </div>

                <div className="rounded-2xl bg-gray-50 p-4">
                    <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Behavior
                    </div>

                    <div className="mt-3">
                        <StatusBehavior
                            status={status}
                        />
                    </div>
                </div>
            </div>

            <div className="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-400">
                    <span className="inline-flex items-center gap-1.5">
                        <UsersRound className="h-3.5 w-3.5" />

                        {
                            status.currently_used_periods_count
                        }{' '}
                        {status.currently_used_periods_count ===
                        1
                            ? 'agent'
                            : 'agents'}
                    </span>

                    <span>
                        Updated{' '}
                        {formatDate(
                            status.updated_at,
                        )}
                    </span>
                </div>

                <StatusActions
                    status={status}
                    permissions={permissions}
                />
            </div>
        </article>
    )
}

function StatusIdentity({
                            status,
                        }: {
    status: Status
}) {
    const Icon = resolveAgentStatusIcon(
        status.icon,
    )

    return (
        <div className="flex min-w-0 items-start gap-3">
            <span
                className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                style={{
                    color: status.color,
                    backgroundColor: withAlpha(
                        status.color,
                        '12',
                    ),
                    boxShadow: `inset 0 0 0 1px ${withAlpha(
                        status.color,
                        '28',
                    )}`,
                }}
            >
                <Icon className="h-5 w-5" />
            </span>

            <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="font-semibold text-gray-900">
                        {status.name}
                    </span>

                    {status.is_default ? (
                        <SmallFlag
                            label="Default"
                            icon={Star}
                            className="bg-amber-50 text-amber-700 ring-amber-200"
                            iconClassName="fill-current"
                        />
                    ) : null}

                    {status.is_system ? (
                        <SmallFlag
                            label="System"
                            icon={ShieldCheck}
                            className="bg-indigo-50 text-indigo-700 ring-indigo-200"
                        />
                    ) : (
                        <SmallFlag
                            label="Custom"
                            icon={Tag}
                            className="bg-gray-100 text-gray-600 ring-gray-200"
                        />
                    )}

                    <StateBadge status={status} />
                </div>

                <p className="mt-1.5 line-clamp-2 max-w-md text-xs leading-5 text-gray-500">
                    {status.description ||
                        'No description provided.'}
                </p>
            </div>
        </div>
    )
}

function StatusBehavior({
                            status,
                        }: {
    status: Status
}) {
    return (
        <div className="space-y-2">
            <div className="flex items-start gap-2">
                <Clock3 className="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

                <div>
                    <div className="text-sm font-medium text-gray-700">
                        {durationLabel(
                            status.default_duration_minutes,
                        )}
                    </div>

                    <div className="text-xs text-gray-400">
                        Default duration
                    </div>
                </div>
            </div>

            <div className="flex items-center gap-2">
                <UserRoundCheck className="h-4 w-4 shrink-0 text-gray-400" />

                <span className="text-xs text-gray-500">
                    {status.is_selectable ===
                    false
                        ? 'Admin only'
                        : 'Agent selectable'}
                </span>
            </div>
        </div>
    )
}

function AvailabilityBadge({
                               value,
                           }: {
    value: string
}) {
    if (value === 'available') {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                <CheckCircle2 className="h-3.5 w-3.5" />
                Available
            </span>
        )
    }

    if (value === 'limited') {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                <CircleDot className="h-3.5 w-3.5" />
                Limited
            </span>
        )
    }

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">
            <Ban className="h-3.5 w-3.5" />
            Unavailable
        </span>
    )
}

function RoutingBadge({
                          value,
                      }: {
    value: string
}) {
    if (value === 'eligible') {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">
                <CheckCircle2 className="h-3.5 w-3.5" />
                Eligible
            </span>
        )
    }

    if (value === 'fallback') {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                <CircleDot className="h-3.5 w-3.5" />
                Fallback
            </span>
        )
    }

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
            <CircleSlash className="h-3.5 w-3.5" />
            Blocked
        </span>
    )
}

function StateBadge({
                        status,
                    }: {
    status: Status
}) {
    if (status.deleted_at) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">
                <span className="h-1.5 w-1.5 rounded-full bg-rose-500" />
                Archived
            </span>
        )
    }

    if (status.is_active) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                Active
            </span>
        )
    }

    return (
        <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
            <span className="h-1.5 w-1.5 rounded-full bg-amber-500" />
            Inactive
        </span>
    )
}

function SmallFlag({
                       label,
                       icon: Icon,
                       className,
                       iconClassName = '',
                   }: {
    label: string
    icon: LucideIcon
    className: string
    iconClassName?: string
}) {
    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset ${className}`}
        >
            <Icon
                className={`h-3 w-3 ${iconClassName}`}
            />
            {label}
        </span>
    )
}

function StatusActions({
                           status,
                           permissions,
                       }: {
    status: Status
    permissions: ActionPermissions
}) {
    const actionsAvailable =
        permissions.canUpdate ||
        permissions.canArchive ||
        permissions.canDelete

    if (!actionsAvailable) {
        return (
            <div className="text-right text-sm text-gray-300">
                —
            </div>
        )
    }

    const historyReferences =
        status.periods_count +
        status.revert_periods_count

    const canPermanentlyDelete =
        Boolean(status.deleted_at) &&
        !status.is_system &&
        !status.is_default &&
        historyReferences === 0

    const permanentDeleteTitle =
        historyReferences > 0
            ? 'Cannot permanently delete: this status is referenced by agent status history.'
            : status.is_system
                ? 'System statuses cannot be permanently deleted.'
                : status.is_default
                    ? 'The default status cannot be permanently deleted.'
                    : 'Delete status permanently'

    const duplicateStatus = () => {
        router.post(
            route(
                'admin.agent-statuses.duplicate',
                status.id,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const setDefaultStatus = () => {
        const confirmed = window.confirm(
            `Set "${status.name}" as the default agent status?`,
        )

        if (!confirmed) {
            return
        }

        router.patch(
            route(
                'admin.agent-statuses.default',
                status.id,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const archiveStatus = () => {
        const confirmed = window.confirm(
            `Archive the "${status.name}" status?`,
        )

        if (!confirmed) {
            return
        }

        router.delete(
            route(
                'admin.agent-statuses.destroy',
                status.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    const restoreStatus = () => {
        router.post(
            route(
                'admin.agent-statuses.restore',
                status.id,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const permanentlyDeleteStatus = () => {
        if (!canPermanentlyDelete) {
            return
        }

        const confirmed = window.confirm(
            `Permanently delete "${status.name}"?\n\nThis action cannot be undone.`,
        )

        if (!confirmed) {
            return
        }

        router.delete(
            route(
                'admin.agent-statuses.force-delete',
                status.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <div className="flex min-w-max items-center justify-end gap-1.5">
            {!status.deleted_at ? (
                <>
                    {permissions.canUpdate ? (
                        <>
                            <ActionButton
                                as="link"
                                href={route(
                                    'admin.agent-statuses.edit',
                                    status.id,
                                )}
                                title="Edit status"
                                label={`Edit ${status.name}`}
                                icon={Pencil}
                                hoverClassName="hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            />

                            <ActionButton
                                title="Duplicate status"
                                label={`Duplicate ${status.name}`}
                                icon={Copy}
                                onClick={duplicateStatus}
                                hoverClassName="hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700"
                            />

                            {!status.is_default ? (
                                <ActionButton
                                    title="Set as default"
                                    label={`Set ${status.name} as default`}
                                    icon={Star}
                                    onClick={setDefaultStatus}
                                    hoverClassName="hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700"
                                />
                            ) : null}
                        </>
                    ) : null}

                    {permissions.canArchive &&
                    !status.is_system &&
                    !status.is_default ? (
                        <ActionButton
                            title="Archive status"
                            label={`Archive ${status.name}`}
                            icon={Archive}
                            onClick={archiveStatus}
                            hoverClassName="hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                        />
                    ) : null}
                </>
            ) : (
                <>
                    {permissions.canArchive ? (
                        <ActionButton
                            title="Restore status"
                            label={`Restore ${status.name}`}
                            icon={RotateCcw}
                            onClick={restoreStatus}
                            hoverClassName="hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                        />
                    ) : null}

                    {permissions.canDelete ? (
                        <ActionButton
                            title={permanentDeleteTitle}
                            label={`Permanently delete ${status.name}`}
                            icon={Trash2}
                            onClick={permanentlyDeleteStatus}
                            disabled={!canPermanentlyDelete}
                            hoverClassName="hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700"
                        />
                    ) : null}
                </>
            )}
        </div>
    )
}

type ActionButtonProps = {
    title: string
    label: string
    icon: LucideIcon
    hoverClassName: string
    onClick?: () => void
    as?: 'button' | 'link'
    href?: string
    disabled?: boolean
}

function ActionButton({
                          title,
                          label,
                          icon: Icon,
                          hoverClassName,
                          onClick,
                          as = 'button',
                          href,
                          disabled = false,
                      }: ActionButtonProps) {
    const baseClassName =
        'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition'

    const className = disabled
        ? `${baseClassName} cursor-not-allowed opacity-40`
        : `${baseClassName} ${hoverClassName}`

    if (as === 'link' && href) {
        return (
            <Link
                href={href}
                title={title}
                aria-label={label}
                className={className}
            >
                <Icon className="h-4 w-4" />
            </Link>
        )
    }

    return (
        <button
            type="button"
            onClick={onClick}
            title={title}
            aria-label={label}
            disabled={disabled}
            className={className}
        >
            <Icon className="h-4 w-4" />
        </button>
    )
}

function FilterCombobox({
                            label,
                            value,
                            options,
                            onChange,
                        }: {
    label: string
    value: string
    options: FilterOption[]
    onChange: (value: string) => void
}) {
    const [open, setOpen] = useState(false)
    const rootRef =
        useRef<HTMLDivElement>(null)

    const selectedOption =
        options.find(
            (option) =>
                option.value === value,
        ) ?? options[0]

    const SelectedIcon =
        selectedOption.icon ?? Activity

    useEffect(() => {
        const closeOnOutsideClick = (
            event: MouseEvent,
        ) => {
            if (
                rootRef.current &&
                !rootRef.current.contains(
                    event.target as Node,
                )
            ) {
                setOpen(false)
            }
        }

        const closeOnEscape = (
            event: KeyboardEvent,
        ) => {
            if (event.key === 'Escape') {
                setOpen(false)
            }
        }

        document.addEventListener(
            'mousedown',
            closeOnOutsideClick,
        )

        document.addEventListener(
            'keydown',
            closeOnEscape,
        )

        return () => {
            document.removeEventListener(
                'mousedown',
                closeOnOutsideClick,
            )

            document.removeEventListener(
                'keydown',
                closeOnEscape,
            )
        }
    }, [])

    return (
        <div ref={rootRef} className="relative">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                {label}
            </span>

            <button
                type="button"
                role="combobox"
                aria-expanded={open}
                onClick={() =>
                    setOpen(
                        (current) => !current,
                    )
                }
                className={`flex h-11 w-full items-center gap-3 rounded-xl border bg-white px-3.5 text-left text-sm outline-none transition ${
                    open
                        ? 'border-sky-400 ring-4 ring-sky-100'
                        : 'border-gray-200 hover:border-gray-300'
                }`}
            >
                <span className="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500">
                    <SelectedIcon className="h-4 w-4" />

                    {selectedOption.badgeClassName ? (
                        <span
                            className={`absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full ring-2 ring-white ${selectedOption.badgeClassName}`}
                        />
                    ) : null}
                </span>

                <span className="min-w-0 flex-1 truncate font-medium text-gray-700">
                    {selectedOption.label}
                </span>

                <ChevronDown
                    className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                        open ? 'rotate-180' : ''
                    }`}
                />
            </button>

            {open ? (
                <div className="absolute left-0 right-0 z-50 mt-2 min-w-[220px] overflow-hidden rounded-2xl border border-gray-200 bg-white p-2 shadow-xl">
                    {options.map((option) => {
                        const Icon =
                            option.icon ??
                            Activity

                        const selected =
                            option.value === value

                        return (
                            <button
                                key={`${label}-${option.value}`}
                                type="button"
                                onClick={() => {
                                    onChange(
                                        option.value,
                                    )
                                    setOpen(false)
                                }}
                                className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition ${
                                    selected
                                        ? 'bg-sky-50'
                                        : 'hover:bg-gray-50'
                                }`}
                            >
                                <span className="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-500">
                                    <Icon className="h-4 w-4" />

                                    {option.badgeClassName ? (
                                        <span
                                            className={`absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full ring-2 ring-white ${option.badgeClassName}`}
                                        />
                                    ) : null}
                                </span>

                                <span className="min-w-0 flex-1">
                                    <span
                                        className={`block text-sm font-semibold ${
                                            selected
                                                ? 'text-sky-700'
                                                : 'text-gray-700'
                                        }`}
                                    >
                                        {option.label}
                                    </span>

                                    {option.description ? (
                                        <span className="mt-0.5 block truncate text-xs text-gray-400">
                                            {
                                                option.description
                                            }
                                        </span>
                                    ) : null}
                                </span>

                                {selected ? (
                                    <Check className="h-4 w-4 shrink-0 text-sky-600" />
                                ) : null}
                            </button>
                        )
                    })}
                </div>
            ) : null}
        </div>
    )
}

function Metric({
                    label,
                    value,
                    icon: Icon,
                }: {
    label: string
    value: string
    icon: LucideIcon
}) {
    return (
        <div className="border-b border-gray-200 px-6 py-4 last:border-b-0 sm:border-b-0 sm:border-r sm:last:border-r-0">
            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                <Icon className="h-4 w-4" />
                {label}
            </div>

            <div className="mt-1 text-lg font-semibold text-gray-900">
                {value}
            </div>
        </div>
    )
}

function EmptyState({
                        canCreate,
                        filtered,
                        onReset,
                    }: {
    canCreate: boolean
    filtered: boolean
    onReset: () => void
}) {
    return (
        <div className="px-6 py-16 text-center">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-gray-100">
                <Activity className="h-8 w-8 text-gray-400" />
            </div>

            <h3 className="mt-5 font-semibold text-gray-900">
                {filtered
                    ? 'No matching statuses'
                    : 'No agent statuses yet'}
            </h3>

            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                {filtered
                    ? 'Try changing or clearing the current filters.'
                    : 'Create statuses that describe agent availability and determine whether agents can receive new work.'}
            </p>

            <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                {filtered ? (
                    <button
                        type="button"
                        onClick={onReset}
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                    >
                        <X className="h-4 w-4" />
                        Clear filters
                    </button>
                ) : null}

                {canCreate ? (
                    <Link
                        href={route(
                            'admin.agent-statuses.create',
                        )}
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white transition hover:bg-sky-700"
                    >
                        <Plus className="h-4 w-4" />
                        Create status
                    </Link>
                ) : null}
            </div>
        </div>
    )
}

function Pagination({
                        links,
                    }: {
    links: PaginationLink[]
}) {
    return (
        <nav
            aria-label="Agent statuses pagination"
            className="flex flex-wrap items-center justify-center gap-1.5"
        >
            {links.map((link, index) => {
                const label =
                    formatPaginationLabel(
                        link.label,
                    )

                if (!link.url) {
                    return (
                        <span
                            key={`${link.label}-${index}`}
                            className="inline-flex h-10 min-w-10 cursor-not-allowed items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm text-gray-300"
                        >
                            {label}
                        </span>
                    )
                }

                return (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        preserveState
                        className={
                            link.active
                                ? 'inline-flex h-10 min-w-10 items-center justify-center rounded-xl bg-sky-600 px-3 text-sm font-semibold text-white shadow-sm'
                                : 'inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700'
                        }
                    >
                        {label}
                    </Link>
                )
            })}
        </nav>
    )
}

function durationLabel(
    value?: number | null,
): string {
    if (!value) {
        return 'No automatic reset'
    }

    if (value < 60) {
        return `${value} min`
    }

    if (value === 60) {
        return '1 hour'
    }

    if (value % 60 === 0) {
        return `${value / 60} hours`
    }

    const hours = Math.floor(value / 60)
    const minutes = value % 60

    return `${hours}h ${minutes}m`
}

function formatDate(value: string): string {
    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return new Intl.DateTimeFormat(
        undefined,
        {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        },
    ).format(date)
}

function formatPaginationLabel(
    label: string,
): string {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace(/<[^>]*>/g, '')
}

function isValidHex(value: string): boolean {
    return /^#[0-9A-Fa-f]{6}$/.test(
        value,
    )
}

function withAlpha(
    color: string,
    alpha: string,
): string {
    if (isValidHex(color)) {
        return `${color}${alpha}`
    }

    return color
}

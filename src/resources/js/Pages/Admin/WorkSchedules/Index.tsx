import { FormEvent, useMemo, useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import {
    Archive,
    CalendarClock,
    ChevronDown,
    Clock3,
    Copy,
    Eye,
    Filter,
    Globe2,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    UsersRound,
    X,
} from 'lucide-react'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout'
import type { Schedule } from './shared'

type Page<T> = {
    data: T[]
    links: {
        url: string | null
        label: string
        active: boolean
    }[]
    total: number
}

type AgentOption = {
    id: number
    name: string
}

type Permissions = {
    create: boolean
    update: boolean
    archive: boolean
}

type Props = {
    schedules: Page<Schedule>
    filters: Record<string, string | undefined>
    timezones: string[]
    agents: AgentOption[]
    permissions: Permissions
}

type FilterState = {
    search: string
    status: string
    timezone: string
    agent_id: string
}

const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

const inputClassName =
    'h-11 w-full rounded-xl border border-gray-200 bg-white text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'

export default function Index({
                                  schedules,
                                  filters,
                                  timezones,
                                  agents,
                                  permissions,
                              }: Props) {
    const [filterState, setFilterState] = useState<FilterState>({
        search: filters.search ?? '',
        status: filters.status ?? 'all',
        timezone: filters.timezone ?? '',
        agent_id: filters.agent_id ?? '',
    })

    const activeFiltersCount = useMemo(() => {
        return [
            filterState.search.trim() !== '',
            filterState.status !== 'all',
            filterState.timezone !== '',
            filterState.agent_id !== '',
        ].filter(Boolean).length
    }, [filterState])

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        const query: Record<string, string> = {}

        if (filterState.search.trim() !== '') {
            query.search = filterState.search.trim()
        }

        if (filterState.status !== 'all') {
            query.status = filterState.status
        }

        if (filterState.timezone !== '') {
            query.timezone = filterState.timezone
        }

        if (filterState.agent_id !== '') {
            query.agent_id = filterState.agent_id
        }

        router.get(route('admin.work-schedules.index'), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    }

    const resetFilters = () => {
        setFilterState({
            search: '',
            status: 'all',
            timezone: '',
            agent_id: '',
        })

        router.get(
            route('admin.work-schedules.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    return (
        <AdminLayout title="Work Schedules">
            <Head title="Work Schedules" />

            <div className="space-y-6 p-4 sm:p-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-5 bg-gradient-to-r from-sky-50/70 via-white to-white p-6 sm:flex-row sm:items-center">
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <CalendarClock className="h-6 w-6 text-sky-700" />
                            </div>

                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    Work Schedules
                                </h1>

                                <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                    Configure reusable weekly schedules, assign
                                    them to agents, and manage individual
                                    exceptions.
                                </p>
                            </div>
                        </div>

                        {permissions.create ? (
                            <Link
                                href={route('admin.work-schedules.create')}
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200"
                            >
                                <Plus className="h-4 w-4" />
                                Create schedule
                            </Link>
                        ) : null}
                    </div>
                </header>

                <form
                    onSubmit={applyFilters}
                    className="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div className="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <div>
                            <div className="flex items-center gap-2">
                                <Filter className="h-4 w-4 text-sky-600" />

                                <h2 className="font-semibold text-gray-900">
                                    Filters
                                </h2>

                                {activeFiltersCount > 0 ? (
                                    <span className="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-sky-100 px-2 text-xs font-semibold text-sky-700">
                                        {activeFiltersCount}
                                    </span>
                                ) : null}
                            </div>

                            <p className="mt-1 text-sm text-gray-500">
                                Narrow the list by name, status, timezone, or
                                assigned agent.
                            </p>
                        </div>

                        {activeFiltersCount > 0 ? (
                            <button
                                type="button"
                                onClick={resetFilters}
                                className="inline-flex items-center gap-1.5 self-start rounded-lg px-3 py-2 text-sm font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 sm:self-auto"
                            >
                                <X className="h-4 w-4" />
                                Reset filters
                            </button>
                        ) : null}
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label className="block">
                            <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Search
                            </span>

                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                <input
                                    type="search"
                                    value={filterState.search}
                                    onChange={(event) =>
                                        setFilterState((current) => ({
                                            ...current,
                                            search: event.target.value,
                                        }))
                                    }
                                    placeholder="Schedule name..."
                                    className={`${inputClassName} pl-10 pr-4`}
                                />
                            </div>
                        </label>

                        <FilterSelect
                            label="Status"
                            icon={
                                <CalendarClock className="h-4 w-4 text-gray-400" />
                            }
                            value={filterState.status}
                            onChange={(value) =>
                                setFilterState((current) => ({
                                    ...current,
                                    status: value,
                                }))
                            }
                        >
                            <option value="all">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="archived">Archived</option>
                        </FilterSelect>

                        <FilterSelect
                            label="Timezone"
                            icon={
                                <Globe2 className="h-4 w-4 text-gray-400" />
                            }
                            value={filterState.timezone}
                            onChange={(value) =>
                                setFilterState((current) => ({
                                    ...current,
                                    timezone: value,
                                }))
                            }
                        >
                            <option value="">All timezones</option>

                            {timezones.map((timezone) => (
                                <option key={timezone} value={timezone}>
                                    {timezone}
                                </option>
                            ))}
                        </FilterSelect>

                        <FilterSelect
                            label="Assigned agent"
                            icon={
                                <UsersRound className="h-4 w-4 text-gray-400" />
                            }
                            value={filterState.agent_id}
                            onChange={(value) =>
                                setFilterState((current) => ({
                                    ...current,
                                    agent_id: value,
                                }))
                            }
                        >
                            <option value="">All agents</option>

                            {agents.map((agent) => (
                                <option key={agent.id} value={agent.id}>
                                    {agent.name}
                                </option>
                            ))}
                        </FilterSelect>
                    </div>

                    <div className="mt-5 flex justify-end border-t border-gray-100 pt-4">
                        <button
                            type="submit"
                            className="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-gray-900 px-5 text-sm font-semibold text-white transition hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-200"
                        >
                            <Search className="h-4 w-4" />
                            Apply filters
                        </button>
                    </div>
                </form>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center">
                        <div>
                            <h2 className="font-semibold text-gray-900">
                                Schedules
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                {schedules.total === 1
                                    ? '1 schedule found'
                                    : `${schedules.total} schedules found`}
                            </p>
                        </div>
                    </div>

                    {schedules.data.length === 0 ? (
                        <EmptyState
                            hasFilters={activeFiltersCount > 0}
                            canCreate={permissions.create}
                            onReset={resetFilters}
                        />
                    ) : (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table className="w-full min-w-[1100px] text-left text-sm">
                                    <thead>
                                    <tr className="border-b border-gray-200 bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <th className="px-5 py-4">
                                            Name
                                        </th>
                                        <th className="px-5 py-4">
                                            Working hours
                                        </th>
                                        <th className="px-5 py-4">
                                            Timezone
                                        </th>
                                        <th className="px-5 py-4">
                                            Agents
                                        </th>
                                        <th className="px-5 py-4">
                                            Status
                                        </th>
                                        <th className="px-5 py-4">
                                            Updated
                                        </th>
                                        <th className="px-5 py-4 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-100">
                                    {schedules.data.map((schedule) => (
                                        <ScheduleRow
                                            key={schedule.id}
                                            schedule={schedule}
                                            permissions={permissions}
                                        />
                                    ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="grid gap-4 p-4 lg:hidden">
                                {schedules.data.map((schedule) => (
                                    <ScheduleCard
                                        key={schedule.id}
                                        schedule={schedule}
                                        permissions={permissions}
                                    />
                                ))}
                            </div>
                        </>
                    )}
                </section>

                {schedules.links.length > 3 ? (
                    <Pagination links={schedules.links} />
                ) : null}
            </div>
        </AdminLayout>
    )
}

function FilterSelect({
                          label,
                          icon,
                          value,
                          onChange,
                          children,
                      }: {
    label: string
    icon: React.ReactNode
    value: string
    onChange: (value: string) => void
    children: React.ReactNode
}) {
    return (
        <label className="block">
            <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                {label}
            </span>

            <div className="relative">
                <div className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2">
                    {icon}
                </div>

                <select
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className={`${inputClassName} cursor-pointer appearance-none pl-10 pr-10`}
                >
                    {children}
                </select>

                <ChevronDown className="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            </div>
        </label>
    )
}

function ScheduleRow({
                         schedule,
                         permissions,
                     }: {
    schedule: Schedule
    permissions: Permissions
}) {
    return (
        <tr
            className={
                schedule.deleted_at
                    ? 'bg-rose-50/30 transition hover:bg-rose-50/60'
                    : 'transition hover:bg-gray-50/70'
            }
        >
            <td className="px-5 py-4">
                <Link
                    href={route('admin.work-schedules.show', schedule.id)}
                    className="font-semibold text-gray-900 transition hover:text-sky-700"
                >
                    {schedule.name}
                </Link>
            </td>

            <td className="max-w-md px-5 py-4">
                <div className="flex items-start gap-2 text-gray-600">
                    <Clock3 className="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

                    <span className="leading-6">
                        {scheduleSummary(schedule)}
                    </span>
                </div>
            </td>

            <td className="px-5 py-4">
                <div className="inline-flex items-center gap-2 text-gray-600">
                    <Globe2 className="h-4 w-4 text-gray-400" />
                    {schedule.timezone}
                </div>
            </td>

            <td className="px-5 py-4">
                <div className="inline-flex items-center gap-2 text-gray-600">
                    <UsersRound className="h-4 w-4 text-gray-400" />
                    {schedule.assigned_agents_count}
                </div>
            </td>

            <td className="px-5 py-4">
                <ScheduleStatus schedule={schedule} />
            </td>

            <td className="whitespace-nowrap px-5 py-4 text-gray-500">
                {formatDate(schedule.updated_at)}
            </td>

            <td className="px-5 py-4">
                <ScheduleActions
                    schedule={schedule}
                    permissions={permissions}
                    align="right"
                />
            </td>
        </tr>
    )
}

function ScheduleCard({
                          schedule,
                          permissions,
                      }: {
    schedule: Schedule
    permissions: Permissions
}) {
    return (
        <article
            className={
                schedule.deleted_at
                    ? 'rounded-2xl border border-rose-200 bg-rose-50/40 p-4'
                    : 'rounded-2xl border border-gray-200 bg-white p-4'
            }
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <Link
                        href={route(
                            'admin.work-schedules.show',
                            schedule.id,
                        )}
                        className="font-semibold text-gray-900 transition hover:text-sky-700"
                    >
                        {schedule.name}
                    </Link>

                    <div className="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-sm text-gray-500">
                        <span className="inline-flex items-center gap-1.5">
                            <Globe2 className="h-4 w-4" />
                            {schedule.timezone}
                        </span>

                        <span className="inline-flex items-center gap-1.5">
                            <UsersRound className="h-4 w-4" />
                            {schedule.assigned_agents_count}{' '}
                            {schedule.assigned_agents_count === 1
                                ? 'agent'
                                : 'agents'}
                        </span>
                    </div>
                </div>

                <ScheduleStatus schedule={schedule} />
            </div>

            <div className="mt-4 rounded-xl bg-gray-50 p-3">
                <div className="flex items-start gap-2">
                    <Clock3 className="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

                    <p className="text-sm leading-6 text-gray-600">
                        {scheduleSummary(schedule)}
                    </p>
                </div>
            </div>

            <div className="mt-4 flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                <span className="text-xs text-gray-400">
                    Updated {formatDate(schedule.updated_at)}
                </span>

                <ScheduleActions
                    schedule={schedule}
                    permissions={permissions}
                    align="right"
                />
            </div>
        </article>
    )
}

function ScheduleStatus({ schedule }: { schedule: Schedule }) {
    if (schedule.deleted_at) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">
                <span className="h-1.5 w-1.5 rounded-full bg-rose-500" />
                Archived
            </span>
        )
    }

    if (schedule.is_active) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                Active
            </span>
        )
    }

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
            <span className="h-1.5 w-1.5 rounded-full bg-amber-500" />
            Inactive
        </span>
    )
}

function ScheduleActions({
                             schedule,
                             permissions,
                             align = 'left',
                         }: {
    schedule: Schedule
    permissions: Permissions
    align?: 'left' | 'right'
}) {
    const archiveSchedule = () => {
        const confirmed = window.confirm(
            `Archive the "${schedule.name}" schedule?`,
        )

        if (!confirmed) {
            return
        }

        router.delete(
            route('admin.work-schedules.destroy', schedule.id),
            {
                preserveScroll: true,
            },
        )
    }

    const duplicateSchedule = () => {
        router.post(
            route('admin.work-schedules.duplicate', schedule.id),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const restoreSchedule = () => {
        router.post(
            route('admin.work-schedules.restore', schedule.id),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <div
            className={`flex flex-wrap items-center gap-1.5 ${
                align === 'right' ? 'justify-end' : 'justify-start'
            }`}
        >
            <Link
                href={route('admin.work-schedules.show', schedule.id)}
                title="View schedule"
                aria-label={`View ${schedule.name}`}
                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
            >
                <Eye className="h-4 w-4" />
            </Link>

            {!schedule.deleted_at && permissions.update ? (
                <Link
                    href={route(
                        'admin.work-schedules.edit',
                        schedule.id,
                    )}
                    title="Edit schedule"
                    aria-label={`Edit ${schedule.name}`}
                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                >
                    <Pencil className="h-4 w-4" />
                </Link>
            ) : null}

            {!schedule.deleted_at && permissions.create ? (
                <button
                    type="button"
                    onClick={duplicateSchedule}
                    title="Duplicate schedule"
                    aria-label={`Duplicate ${schedule.name}`}
                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700"
                >
                    <Copy className="h-4 w-4" />
                </button>
            ) : null}

            {schedule.deleted_at && permissions.archive ? (
                <button
                    type="button"
                    onClick={restoreSchedule}
                    title="Restore schedule"
                    aria-label={`Restore ${schedule.name}`}
                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                >
                    <RotateCcw className="h-4 w-4" />
                </button>
            ) : null}

            {!schedule.deleted_at && permissions.archive ? (
                <button
                    type="button"
                    onClick={archiveSchedule}
                    title="Archive schedule"
                    aria-label={`Archive ${schedule.name}`}
                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                >
                    <Archive className="h-4 w-4" />
                </button>
            ) : null}
        </div>
    )
}

function EmptyState({
                        hasFilters,
                        canCreate,
                        onReset,
                    }: {
    hasFilters: boolean
    canCreate: boolean
    onReset: () => void
}) {
    return (
        <div className="px-6 py-16 text-center">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-gray-100">
                <CalendarClock className="h-8 w-8 text-gray-400" />
            </div>

            <h3 className="mt-5 font-semibold text-gray-900">
                {hasFilters
                    ? 'No matching schedules'
                    : 'No work schedules yet'}
            </h3>

            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                {hasFilters
                    ? 'Try changing or resetting the selected filters.'
                    : 'Create a reusable weekly schedule and assign it to your support agents.'}
            </p>

            <div className="mt-6 flex flex-wrap justify-center gap-3">
                {hasFilters ? (
                    <button
                        type="button"
                        onClick={onReset}
                        className="inline-flex h-10 items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        <RotateCcw className="h-4 w-4" />
                        Reset filters
                    </button>
                ) : null}

                {!hasFilters && canCreate ? (
                    <Link
                        href={route('admin.work-schedules.create')}
                        className="inline-flex h-10 items-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white transition hover:bg-sky-700"
                    >
                        <Plus className="h-4 w-4" />
                        Create schedule
                    </Link>
                ) : null}
            </div>
        </div>
    )
}

function Pagination({
                        links,
                    }: {
    links: Page<Schedule>['links']
}) {
    return (
        <nav
            aria-label="Work schedules pagination"
            className="flex flex-wrap items-center justify-center gap-1.5"
        >
            {links.map((link, index) => {
                const label = formatPaginationLabel(link.label)

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

function scheduleSummary(schedule: Schedule): string {
    const daySchedules = dayNames.map((_, dayIndex) => {
        return schedule.intervals
            .filter(
                (interval) =>
                    Number(interval.day_of_week) === dayIndex + 1,
            )
            .sort((first, second) =>
                first.starts_at.localeCompare(second.starts_at),
            )
            .map((interval) => {
                const start = formatTime(interval.starts_at)
                const end = formatTime(interval.ends_at)
                const nextDay = interval.ends_next_day
                    ? ' next day'
                    : ''

                return `${start}–${end}${nextDay}`
            })
            .join(', ')
    })

    const groups: {
        start: number
        end: number
        value: string
    }[] = []

    daySchedules.forEach((value, dayIndex) => {
        if (value === '') {
            return
        }

        const previousGroup =
            groups.length > 0 ? groups[groups.length - 1] : undefined

        if (
            previousGroup &&
            previousGroup.end === dayIndex - 1 &&
            previousGroup.value === value
        ) {
            previousGroup.end = dayIndex
            return
        }

        groups.push({
            start: dayIndex,
            end: dayIndex,
            value,
        })
    })

    if (groups.length === 0) {
        return 'No working intervals configured'
    }

    return groups
        .map((group) => {
            const days =
                group.start === group.end
                    ? dayNames[group.start]
                    : `${dayNames[group.start]}–${dayNames[group.end]}`

            return `${days} · ${group.value}`
        })
        .join('  •  ')
}

function formatTime(value: string): string {
    return value.slice(0, 5)
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value))
}

function formatPaginationLabel(label: string): string {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace('Previous', 'Previous')
        .replace('Next', 'Next')
}

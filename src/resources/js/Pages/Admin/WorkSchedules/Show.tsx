import { FormEvent, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { Head, Link, router, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    CalendarClock,
    CalendarDays,
    Check,
    Clock3,
    History,
    Pencil,
    Plus,
    Search,
    Trash2,
    UserPlus,
    UsersRound,
    X,
} from 'lucide-react'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout'
import type { Interval, Schedule } from './shared'

type Assignment = {
    id: number
    agent: {
        id: number
        name: string
    }
    effective_from: string
    effective_until: string | null
    exceptions: unknown[]
}

type AgentOption = {
    id: number
    name: string
}

type Permissions = {
    update: boolean
    assignments: boolean
    exceptions: boolean
}

type Props = {
    schedule: Schedule & {
        assignments: Assignment[]
    }
    agents: AgentOption[]
    permissions: Permissions
}

type AssignmentFormData = {
    user_ids: number[]
    effective_from: string
    effective_until: string
}

type AssignmentGroupProps = {
    title: string
    description: string
    assignments: Assignment[]
    variant: 'current' | 'future' | 'past'
    permissions: Permissions
}

const days = [
    {
        value: 1,
        name: 'Monday',
        short: 'Mon',
    },
    {
        value: 2,
        name: 'Tuesday',
        short: 'Tue',
    },
    {
        value: 3,
        name: 'Wednesday',
        short: 'Wed',
    },
    {
        value: 4,
        name: 'Thursday',
        short: 'Thu',
    },
    {
        value: 5,
        name: 'Friday',
        short: 'Fri',
    },
    {
        value: 6,
        name: 'Saturday',
        short: 'Sat',
    },
    {
        value: 7,
        name: 'Sunday',
        short: 'Sun',
    },
]

const fieldClassName =
    'h-11 w-full rounded-xl border border-gray-200 bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'

export default function Show({
                                 schedule,
                                 agents,
                                 permissions,
                             }: Props) {
    const form = useForm<AssignmentFormData>({
        user_ids: [],
        effective_from: '',
        effective_until: '',
    })

    const errors = form.errors as Record<
        string,
        string | undefined
    >

    const assignmentGroups = useMemo(() => {
        const today = startOfToday()

        const current: Assignment[] = []
        const future: Assignment[] = []
        const past: Assignment[] = []

        schedule.assignments.forEach((assignment) => {
            const startsAt = parseDate(assignment.effective_from)
            const endsAt = assignment.effective_until
                ? parseDate(assignment.effective_until)
                : null

            if (startsAt > today) {
                future.push(assignment)

                return
            }

            if (!endsAt || endsAt >= today) {
                current.push(assignment)

                return
            }

            past.push(assignment)
        })

        current.sort((first, second) =>
            first.agent.name.localeCompare(second.agent.name),
        )

        future.sort(
            (first, second) =>
                parseDate(first.effective_from).getTime() -
                parseDate(second.effective_from).getTime(),
        )

        past.sort((first, second) => {
            const firstEnd = first.effective_until
                ? parseDate(first.effective_until).getTime()
                : 0

            const secondEnd = second.effective_until
                ? parseDate(second.effective_until).getTime()
                : 0

            return secondEnd - firstEnd
        })

        return {
            current,
            future,
            past,
        }
    }, [schedule.assignments])

    const submitAssignment = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        form.post(
            route(
                'admin.work-schedules.assignments.store',
                schedule.id,
            ),
            {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset()
                },
            },
        )
    }

    const canCreateAssignment =
        permissions.assignments &&
        schedule.is_active &&
        !schedule.deleted_at

    return (
        <AdminLayout title={schedule.name}>
            <Head title={schedule.name} />

            <div className="space-y-6 p-4 sm:p-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="bg-gradient-to-r from-sky-50/80 via-white to-white p-6">
                        <div className="flex flex-col justify-between gap-5 lg:flex-row lg:items-start">
                            <div className="flex min-w-0 items-start gap-4">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                    <CalendarClock className="h-6 w-6 text-sky-700" />
                                </div>

                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-3">
                                        <h1 className="truncate text-2xl font-semibold tracking-tight text-gray-900">
                                            {schedule.name}
                                        </h1>

                                        <ScheduleStatus
                                            schedule={schedule}
                                        />
                                    </div>

                                    <p className="mt-2 text-sm text-gray-500">
                                        Weekly work schedule in{' '}
                                        <span className="font-medium text-gray-700">
                                            {schedule.timezone}
                                        </span>
                                    </p>

                                    {schedule.description ? (
                                        <p className="mt-3 max-w-3xl text-sm leading-6 text-gray-600">
                                            {schedule.description}
                                        </p>
                                    ) : null}
                                </div>
                            </div>

                            <div className="flex shrink-0 flex-wrap gap-2">
                                <Link
                                    href={route(
                                        'admin.work-schedules.index',
                                    )}
                                    className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    Back
                                </Link>

                                {permissions.update &&
                                !schedule.deleted_at ? (
                                    <Link
                                        href={route(
                                            'admin.work-schedules.edit',
                                            schedule.id,
                                        )}
                                        className="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200"
                                    >
                                        <Pencil className="h-4 w-4" />
                                        Edit schedule
                                    </Link>
                                ) : null}
                            </div>
                        </div>
                    </div>

                    <div className="grid border-t border-gray-200 sm:grid-cols-3">
                        <ScheduleMetric
                            label="Timezone"
                            value={schedule.timezone}
                        />

                        <ScheduleMetric
                            label="Weekly intervals"
                            value={String(schedule.intervals.length)}
                        />

                        <ScheduleMetric
                            label="Assigned agents"
                            value={String(
                                schedule.assigned_agents_count,
                            )}
                        />
                    </div>
                </header>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <SectionHeader
                        title="Weekly schedule"
                        description="Standard working hours repeated every week."
                        icon={
                            <CalendarDays className="h-5 w-5 text-sky-600" />
                        }
                    />

                    <div className="grid gap-4 p-4 sm:p-6 md:grid-cols-2 xl:grid-cols-3">
                        {days.map((day) => (
                            <ScheduleDay
                                key={day.value}
                                name={day.name}
                                shortName={day.short}
                                intervals={schedule.intervals.filter(
                                    (interval) =>
                                        Number(
                                            interval.day_of_week,
                                        ) === day.value,
                                )}
                            />
                        ))}
                    </div>
                </section>

                {permissions.assignments ? (
                    canCreateAssignment ? (
                        <AssignmentForm
                            agents={agents}
                            selectedAgentIds={
                                form.data.user_ids
                            }
                            effectiveFrom={
                                form.data.effective_from
                            }
                            effectiveUntil={
                                form.data.effective_until
                            }
                            errors={errors}
                            processing={form.processing}
                            onSubmit={submitAssignment}
                            onAgentsChange={(agentIds) =>
                                form.setData(
                                    'user_ids',
                                    agentIds,
                                )
                            }
                            onEffectiveFromChange={(value) =>
                                form.setData(
                                    'effective_from',
                                    value,
                                )
                            }
                            onEffectiveUntilChange={(value) =>
                                form.setData(
                                    'effective_until',
                                    value,
                                )
                            }
                        />
                    ) : (
                        <section className="rounded-[28px] border border-amber-200 bg-amber-50 p-6 shadow-sm">
                            <div className="flex items-start gap-4">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100">
                                    <UserPlus className="h-5 w-5 text-amber-700" />
                                </div>

                                <div>
                                    <h2 className="font-semibold text-amber-900">
                                        New assignments are unavailable
                                    </h2>

                                    <p className="mt-1 text-sm leading-6 text-amber-800">
                                        {schedule.deleted_at
                                            ? 'This schedule is archived. Restore it and activate it before assigning agents.'
                                            : 'This schedule is inactive. Activate it before assigning agents.'}
                                    </p>
                                </div>
                            </div>
                        </section>
                    )
                ) : null}

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <SectionHeader
                        title="Assignments"
                        description="Agents using this schedule now, in the future, or in previous periods."
                        icon={
                            <UsersRound className="h-5 w-5 text-sky-600" />
                        }
                        badge={schedule.assignments.length}
                    />

                    {schedule.assignments.length === 0 ? (
                        <div className="px-6 py-16 text-center">
                            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-gray-100">
                                <UsersRound className="h-8 w-8 text-gray-400" />
                            </div>

                            <h3 className="mt-5 font-semibold text-gray-900">
                                No assignments yet
                            </h3>

                            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                                Assign this schedule to one or more
                                agents and specify when the assignment
                                becomes effective.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-6 p-4 sm:p-6">
                            <AssignmentGroup
                                title="Current assignments"
                                description="Assignments active today."
                                assignments={
                                    assignmentGroups.current
                                }
                                variant="current"
                                permissions={permissions}
                            />

                            <AssignmentGroup
                                title="Future assignments"
                                description="Assignments that have not started yet."
                                assignments={
                                    assignmentGroups.future
                                }
                                variant="future"
                                permissions={permissions}
                            />

                            <AssignmentGroup
                                title="Assignment history"
                                description="Assignments whose effective period has ended."
                                assignments={
                                    assignmentGroups.past
                                }
                                variant="past"
                                permissions={permissions}
                            />
                        </div>
                    )}
                </section>
            </div>
        </AdminLayout>
    )
}

function AssignmentForm({
                            agents,
                            selectedAgentIds,
                            effectiveFrom,
                            effectiveUntil,
                            errors,
                            processing,
                            onSubmit,
                            onAgentsChange,
                            onEffectiveFromChange,
                            onEffectiveUntilChange,
                        }: {
    agents: AgentOption[]
    selectedAgentIds: number[]
    effectiveFrom: string
    effectiveUntil: string
    errors: Record<string, string | undefined>
    processing: boolean
    onSubmit: (event: FormEvent<HTMLFormElement>) => void
    onAgentsChange: (agentIds: number[]) => void
    onEffectiveFromChange: (value: string) => void
    onEffectiveUntilChange: (value: string) => void
}) {
    const [search, setSearch] = useState('')

    const filteredAgents = useMemo(() => {
        const query = search.trim().toLowerCase()

        if (query === '') {
            return agents
        }

        return agents.filter((agent) =>
            agent.name.toLowerCase().includes(query),
        )
    }, [agents, search])

    const selectedAgents = agents.filter((agent) =>
        selectedAgentIds.includes(agent.id),
    )

    const toggleAgent = (agentId: number) => {
        if (selectedAgentIds.includes(agentId)) {
            onAgentsChange(
                selectedAgentIds.filter(
                    (selectedId) => selectedId !== agentId,
                ),
            )

            return
        }

        onAgentsChange([...selectedAgentIds, agentId])
    }

    return (
        <form
            onSubmit={onSubmit}
            className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm"
        >
            <SectionHeader
                title="Assign agents"
                description="Create a new assignment period for one or more agents."
                icon={
                    <UserPlus className="h-5 w-5 text-sky-600" />
                }
                optional
            />

            <div className="grid gap-6 p-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(320px,0.6fr)]">
                <div>
                    <div className="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <h3 className="text-sm font-semibold text-gray-900">
                                Available agents
                            </h3>

                            <p className="mt-1 text-xs text-gray-500">
                                Select all agents who should receive
                                this schedule.
                            </p>
                        </div>

                        {selectedAgentIds.length > 0 ? (
                            <button
                                type="button"
                                onClick={() =>
                                    onAgentsChange([])
                                }
                                className="text-xs font-semibold text-gray-500 transition hover:text-rose-600"
                            >
                                Clear selection
                            </button>
                        ) : null}
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-gray-200">
                        <div className="border-b border-gray-200 bg-gray-50 p-3">
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                <input
                                    type="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Search agents..."
                                    className={`${fieldClassName} pl-10 pr-10`}
                                />

                                {search !== '' ? (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setSearch('')
                                        }
                                        aria-label="Clear agent search"
                                        className="absolute right-3 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                    </button>
                                ) : null}
                            </div>
                        </div>

                        <div className="max-h-72 divide-y divide-gray-100 overflow-y-auto">
                            {filteredAgents.length > 0 ? (
                                filteredAgents.map((agent) => {
                                    const selected =
                                        selectedAgentIds.includes(
                                            agent.id,
                                        )

                                    return (
                                        <button
                                            key={agent.id}
                                            type="button"
                                            onClick={() =>
                                                toggleAgent(
                                                    agent.id,
                                                )
                                            }
                                            className={`flex w-full items-center gap-3 px-4 py-3 text-left transition ${
                                                selected
                                                    ? 'bg-sky-50'
                                                    : 'bg-white hover:bg-gray-50'
                                            }`}
                                        >
                                            <span
                                                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold ${
                                                    selected
                                                        ? 'bg-sky-600 text-white'
                                                        : 'bg-gray-100 text-gray-500'
                                                }`}
                                            >
                                                {initials(
                                                    agent.name,
                                                )}
                                            </span>

                                            <span className="min-w-0 flex-1 truncate text-sm font-semibold text-gray-900">
                                                {agent.name}
                                            </span>

                                            <span
                                                className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-md border ${
                                                    selected
                                                        ? 'border-sky-600 bg-sky-600 text-white'
                                                        : 'border-gray-300 bg-white'
                                                }`}
                                            >
                                                {selected ? (
                                                    <Check className="h-3.5 w-3.5" />
                                                ) : null}
                                            </span>
                                        </button>
                                    )
                                })
                            ) : (
                                <div className="px-4 py-10 text-center">
                                    <UsersRound className="mx-auto h-8 w-8 text-gray-300" />

                                    <p className="mt-3 text-sm text-gray-500">
                                        No agents match your
                                        search.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    <ErrorMessage value={errors.user_ids} />
                </div>

                <div className="space-y-5">
                    <div className="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                        <div className="flex items-center gap-2">
                            <UsersRound className="h-4 w-4 text-gray-400" />

                            <h3 className="text-sm font-semibold text-gray-900">
                                Selected agents
                            </h3>

                            <span className="ml-auto rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                {selectedAgents.length}
                            </span>
                        </div>

                        {selectedAgents.length > 0 ? (
                            <div className="mt-4 flex flex-wrap gap-2">
                                {selectedAgents.map((agent) => (
                                    <button
                                        key={agent.id}
                                        type="button"
                                        onClick={() =>
                                            toggleAgent(agent.id)
                                        }
                                        className="inline-flex items-center gap-2 rounded-full bg-white py-1.5 pl-3 pr-2 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200 transition hover:ring-rose-200"
                                    >
                                        {agent.name}
                                        <X className="h-3.5 w-3.5 text-gray-400" />
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <p className="mt-4 text-sm leading-6 text-gray-500">
                                No agents selected.
                            </p>
                        )}
                    </div>

                    <Field
                        label="Effective from"
                        required
                        error={errors.effective_from}
                    >
                        <input
                            type="date"
                            value={effectiveFrom}
                            onChange={(event) =>
                                onEffectiveFromChange(
                                    event.target.value,
                                )
                            }
                            className={fieldClassName}
                        />
                    </Field>

                    <Field
                        label="Effective until"
                        error={errors.effective_until}
                        hint="Leave empty for an open-ended assignment."
                    >
                        <input
                            type="date"
                            value={effectiveUntil}
                            min={effectiveFrom || undefined}
                            onChange={(event) =>
                                onEffectiveUntilChange(
                                    event.target.value,
                                )
                            }
                            className={fieldClassName}
                        />
                    </Field>

                    <button
                        type="submit"
                        disabled={
                            processing ||
                            selectedAgentIds.length === 0
                        }
                        className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing ? (
                            <>
                                <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                Assigning...
                            </>
                        ) : (
                            <>
                                <Plus className="h-4 w-4" />
                                Create assignment
                            </>
                        )}
                    </button>
                </div>
            </div>
        </form>
    )
}

function ScheduleDay({
                         name,
                         shortName,
                         intervals,
                     }: {
    name: string
    shortName: string
    intervals: Interval[]
}) {
    const orderedIntervals = [...intervals].sort(
        (first, second) =>
            first.starts_at.localeCompare(second.starts_at),
    )

    return (
        <article
            className={`rounded-2xl border p-4 ${
                orderedIntervals.length > 0
                    ? 'border-gray-200 bg-white'
                    : 'border-gray-200 bg-gray-50/70'
            }`}
        >
            <div className="flex items-center gap-3">
                <span
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold ${
                        orderedIntervals.length > 0
                            ? 'bg-sky-100 text-sky-700'
                            : 'bg-gray-200 text-gray-500'
                    }`}
                >
                    {shortName}
                </span>

                <div>
                    <h3 className="font-semibold text-gray-900">
                        {name}
                    </h3>

                    <p className="mt-0.5 text-xs text-gray-500">
                        {orderedIntervals.length === 0
                            ? 'Day off'
                            : `${orderedIntervals.length} ${
                                orderedIntervals.length === 1
                                    ? 'interval'
                                    : 'intervals'
                            }`}
                    </p>
                </div>
            </div>

            {orderedIntervals.length > 0 ? (
                <div className="mt-4 space-y-2">
                    {orderedIntervals.map(
                        (interval, index) => (
                            <div
                                key={`${interval.starts_at}-${interval.ends_at}-${index}`}
                                className="flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-3 py-2.5"
                            >
                                <span className="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <Clock3 className="h-4 w-4 text-gray-400" />

                                    {formatTime(
                                        interval.starts_at,
                                    )}
                                    {' — '}
                                    {formatTime(interval.ends_at)}
                                </span>

                                {interval.ends_next_day ? (
                                    <span className="rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                                        Next day
                                    </span>
                                ) : null}
                            </div>
                        ),
                    )}
                </div>
            ) : (
                <div className="mt-4 rounded-xl border border-dashed border-gray-200 px-3 py-5 text-center text-sm text-gray-400">
                    No working hours
                </div>
            )}
        </article>
    )
}

function AssignmentGroup({
                             title,
                             description,
                             assignments,
                             variant,
                             permissions,
                         }: AssignmentGroupProps) {
    const variantClasses = {
        current: {
            icon: 'bg-emerald-100 text-emerald-700',
            badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        },
        future: {
            icon: 'bg-sky-100 text-sky-700',
            badge: 'bg-sky-50 text-sky-700 ring-sky-200',
        },
        past: {
            icon: 'bg-gray-100 text-gray-500',
            badge: 'bg-gray-100 text-gray-600 ring-gray-200',
        },
    }

    const styles = variantClasses[variant]

    return (
        <div>
            <div className="mb-3 flex items-start gap-3">
                <div
                    className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${styles.icon}`}
                >
                    {variant === 'past' ? (
                        <History className="h-4 w-4" />
                    ) : (
                        <CalendarClock className="h-4 w-4" />
                    )}
                </div>

                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-semibold text-gray-900">
                            {title}
                        </h3>

                        <span
                            className={`rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${styles.badge}`}
                        >
                            {assignments.length}
                        </span>
                    </div>

                    <p className="mt-1 text-sm text-gray-500">
                        {description}
                    </p>
                </div>
            </div>

            {assignments.length > 0 ? (
                <div className="grid gap-3 xl:grid-cols-2">
                    {assignments.map((assignment) => (
                        <AssignmentCard
                            key={assignment.id}
                            assignment={assignment}
                            variant={variant}
                            permissions={permissions}
                        />
                    ))}
                </div>
            ) : (
                <div className="rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-8 text-center text-sm text-gray-400">
                    No {variant} assignments.
                </div>
            )}
        </div>
    )
}

function AssignmentCard({
                            assignment,
                            variant,
                            permissions,
                        }: {
    assignment: Assignment
    variant: 'current' | 'future' | 'past'
    permissions: Permissions
}) {
    const deleteFutureAssignment = () => {
        const confirmed = window.confirm(
            `Delete the future assignment for "${assignment.agent.name}"?`,
        )

        if (!confirmed) {
            return
        }

        router.delete(
            route(
                'admin.work-schedule-assignments.destroy',
                assignment.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <article className="rounded-2xl border border-gray-200 bg-white p-4 transition hover:border-sky-200 hover:shadow-sm">
            <div className="flex items-start gap-3">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600">
                    {initials(assignment.agent.name)}
                </span>

                <div className="min-w-0 flex-1">
                    <h4 className="truncate font-semibold text-gray-900">
                        {assignment.agent.name}
                    </h4>

                    <div className="mt-2 flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500">
                        <span className="inline-flex items-center gap-1.5">
                            <CalendarDays className="h-3.5 w-3.5" />
                            From{' '}
                            {formatDate(
                                assignment.effective_from,
                            )}
                        </span>

                        <span className="inline-flex items-center gap-1.5">
                            <CalendarDays className="h-3.5 w-3.5" />
                            Until{' '}
                            {assignment.effective_until
                                ? formatDate(
                                    assignment.effective_until,
                                )
                                : 'ongoing'}
                        </span>
                    </div>
                </div>
            </div>

            <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                <span className="text-xs text-gray-400">
                    {assignment.exceptions.length}{' '}
                    {assignment.exceptions.length === 1
                        ? 'exception'
                        : 'exceptions'}
                </span>

                <div className="flex flex-wrap gap-2">
                    {permissions.exceptions ? (
                        <Link
                            href={route(
                                'admin.work-schedule-exceptions.index',
                                assignment.id,
                            )}
                            className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                        >
                            <CalendarClock className="h-3.5 w-3.5" />
                            Exceptions
                        </Link>
                    ) : null}

                    {variant === 'future' &&
                    permissions.assignments ? (
                        <button
                            type="button"
                            onClick={deleteFutureAssignment}
                            className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                            Delete
                        </button>
                    ) : null}
                </div>
            </div>
        </article>
    )
}

function ScheduleMetric({
                            label,
                            value,
                        }: {
    label: string
    value: string
}) {
    return (
        <div className="border-b border-gray-200 px-6 py-4 last:border-b-0 sm:border-b-0 sm:border-r sm:last:border-r-0">
            <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                {label}
            </div>

            <div className="mt-1 truncate text-sm font-semibold text-gray-800">
                {value}
            </div>
        </div>
    )
}

function ScheduleStatus({
                            schedule,
                        }: {
    schedule: Schedule
}) {
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

function SectionHeader({
                           title,
                           description,
                           icon,
                           badge,
                           optional = false,
                       }: {
    title: string
    description: string
    icon: ReactNode
    badge?: number
    optional?: boolean
}) {
    return (
        <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
            <div className="flex items-start gap-3">
                <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                    {icon}
                </div>

                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="font-semibold text-gray-900">
                            {title}
                        </h2>

                        {typeof badge === 'number' ? (
                            <span className="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                {badge}
                            </span>
                        ) : null}

                        {optional ? (
                            <span className="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                Optional
                            </span>
                        ) : null}
                    </div>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        {description}
                    </p>
                </div>
            </div>
        </div>
    )
}

function Field({
                   label,
                   required = false,
                   hint,
                   error,
                   children,
               }: {
    label: string
    required?: boolean
    hint?: string
    error?: string
    children: ReactNode
}) {
    return (
        <label className="block">
            <span className="mb-2 block text-sm font-semibold text-gray-700">
                {label}

                {required ? (
                    <span className="ml-1 text-rose-500">*</span>
                ) : null}
            </span>

            {children}

            {hint && !error ? (
                <span className="mt-1.5 block text-xs leading-5 text-gray-400">
                    {hint}
                </span>
            ) : null}

            <ErrorMessage value={error} />
        </label>
    )
}

function ErrorMessage({ value }: { value?: string }) {
    if (!value) {
        return null
    }

    return (
        <p className="mt-1.5 text-sm font-medium text-rose-600">
            {value}
        </p>
    )
}

function parseDate(value: string): Date {
    return new Date(`${value.slice(0, 10)}T00:00:00`)
}

function startOfToday(): Date {
    const date = new Date()

    date.setHours(0, 0, 0, 0)

    return date
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(parseDate(value))
}

function formatTime(value: string): string {
    return value.slice(0, 5)
}

function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
}

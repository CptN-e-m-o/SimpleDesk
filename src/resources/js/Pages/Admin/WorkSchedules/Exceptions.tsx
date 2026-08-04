import { useMemo } from 'react'
import type { FormEvent, ReactNode } from 'react'
import { Head, Link, router, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    CalendarClock,
    CalendarDays,
    CalendarOff,
    CalendarPlus,
    Check,
    Clock3,
    Info,
    Plus,
    Trash2,
    UserRound,
} from 'lucide-react'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout'

type Interval = {
    starts_at: string
    ends_at: string
    ends_next_day: boolean
}

type ScheduleException = {
    id: number
    date: string
    type: string
    reason: string | null
    intervals: Interval[]
}

type Assignment = {
    id: number
    agent: {
        name: string
    }
    schedule: {
        id: number
        name: string
        timezone: string
    }
    effective_from: string
    effective_until: string | null
    exceptions: ScheduleException[]
}

type Props = {
    assignment: Assignment
    types: string[]
}

type ExceptionFormData = {
    date: string
    type: string
    reason: string
    intervals: Interval[]
}

type ExceptionTypeMeta = {
    label: string
    description: string
    icon: ReactNode
    activeClassName: string
    badgeClassName: string
}

const fieldClassName =
    'h-11 w-full rounded-xl border border-gray-200 bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'

const defaultInterval = (): Interval => ({
    starts_at: '09:00',
    ends_at: '17:00',
    ends_next_day: false,
})

export default function Exceptions({
                                       assignment,
                                       types,
                                   }: Props) {
    const initialType = types.includes('day_off')
        ? 'day_off'
        : (types[0] ?? 'day_off')

    const form = useForm<ExceptionFormData>({
        date: '',
        type: initialType,
        reason: '',
        intervals: [],
    })

    const errors = form.errors as Record<
        string,
        string | undefined
    >

    const groupedExceptions = useMemo(() => {
        const today = startOfToday()

        const upcoming: ScheduleException[] = []
        const past: ScheduleException[] = []

        assignment.exceptions.forEach((exception) => {
            if (parseDate(exception.date) >= today) {
                upcoming.push(exception)

                return
            }

            past.push(exception)
        })

        upcoming.sort(
            (first, second) =>
                parseDate(first.date).getTime() -
                parseDate(second.date).getTime(),
        )

        past.sort(
            (first, second) =>
                parseDate(second.date).getTime() -
                parseDate(first.date).getTime(),
        )

        return {
            upcoming,
            past,
        }
    }, [assignment.exceptions])

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.post(
            route(
                'admin.work-schedule-exceptions.store',
                assignment.id,
            ),
            {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset()

                    form.setData({
                        date: '',
                        type: initialType,
                        reason: '',
                        intervals: [],
                    })
                },
            },
        )
    }

    const changeType = (type: string) => {
        if (type === 'day_off') {
            form.setData({
                ...form.data,
                type,
                intervals: [],
            })

            return
        }

        form.setData({
            ...form.data,
            type,
            intervals:
                form.data.intervals.length > 0
                    ? form.data.intervals
                    : [defaultInterval()],
        })
    }

    const addInterval = () => {
        form.setData('intervals', [
            ...form.data.intervals,
            defaultInterval(),
        ])
    }

    const updateInterval = (
        index: number,
        value: Partial<Interval>,
    ) => {
        form.setData(
            'intervals',
            form.data.intervals.map(
                (interval, currentIndex) =>
                    currentIndex === index
                        ? {
                            ...interval,
                            ...value,
                        }
                        : interval,
            ),
        )
    }

    const removeInterval = (index: number) => {
        form.setData(
            'intervals',
            form.data.intervals.filter(
                (_, currentIndex) => currentIndex !== index,
            ),
        )
    }

    const intervalRequired = form.data.type !== 'day_off'

    const canSubmit =
        form.data.date !== '' &&
        (!intervalRequired ||
            form.data.intervals.length > 0)

    return (
        <AdminLayout title="Schedule Exceptions">
            <Head title="Schedule Exceptions" />

            <div className="space-y-6 p-4 sm:p-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="bg-gradient-to-r from-violet-50/80 via-white to-white p-6">
                        <div className="flex flex-col justify-between gap-5 lg:flex-row lg:items-start">
                            <div className="flex min-w-0 items-start gap-4">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-100 ring-1 ring-inset ring-violet-200">
                                    <CalendarClock className="h-6 w-6 text-violet-700" />
                                </div>

                                <div className="min-w-0">
                                    <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                        Schedule Exceptions
                                    </h1>

                                    <p className="mt-1 text-sm leading-6 text-gray-500">
                                        Manage days off, replacement
                                        hours, and additional shifts for{' '}
                                        <span className="font-semibold text-gray-700">
                                            {assignment.agent.name}
                                        </span>
                                        .
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route(
                                    'admin.work-schedules.show',
                                    assignment.schedule.id,
                                )}
                                className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Back to schedule
                            </Link>
                        </div>
                    </div>

                    <div className="grid border-t border-gray-200 sm:grid-cols-2 xl:grid-cols-4">
                        <AssignmentMetric
                            icon={
                                <UserRound className="h-4 w-4" />
                            }
                            label="Agent"
                            value={assignment.agent.name}
                        />

                        <AssignmentMetric
                            icon={
                                <CalendarDays className="h-4 w-4" />
                            }
                            label="Schedule"
                            value={assignment.schedule.name}
                        />

                        <AssignmentMetric
                            icon={
                                <Clock3 className="h-4 w-4" />
                            }
                            label="Timezone"
                            value={assignment.schedule.timezone}
                        />

                        <AssignmentMetric
                            icon={
                                <CalendarClock className="h-4 w-4" />
                            }
                            label="Assignment period"
                            value={`${formatDate(
                                assignment.effective_from,
                            )} — ${
                                assignment.effective_until
                                    ? formatDate(
                                        assignment.effective_until,
                                    )
                                    : 'Ongoing'
                            }`}
                        />
                    </div>
                </header>

                <form
                    onSubmit={submit}
                    className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm"
                >
                    <SectionHeader
                        title="Add exception"
                        description="Create a one-day change that overrides or extends the agent's regular schedule."
                        icon={
                            <Plus className="h-5 w-5 text-sky-600" />
                        }
                    />

                    <div className="space-y-6 p-4 sm:p-6">
                        <div className="grid gap-5 lg:grid-cols-2">
                            <Field
                                label="Exception date"
                                required
                                error={errors.date}
                                hint="The date must fall within the assignment period."
                            >
                                <input
                                    type="date"
                                    value={form.data.date}
                                    min={assignment.effective_from.slice(
                                        0,
                                        10,
                                    )}
                                    max={
                                        assignment.effective_until?.slice(
                                            0,
                                            10,
                                        ) || undefined
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'date',
                                            event.target.value,
                                        )
                                    }
                                    className={fieldClassName}
                                />
                            </Field>

                            <Field
                                label="Reason"
                                error={errors.reason}
                                hint="Optional note explaining why this exception exists."
                            >
                                <input
                                    type="text"
                                    value={form.data.reason}
                                    onChange={(event) =>
                                        form.setData(
                                            'reason',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Vacation, appointment, additional coverage..."
                                    className={fieldClassName}
                                />
                            </Field>
                        </div>

                        <div>
                            <div className="mb-3">
                                <h3 className="text-sm font-semibold text-gray-700">
                                    Exception type
                                    <span className="ml-1 text-rose-500">
                                        *
                                    </span>
                                </h3>

                                <p className="mt-1 text-xs leading-5 text-gray-400">
                                    Choose how this exception affects
                                    the regular schedule.
                                </p>
                            </div>

                            <div className="grid gap-3 md:grid-cols-3">
                                {types.map((type) => {
                                    const meta =
                                        exceptionTypeMeta(type)

                                    const selected =
                                        form.data.type === type

                                    return (
                                        <button
                                            key={type}
                                            type="button"
                                            onClick={() =>
                                                changeType(type)
                                            }
                                            className={`relative rounded-2xl border p-4 text-left transition ${
                                                selected
                                                    ? meta.activeClassName
                                                    : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
                                            }`}
                                        >
                                            <div className="flex items-start gap-3">
                                                <span
                                                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                                                        selected
                                                            ? 'bg-white/80'
                                                            : 'bg-gray-100 text-gray-500'
                                                    }`}
                                                >
                                                    {meta.icon}
                                                </span>

                                                <span className="min-w-0 flex-1">
                                                    <span className="block font-semibold text-gray-900">
                                                        {meta.label}
                                                    </span>

                                                    <span className="mt-1 block text-xs leading-5 text-gray-500">
                                                        {
                                                            meta.description
                                                        }
                                                    </span>
                                                </span>

                                                <span
                                                    className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border ${
                                                        selected
                                                            ? 'border-sky-600 bg-sky-600 text-white'
                                                            : 'border-gray-300 bg-white'
                                                    }`}
                                                >
                                                    {selected ? (
                                                        <Check className="h-3.5 w-3.5" />
                                                    ) : null}
                                                </span>
                                            </div>
                                        </button>
                                    )
                                })}
                            </div>

                            <ErrorMessage value={errors.type} />
                        </div>

                        {form.data.type !== 'day_off' ? (
                            <div className="overflow-hidden rounded-2xl border border-gray-200">
                                <div className="flex flex-col justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-4 sm:flex-row sm:items-center">
                                    <div>
                                        <h3 className="font-semibold text-gray-900">
                                            Exception intervals
                                        </h3>

                                        <p className="mt-1 text-xs text-gray-500">
                                            {form.data.type ===
                                            'custom_hours'
                                                ? 'These intervals replace the regular working hours for this date.'
                                                : 'These intervals are added to the regular working hours for this date.'}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={addInterval}
                                        className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                    >
                                        <Plus className="h-3.5 w-3.5" />
                                        Add interval
                                    </button>
                                </div>

                                <div className="space-y-3 p-4">
                                    <ErrorMessage
                                        value={errors.intervals}
                                    />

                                    {form.data.intervals.length > 0 ? (
                                        form.data.intervals.map(
                                            (interval, index) => (
                                                <ExceptionIntervalRow
                                                    key={index}
                                                    interval={
                                                        interval
                                                    }
                                                    index={index}
                                                    errors={errors}
                                                    onUpdate={
                                                        updateInterval
                                                    }
                                                    onRemove={
                                                        removeInterval
                                                    }
                                                />
                                            ),
                                        )
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={addInterval}
                                            className="flex w-full flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center transition hover:border-sky-200 hover:bg-sky-50/50"
                                        >
                                            <Clock3 className="h-8 w-8 text-gray-300" />

                                            <span className="mt-3 text-sm font-semibold text-gray-600">
                                                No intervals added
                                            </span>

                                            <span className="mt-1 text-xs text-gray-400">
                                                Add at least one
                                                interval to save this
                                                exception.
                                            </span>
                                        </button>
                                    )}
                                </div>
                            </div>
                        ) : (
                            <div className="flex items-start gap-3 rounded-2xl border border-rose-100 bg-rose-50/60 p-4">
                                <CalendarOff className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />

                                <div>
                                    <h3 className="text-sm font-semibold text-rose-900">
                                        Full day off
                                    </h3>

                                    <p className="mt-1 text-sm leading-6 text-rose-700">
                                        All regular working intervals
                                        will be cancelled for the
                                        selected date. No additional
                                        interval is required.
                                    </p>
                                </div>
                            </div>
                        )}

                        <div className="flex items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                            <Info className="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />

                            <p className="text-sm leading-6 text-sky-800">
                                Use <strong>Next day</strong> for an
                                interval that crosses midnight, such
                                as 22:00–06:00.
                            </p>
                        </div>

                        <div className="flex justify-end border-t border-gray-100 pt-5">
                            <button
                                type="submit"
                                disabled={
                                    form.processing || !canSubmit
                                }
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {form.processing ? (
                                    <>
                                        <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                        Saving...
                                    </>
                                ) : (
                                    <>
                                        <Check className="h-4 w-4" />
                                        Save exception
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                </form>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <SectionHeader
                        title="Exceptions"
                        description="Upcoming changes and the historical exceptions for this assignment."
                        icon={
                            <CalendarClock className="h-5 w-5 text-sky-600" />
                        }
                        badge={assignment.exceptions.length}
                    />

                    {assignment.exceptions.length === 0 ? (
                        <div className="px-6 py-16 text-center">
                            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-gray-100">
                                <CalendarClock className="h-8 w-8 text-gray-400" />
                            </div>

                            <h3 className="mt-5 font-semibold text-gray-900">
                                No exceptions yet
                            </h3>

                            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                                This assignment currently follows its
                                regular weekly schedule without
                                individual changes.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-7 p-4 sm:p-6">
                            <ExceptionGroup
                                title="Upcoming exceptions"
                                description="Exceptions scheduled for today or a future date."
                                exceptions={
                                    groupedExceptions.upcoming
                                }
                                variant="upcoming"
                            />

                            <ExceptionGroup
                                title="Exception history"
                                description="Exceptions whose dates have already passed."
                                exceptions={
                                    groupedExceptions.past
                                }
                                variant="past"
                            />
                        </div>
                    )}
                </section>
            </div>
        </AdminLayout>
    )
}

function ExceptionIntervalRow({
                                  interval,
                                  index,
                                  errors,
                                  onUpdate,
                                  onRemove,
                              }: {
    interval: Interval
    index: number
    errors: Record<string, string | undefined>
    onUpdate: (
        index: number,
        value: Partial<Interval>,
    ) => void
    onRemove: (index: number) => void
}) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-gray-50/50 p-4">
            <div className="grid gap-4 lg:grid-cols-[minmax(150px,1fr)_minmax(150px,1fr)_auto_auto] lg:items-start">
                <Field
                    label={
                        index === 0
                            ? 'Starts at'
                            : `Interval ${index + 1} starts`
                    }
                    error={
                        errors[`intervals.${index}.starts_at`]
                    }
                >
                    <input
                        type="time"
                        value={interval.starts_at}
                        onChange={(event) =>
                            onUpdate(index, {
                                starts_at: event.target.value,
                            })
                        }
                        className={fieldClassName}
                    />
                </Field>

                <Field
                    label={
                        index === 0
                            ? 'Ends at'
                            : `Interval ${index + 1} ends`
                    }
                    error={
                        errors[`intervals.${index}.ends_at`]
                    }
                >
                    <input
                        type="time"
                        value={interval.ends_at}
                        onChange={(event) =>
                            onUpdate(index, {
                                ends_at: event.target.value,
                            })
                        }
                        className={fieldClassName}
                    />
                </Field>

                <div className="lg:pt-7">
                    <button
                        type="button"
                        onClick={() =>
                            onUpdate(index, {
                                ends_next_day:
                                    !interval.ends_next_day,
                            })
                        }
                        className={`flex h-11 min-w-36 items-center justify-between gap-3 rounded-xl border px-3 text-sm font-medium transition ${
                            interval.ends_next_day
                                ? 'border-violet-200 bg-violet-50 text-violet-700'
                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                        }`}
                    >
                        <span>Next day</span>

                        <Toggle
                            enabled={interval.ends_next_day}
                        />
                    </button>

                    <ErrorMessage
                        value={
                            errors[
                                `intervals.${index}.ends_next_day`
                                ]
                        }
                    />
                </div>

                <div className="lg:pt-7">
                    <button
                        type="button"
                        onClick={() => onRemove(index)}
                        title="Remove interval"
                        aria-label={`Remove interval ${index + 1}`}
                        className="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                    >
                        <Trash2 className="h-4 w-4" />
                    </button>
                </div>
            </div>

            <ErrorMessage
                value={errors[`intervals.${index}`]}
            />
        </div>
    )
}

function ExceptionGroup({
                            title,
                            description,
                            exceptions,
                            variant,
                        }: {
    title: string
    description: string
    exceptions: ScheduleException[]
    variant: 'upcoming' | 'past'
}) {
    return (
        <div>
            <div className="mb-3 flex items-start gap-3">
                <div
                    className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${
                        variant === 'upcoming'
                            ? 'bg-sky-100 text-sky-700'
                            : 'bg-gray-100 text-gray-500'
                    }`}
                >
                    {variant === 'upcoming' ? (
                        <CalendarDays className="h-4 w-4" />
                    ) : (
                        <CalendarClock className="h-4 w-4" />
                    )}
                </div>

                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-semibold text-gray-900">
                            {title}
                        </h3>

                        <span className="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                            {exceptions.length}
                        </span>
                    </div>

                    <p className="mt-1 text-sm text-gray-500">
                        {description}
                    </p>
                </div>
            </div>

            {exceptions.length > 0 ? (
                <div className="grid gap-3 xl:grid-cols-2">
                    {exceptions.map((exception) => (
                        <ExceptionCard
                            key={exception.id}
                            exception={exception}
                            past={variant === 'past'}
                        />
                    ))}
                </div>
            ) : (
                <div className="rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-8 text-center text-sm text-gray-400">
                    No {variant} exceptions.
                </div>
            )}
        </div>
    )
}

function ExceptionCard({
                           exception,
                           past,
                       }: {
    exception: ScheduleException
    past: boolean
}) {
    const meta = exceptionTypeMeta(exception.type)

    const deleteException = () => {
        const confirmed = window.confirm(
            `Delete the ${meta.label.toLowerCase()} exception for ${formatDate(
                exception.date,
            )}?`,
        )

        if (!confirmed) {
            return
        }

        router.delete(
            route(
                'admin.work-schedule-exceptions.destroy',
                exception.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <article
            className={`rounded-2xl border p-4 transition ${
                past
                    ? 'border-gray-200 bg-gray-50/60'
                    : 'border-gray-200 bg-white hover:border-sky-200 hover:shadow-sm'
            }`}
        >
            <div className="flex items-start gap-3">
                <span
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${meta.badgeClassName}`}
                >
                    {meta.icon}
                </span>

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h4 className="font-semibold text-gray-900">
                            {formatDate(exception.date)}
                        </h4>

                        <span
                            className={`rounded-full px-2.5 py-1 text-[11px] font-semibold ${meta.badgeClassName}`}
                        >
                            {meta.label}
                        </span>
                    </div>

                    <p className="mt-2 text-sm leading-6 text-gray-500">
                        {exception.reason || 'No reason provided.'}
                    </p>
                </div>

                <button
                    type="button"
                    onClick={deleteException}
                    title="Delete exception"
                    aria-label={`Delete exception for ${exception.date}`}
                    className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                >
                    <Trash2 className="h-4 w-4" />
                </button>
            </div>

            {exception.intervals.length > 0 ? (
                <div className="mt-4 space-y-2 border-t border-gray-100 pt-4">
                    {exception.intervals.map(
                        (interval, index) => (
                            <div
                                key={`${interval.starts_at}-${interval.ends_at}-${index}`}
                                className="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white px-3 py-2.5 ring-1 ring-inset ring-gray-100"
                            >
                                <span className="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <Clock3 className="h-4 w-4 text-gray-400" />

                                    {formatTime(
                                        interval.starts_at,
                                    )}
                                    {' — '}
                                    {formatTime(
                                        interval.ends_at,
                                    )}
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
            ) : null}
        </article>
    )
}

function AssignmentMetric({
                              icon,
                              label,
                              value,
                          }: {
    icon: ReactNode
    label: string
    value: string
}) {
    return (
        <div className="border-b border-gray-200 px-6 py-4 last:border-b-0 sm:border-b-0 sm:border-r sm:last:border-r-0">
            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                {icon}
                {label}
            </div>

            <div className="mt-1 truncate text-sm font-semibold text-gray-800">
                {value}
            </div>
        </div>
    )
}

function SectionHeader({
                           title,
                           description,
                           icon,
                           badge,
                       }: {
    title: string
    description: string
    icon: ReactNode
    badge?: number
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

function Toggle({ enabled }: { enabled: boolean }) {
    return (
        <span
            aria-hidden="true"
            className={`relative inline-flex h-5 w-9 shrink-0 rounded-full transition ${
                enabled ? 'bg-violet-600' : 'bg-gray-300'
            }`}
        >
            <span
                className={`absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition ${
                    enabled
                        ? 'translate-x-4'
                        : 'translate-x-0'
                }`}
            />
        </span>
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

function exceptionTypeMeta(type: string): ExceptionTypeMeta {
    if (type === 'day_off') {
        return {
            label: 'Day off',
            description:
                'Cancel all regular working hours for the selected date.',
            icon: <CalendarOff className="h-5 w-5 text-rose-600" />,
            activeClassName:
                'border-rose-300 bg-rose-50 ring-1 ring-inset ring-rose-200',
            badgeClassName:
                'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200',
        }
    }

    if (type === 'custom_hours') {
        return {
            label: 'Custom hours',
            description:
                'Replace the regular schedule with different working hours.',
            icon: <Clock3 className="h-5 w-5 text-amber-600" />,
            activeClassName:
                'border-amber-300 bg-amber-50 ring-1 ring-inset ring-amber-200',
            badgeClassName:
                'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200',
        }
    }

    if (type === 'extra_shift') {
        return {
            label: 'Extra shift',
            description:
                'Add working hours without replacing the regular schedule.',
            icon: (
                <CalendarPlus className="h-5 w-5 text-emerald-600" />
            ),
            activeClassName:
                'border-emerald-300 bg-emerald-50 ring-1 ring-inset ring-emerald-200',
            badgeClassName:
                'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        }
    }

    return {
        label: formatType(type),
        description:
            'Apply this exception to the selected assignment date.',
        icon: <CalendarClock className="h-5 w-5 text-sky-600" />,
        activeClassName:
            'border-sky-300 bg-sky-50 ring-1 ring-inset ring-sky-200',
        badgeClassName:
            'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200',
    }
}

function formatType(type: string): string {
    const value = type.replace(/_/g, ' ')

    return value.charAt(0).toUpperCase() + value.slice(1)
}

function parseDate(value: string): Date {
    return new Date(`${value.slice(0, 10)}T00:00:00`)
}

function startOfToday(): Date {
    const today = new Date()

    today.setHours(0, 0, 0, 0)

    return today
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

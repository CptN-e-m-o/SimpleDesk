import {
    FormEvent,
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react'
import type { ReactNode } from 'react'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    CalendarClock,
    Check,
    ChevronDown,
    Clock3,
    Copy,
    Globe2,
    Info,
    Plus,
    RotateCcw,
    Search,
    Trash2,
    UserPlus,
    UsersRound,
    X,
} from 'lucide-react'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout'

export type Interval = {
    day_of_week: number
    starts_at: string
    ends_at: string
    ends_next_day: boolean
}

export type Schedule = {
    id: number
    name: string
    description: string | null
    timezone: string
    is_active: boolean
    deleted_at: string | null
    updated_at: string
    assigned_agents_count: number
    intervals: Interval[]
}

type AgentOption = {
    id: number
    name: string
    email: string
}

type Props = {
    schedule?: Schedule
    timezones: string[]
    defaultTimezone?: string
    agents?: AgentOption[]
}

type WorkScheduleFormData = {
    name: string
    description: string
    timezone: string
    is_active: boolean
    intervals: Interval[]
    agent_ids: number[]
    effective_from: string
    effective_until: string
}

const days = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
]

const shortDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

const createDefaultIntervals = (): Interval[] =>
    [1, 2, 3, 4, 5].map((day) => ({
        day_of_week: day,
        starts_at: '09:00',
        ends_at: '18:00',
        ends_next_day: false,
    }))

const fieldClassName =
    'h-11 w-full rounded-xl border border-gray-200 bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'

export function WorkScheduleForm({
                                     schedule,
                                     timezones,
                                     defaultTimezone = 'UTC',
                                     agents = [],
                                 }: Props) {
    const editing = schedule !== undefined

    const form = useForm<WorkScheduleFormData>({
        name: schedule?.name ?? '',
        description: schedule?.description ?? '',
        timezone: schedule?.timezone ?? defaultTimezone,
        is_active: schedule?.is_active ?? true,
        intervals: schedule
            ? normalizeIntervals(schedule.intervals)
            : createDefaultIntervals(),
        agent_ids: [],
        effective_from: '',
        effective_until: '',
    })

    const errors = form.errors as Record<string, string | undefined>

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        form.transform((data) => ({
            ...data,
            intervals: sortIntervals(data.intervals),
        }))

        if (schedule) {
            form.put(route('admin.work-schedules.update', schedule.id), {
                preserveScroll: true,
            })

            return
        }

        form.post(route('admin.work-schedules.store'), {
            preserveScroll: true,
        })
    }

    const dayRows = (day: number) =>
        form.data.intervals
            .map((item, index) => ({
                item,
                index,
            }))
            .filter(({ item }) => item.day_of_week === day)
            .sort((first, second) =>
                first.item.starts_at.localeCompare(second.item.starts_at),
            )

    const addInterval = (day: number) => {
        form.setData('intervals', [
            ...form.data.intervals,
            {
                day_of_week: day,
                starts_at: '09:00',
                ends_at: '18:00',
                ends_next_day: false,
            },
        ])
    }

    const updateInterval = (
        index: number,
        value: Partial<Interval>,
    ) => {
        form.setData(
            'intervals',
            form.data.intervals.map((interval, currentIndex) =>
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

    const setDayEnabled = (day: number, enabled: boolean) => {
        if (enabled) {
            if (dayRows(day).length === 0) {
                addInterval(day)
            }

            return
        }

        form.setData(
            'intervals',
            form.data.intervals.filter(
                (interval) => interval.day_of_week !== day,
            ),
        )
    }

    const copyDayToTargets = (
        sourceDay: number,
        targetDays: number[],
    ) => {
        const sourceIntervals = form.data.intervals.filter(
            (interval) => interval.day_of_week === sourceDay,
        )

        if (sourceIntervals.length === 0) {
            return
        }

        const intervalsWithoutTargets = form.data.intervals.filter(
            (interval) => !targetDays.includes(interval.day_of_week),
        )

        const copiedIntervals = targetDays.flatMap((targetDay) =>
            sourceIntervals.map((interval) => ({
                ...interval,
                day_of_week: targetDay,
            })),
        )

        form.setData(
            'intervals',
            sortIntervals([
                ...intervalsWithoutTargets,
                ...copiedIntervals,
            ]),
        )
    }

    const restoreDefaultWeek = () => {
        form.setData('intervals', createDefaultIntervals())
    }

    return (
        <AdminLayout
            title={
                editing
                    ? 'Edit Work Schedule'
                    : 'Create Work Schedule'
            }
        >
            <Head
                title={
                    editing
                        ? 'Edit Work Schedule'
                        : 'Create Work Schedule'
                }
            />

            <form
                onSubmit={submit}
                className="mx-auto max-w-6xl space-y-6 p-4 sm:p-6"
            >
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="bg-gradient-to-r from-sky-50/80 via-white to-white p-6">
                        <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                            <div className="flex items-start gap-4">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                    <CalendarClock className="h-6 w-6 text-sky-700" />
                                </div>

                                <div>
                                    <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                        {editing
                                            ? 'Edit Work Schedule'
                                            : 'Create Work Schedule'}
                                    </h1>

                                    <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                        Configure reusable weekly working
                                        hours in a single IANA timezone.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route(
                                    'admin.work-schedules.index',
                                )}
                                className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Back to schedules
                            </Link>
                        </div>
                    </div>
                </header>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <SectionHeader
                        title="Schedule details"
                        description="Basic information and the timezone used to interpret all working hours."
                        icon={
                            <CalendarClock className="h-5 w-5 text-sky-600" />
                        }
                    />

                    <div className="grid gap-5 p-6 md:grid-cols-2">
                        <Field
                            label="Name"
                            required
                            error={errors.name}
                        >
                            <input
                                type="text"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                                placeholder="Standard Support"
                                autoFocus
                                className={fieldClassName}
                            />
                        </Field>

                        <div>
    <span className="mb-2 block text-sm font-semibold text-gray-700">
        Timezone
        <span className="ml-1 text-rose-500">*</span>
    </span>

                            <TimezoneCombobox
                                value={form.data.timezone}
                                options={timezones}
                                onChange={(timezone) =>
                                    form.setData('timezone', timezone)
                                }
                            />

                            {!errors.timezone ? (
                                <span className="mt-1.5 block text-xs leading-5 text-gray-400">
            All intervals are interpreted in this timezone.
        </span>
                            ) : null}

                            <ErrorMessage value={errors.timezone} />
                        </div>

                        <Field
                            label="Description"
                            error={errors.description}
                            className="md:col-span-2"
                        >
                            <textarea
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                placeholder="Describe when and for whom this schedule should be used."
                                rows={4}
                                className="w-full resize-y rounded-xl border border-gray-200 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                            />
                        </Field>

                        <div className="md:col-span-2">
                            <button
                                type="button"
                                onClick={() =>
                                    form.setData(
                                        'is_active',
                                        !form.data.is_active,
                                    )
                                }
                                className={`flex w-full items-center justify-between gap-4 rounded-2xl border p-4 text-left transition ${
                                    form.data.is_active
                                        ? 'border-emerald-200 bg-emerald-50/60'
                                        : 'border-gray-200 bg-gray-50'
                                }`}
                            >
                                <div>
                                    <div className="font-semibold text-gray-900">
                                        Active schedule
                                    </div>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Active schedules can be assigned to
                                        agents.
                                    </p>
                                </div>

                                <Toggle enabled={form.data.is_active} />
                            </button>

                            <ErrorMessage value={errors.is_active} />
                        </div>
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
                        <div className="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
                            <div className="flex items-start gap-3">
                                <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                                    <Clock3 className="h-5 w-5 text-sky-600" />
                                </div>

                                <div>
                                    <h2 className="font-semibold text-gray-900">
                                        Weekly hours
                                    </h2>

                                    <p className="mt-1 text-sm leading-6 text-gray-500">
                                        Enable working days and add one or
                                        more intervals for each day.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    onClick={() =>
                                        copyDayToTargets(
                                            1,
                                            [1, 2, 3, 4, 5],
                                        )
                                    }
                                    disabled={dayRows(1).length === 0}
                                    className="inline-flex h-9 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <Copy className="h-3.5 w-3.5" />
                                    Copy Monday to weekdays
                                </button>

                                <button
                                    type="button"
                                    onClick={() =>
                                        copyDayToTargets(
                                            1,
                                            [1, 2, 3, 4, 5, 6, 7],
                                        )
                                    }
                                    disabled={dayRows(1).length === 0}
                                    className="inline-flex h-9 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <Copy className="h-3.5 w-3.5" />
                                    Copy Monday to all
                                </button>

                                <button
                                    type="button"
                                    onClick={restoreDefaultWeek}
                                    className="inline-flex h-9 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-gray-300 hover:bg-gray-100"
                                >
                                    <RotateCcw className="h-3.5 w-3.5" />
                                    Restore default
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4 p-4 sm:p-6">
                        <ErrorMessage value={errors.intervals} />

                        {days.map((dayLabel, dayIndex) => {
                            const day = dayIndex + 1
                            const rows = dayRows(day)
                            const enabled = rows.length > 0

                            return (
                                <DayCard
                                    key={day}
                                    day={day}
                                    label={dayLabel}
                                    shortLabel={shortDays[dayIndex]}
                                    enabled={enabled}
                                    rows={rows}
                                    errors={errors}
                                    onToggle={(value) =>
                                        setDayEnabled(day, value)
                                    }
                                    onAdd={() => addInterval(day)}
                                    onUpdate={updateInterval}
                                    onRemove={removeInterval}
                                />
                            )
                        })}

                        <div className="flex items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                            <Info className="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />

                            <p className="text-sm leading-6 text-sky-800">
                                Use <strong>Next day</strong> for overnight
                                shifts such as 22:00–06:00. Adjacent
                                intervals are allowed, but overlapping
                                intervals will be rejected.
                            </p>
                        </div>
                    </div>
                </section>

                {!editing && agents.length > 0 ? (
                    <InitialAssignments
                        agents={agents}
                        selectedAgentIds={form.data.agent_ids}
                        effectiveFrom={form.data.effective_from}
                        effectiveUntil={form.data.effective_until}
                        errors={errors}
                        onAgentsChange={(agentIds) =>
                            form.setData('agent_ids', agentIds)
                        }
                        onEffectiveFromChange={(value) =>
                            form.setData('effective_from', value)
                        }
                        onEffectiveUntilChange={(value) =>
                            form.setData('effective_until', value)
                        }
                    />
                ) : null}

                <div className="sticky bottom-4 z-10 flex flex-col-reverse justify-end gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row">
                    <Link
                        href={route('admin.work-schedules.index')}
                        className="inline-flex h-11 items-center justify-center rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {form.processing ? (
                            <>
                                <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                Saving...
                            </>
                        ) : (
                            <>
                                <Check className="h-4 w-4" />
                                {editing
                                    ? 'Save changes'
                                    : 'Create work schedule'}
                            </>
                        )}
                    </button>
                </div>
            </form>
        </AdminLayout>
    )
}

function DayCard({
                     day,
                     label,
                     shortLabel,
                     enabled,
                     rows,
                     errors,
                     onToggle,
                     onAdd,
                     onUpdate,
                     onRemove,
                 }: {
    day: number
    label: string
    shortLabel: string
    enabled: boolean
    rows: {
        item: Interval
        index: number
    }[]
    errors: Record<string, string | undefined>
    onToggle: (enabled: boolean) => void
    onAdd: () => void
    onUpdate: (index: number, value: Partial<Interval>) => void
    onRemove: (index: number) => void
}) {
    return (
        <article
            className={`overflow-hidden rounded-2xl border transition ${
                enabled
                    ? 'border-gray-200 bg-white'
                    : 'border-gray-200 bg-gray-50/70'
            }`}
        >
            <div className="flex flex-col justify-between gap-3 border-b border-gray-100 px-4 py-4 sm:flex-row sm:items-center">
                <button
                    type="button"
                    onClick={() => onToggle(!enabled)}
                    className="flex items-center gap-3 text-left"
                >
                    <div
                        className={`flex h-10 w-10 items-center justify-center rounded-xl text-xs font-bold ${
                            enabled
                                ? 'bg-sky-100 text-sky-700'
                                : 'bg-gray-200 text-gray-500'
                        }`}
                    >
                        {shortLabel}
                    </div>

                    <div>
                        <div className="font-semibold text-gray-900">
                            {label}
                        </div>

                        <div className="mt-0.5 text-xs text-gray-500">
                            {enabled
                                ? `${rows.length} ${
                                    rows.length === 1
                                        ? 'interval'
                                        : 'intervals'
                                }`
                                : 'Day off'}
                        </div>
                    </div>
                </button>

                <div className="flex items-center gap-3">
                    {enabled ? (
                        <button
                            type="button"
                            onClick={onAdd}
                            className="inline-flex h-9 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                        >
                            <Plus className="h-3.5 w-3.5" />
                            Add interval
                        </button>
                    ) : null}

                    <button
                        type="button"
                        onClick={() => onToggle(!enabled)}
                        aria-label={
                            enabled
                                ? `Disable ${label}`
                                : `Enable ${label}`
                        }
                    >
                        <Toggle enabled={enabled} />
                    </button>
                </div>
            </div>

            {enabled ? (
                <div className="space-y-3 p-4">
                    {rows.map(({ item, index }, rowIndex) => (
                        <div
                            key={`${day}-${index}`}
                            className="rounded-2xl border border-gray-200 bg-gray-50/50 p-4"
                        >
                            <div className="grid gap-4 lg:grid-cols-[minmax(150px,1fr)_minmax(150px,1fr)_auto_auto] lg:items-start">
                                <Field
                                    label={
                                        rowIndex === 0
                                            ? 'Starts at'
                                            : `Interval ${rowIndex + 1} starts`
                                    }
                                    error={
                                        errors[
                                            `intervals.${index}.starts_at`
                                            ]
                                    }
                                >
                                    <input
                                        type="time"
                                        value={item.starts_at}
                                        onChange={(event) =>
                                            onUpdate(index, {
                                                starts_at:
                                                event.target.value,
                                            })
                                        }
                                        className={fieldClassName}
                                    />
                                </Field>

                                <Field
                                    label={
                                        rowIndex === 0
                                            ? 'Ends at'
                                            : `Interval ${rowIndex + 1} ends`
                                    }
                                    error={
                                        errors[
                                            `intervals.${index}.ends_at`
                                            ]
                                    }
                                >
                                    <input
                                        type="time"
                                        value={item.ends_at}
                                        onChange={(event) =>
                                            onUpdate(index, {
                                                ends_at:
                                                event.target.value,
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
                                                    !item.ends_next_day,
                                            })
                                        }
                                        className={`flex h-11 min-w-36 items-center justify-between gap-3 rounded-xl border px-3 text-sm font-medium transition ${
                                            item.ends_next_day
                                                ? 'border-violet-200 bg-violet-50 text-violet-700'
                                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                                        }`}
                                    >
                                        <span>Next day</span>

                                        <Toggle
                                            enabled={
                                                item.ends_next_day
                                            }
                                            compact
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
                                        aria-label={`Remove interval ${rowIndex + 1} from ${label}`}
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
                    ))}
                </div>
            ) : (
                <button
                    type="button"
                    onClick={() => onToggle(true)}
                    className="flex w-full items-center justify-center gap-2 px-4 py-8 text-sm font-medium text-gray-400 transition hover:bg-white hover:text-sky-600"
                >
                    <Plus className="h-4 w-4" />
                    Add working hours for {label}
                </button>
            )}
        </article>
    )
}

function TimezoneCombobox({
                              value,
                              options,
                              onChange,
                          }: {
    value: string
    options: string[]
    onChange: (timezone: string) => void
}) {
    const [open, setOpen] = useState(false)
    const [query, setQuery] = useState('')

    const rootRef = useRef<HTMLDivElement>(null)
    const searchInputRef = useRef<HTMLInputElement>(null)

    const normalizedOptions = useMemo(() => {
        const uniqueOptions = Array.from(new Set(options))

        if (
            value !== '' &&
            !uniqueOptions.includes(value)
        ) {
            uniqueOptions.unshift(value)
        }

        return uniqueOptions
    }, [options, value])

    const filteredOptions = useMemo(() => {
        const normalizedQuery = query
            .trim()
            .toLowerCase()

        if (normalizedQuery === '') {
            return normalizedOptions
        }

        return normalizedOptions.filter((timezone) =>
            timezone
                .toLowerCase()
                .includes(normalizedQuery),
        )
    }, [normalizedOptions, query])

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
                setQuery('')
            }
        }

        const closeOnEscape = (
            event: KeyboardEvent,
        ) => {
            if (event.key !== 'Escape') {
                return
            }

            setOpen(false)
            setQuery('')
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

    useEffect(() => {
        if (!open) {
            return
        }

        searchInputRef.current?.focus()
    }, [open])

    const selectTimezone = (timezone: string) => {
        onChange(timezone)
        setOpen(false)
        setQuery('')
    }

    return (
        <div
            ref={rootRef}
            className="relative"
        >
            <button
                type="button"
                role="combobox"
                aria-expanded={open}
                aria-controls="work-schedule-timezone-options"
                onClick={() => {
                    setOpen((current) => !current)
                    setQuery('')
                }}
                className={`flex h-11 w-full items-center rounded-xl border bg-white px-3.5 text-left text-sm outline-none transition ${
                    open
                        ? 'border-sky-400 ring-4 ring-sky-100'
                        : 'border-gray-200 hover:border-gray-300'
                }`}
            >
                <Globe2 className="mr-3 h-4 w-4 shrink-0 text-gray-400" />

                <span
                    className={`min-w-0 flex-1 truncate ${
                        value
                            ? 'text-gray-900'
                            : 'text-gray-400'
                    }`}
                >
                    {value || 'Select timezone'}
                </span>

                <ChevronDown
                    className={`ml-3 h-4 w-4 shrink-0 text-gray-400 transition ${
                        open ? 'rotate-180' : ''
                    }`}
                />
            </button>

            {open ? (
                <div className="absolute left-0 right-0 z-40 mt-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
                    <div className="border-b border-gray-100 p-3">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                            <input
                                ref={searchInputRef}
                                type="search"
                                value={query}
                                onChange={(event) =>
                                    setQuery(
                                        event.target.value,
                                    )
                                }
                                placeholder="Search timezone..."
                                className={`${fieldClassName} pl-10 pr-9`}
                            />

                            {query !== '' ? (
                                <button
                                    type="button"
                                    onClick={() =>
                                        setQuery('')
                                    }
                                    aria-label="Clear timezone search"
                                    className="absolute right-3 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                >
                                    <X className="h-3.5 w-3.5" />
                                </button>
                            ) : null}
                        </div>
                    </div>

                    <div
                        id="work-schedule-timezone-options"
                        role="listbox"
                        className="max-h-72 overflow-y-auto p-2"
                    >
                        {filteredOptions.length > 0 ? (
                            filteredOptions.map(
                                (timezone) => {
                                    const selected =
                                        timezone === value

                                    return (
                                        <button
                                            key={timezone}
                                            type="button"
                                            role="option"
                                            aria-selected={
                                                selected
                                            }
                                            onClick={() =>
                                                selectTimezone(
                                                    timezone,
                                                )
                                            }
                                            className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition ${
                                                selected
                                                    ? 'bg-sky-50 font-semibold text-sky-700'
                                                    : 'text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            <Globe2
                                                className={`h-4 w-4 shrink-0 ${
                                                    selected
                                                        ? 'text-sky-500'
                                                        : 'text-gray-400'
                                                }`}
                                            />

                                            <span className="min-w-0 flex-1 truncate">
                                                {timezone}
                                            </span>

                                            {selected ? (
                                                <Check className="h-4 w-4 shrink-0 text-sky-600" />
                                            ) : null}
                                        </button>
                                    )
                                },
                            )
                        ) : (
                            <div className="px-4 py-10 text-center">
                                <Globe2 className="mx-auto h-8 w-8 text-gray-300" />

                                <p className="mt-3 text-sm font-medium text-gray-600">
                                    No timezones found
                                </p>

                                <p className="mt-1 text-xs text-gray-400">
                                    Try another city or region.
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="border-t border-gray-100 bg-gray-50 px-4 py-2.5 text-xs text-gray-400">
                        {filteredOptions.length}{' '}
                        {filteredOptions.length === 1
                            ? 'timezone'
                            : 'timezones'}
                    </div>
                </div>
            ) : null}
        </div>
    )
}

function InitialAssignments({
                                agents,
                                selectedAgentIds,
                                effectiveFrom,
                                effectiveUntil,
                                errors,
                                onAgentsChange,
                                onEffectiveFromChange,
                                onEffectiveUntilChange,
                            }: {
    agents: AgentOption[]
    selectedAgentIds: number[]
    effectiveFrom: string
    effectiveUntil: string
    errors: Record<string, string | undefined>
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

        return agents.filter(
            (agent) =>
                agent.name.toLowerCase().includes(query) ||
                agent.email.toLowerCase().includes(query),
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
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <SectionHeader
                title="Initial assignments"
                description="Optionally assign the new schedule to one or more agents immediately."
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
                                Agents
                            </h3>

                            <p className="mt-1 text-xs text-gray-500">
                                Select every agent who should receive this
                                schedule.
                            </p>
                        </div>

                        {selectedAgentIds.length > 0 ? (
                            <button
                                type="button"
                                onClick={() => onAgentsChange([])}
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
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Search agents by name or email..."
                                    className={`${fieldClassName} pl-10`}
                                />
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
                                                toggleAgent(agent.id)
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
                                                {initials(agent.name)}
                                            </span>

                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-sm font-semibold text-gray-900">
                                                    {agent.name}
                                                </span>

                                                <span className="mt-0.5 block truncate text-xs text-gray-500">
                                                    {agent.email}
                                                </span>
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
                                        No agents match your search.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    <ErrorMessage value={errors.agent_ids} />
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
                                No agents selected. The schedule will be
                                created without assignments.
                            </p>
                        )}
                    </div>

                    <Field
                        label="Effective from"
                        error={errors.effective_from}
                        hint="Required when at least one agent is selected."
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
                </div>
            </div>
        </section>
    )
}

function SectionHeader({
                           title,
                           description,
                           icon,
                           optional = false,
                       }: {
    title: string
    description: string
    icon: ReactNode
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
                   className = '',
                   children,
               }: {
    label: string
    required?: boolean
    hint?: string
    error?: string
    className?: string
    children: ReactNode
}) {
    return (
        <label className={`block ${className}`}>
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

function Toggle({
                    enabled,
                    compact = false,
                }: {
    enabled: boolean
    compact?: boolean
}) {
    return (
        <span
            aria-hidden="true"
            className={`relative inline-flex shrink-0 rounded-full transition ${
                compact ? 'h-5 w-9' : 'h-6 w-11'
            } ${
                enabled
                    ? 'bg-sky-600'
                    : 'bg-gray-300'
            }`}
        >
            <span
                className={`absolute rounded-full bg-white shadow-sm transition ${
                    compact
                        ? 'left-0.5 top-0.5 h-4 w-4'
                        : 'left-0.5 top-0.5 h-5 w-5'
                } ${
                    enabled
                        ? compact
                            ? 'translate-x-4'
                            : 'translate-x-5'
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

function normalizeIntervals(intervals: Interval[]): Interval[] {
    return intervals.map((interval) => ({
        ...interval,
        day_of_week: Number(interval.day_of_week),
        starts_at: interval.starts_at.slice(0, 5),
        ends_at: interval.ends_at.slice(0, 5),
        ends_next_day: Boolean(interval.ends_next_day),
    }))
}

function sortIntervals(intervals: Interval[]): Interval[] {
    return [...intervals].sort((first, second) => {
        if (first.day_of_week !== second.day_of_week) {
            return first.day_of_week - second.day_of_week
        }

        return first.starts_at.localeCompare(second.starts_at)
    })
}

function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
}

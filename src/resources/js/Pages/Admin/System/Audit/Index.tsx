import AdminLayout from '@/Layouts/AdminLayout'
import {
    Head,
    Link,
    router,
} from '@inertiajs/react'
import {
    CalendarDays,
    Check,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    CircleUserRound,
    Clock3,
    Filter,
    ScrollText,
    ShieldCheck,
    SlidersHorizontal,
    X,
} from 'lucide-react'
import {
    useEffect,
    useRef,
    useState,
} from 'react'
import { route } from 'ziggy-js'

type Actor = {
    id: number
    email: string
    username?: string | null
    first_name?: string | null
    last_name?: string | null
}

type AuditLog = {
    id: number
    area: string
    action: string
    actor_id?: number | null
    actor?: Actor | null
    subject_type?: string | null
    subject_id?: number | null
    before_state?: Record<string, unknown> | null
    after_state?: Record<string, unknown> | null
    metadata?: Record<string, unknown> | null
    ip_address?: string | null
    user_agent?: string | null
    created_at: string
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type Pagination = {
    data: AuditLog[]
    links: PaginationLink[]
    total: number
    from?: number | null
    to?: number | null
    current_page?: number
    last_page?: number
}

type Filters = {
    area?: string
    action?: string
    actor_id?: number | string
    created_from?: string
    created_to?: string
}

type FilterOptions = {
    areas: string[]
    actions: string[]
    actors: Actor[]
}

type Props = {
    logs: Pagination
    filters?: Filters
    filterOptions: FilterOptions
}

type SelectOption = {
    value: string
    label: string
}

export default function Index({
                                  logs,
                                  filters = {},
                                  filterOptions,
                              }: Props) {
    const [expandedLogId, setExpandedLogId] =
        useState<number | null>(null)

    const activeFilters = {
        area: filters.area ?? '',
        action: filters.action ?? '',
        actor_id:
            filters.actor_id !== undefined
                ? String(filters.actor_id)
                : '',
        created_from: filters.created_from ?? '',
        created_to: filters.created_to ?? '',
    }

    const hasFilters =
        activeFilters.area !== '' ||
        activeFilters.action !== '' ||
        activeFilters.actor_id !== '' ||
        activeFilters.created_from !== '' ||
        activeFilters.created_to !== ''

    const navigate = (
        changes: Partial<typeof activeFilters>,
    ) => {
        const nextFilters = {
            ...activeFilters,
            ...changes,
        }

        router.get(
            route('admin.system.audit.index'),
            removeEmptyValues(nextFilters),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const resetFilters = () => {
        router.get(
            route('admin.system.audit.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const actorOptions: SelectOption[] = [
        {
            value: '',
            label: 'All actors',
        },
        ...filterOptions.actors.map((actor) => ({
            value: String(actor.id),
            label: actorName(actor),
        })),
    ]

    const areaOptions: SelectOption[] = [
        {
            value: '',
            label: 'All areas',
        },
        ...filterOptions.areas.map((area) => ({
            value: area,
            label: humanize(area),
        })),
    ]

    const actionOptions: SelectOption[] = [
        {
            value: '',
            label: 'All actions',
        },
        ...filterOptions.actions.map((action) => ({
            value: action,
            label: humanize(action),
        })),
    ]

    return (
        <AdminLayout title="System Audit">
            <Head title="System Audit" />

            <div className="space-y-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white p-6 sm:flex-row sm:items-center">
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <ScrollText className="h-6 w-6 text-sky-700" />
                            </div>

                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    System Audit
                                </h1>

                                <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                    Review the append-only history of
                                    security-sensitive system administration
                                    operations.
                                </p>
                            </div>
                        </div>

                        <div className="inline-flex h-10 shrink-0 items-center gap-2 self-start rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 shadow-sm sm:self-auto">
                            <ShieldCheck className="h-4 w-4 text-emerald-600" />
                            Append-only log
                        </div>
                    </div>

                    <div className="grid border-t border-gray-200 sm:grid-cols-3">
                        <Metric
                            label="Matching events"
                            value={logs.total}
                            icon={ScrollText}
                        />

                        <Metric
                            label="Visible on this page"
                            value={logs.data.length}
                            icon={SlidersHorizontal}
                        />

                        <Metric
                            label="Filter status"
                            value={hasFilters ? 'Filtered' : 'All events'}
                            icon={Filter}
                        />
                    </div>
                </header>

                <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-4 rounded-t-[28px] border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <div className="flex items-start gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                                <Filter className="h-5 w-5 text-sky-600" />
                            </div>

                            <div>
                                <h2 className="font-semibold text-gray-900">
                                    Filters
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Narrow the audit history by subsystem,
                                    operation, actor, or date range.
                                </p>
                            </div>
                        </div>

                        {hasFilters ? (
                            <button
                                type="button"
                                onClick={resetFilters}
                                className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                            >
                                <X className="h-4 w-4" />
                                Reset
                            </button>
                        ) : null}
                    </div>

                    <div className="grid gap-4 rounded-b-[28px] p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-5">
                        <FilterField label="Area">
                            <AuditSelect
                                value={activeFilters.area}
                                options={areaOptions}
                                onChange={(value) =>
                                    navigate({
                                        area: value,
                                    })
                                }
                            />
                        </FilterField>

                        <FilterField label="Action">
                            <AuditSelect
                                value={activeFilters.action}
                                options={actionOptions}
                                onChange={(value) =>
                                    navigate({
                                        action: value,
                                    })
                                }
                            />
                        </FilterField>

                        <FilterField label="Actor">
                            <AuditSelect
                                value={activeFilters.actor_id}
                                options={actorOptions}
                                onChange={(value) =>
                                    navigate({
                                        actor_id: value,
                                    })
                                }
                            />
                        </FilterField>

                        <FilterField label="From">
                            <div className="relative">
                                <CalendarDays className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                <input
                                    type="date"
                                    value={
                                        activeFilters.created_from
                                    }
                                    onChange={(event) =>
                                        navigate({
                                            created_from:
                                            event.target.value,
                                        })
                                    }
                                    className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-3 text-sm text-gray-900 outline-none transition hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                />
                            </div>
                        </FilterField>

                        <FilterField label="To">
                            <div className="relative">
                                <CalendarDays className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                <input
                                    type="date"
                                    value={
                                        activeFilters.created_to
                                    }
                                    min={
                                        activeFilters.created_from ||
                                        undefined
                                    }
                                    onChange={(event) =>
                                        navigate({
                                            created_to:
                                            event.target.value,
                                        })
                                    }
                                    className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-3 text-sm text-gray-900 outline-none transition hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                />
                            </div>
                        </FilterField>
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-2 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <div>
                            <h2 className="font-semibold text-gray-900">
                                Audit history
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                {logs.total === 1
                                    ? '1 event matches the current filters.'
                                    : `${logs.total} events match the current filters.`}
                            </p>
                        </div>

                        {logs.from && logs.to ? (
                            <span className="text-sm text-gray-400">
                                Showing {logs.from}–{logs.to}
                            </span>
                        ) : null}
                    </div>

                    {logs.data.length > 0 ? (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table className="w-full min-w-[1050px] table-fixed text-left text-sm">
                                    <colgroup>
                                        <col className="w-[15%]" />
                                        <col className="w-[18%]" />
                                        <col className="w-[13%]" />
                                        <col className="w-[15%]" />
                                        <col className="w-[22%]" />
                                        <col className="w-[17%]" />
                                    </colgroup>

                                    <thead>
                                    <tr className="border-b border-gray-200 bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <th className="px-5 py-4">
                                            Time
                                        </th>

                                        <th className="px-4 py-4">
                                            Actor
                                        </th>

                                        <th className="px-4 py-4">
                                            Area
                                        </th>

                                        <th className="px-4 py-4">
                                            Action
                                        </th>

                                        <th className="px-4 py-4">
                                            Subject
                                        </th>

                                        <th className="px-4 py-4 text-right">
                                            Details
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-100">
                                    {logs.data.map((log) => (
                                        <AuditRow
                                            key={log.id}
                                            log={log}
                                            expanded={
                                                expandedLogId ===
                                                log.id
                                            }
                                            onToggle={() =>
                                                setExpandedLogId(
                                                    expandedLogId ===
                                                    log.id
                                                        ? null
                                                        : log.id,
                                                )
                                            }
                                        />
                                    ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="grid gap-4 p-4 lg:hidden">
                                {logs.data.map((log) => (
                                    <AuditCard
                                        key={log.id}
                                        log={log}
                                        expanded={
                                            expandedLogId === log.id
                                        }
                                        onToggle={() =>
                                            setExpandedLogId(
                                                expandedLogId ===
                                                log.id
                                                    ? null
                                                    : log.id,
                                            )
                                        }
                                    />
                                ))}
                            </div>
                        </>
                    ) : (
                        <EmptyState
                            filtered={hasFilters}
                            onReset={resetFilters}
                        />
                    )}

                    {logs.links.length > 3 ? (
                        <Pagination links={logs.links} />
                    ) : null}
                </section>
            </div>
        </AdminLayout>
    )
}

function AuditRow({
                      log,
                      expanded,
                      onToggle,
                  }: {
    log: AuditLog
    expanded: boolean
    onToggle: () => void
}) {
    return (
        <>
            <tr className="transition hover:bg-gray-50/80">
                <td className="px-5 py-4 align-top">
                    <TimeCell value={log.created_at} />
                </td>

                <td className="px-4 py-4 align-top">
                    <ActorCell actor={log.actor} />
                </td>

                <td className="px-4 py-4 align-top">
                    <AreaBadge area={log.area} />
                </td>

                <td className="px-4 py-4 align-top">
                    <ActionBadge action={log.action} />
                </td>

                <td className="px-4 py-4 align-top">
                    <SubjectCell log={log} />
                </td>

                <td className="px-4 py-4 text-right align-top">
                    <button
                        type="button"
                        onClick={onToggle}
                        className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                    >
                        {expanded ? 'Hide' : 'View details'}

                        <ChevronDown
                            className={`h-4 w-4 transition ${
                                expanded
                                    ? 'rotate-180'
                                    : ''
                            }`}
                        />
                    </button>
                </td>
            </tr>

            {expanded ? (
                <tr className="bg-gray-50/60">
                    <td
                        colSpan={6}
                        className="px-5 py-5"
                    >
                        <AuditDetails log={log} />
                    </td>
                </tr>
            ) : null}
        </>
    )
}

function AuditCard({
                       log,
                       expanded,
                       onToggle,
                   }: {
    log: AuditLog
    expanded: boolean
    onToggle: () => void
}) {
    return (
        <article className="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div className="space-y-4 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="flex flex-wrap gap-2">
                        <AreaBadge area={log.area} />
                        <ActionBadge action={log.action} />
                    </div>

                    <TimeCell value={log.created_at} compact />
                </div>

                <ActorCell actor={log.actor} />

                <div>
                    <span className="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Subject
                    </span>

                    <SubjectCell log={log} />
                </div>

                <button
                    type="button"
                    onClick={onToggle}
                    className="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-gray-50 text-sm font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                >
                    {expanded
                        ? 'Hide details'
                        : 'View details'}

                    <ChevronDown
                        className={`h-4 w-4 transition ${
                            expanded ? 'rotate-180' : ''
                        }`}
                    />
                </button>
            </div>

            {expanded ? (
                <div className="border-t border-gray-200 bg-gray-50/70 p-4">
                    <AuditDetails log={log} />
                </div>
            ) : null}
        </article>
    )
}

function AuditDetails({
                          log,
                      }: {
    log: AuditLog
}) {
    const metadata =
        log.metadata &&
        Object.keys(log.metadata).length > 0
            ? log.metadata
            : null

    const beforeState =
        log.before_state &&
        Object.keys(log.before_state).length > 0
            ? log.before_state
            : null

    const afterState =
        log.after_state &&
        Object.keys(log.after_state).length > 0
            ? log.after_state
            : null

    return (
        <div className="grid gap-4 xl:grid-cols-3">
            <DetailBlock
                title="Metadata"
                value={metadata}
            />

            <DetailBlock
                title="Before"
                value={beforeState}
            />

            <DetailBlock
                title="After"
                value={afterState}
            />

            {log.ip_address || log.user_agent ? (
                <div className="rounded-2xl border border-gray-200 bg-white p-4 xl:col-span-3">
                    <h3 className="text-sm font-semibold text-gray-900">
                        Request context
                    </h3>

                    <dl className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                IP address
                            </dt>

                            <dd className="mt-1 break-all text-gray-700">
                                {log.ip_address ?? '—'}
                            </dd>
                        </div>

                        <div>
                            <dt className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                User agent
                            </dt>

                            <dd className="mt-1 break-words text-gray-700">
                                {log.user_agent ?? '—'}
                            </dd>
                        </div>
                    </dl>
                </div>
            ) : null}
        </div>
    )
}

function DetailBlock({
                         title,
                         value,
                     }: {
    title: string
    value: Record<string, unknown> | null
}) {
    return (
        <div className="min-w-0 rounded-2xl border border-gray-200 bg-white p-4">
            <h3 className="text-sm font-semibold text-gray-900">
                {title}
            </h3>

            {value ? (
                <dl className="mt-3 space-y-3">
                    {Object.entries(value).map(
                        ([key, item]) => (
                            <div key={key}>
                                <dt className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    {humanize(key)}
                                </dt>

                                <dd className="mt-1 break-words text-sm text-gray-700">
                                    {formatValue(item)}
                                </dd>
                            </div>
                        ),
                    )}
                </dl>
            ) : (
                <p className="mt-3 text-sm text-gray-400">
                    No data recorded.
                </p>
            )}
        </div>
    )
}

function Metric({
                    label,
                    value,
                    icon: Icon,
                }: {
    label: string
    value: number | string
    icon: typeof ScrollText
}) {
    return (
        <div className="flex items-center gap-3 border-gray-200 px-5 py-4 sm:border-r sm:last:border-r-0">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-100">
                <Icon className="h-4 w-4 text-gray-500" />
            </div>

            <div>
                <div className="text-lg font-semibold text-gray-900">
                    {value}
                </div>

                <div className="text-xs font-medium text-gray-500">
                    {label}
                </div>
            </div>
        </div>
    )
}

function FilterField({
                         label,
                         children,
                     }: {
    label: string
    children: React.ReactNode
}) {
    return (
        <div>
            <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                {label}
            </span>

            {children}
        </div>
    )
}

function AuditSelect({
                         value,
                         options,
                         onChange,
                     }: {
    value: string
    options: SelectOption[]
    onChange: (value: string) => void
}) {
    const [open, setOpen] = useState(false)
    const ref = useRef<HTMLDivElement>(null)

    const selected =
        options.find(
            (option) => option.value === value,
        ) ?? options[0]

    useEffect(() => {
        const close = (event: MouseEvent) => {
            if (
                !ref.current?.contains(
                    event.target as Node,
                )
            ) {
                setOpen(false)
            }
        }

        document.addEventListener(
            'mousedown',
            close,
        )

        return () => {
            document.removeEventListener(
                'mousedown',
                close,
            )
        }
    }, [])

    return (
        <div
            ref={ref}
            className="relative"
        >
            <button
                type="button"
                onClick={() =>
                    setOpen((current) => !current)
                }
                className="flex h-11 w-full items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-3.5 text-left text-sm text-gray-700 outline-none transition hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
            >
                <span className="truncate">
                    {selected?.label ?? 'Select'}
                </span>

                <ChevronDown
                    className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                        open ? 'rotate-180' : ''
                    }`}
                />
            </button>

            {open ? (
                <div className="absolute z-50 mt-2 max-h-64 w-full min-w-[180px] overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl">
                    {options.map((option) => {
                        const active =
                            option.value === value

                        return (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => {
                                    onChange(
                                        option.value,
                                    )
                                    setOpen(false)
                                }}
                                className={`flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition ${
                                    active
                                        ? 'bg-sky-50 font-semibold text-sky-700'
                                        : 'text-gray-700 hover:bg-gray-50'
                                }`}
                            >
                                <span className="truncate">
                                    {option.label}
                                </span>

                                {active ? (
                                    <Check className="h-4 w-4 shrink-0" />
                                ) : null}
                            </button>
                        )
                    })}
                </div>
            ) : null}
        </div>
    )
}

function ActorCell({
                       actor,
                   }: {
    actor?: Actor | null
}) {
    if (!actor) {
        return (
            <div className="flex items-center gap-2.5">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100">
                    <ShieldCheck className="h-4 w-4 text-gray-500" />
                </div>

                <div>
                    <div className="font-medium text-gray-700">
                        System
                    </div>

                    <div className="text-xs text-gray-400">
                        Automated operation
                    </div>
                </div>
            </div>
        )
    }

    return (
        <div className="flex min-w-0 items-center gap-2.5">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50">
                <CircleUserRound className="h-4 w-4 text-sky-600" />
            </div>

            <div className="min-w-0">
                <div className="truncate font-medium text-gray-800">
                    {actorName(actor)}
                </div>

                <div className="truncate text-xs text-gray-400">
                    {actor.email}
                </div>
            </div>
        </div>
    )
}

function SubjectCell({
                         log,
                     }: {
    log: AuditLog
}) {
    if (
        !log.subject_type &&
        !log.subject_id
    ) {
        return (
            <span className="text-gray-400">
                —
            </span>
        )
    }

    const type = log.subject_type
        ? log.subject_type
            .split('\\')
            .pop()
        : null

    return (
        <div>
            <div className="font-medium text-gray-800">
                {type
                    ? humanize(type)
                    : 'System object'}
            </div>

            {log.subject_id ? (
                <div className="mt-0.5 text-xs text-gray-400">
                    ID #{log.subject_id}
                </div>
            ) : null}
        </div>
    )
}

function TimeCell({
                      value,
                      compact = false,
                  }: {
    value: string
    compact?: boolean
}) {
    const date = new Date(value)

    if (compact) {
        return (
            <span className="whitespace-nowrap text-xs text-gray-400">
                {new Intl.DateTimeFormat(
                    undefined,
                    {
                        dateStyle: 'short',
                        timeStyle: 'short',
                    },
                ).format(date)}
            </span>
        )
    }

    return (
        <div className="flex items-start gap-2">
            <Clock3 className="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

            <div>
                <div className="whitespace-nowrap font-medium text-gray-700">
                    {new Intl.DateTimeFormat(
                        undefined,
                        {
                            dateStyle: 'medium',
                        },
                    ).format(date)}
                </div>

                <div className="mt-0.5 text-xs text-gray-400">
                    {new Intl.DateTimeFormat(
                        undefined,
                        {
                            timeStyle: 'medium',
                        },
                    ).format(date)}
                </div>
            </div>
        </div>
    )
}

function AreaBadge({
                       area,
                   }: {
    area: string
}) {
    return (
        <span className="inline-flex max-w-full rounded-lg bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-100">
            <span className="truncate">
                {humanize(area)}
            </span>
        </span>
    )
}

function ActionBadge({
                         action,
                     }: {
    action: string
}) {
    return (
        <span className="inline-flex max-w-full rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
            <span className="truncate">
                {humanize(action)}
            </span>
        </span>
    )
}

function EmptyState({
                        filtered,
                        onReset,
                    }: {
    filtered: boolean
    onReset: () => void
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                <ScrollText className="h-6 w-6 text-gray-400" />
            </div>

            <h3 className="mt-4 font-semibold text-gray-900">
                {filtered
                    ? 'No audit events found'
                    : 'No audit events yet'}
            </h3>

            <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
                {filtered
                    ? 'No system operations match the current filter combination.'
                    : 'Security-sensitive system administration operations will appear here.'}
            </p>

            {filtered ? (
                <button
                    type="button"
                    onClick={onReset}
                    className="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                >
                    <X className="h-4 w-4" />
                    Clear filters
                </button>
            ) : null}
        </div>
    )
}

function Pagination({
                        links,
                    }: {
    links: PaginationLink[]
}) {
    return (
        <div className="flex flex-wrap items-center justify-center gap-1.5 border-t border-gray-200 px-5 py-4">
            {links.map((link, index) => {
                const previous = index === 0
                const next =
                    index === links.length - 1

                const content = previous ? (
                    <>
                        <ChevronLeft className="h-4 w-4" />
                        <span className="hidden sm:inline">
                            Previous
                        </span>
                    </>
                ) : next ? (
                    <>
                        <span className="hidden sm:inline">
                            Next
                        </span>
                        <ChevronRight className="h-4 w-4" />
                    </>
                ) : (
                    cleanPaginationLabel(
                        link.label,
                    )
                )

                const className = `inline-flex h-9 min-w-9 items-center justify-center gap-1 rounded-lg px-3 text-sm font-medium transition ${
                    link.active
                        ? 'bg-sky-600 text-white shadow-sm'
                        : link.url
                            ? 'border border-gray-200 bg-white text-gray-600 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700'
                            : 'cursor-not-allowed border border-gray-100 bg-gray-50 text-gray-300'
                }`

                if (!link.url) {
                    return (
                        <span
                            key={`${link.label}-${index}`}
                            className={className}
                        >
                            {content}
                        </span>
                    )
                }

                return (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        className={className}
                    >
                        {content}
                    </Link>
                )
            })}
        </div>
    )
}

function actorName(
    actor: Actor,
): string {
    const fullName = [
        actor.first_name,
        actor.last_name,
    ]
        .filter(Boolean)
        .join(' ')
        .trim()

    if (fullName !== '') {
        return fullName
    }

    if (actor.username) {
        return actor.username
    }

    return actor.email
}

function humanize(
    value: string,
): string {
    return value
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/[._-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\b\w/g, (character) =>
            character.toUpperCase(),
        )
}

function formatValue(
    value: unknown,
): string {
    if (value === null) {
        return 'null'
    }

    if (value === undefined) {
        return '—'
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No'
    }

    if (
        typeof value === 'string' ||
        typeof value === 'number'
    ) {
        return String(value)
    }

    try {
        return JSON.stringify(
            value,
            null,
            2,
        )
    } catch {
        return String(value)
    }
}

function removeEmptyValues(
    values: Record<string, string>,
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(values).filter(
            ([, value]) => value !== '',
        ),
    )
}

function cleanPaginationLabel(
    value: string,
): string {
    return value
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .trim()
}

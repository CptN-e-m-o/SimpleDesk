import AdminLayout from '@/Layouts/AdminLayout'
import { usePermissions } from '@/hooks/usePermissions'
import {
    Head,
    Link,
    router,
} from '@inertiajs/react'
import {
    Activity,
    Archive,
    Cable,
    Check,
    CheckCircle2,
    ChevronDown,
    CircleOff,
    Clock3,
    Gauge,
    Pencil,
    Play,
    Plus,
    RefreshCw,
    RotateCcw,
    Search,
    Server,
    SlidersHorizontal,
    Trash2,
    X,
    XCircle,
} from 'lucide-react'
import {
    useEffect,
    useMemo,
    useRef,
    useState,
} from 'react'
import type { ReactNode } from 'react'
import type {
    LucideIcon,
} from 'lucide-react'
import { route } from 'ziggy-js'

type HealthStatus =
    | 'unknown'
    | 'healthy'
    | 'degraded'
    | 'unhealthy'
    | 'unavailable'
    | string

type LatestHealthCheck = {
    status: HealthStatus
    latency_ms?: number | null
    message?: string | null
    created_at: string
}

type Connection = {
    id: number
    name: string
    type: string
    source: string
    is_enabled: boolean
    deleted_at?: string | null
    latest_health_check?: LatestHealthCheck | null
}

type Props = {
    connections: Connection[]
}

type SelectOption = {
    value: string
    label: string
}

type TestResult = {
    connectionId: number
    status: string
    latencyMs?: number | null
    message?: string | null
    failed?: boolean
}

export default function Index({
                                  connections,
                              }: Props) {
    const { can } = usePermissions()

    const [search, setSearch] = useState('')
    const [source, setSource] = useState('')
    const [state, setState] = useState('')
    const [health, setHealth] = useState('')
    const [testing, setTesting] =
        useState<number | null>(null)

    const [testResult, setTestResult] =
        useState<TestResult | null>(null)

    const healthy = connections.filter(
        (connection) =>
            connection.latest_health_check?.status ===
            'healthy',
    ).length

    const problems = connections.filter(
        (connection) =>
            [
                'degraded',
                'unhealthy',
                'unavailable',
            ].includes(
                connection.latest_health_check?.status ??
                '',
            ),
    ).length

    const enabled = connections.filter(
        (connection) =>
            connection.is_enabled &&
            !connection.deleted_at,
    ).length

    const hasFilters =
        search.trim() !== '' ||
        source !== '' ||
        state !== '' ||
        health !== ''

    const filteredConnections = useMemo(() => {
        const normalizedSearch = search
            .trim()
            .toLowerCase()

        return connections.filter((connection) => {
            if (
                normalizedSearch !== '' &&
                ![
                    connection.name,
                    connection.type,
                    connection.source,
                ].some((value) =>
                    value
                        .toLowerCase()
                        .includes(normalizedSearch),
                )
            ) {
                return false
            }

            if (
                source !== '' &&
                connection.source !== source
            ) {
                return false
            }

            if (state === 'enabled') {
                if (
                    connection.deleted_at ||
                    !connection.is_enabled
                ) {
                    return false
                }
            }

            if (state === 'disabled') {
                if (
                    connection.deleted_at ||
                    connection.is_enabled
                ) {
                    return false
                }
            }

            if (
                state === 'archived' &&
                !connection.deleted_at
            ) {
                return false
            }

            if (health !== '') {
                const currentHealth =
                    connection.latest_health_check
                        ?.status ?? 'unknown'

                if (currentHealth !== health) {
                    return false
                }
            }

            return true
        })
    }, [
        connections,
        search,
        source,
        state,
        health,
    ])

    const resetFilters = () => {
        setSearch('')
        setSource('')
        setState('')
        setHealth('')
    }

    const testConnection = async (
        connection: Connection,
    ) => {
        if (testing !== null) {
            return
        }

        setTesting(connection.id)
        setTestResult(null)

        try {
            const token = (
                document.querySelector(
                    'meta[name="csrf-token"]',
                ) as HTMLMetaElement | null
            )?.content

            const response = await fetch(
                route(
                    'admin.system.connections.test',
                    connection.id,
                ),
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        ...(token
                            ? {
                                'X-CSRF-TOKEN':
                                token,
                            }
                            : {}),
                    },
                },
            )

            const result = await readJsonResponse(
                response,
            )

            if (!response.ok) {
                throw new Error(
                    getResponseMessage(result) ||
                    'The connection test failed.',
                )
            }

            setTestResult({
                connectionId: connection.id,
                status:
                    getStringValue(
                        result,
                        'status',
                    ) ?? 'unknown',
                latencyMs:
                    getNumberValue(
                        result,
                        'latency_ms',
                    ),
                message:
                    getStringValue(
                        result,
                        'message',
                    ),
            })

            router.reload()
        } catch (error) {
            setTestResult({
                connectionId: connection.id,
                status: 'failed',
                message:
                    error instanceof Error
                        ? error.message
                        : 'The connection test failed.',
                failed: true,
            })
        } finally {
            setTesting(null)
        }
    }

    const toggleConnection = (
        connection: Connection,
    ) => {
        router.patch(
            route(
                'admin.system.connections.toggle',
                connection.id,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const archiveConnection = (
        connection: Connection,
    ) => {
        if (
            !window.confirm(
                `Archive "${connection.name}"?`,
            )
        ) {
            return
        }

        router.delete(
            route(
                'admin.system.connections.destroy',
                connection.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    const restoreConnection = (
        connection: Connection,
    ) => {
        router.post(
            route(
                'admin.system.connections.restore',
                connection.id,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const deleteConnection = (
        connection: Connection,
    ) => {
        if (
            !window.confirm(
                `Permanently delete "${connection.name}"? This cannot be undone.`,
            )
        ) {
            return
        }

        router.delete(
            route(
                'admin.system.connections.force-delete',
                connection.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <AdminLayout title="Infrastructure Connections">
            <Head title="Infrastructure Connections" />

            <div className="space-y-6 p-4 sm:p-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white p-6 sm:flex-row sm:items-center">
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <Cable className="h-6 w-6 text-sky-700" />
                            </div>

                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    Infrastructure Connections
                                </h1>

                                <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                    Configure and monitor secure
                                    connections to infrastructure
                                    resources used by SimpleDesk
                                    subsystems.
                                </p>
                            </div>
                        </div>

                        {can(
                            'admin.settings.infrastructure_connections.create',
                        ) ? (
                            <Link
                                href={route(
                                    'admin.system.connections.create',
                                )}
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200"
                            >
                                <Plus className="h-4 w-4" />
                                New connection
                            </Link>
                        ) : null}
                    </div>

                    <div className="grid border-t border-gray-200 sm:grid-cols-2 xl:grid-cols-4">
                        <Metric
                            label="Total connections"
                            value={connections.length}
                            icon={Cable}
                        />

                        <Metric
                            label="Enabled"
                            value={enabled}
                            icon={CheckCircle2}
                        />

                        <Metric
                            label="Healthy"
                            value={healthy}
                            icon={Activity}
                        />

                        <Metric
                            label="Problems"
                            value={problems}
                            icon={XCircle}
                        />
                    </div>
                </header>

                {testResult ? (
                    <TestResultBanner
                        result={testResult}
                        connection={
                            connections.find(
                                (connection) =>
                                    connection.id ===
                                    testResult.connectionId,
                            ) ?? null
                        }
                        onClose={() =>
                            setTestResult(null)
                        }
                    />
                ) : null}

                <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-4 rounded-t-[28px] border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <div className="flex items-start gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                                <SlidersHorizontal className="h-5 w-5 text-sky-600" />
                            </div>

                            <div>
                                <h2 className="font-semibold text-gray-900">
                                    Filters
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Find connections by name,
                                    source, operational state,
                                    or latest health result.
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

                    <div className="grid gap-4 rounded-b-[28px] p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-[minmax(280px,1fr)_220px_220px_220px]">
                        <FilterField label="Search">
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
                                    placeholder="Search connections..."
                                    className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                />

                                {search !== '' ? (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setSearch('')
                                        }
                                        aria-label="Clear search"
                                        title="Clear search"
                                        className="absolute right-3 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                    </button>
                                ) : null}
                            </div>
                        </FilterField>

                        <FilterField label="Source">
                            <ConnectionSelect
                                value={source}
                                options={[
                                    {
                                        value: '',
                                        label: 'All sources',
                                    },
                                    {
                                        value: 'managed',
                                        label: 'Managed',
                                    },
                                    {
                                        value: 'deployment',
                                        label:
                                            'Deployment',
                                    },
                                ]}
                                onChange={setSource}
                            />
                        </FilterField>

                        <FilterField label="State">
                            <ConnectionSelect
                                value={state}
                                options={[
                                    {
                                        value: '',
                                        label: 'All states',
                                    },
                                    {
                                        value: 'enabled',
                                        label: 'Enabled',
                                    },
                                    {
                                        value: 'disabled',
                                        label: 'Disabled',
                                    },
                                    {
                                        value: 'archived',
                                        label: 'Archived',
                                    },
                                ]}
                                onChange={setState}
                            />
                        </FilterField>

                        <FilterField label="Health">
                            <ConnectionSelect
                                value={health}
                                options={[
                                    {
                                        value: '',
                                        label: 'All health',
                                    },
                                    {
                                        value: 'healthy',
                                        label: 'Healthy',
                                    },
                                    {
                                        value: 'degraded',
                                        label: 'Degraded',
                                    },
                                    {
                                        value: 'unhealthy',
                                        label:
                                            'Unhealthy',
                                    },
                                    {
                                        value: 'unavailable',
                                        label:
                                            'Unavailable',
                                    },
                                    {
                                        value: 'unknown',
                                        label: 'Unknown',
                                    },
                                ]}
                                onChange={setHealth}
                            />
                        </FilterField>
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-2 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <div>
                            <h2 className="font-semibold text-gray-900">
                                Connections
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                {filteredConnections.length ===
                                1
                                    ? '1 connection matches the current filters.'
                                    : `${filteredConnections.length} connections match the current filters.`}
                            </p>
                        </div>

                        {connections.length > 0 ? (
                            <span className="text-sm text-gray-400">
                                {filteredConnections.length} of{' '}
                                {connections.length} shown
                            </span>
                        ) : null}
                    </div>

                    {filteredConnections.length > 0 ? (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table className="w-full min-w-[1060px] table-fixed text-left text-sm">
                                    <colgroup>
                                        <col className="w-[23%]" />
                                        <col className="w-[13%]" />
                                        <col className="w-[12%]" />
                                        <col className="w-[19%]" />
                                        <col className="w-[17%]" />
                                        <col className="w-[16%]" />
                                    </colgroup>

                                    <thead>
                                    <tr className="border-b border-gray-200 bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <th className="px-5 py-4">
                                            Connection
                                        </th>

                                        <th className="px-4 py-4">
                                            Source
                                        </th>

                                        <th className="px-4 py-4">
                                            State
                                        </th>

                                        <th className="px-4 py-4">
                                            Health
                                        </th>

                                        <th className="px-4 py-4">
                                            Last checked
                                        </th>

                                        <th className="sticky right-0 z-20 border-l border-gray-200 bg-gray-50 px-4 py-4 text-right shadow-[-8px_0_18px_-16px_rgba(15,23,42,0.45)]">
                                            Actions
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-100">
                                    {filteredConnections.map(
                                        (
                                            connection,
                                        ) => (
                                            <ConnectionRow
                                                key={
                                                    connection.id
                                                }
                                                connection={
                                                    connection
                                                }
                                                testing={
                                                    testing ===
                                                    connection.id
                                                }
                                                can={can}
                                                onTest={() =>
                                                    testConnection(
                                                        connection,
                                                    )
                                                }
                                                onToggle={() =>
                                                    toggleConnection(
                                                        connection,
                                                    )
                                                }
                                                onArchive={() =>
                                                    archiveConnection(
                                                        connection,
                                                    )
                                                }
                                                onRestore={() =>
                                                    restoreConnection(
                                                        connection,
                                                    )
                                                }
                                                onDelete={() =>
                                                    deleteConnection(
                                                        connection,
                                                    )
                                                }
                                            />
                                        ),
                                    )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="grid gap-4 p-4 lg:hidden">
                                {filteredConnections.map(
                                    (connection) => (
                                        <ConnectionCard
                                            key={
                                                connection.id
                                            }
                                            connection={
                                                connection
                                            }
                                            testing={
                                                testing ===
                                                connection.id
                                            }
                                            can={can}
                                            onTest={() =>
                                                testConnection(
                                                    connection,
                                                )
                                            }
                                            onToggle={() =>
                                                toggleConnection(
                                                    connection,
                                                )
                                            }
                                            onArchive={() =>
                                                archiveConnection(
                                                    connection,
                                                )
                                            }
                                            onRestore={() =>
                                                restoreConnection(
                                                    connection,
                                                )
                                            }
                                            onDelete={() =>
                                                deleteConnection(
                                                    connection,
                                                )
                                            }
                                        />
                                    ),
                                )}
                            </div>
                        </>
                    ) : (
                        <EmptyState
                            filtered={hasFilters}
                            canCreate={can(
                                'admin.settings.infrastructure_connections.create',
                            )}
                            onReset={resetFilters}
                        />
                    )}
                </section>
            </div>
        </AdminLayout>
    )
}

function ConnectionRow({
                           connection,
                           testing,
                           can,
                           onTest,
                           onToggle,
                           onArchive,
                           onRestore,
                           onDelete,
                       }: {
    connection: Connection
    testing: boolean
    can: (permission: string) => boolean
    onTest: () => void
    onToggle: () => void
    onArchive: () => void
    onRestore: () => void
    onDelete: () => void
}) {
    const archived = Boolean(
        connection.deleted_at,
    )

    return (
        <tr
            className={`group transition ${
                archived
                    ? 'bg-gray-50/70'
                    : 'hover:bg-gray-50/80'
            }`}
        >
            <td className="px-5 py-4 align-top">
                <ConnectionIdentity
                    connection={connection}
                />
            </td>

            <td className="px-4 py-4 align-top">
                <SourceBadge
                    source={connection.source}
                />
            </td>

            <td className="px-4 py-4 align-top">
                <StateBadge
                    connection={connection}
                />
            </td>

            <td className="px-4 py-4 align-top">
                <HealthCell
                    health={
                        connection.latest_health_check
                    }
                />
            </td>

            <td className="px-4 py-4 align-top">
                <LastChecked
                    health={
                        connection.latest_health_check
                    }
                />
            </td>

            <td
                className={`sticky right-0 z-10 border-l border-gray-100 px-4 py-4 align-top shadow-[-8px_0_18px_-16px_rgba(15,23,42,0.45)] transition ${
                    archived
                        ? 'bg-gray-50'
                        : 'bg-white group-hover:bg-gray-50'
                }`}
            >
                <ConnectionActions
                    connection={connection}
                    testing={testing}
                    can={can}
                    onTest={onTest}
                    onToggle={onToggle}
                    onArchive={onArchive}
                    onRestore={onRestore}
                    onDelete={onDelete}
                />
            </td>
        </tr>
    )
}

function ConnectionCard({
                            connection,
                            testing,
                            can,
                            onTest,
                            onToggle,
                            onArchive,
                            onRestore,
                            onDelete,
                        }: {
    connection: Connection
    testing: boolean
    can: (permission: string) => boolean
    onTest: () => void
    onToggle: () => void
    onArchive: () => void
    onRestore: () => void
    onDelete: () => void
}) {
    return (
        <article className="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <div className="space-y-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <ConnectionIdentity
                        connection={connection}
                    />

                    <StateBadge
                        connection={connection}
                    />
                </div>

                <div className="grid grid-cols-2 gap-4 border-y border-gray-100 py-4">
                    <div>
                        <span className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Source
                        </span>

                        <SourceBadge
                            source={
                                connection.source
                            }
                        />
                    </div>

                    <div>
                        <span className="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Health
                        </span>

                        <HealthCell
                            health={
                                connection.latest_health_check
                            }
                        />
                    </div>
                </div>

                <div>
                    <span className="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Last checked
                    </span>

                    <LastChecked
                        health={
                            connection.latest_health_check
                        }
                    />
                </div>

                <ConnectionActions
                    connection={connection}
                    testing={testing}
                    can={can}
                    onTest={onTest}
                    onToggle={onToggle}
                    onArchive={onArchive}
                    onRestore={onRestore}
                    onDelete={onDelete}
                    mobile
                />
            </div>
        </article>
    )
}

function ConnectionIdentity({
                                connection,
                            }: {
    connection: Connection
}) {
    return (
        <div className="flex min-w-0 items-start gap-3">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                <Server className="h-5 w-5 text-sky-600" />
            </div>

            <div className="min-w-0">
                <div className="truncate font-semibold text-gray-900">
                    {connection.name}
                </div>

                <div className="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    {humanize(connection.type)}
                </div>
            </div>
        </div>
    )
}

function SourceBadge({
                         source,
                     }: {
    source: string
}) {
    const managed = source === 'managed'

    return (
        <span
            className={`inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                managed
                    ? 'bg-sky-50 text-sky-700 ring-sky-100'
                    : 'bg-violet-50 text-violet-700 ring-violet-100'
            }`}
        >
            {managed
                ? 'Managed'
                : 'Deployment'}
        </span>
    )
}

function StateBadge({
                        connection,
                    }: {
    connection: Connection
}) {
    if (connection.deleted_at) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                <Archive className="h-3.5 w-3.5" />
                Archived
            </span>
        )
    }

    if (connection.is_enabled) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100">
                <CheckCircle2 className="h-3.5 w-3.5" />
                Enabled
            </span>
        )
    }

    return (
        <span className="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-100">
            <CircleOff className="h-3.5 w-3.5" />
            Disabled
        </span>
    )
}

function HealthCell({
                        health,
                    }: {
    health?: LatestHealthCheck | null
}) {
    const status =
        health?.status ?? 'unknown'

    const config = healthAppearance(status)

    const Icon = config.icon

    return (
        <div className="min-w-0">
            <div
                className={`inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${config.className}`}
            >
                <Icon className="h-3.5 w-3.5" />

                {config.label}

                {health?.latency_ms != null ? (
                    <span className="font-medium opacity-70">
                        · {health.latency_ms} ms
                    </span>
                ) : null}
            </div>

            {health?.message ? (
                <p
                    title={health.message}
                    className="mt-1.5 max-w-[240px] truncate text-xs text-gray-400"
                >
                    {health.message}
                </p>
            ) : null}
        </div>
    )
}

function LastChecked({
                         health,
                     }: {
    health?: LatestHealthCheck | null
}) {
    if (!health) {
        return (
            <span className="text-sm text-gray-400">
                Never
            </span>
        )
    }

    const date = new Date(
        health.created_at,
    )

    return (
        <div className="flex items-start gap-2">
            <Clock3 className="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

            <div>
                <div className="whitespace-nowrap text-sm font-medium text-gray-700">
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
                            timeStyle: 'short',
                        },
                    ).format(date)}
                </div>
            </div>
        </div>
    )
}

function ConnectionActions({
                               connection,
                               testing,
                               can,
                               onTest,
                               onToggle,
                               onArchive,
                               onRestore,
                               onDelete,
                               mobile = false,
                           }: {
    connection: Connection
    testing: boolean
    can: (permission: string) => boolean
    onTest: () => void
    onToggle: () => void
    onArchive: () => void
    onRestore: () => void
    onDelete: () => void
    mobile?: boolean
}) {
    const archived = Boolean(
        connection.deleted_at,
    )

    if (archived) {
        return (
            <div
                className={`flex gap-2 ${
                    mobile
                        ? 'w-full'
                        : 'justify-end'
                }`}
            >
                {can(
                    'admin.settings.infrastructure_connections.archive',
                ) ? (
                    <ActionButton
                        icon={RotateCcw}
                        label="Restore"
                        onClick={onRestore}
                        grow={mobile}
                    />
                ) : null}

                {can(
                    'admin.settings.infrastructure_connections.delete',
                ) ? (
                    <ActionButton
                        icon={Trash2}
                        label="Delete"
                        onClick={onDelete}
                        danger
                        grow={mobile}
                    />
                ) : null}
            </div>
        )
    }

    return (
        <div
            className={`flex flex-wrap gap-2 ${
                mobile
                    ? 'w-full'
                    : 'justify-end'
            }`}
        >
            {can(
                'admin.settings.infrastructure_connections.test',
            ) ? (
                <ActionButton
                    icon={
                        testing
                            ? RefreshCw
                            : Play
                    }
                    label={
                        testing
                            ? 'Testing…'
                            : 'Test'
                    }
                    onClick={onTest}
                    disabled={testing}
                    spinning={testing}
                    grow={mobile}
                />
            ) : null}

            {can(
                'admin.settings.infrastructure_connections.update',
            ) ? (
                <>
                    <Link
                        href={route(
                            'admin.system.connections.edit',
                            connection.id,
                        )}
                        className={`inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 ${
                            mobile
                                ? 'flex-1'
                                : ''
                        }`}
                    >
                        <Pencil className="h-4 w-4" />
                        Edit
                    </Link>

                    <ActionButton
                        icon={
                            connection.is_enabled
                                ? CircleOff
                                : CheckCircle2
                        }
                        label={
                            connection.is_enabled
                                ? 'Disable'
                                : 'Enable'
                        }
                        onClick={onToggle}
                        grow={mobile}
                    />
                </>
            ) : null}

            {can(
                'admin.settings.infrastructure_connections.archive',
            ) ? (
                <ActionButton
                    icon={Archive}
                    label="Archive"
                    onClick={onArchive}
                    grow={mobile}
                />
            ) : null}
        </div>
    )
}

function ActionButton({
                          icon: Icon,
                          label,
                          onClick,
                          danger = false,
                          disabled = false,
                          spinning = false,
                          grow = false,
                      }: {
    icon: LucideIcon
    label: string
    onClick: () => void
    danger?: boolean
    disabled?: boolean
    spinning?: boolean
    grow?: boolean
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            className={`inline-flex h-9 items-center justify-center gap-2 rounded-xl border px-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 ${
                grow ? 'flex-1' : ''
            } ${
                danger
                    ? 'border-red-200 bg-white text-red-600 hover:bg-red-50'
                    : 'border-gray-200 bg-white text-gray-600 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700'
            }`}
        >
            <Icon
                className={`h-4 w-4 ${
                    spinning
                        ? 'animate-spin'
                        : ''
                }`}
            />

            {label}
        </button>
    )
}

function TestResultBanner({
                              result,
                              connection,
                              onClose,
                          }: {
    result: TestResult
    connection: Connection | null
    onClose: () => void
}) {
    const successful =
        !result.failed &&
        result.status === 'healthy'

    return (
        <div
            className={`flex items-start justify-between gap-4 rounded-[22px] border p-4 shadow-sm ${
                successful
                    ? 'border-emerald-200 bg-emerald-50/70'
                    : 'border-amber-200 bg-amber-50/70'
            }`}
        >
            <div className="flex items-start gap-3">
                <div
                    className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${
                        successful
                            ? 'bg-emerald-100'
                            : 'bg-amber-100'
                    }`}
                >
                    {successful ? (
                        <CheckCircle2 className="h-5 w-5 text-emerald-700" />
                    ) : (
                        <Gauge className="h-5 w-5 text-amber-700" />
                    )}
                </div>

                <div>
                    <div className="font-semibold text-gray-900">
                        {connection?.name ??
                            'Connection'}{' '}
                        test result
                    </div>

                    <div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600">
                        <span className="font-medium capitalize">
                            {humanize(
                                result.status,
                            )}
                        </span>

                        {result.latencyMs !=
                        null ? (
                            <span>
                                {result.latencyMs} ms
                            </span>
                        ) : null}

                        {result.message ? (
                            <span>
                                {result.message}
                            </span>
                        ) : null}
                    </div>
                </div>
            </div>

            <button
                type="button"
                onClick={onClose}
                aria-label="Dismiss test result"
                title="Dismiss"
                className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-white/70 hover:text-gray-700"
            >
                <X className="h-4 w-4" />
            </button>
        </div>
    )
}

function Metric({
                    label,
                    value,
                    icon: Icon,
                }: {
    label: string
    value: number
    icon: LucideIcon
}) {
    return (
        <div className="flex items-center gap-3 border-gray-200 px-5 py-4 sm:border-r sm:nth-[2n]:border-r-0 xl:nth-[2n]:border-r xl:last:border-r-0">
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
    children: ReactNode
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

function ConnectionSelect({
                              value,
                              options,
                              onChange,
                          }: {
    value: string
    options: SelectOption[]
    onChange: (value: string) => void
}) {
    const [open, setOpen] =
        useState(false)

    const ref =
        useRef<HTMLDivElement>(null)

    const selected =
        options.find(
            (option) =>
                option.value === value,
        ) ?? options[0]

    useEffect(() => {
        const close = (
            event: MouseEvent,
        ) => {
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
                    setOpen(
                        (current) => !current,
                    )
                }
                className="flex h-11 w-full items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-3.5 text-left text-sm text-gray-700 outline-none transition hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
            >
                <span className="truncate">
                    {selected?.label ??
                        'Select'}
                </span>

                <ChevronDown
                    className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                        open
                            ? 'rotate-180'
                            : ''
                    }`}
                />
            </button>

            {open ? (
                <div className="absolute z-50 mt-2 max-h-64 w-full min-w-[180px] overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl">
                    {options.map(
                        (option) => {
                            const active =
                                option.value ===
                                value

                            return (
                                <button
                                    key={
                                        option.value
                                    }
                                    type="button"
                                    onClick={() => {
                                        onChange(
                                            option.value,
                                        )
                                        setOpen(
                                            false,
                                        )
                                    }}
                                    className={`flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition ${
                                        active
                                            ? 'bg-sky-50 font-semibold text-sky-700'
                                            : 'text-gray-700 hover:bg-gray-50'
                                    }`}
                                >
                                    <span className="truncate">
                                        {
                                            option.label
                                        }
                                    </span>

                                    {active ? (
                                        <Check className="h-4 w-4 shrink-0" />
                                    ) : null}
                                </button>
                            )
                        },
                    )}
                </div>
            ) : null}
        </div>
    )
}

function EmptyState({
                        filtered,
                        canCreate,
                        onReset,
                    }: {
    filtered: boolean
    canCreate: boolean
    onReset: () => void
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                <Cable className="h-6 w-6 text-gray-400" />
            </div>

            <h3 className="mt-4 font-semibold text-gray-900">
                {filtered
                    ? 'No connections found'
                    : 'No infrastructure connections yet'}
            </h3>

            <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
                {filtered
                    ? 'No infrastructure connections match the current filters.'
                    : 'Create a connection when SimpleDesk needs secure access to an infrastructure resource.'}
            </p>

            <div className="mt-5 flex flex-wrap justify-center gap-2">
                {filtered ? (
                    <button
                        type="button"
                        onClick={onReset}
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                    >
                        <X className="h-4 w-4" />
                        Clear filters
                    </button>
                ) : null}

                {!filtered &&
                canCreate ? (
                    <Link
                        href={route(
                            'admin.system.connections.create',
                        )}
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 text-sm font-semibold text-white transition hover:bg-sky-700"
                    >
                        <Plus className="h-4 w-4" />
                        New connection
                    </Link>
                ) : null}
            </div>
        </div>
    )
}

function healthAppearance(
    status: HealthStatus,
): {
    label: string
    icon: LucideIcon
    className: string
} {
    switch (status) {
        case 'healthy':
            return {
                label: 'Healthy',
                icon: CheckCircle2,
                className:
                    'bg-emerald-50 text-emerald-700 ring-emerald-100',
            }

        case 'degraded':
            return {
                label: 'Degraded',
                icon: Activity,
                className:
                    'bg-amber-50 text-amber-700 ring-amber-100',
            }

        case 'unhealthy':
            return {
                label: 'Unhealthy',
                icon: XCircle,
                className:
                    'bg-red-50 text-red-700 ring-red-100',
            }

        case 'unavailable':
            return {
                label: 'Unavailable',
                icon: CircleOff,
                className:
                    'bg-red-50 text-red-700 ring-red-100',
            }

        default:
            return {
                label: 'Unknown',
                icon: Gauge,
                className:
                    'bg-gray-100 text-gray-600 ring-gray-200',
            }
    }
}

function humanize(
    value: string,
): string {
    return value
        .replace(
            /([a-z])([A-Z])/g,
            '$1 $2',
        )
        .replace(/[._-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(
            /\b\w/g,
            (character) =>
                character.toUpperCase(),
        )
}

async function readJsonResponse(
    response: Response,
): Promise<Record<string, unknown>> {
    try {
        const value: unknown =
            await response.json()

        if (
            value &&
            typeof value === 'object' &&
            !Array.isArray(value)
        ) {
            return value as Record<
                string,
                unknown
            >
        }

        return {}
    } catch {
        return {}
    }
}

function getStringValue(
    value: Record<string, unknown>,
    key: string,
): string | null {
    const candidate = value[key]

    return typeof candidate === 'string'
        ? candidate
        : null
}

function getNumberValue(
    value: Record<string, unknown>,
    key: string,
): number | null {
    const candidate = value[key]

    return typeof candidate === 'number'
        ? candidate
        : null
}

function getResponseMessage(
    value: Record<string, unknown>,
): string | null {
    return (
        getStringValue(
            value,
            'message',
        ) ??
        getStringValue(
            value,
            'error',
        )
    )
}

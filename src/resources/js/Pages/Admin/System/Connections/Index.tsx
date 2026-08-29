import AdminLayout from '@/Layouts/AdminLayout'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'
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
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    CircleOff,
    Clock3,
    Gauge,
    Pencil,
    Play,
    Plus,
    RefreshCw,
    RotateCcw,
    Search,
    SlidersHorizontal,
    Trash2,
    X,
    XCircle,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import {
    useEffect,
    useState,
} from 'react'
import type {
    FormEvent,
    ReactNode,
} from 'react'
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

type Definition = {
    type: string
    label: string
    description?: string
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type ConnectionsPagination = {
    data: Connection[]
    links: PaginationLink[]
    total: number
    from?: number | null
    to?: number | null
    current_page: number
    last_page: number
}

type Filters = {
    search?: string
    type?: string
    source?: string
    state?: string
    health?: string
    archived?: string
}

type Stats = {
    total: number
    enabled: number
    healthy: number
    problems: number
    archived: number
}

type Props = {
    connections: ConnectionsPagination
    definitions: Definition[]
    filters?: Filters
    stats: Stats
}

type TestResult = {
    connectionId: number
    status: string
    latencyMs?: number | null
    message?: string | null
    failed?: boolean
}

const ALL = '__all__'

export default function Index({
                                  connections,
                                  definitions,
                                  filters = {},
                                  stats,
                              }: Props) {
    const { can } = usePermissions()

    const [search, setSearch] = useState(
        filters.search ?? '',
    )

    const [testing, setTesting] =
        useState<number | null>(null)

    const [testResult, setTestResult] =
        useState<TestResult | null>(null)

    useEffect(() => {
        setSearch(filters.search ?? '')
    }, [filters.search])

    const activeFilters = {
        search: filters.search ?? '',
        type: filters.type ?? '',
        source: filters.source ?? '',
        state: filters.state ?? '',
        health: filters.health ?? '',
        archived: filters.archived ?? 'active',
    }

    const hasFilters =
        activeFilters.search !== '' ||
        activeFilters.type !== '' ||
        activeFilters.source !== '' ||
        activeFilters.state !== '' ||
        activeFilters.health !== '' ||
        activeFilters.archived !== 'active'

    const navigate = (
        changes: Partial<typeof activeFilters>,
    ) => {
        const next = {
            ...activeFilters,
            ...changes,
        }

        router.get(
            route(
                'admin.system.connections.index',
            ),
            removeEmptyFilters(next),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const submitSearch = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        navigate({
            search: search.trim(),
        })
    }

    const clearSearch = () => {
        setSearch('')

        navigate({
            search: '',
        })
    }

    const resetFilters = () => {
        setSearch('')

        router.get(
            route(
                'admin.system.connections.index',
            ),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
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

            const result =
                await readJsonResponse(
                    response,
                )

            if (!response.ok) {
                throw new Error(
                    getResponseMessage(
                        result,
                    ) ||
                    'The connection test failed.',
                )
            }

            setTestResult({
                connectionId:
                connection.id,
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
                connectionId:
                connection.id,
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

    const testedConnection =
        testResult
            ? connections.data.find(
            (connection) =>
                connection.id ===
                testResult.connectionId,
        ) ?? null
            : null

    return (
        <AdminLayout title="Infrastructure Connections">
            <Head title="Infrastructure Connections" />

            <div className="space-y-6">
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

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
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
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200"
                            >
                                <Plus className="h-4 w-4" />
                                New connection
                            </Link>
                        ) : null}
                    </div>

                    <div className="grid border-t border-gray-200 sm:grid-cols-2 xl:grid-cols-5">
                        <Metric
                            label="Total"
                            value={stats.total}
                            icon={Cable}
                        />

                        <Metric
                            label="Enabled"
                            value={stats.enabled}
                            icon={CheckCircle2}
                        />

                        <Metric
                            label="Healthy"
                            value={stats.healthy}
                            icon={Activity}
                        />

                        <Metric
                            label="Problems"
                            value={stats.problems}
                            icon={XCircle}
                        />

                        <Metric
                            label="Archived"
                            value={stats.archived}
                            icon={Archive}
                            last
                        />
                    </div>
                </header>

                {testResult ? (
                    <TestResultBanner
                        result={testResult}
                        connection={
                            testedConnection
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
                                    Filter connections on the server
                                    without loading the complete
                                    infrastructure catalog into the
                                    browser.
                                </p>
                            </div>
                        </div>

                        {hasFilters ? (
                            <button
                                type="button"
                                onClick={
                                    resetFilters
                                }
                                className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                            >
                                <X className="h-4 w-4" />
                                Reset
                            </button>
                        ) : null}
                    </div>

                    <div className="grid gap-4 rounded-b-[28px] p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                        <FilterField
                            label="Search"
                            className="md:col-span-2 xl:col-span-1 2xl:col-span-1"
                        >
                            <form
                                onSubmit={
                                    submitSearch
                                }
                                className="relative"
                            >
                                <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                <input
                                    type="search"
                                    value={search}
                                    onChange={(
                                        event,
                                    ) =>
                                        setSearch(
                                            event
                                                .target
                                                .value,
                                        )
                                    }
                                    placeholder="Search by name..."
                                    className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                />

                                {search !== '' ? (
                                    <button
                                        type="button"
                                        onClick={
                                            clearSearch
                                        }
                                        aria-label="Clear search"
                                        className="absolute right-2.5 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                ) : null}
                            </form>
                        </FilterField>

                        <FilterField label="Type">
                            <FilterSelect
                                value={
                                    activeFilters.type
                                }
                                placeholder="All types"
                                onChange={(
                                    value,
                                ) =>
                                    navigate({
                                        type: value,
                                    })
                                }
                            >
                                {definitions.map(
                                    (
                                        definition,
                                    ) => (
                                        <SelectItem
                                            key={
                                                definition.type
                                            }
                                            value={
                                                definition.type
                                            }
                                        >
                                            {
                                                definition.label
                                            }
                                        </SelectItem>
                                    ),
                                )}
                            </FilterSelect>
                        </FilterField>

                        <FilterField label="Source">
                            <FilterSelect
                                value={
                                    activeFilters.source
                                }
                                placeholder="All sources"
                                onChange={(
                                    value,
                                ) =>
                                    navigate({
                                        source: value,
                                    })
                                }
                            >
                                <SelectItem value="managed">
                                    Managed
                                </SelectItem>

                                <SelectItem value="deployment">
                                    Deployment
                                </SelectItem>
                            </FilterSelect>
                        </FilterField>

                        <FilterField label="State">
                            <FilterSelect
                                value={
                                    activeFilters.state
                                }
                                placeholder="All states"
                                onChange={(
                                    value,
                                ) =>
                                    navigate({
                                        state: value,
                                    })
                                }
                            >
                                <SelectItem value="enabled">
                                    Enabled
                                </SelectItem>

                                <SelectItem value="disabled">
                                    Disabled
                                </SelectItem>
                            </FilterSelect>
                        </FilterField>

                        <FilterField label="Health">
                            <FilterSelect
                                value={
                                    activeFilters.health
                                }
                                placeholder="All health"
                                onChange={(
                                    value,
                                ) =>
                                    navigate({
                                        health: value,
                                    })
                                }
                            >
                                <SelectItem value="unknown">
                                    Unknown
                                </SelectItem>

                                <SelectItem value="healthy">
                                    Healthy
                                </SelectItem>

                                <SelectItem value="degraded">
                                    Degraded
                                </SelectItem>

                                <SelectItem value="unhealthy">
                                    Unhealthy
                                </SelectItem>

                                <SelectItem value="unavailable">
                                    Unavailable
                                </SelectItem>
                            </FilterSelect>
                        </FilterField>

                        <FilterField label="Records">
                            <Select
                                value={
                                    activeFilters.archived
                                }
                                onValueChange={(
                                    value,
                                ) =>
                                    navigate({
                                        archived:
                                        value,
                                    })
                                }
                            >
                                <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="active">
                                        Active records
                                    </SelectItem>

                                    <SelectItem value="archived">
                                        Archived
                                    </SelectItem>

                                    <SelectItem value="all">
                                        All records
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </FilterField>
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-3 border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <div>
                            <h2 className="font-semibold text-gray-900">
                                Connections
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                {connections.total ===
                                0
                                    ? 'No matching infrastructure connections.'
                                    : `Showing ${connections.from ?? 0}–${connections.to ?? 0} of ${connections.total} matching connections.`}
                            </p>
                        </div>

                        {activeFilters.archived ===
                        'archived' ? (
                            <span className="inline-flex self-start rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200 sm:self-auto">
                                Archive view
                            </span>
                        ) : activeFilters.archived ===
                        'all' ? (
                            <span className="inline-flex self-start rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200 sm:self-auto">
                                Active + archived
                            </span>
                        ) : null}
                    </div>

                    {connections.data.length >
                    0 ? (
                        <>
                            <div className="hidden overflow-x-auto lg:block">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-white text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    <tr>
                                        <th className="px-6 py-3.5">
                                            Connection
                                        </th>

                                        <th className="px-4 py-3.5">
                                            Source
                                        </th>

                                        <th className="px-4 py-3.5">
                                            State
                                        </th>

                                        <th className="px-4 py-3.5">
                                            Health
                                        </th>

                                        <th className="px-4 py-3.5">
                                            Last checked
                                        </th>

                                        <th className="px-6 py-3.5 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-100">
                                    {connections.data.map(
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
                                                can={
                                                    can
                                                }
                                                testing={
                                                    testing
                                                }
                                                onTest={
                                                    testConnection
                                                }
                                                onToggle={
                                                    toggleConnection
                                                }
                                                onArchive={
                                                    archiveConnection
                                                }
                                                onRestore={
                                                    restoreConnection
                                                }
                                                onDelete={
                                                    deleteConnection
                                                }
                                            />
                                        ),
                                    )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="divide-y divide-gray-100 lg:hidden">
                                {connections.data.map(
                                    (
                                        connection,
                                    ) => (
                                        <ConnectionCard
                                            key={
                                                connection.id
                                            }
                                            connection={
                                                connection
                                            }
                                            can={can}
                                            testing={
                                                testing
                                            }
                                            onTest={
                                                testConnection
                                            }
                                            onToggle={
                                                toggleConnection
                                            }
                                            onArchive={
                                                archiveConnection
                                            }
                                            onRestore={
                                                restoreConnection
                                            }
                                            onDelete={
                                                deleteConnection
                                            }
                                        />
                                    ),
                                )}
                            </div>

                            <Pagination
                                pagination={
                                    connections
                                }
                            />
                        </>
                    ) : (
                        <EmptyState
                            hasFilters={
                                hasFilters
                            }
                            onReset={
                                resetFilters
                            }
                            canCreate={can(
                                'admin.settings.infrastructure_connections.create',
                            )}
                        />
                    )}
                </section>
            </div>
        </AdminLayout>
    )
}

function ConnectionRow({
                           connection,
                           can,
                           testing,
                           onTest,
                           onToggle,
                           onArchive,
                           onRestore,
                           onDelete,
                       }: ConnectionActionsProps) {
    return (
        <tr className="bg-white transition hover:bg-gray-50/60">
            <td className="px-6 py-4">
                <ConnectionIdentity
                    connection={
                        connection
                    }
                />
            </td>

            <td className="px-4 py-4">
                <SourceBadge
                    source={
                        connection.source
                    }
                />
            </td>

            <td className="px-4 py-4">
                <StateBadge
                    connection={
                        connection
                    }
                />
            </td>

            <td className="px-4 py-4">
                <HealthCell
                    health={
                        connection.latest_health_check
                    }
                />
            </td>

            <td className="px-4 py-4">
                <LastChecked
                    health={
                        connection.latest_health_check
                    }
                />
            </td>

            <td className="px-6 py-4">
                <div className="flex justify-end">
                    <Actions
                        connection={
                            connection
                        }
                        can={can}
                        testing={
                            testing
                        }
                        onTest={
                            onTest
                        }
                        onToggle={
                            onToggle
                        }
                        onArchive={
                            onArchive
                        }
                        onRestore={
                            onRestore
                        }
                        onDelete={
                            onDelete
                        }
                    />
                </div>
            </td>
        </tr>
    )
}

function ConnectionCard({
                            connection,
                            can,
                            testing,
                            onTest,
                            onToggle,
                            onArchive,
                            onRestore,
                            onDelete,
                        }: ConnectionActionsProps) {
    return (
        <article className="p-5 sm:p-6">
            <div className="flex flex-col gap-4">
                <div className="flex items-start justify-between gap-4">
                    <ConnectionIdentity
                        connection={
                            connection
                        }
                    />

                    <StateBadge
                        connection={
                            connection
                        }
                    />
                </div>

                <div className="grid gap-3 rounded-2xl bg-gray-50 p-4 sm:grid-cols-3">
                    <MobileDetail
                        label="Source"
                    >
                        <SourceBadge
                            source={
                                connection.source
                            }
                        />
                    </MobileDetail>

                    <MobileDetail
                        label="Health"
                    >
                        <HealthCell
                            health={
                                connection.latest_health_check
                            }
                        />
                    </MobileDetail>

                    <MobileDetail
                        label="Last checked"
                    >
                        <LastChecked
                            health={
                                connection.latest_health_check
                            }
                        />
                    </MobileDetail>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Actions
                        connection={
                            connection
                        }
                        can={can}
                        testing={
                            testing
                        }
                        onTest={
                            onTest
                        }
                        onToggle={
                            onToggle
                        }
                        onArchive={
                            onArchive
                        }
                        onRestore={
                            onRestore
                        }
                        onDelete={
                            onDelete
                        }
                    />
                </div>
            </div>
        </article>
    )
}

type ConnectionActionsProps = {
    connection: Connection
    can: (permission: string) => boolean
    testing: number | null
    onTest: (
        connection: Connection,
    ) => void
    onToggle: (
        connection: Connection,
    ) => void
    onArchive: (
        connection: Connection,
    ) => void
    onRestore: (
        connection: Connection,
    ) => void
    onDelete: (
        connection: Connection,
    ) => void
}

function Actions({
                     connection,
                     can,
                     testing,
                     onTest,
                     onToggle,
                     onArchive,
                     onRestore,
                     onDelete,
                 }: ConnectionActionsProps) {
    const archived =
        Boolean(
            connection.deleted_at,
        )

    if (archived) {
        return (
            <div className="flex flex-wrap justify-end gap-2">
                {can(
                    'admin.settings.infrastructure_connections.archive',
                ) ? (
                    <button
                        type="button"
                        onClick={() =>
                            onRestore(
                                connection,
                            )
                        }
                        className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                    >
                        <RotateCcw className="h-3.5 w-3.5" />
                        Restore
                    </button>
                ) : null}

                {can(
                    'admin.settings.infrastructure_connections.delete',
                ) ? (
                    <button
                        type="button"
                        onClick={() =>
                            onDelete(
                                connection,
                            )
                        }
                        className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-3 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                        Delete
                    </button>
                ) : null}
            </div>
        )
    }

    return (
        <div className="flex flex-wrap justify-end gap-2">
            {can(
                'admin.settings.infrastructure_connections.update',
            ) ? (
                <Link
                    href={route(
                        'admin.system.connections.edit',
                        connection.id,
                    )}
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                >
                    <Pencil className="h-3.5 w-3.5" />
                    Edit
                </Link>
            ) : null}

            {can(
                'admin.settings.infrastructure_connections.test',
            ) ? (
                <button
                    type="button"
                    disabled={
                        testing !== null
                    }
                    onClick={() =>
                        onTest(connection)
                    }
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {testing ===
                    connection.id ? (
                        <RefreshCw className="h-3.5 w-3.5 animate-spin" />
                    ) : (
                        <Play className="h-3.5 w-3.5" />
                    )}

                    {testing ===
                    connection.id
                        ? 'Testing…'
                        : 'Test'}
                </button>
            ) : null}

            {can(
                'admin.settings.infrastructure_connections.update',
            ) ? (
                <button
                    type="button"
                    onClick={() =>
                        onToggle(
                            connection,
                        )
                    }
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                >
                    <CircleOff className="h-3.5 w-3.5" />

                    {connection.is_enabled
                        ? 'Disable'
                        : 'Enable'}
                </button>
            ) : null}

            {can(
                'admin.settings.infrastructure_connections.archive',
            ) ? (
                <button
                    type="button"
                    onClick={() =>
                        onArchive(
                            connection,
                        )
                    }
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700"
                >
                    <Archive className="h-3.5 w-3.5" />
                    Archive
                </button>
            ) : null}
        </div>
    )
}

function ConnectionIdentity({
                                connection,
                            }: {
    connection: Connection
}) {
    return (
        <div className="flex min-w-0 items-center gap-3">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                <Cable className="h-5 w-5 text-sky-600" />
            </div>

            <div className="min-w-0">
                <div className="truncate font-semibold text-gray-900">
                    {connection.name}
                </div>

                <div className="mt-0.5 text-xs font-medium text-gray-400">
                    {humanize(
                        connection.type,
                    )}
                    {' · '}#{connection.id}
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
    const managed =
        source === 'managed'

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
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
            <span className="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-100">
                Archived
            </span>
        )
    }

    if (connection.is_enabled) {
        return (
            <span className="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100">
                Enabled
            </span>
        )
    }

    return (
        <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
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

    const config =
        healthConfig(status)

    const Icon =
        config.icon

    return (
        <div>
            <div
                className={`inline-flex items-center gap-1.5 text-sm font-semibold ${config.text}`}
            >
                <Icon className="h-4 w-4" />

                {humanize(status)}
            </div>

            {health?.latency_ms != null ? (
                <div className="mt-1 flex items-center gap-1 text-xs text-gray-400">
                    <Gauge className="h-3.5 w-3.5" />
                    {health.latency_ms} ms
                </div>
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

    return (
        <div className="flex items-start gap-2 text-sm text-gray-500">
            <Clock3 className="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

            <span>
                {formatDate(
                    health.created_at,
                )}
            </span>
        </div>
    )
}

function FilterSelect({
                          value,
                          placeholder,
                          onChange,
                          children,
                      }: {
    value: string
    placeholder: string
    onChange: (value: string) => void
    children: ReactNode
}) {
    return (
        <Select
            value={
                value === ''
                    ? ALL
                    : value
            }
            onValueChange={(
                nextValue,
            ) =>
                onChange(
                    nextValue === ALL
                        ? ''
                        : nextValue,
                )
            }
        >
            <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                <SelectValue />
            </SelectTrigger>

            <SelectContent>
                <SelectItem value={ALL}>
                    {placeholder}
                </SelectItem>

                {children}
            </SelectContent>
        </Select>
    )
}

function FilterField({
                         label,
                         className = '',
                         children,
                     }: {
    label: string
    className?: string
    children: ReactNode
}) {
    return (
        <div className={className}>
            <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                {label}
            </div>

            {children}
        </div>
    )
}

function Metric({
                    label,
                    value,
                    icon: Icon,
                    last = false,
                }: {
    label: string
    value: number
    icon: LucideIcon
    last?: boolean
}) {
    return (
        <div
            className={`flex items-center gap-3 border-b border-gray-200 px-5 py-4 sm:border-b-0 sm:border-r sm:px-6 ${
                last
                    ? 'xl:border-r-0'
                    : ''
            }`}
        >
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100">
                <Icon className="h-5 w-5 text-gray-500" />
            </div>

            <div>
                <div className="text-2xl font-semibold tracking-tight text-gray-900">
                    {value}
                </div>

                <div className="text-xs font-medium text-gray-400">
                    {label}
                </div>
            </div>
        </div>
    )
}

function MobileDetail({
                          label,
                          children,
                      }: {
    label: string
    children: ReactNode
}) {
    return (
        <div>
            <div className="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">
                {label}
            </div>

            {children}
        </div>
    )
}

function Pagination({
                        pagination,
                    }: {
    pagination: ConnectionsPagination
}) {
    if (
        pagination.last_page <= 1
    ) {
        return null
    }

    const previous =
        pagination.links[0]

    const next =
        pagination.links[
        pagination.links.length - 1
            ]

    const numericLinks =
        pagination.links.slice(
            1,
            -1,
        )

    return (
        <div className="flex flex-col gap-3 border-t border-gray-200 bg-gray-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div className="text-sm text-gray-500">
                Page{' '}
                <span className="font-semibold text-gray-700">
                    {
                        pagination.current_page
                    }
                </span>{' '}
                of{' '}
                <span className="font-semibold text-gray-700">
                    {
                        pagination.last_page
                    }
                </span>
            </div>

            <div className="flex items-center gap-1.5">
                <PaginationButton
                    href={previous?.url}
                    disabled={
                        !previous?.url
                    }
                    label="Previous"
                    icon={
                        ChevronLeft
                    }
                />

                <div className="hidden items-center gap-1.5 sm:flex">
                    {numericLinks.map(
                        (link) => (
                            <Link
                                key={`${link.label}-${link.url}`}
                                href={
                                    link.url ??
                                    '#'
                                }
                                preserveScroll
                                className={`inline-flex h-9 min-w-9 items-center justify-center rounded-xl px-2.5 text-sm font-semibold transition ${
                                    link.active
                                        ? 'bg-sky-600 text-white shadow-sm'
                                        : link.url
                                            ? 'border border-gray-200 bg-white text-gray-600 hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700'
                                            : 'cursor-default text-gray-300'
                                }`}
                            >
                                {decodePaginationLabel(
                                    link.label,
                                )}
                            </Link>
                        ),
                    )}
                </div>

                <PaginationButton
                    href={next?.url}
                    disabled={
                        !next?.url
                    }
                    label="Next"
                    icon={
                        ChevronRight
                    }
                    iconRight
                />
            </div>
        </div>
    )
}

function PaginationButton({
                              href,
                              disabled,
                              label,
                              icon: Icon,
                              iconRight = false,
                          }: {
    href?: string | null
    disabled: boolean
    label: string
    icon: LucideIcon
    iconRight?: boolean
}) {
    if (disabled || !href) {
        return (
            <span className="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm font-semibold text-gray-300">
                {!iconRight ? (
                    <Icon className="h-4 w-4" />
                ) : null}

                {label}

                {iconRight ? (
                    <Icon className="h-4 w-4" />
                ) : null}
            </span>
        )
    }

    return (
        <Link
            href={href}
            preserveScroll
            className="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
        >
            {!iconRight ? (
                <Icon className="h-4 w-4" />
            ) : null}

            {label}

            {iconRight ? (
                <Icon className="h-4 w-4" />
            ) : null}
        </Link>
    )
}

function EmptyState({
                        hasFilters,
                        onReset,
                        canCreate,
                    }: {
    hasFilters: boolean
    onReset: () => void
    canCreate: boolean
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                <Cable className="h-7 w-7 text-gray-400" />
            </div>

            <h3 className="mt-4 font-semibold text-gray-900">
                {hasFilters
                    ? 'No matching connections'
                    : 'No infrastructure connections'}
            </h3>

            <p className="mt-1 max-w-md text-sm leading-6 text-gray-500">
                {hasFilters
                    ? 'No infrastructure connections match the selected filters.'
                    : 'Create a reusable infrastructure connection before assigning it to a SimpleDesk subsystem.'}
            </p>

            <div className="mt-5 flex flex-wrap justify-center gap-2">
                {hasFilters ? (
                    <button
                        type="button"
                        onClick={onReset}
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                    >
                        <RefreshCw className="h-4 w-4" />
                        Reset filters
                    </button>
                ) : null}

                {!hasFilters &&
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

function TestResultBanner({
                              result,
                              connection,
                              onClose,
                          }: {
    result: TestResult
    connection: Connection | null
    onClose: () => void
}) {
    const status =
        result.failed
            ? 'failed'
            : result.status

    const healthy =
        status === 'healthy'

    const degraded =
        status === 'degraded'

    const unavailable =
        status === 'unavailable' ||
        status === 'failed'

    const containerClass =
        healthy
            ? 'border-emerald-200 bg-emerald-50'
            : degraded
                ? 'border-amber-200 bg-amber-50'
                : unavailable
                    ? 'border-red-200 bg-red-50'
                    : 'border-red-200 bg-red-50'

    const textClass =
        healthy
            ? 'text-emerald-900'
            : degraded
                ? 'text-amber-900'
                : 'text-red-900'

    const secondaryClass =
        healthy
            ? 'text-emerald-700'
            : degraded
                ? 'text-amber-700'
                : 'text-red-700'

    const Icon =
        healthy
            ? CheckCircle2
            : XCircle

    return (
        <section
            className={`rounded-[24px] border px-5 py-4 ${containerClass}`}
        >
            <div className="flex items-start justify-between gap-4">
                <div className="flex items-start gap-3">
                    <Icon
                        className={`mt-0.5 h-5 w-5 shrink-0 ${secondaryClass}`}
                    />

                    <div>
                        <div
                            className={`font-semibold ${textClass}`}
                        >
                            Connection test:{' '}
                            {humanize(status)}
                        </div>

                        <p
                            className={`mt-1 text-sm leading-6 ${secondaryClass}`}
                        >
                            {connection
                                ? `${connection.name}: `
                                : ''}
                            {result.message ??
                                'No additional details were returned.'}

                            {result.latencyMs !=
                            null
                                ? ` · ${result.latencyMs} ms`
                                : ''}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Dismiss result"
                    className={`inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl transition hover:bg-white/60 ${secondaryClass}`}
                >
                    <X className="h-4 w-4" />
                </button>
            </div>
        </section>
    )
}

function healthConfig(
    status: string,
): {
    icon: LucideIcon
    text: string
} {
    switch (status) {
        case 'healthy':
            return {
                icon: CheckCircle2,
                text: 'text-emerald-600',
            }

        case 'degraded':
            return {
                icon: Activity,
                text: 'text-amber-600',
            }

        case 'unhealthy':
            return {
                icon: XCircle,
                text: 'text-red-600',
            }

        case 'unavailable':
            return {
                icon: CircleOff,
                text: 'text-red-600',
            }

        default:
            return {
                icon: Clock3,
                text: 'text-gray-400',
            }
    }
}

function removeEmptyFilters(
    filters: Record<
        string,
        string
    >,
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([key, value]) =>
                value !== '' &&
                !(
                    key ===
                    'archived' &&
                    value ===
                    'active'
                ),
        ),
    )
}

function formatDate(
    value: string,
): string {
    const date =
        new Date(value)

    if (
        Number.isNaN(
            date.getTime(),
        )
    ) {
        return 'Unknown'
    }

    return new Intl.DateTimeFormat(
        undefined,
        {
            dateStyle: 'medium',
            timeStyle: 'short',
        },
    ).format(date)
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

function decodePaginationLabel(
    label: string,
): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&hellip;/g, '…')
        .replace(/&amp;/g, '&')
}

async function readJsonResponse(
    response: Response,
): Promise<Record<string, unknown>> {
    try {
        const value =
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
    } catch {
        // handled by empty object below
    }

    return {}
}

function getStringValue(
    value: Record<string, unknown>,
    key: string,
): string | null {
    const candidate =
        value[key]

    return typeof candidate ===
    'string'
        ? candidate
        : null
}

function getNumberValue(
    value: Record<string, unknown>,
    key: string,
): number | null {
    const candidate =
        value[key]

    if (
        typeof candidate ===
        'number' &&
        Number.isFinite(candidate)
    ) {
        return candidate
    }

    if (
        typeof candidate ===
        'string' &&
        candidate.trim() !== ''
    ) {
        const parsed =
            Number(candidate)

        if (
            Number.isFinite(parsed)
        ) {
            return parsed
        }
    }

    return null
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

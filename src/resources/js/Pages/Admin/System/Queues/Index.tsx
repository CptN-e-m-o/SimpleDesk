import { type FormEvent, type ReactNode, useEffect, useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import {
    AlertTriangle,
    Archive,
    ArrowLeft,
    Boxes,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    CircleGauge,
    Database,
    Inbox,
    Pencil,
    Plus,
    Power,
    RefreshCw,
    RotateCcw,
    Search,
    Settings2,
    Trash2,
    Undo2,
    Workflow,
    X,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'
import { usePermissions } from '@/hooks/usePermissions'
import AdminLayout from '@/Layouts/AdminLayout'

import {
    QueueDriverBadge,
    QueueHealthBadge,
} from './components/QueueBadges'
import QueueTestButton from './components/QueueTestButton'
import type {
    QueueBacklog,
    QueueConfiguration,
    QueueDeploymentTarget,
    QueueDriverDefinition,
    QueueFilters,
    QueueHealthResult,
    QueuePagination,
    QueueWorkload,
} from './queueTypes'

type Props = {
    ownership: {
        mode: 'deployment' | 'managed'
        owned: boolean
        worker_restart_required: boolean
    }
    effective_connection: string
    effective_driver: string
    deployment_target: QueueDeploymentTarget
    active_configuration: QueueConfiguration | null
    configurations: QueuePagination
    definitions: QueueDriverDefinition[]
    workloads: QueueWorkload[]
    backlog: QueueBacklog
    filters: QueueFilters
    stats: {
        total: number
        enabled: number
        archived: number
    }
}

type ConfirmAction = {
    kind:
        | 'activate'
        | 'force-activate'
        | 'activate-deployment'
        | 'force-activate-deployment'
        | 'archive'
        | 'restore'
        | 'delete'
    configuration?: QueueConfiguration
}

type ActiveFilters = {
    search: string
    driver: string
    state: string
    archived: string
    health: string
}

export default function Index(props: Props) {
    const {
        ownership,
        effective_connection,
        effective_driver,
        deployment_target,
        active_configuration,
        configurations,
        definitions,
        workloads,
        backlog,
        filters,
        stats,
    } = props

    const { can } = usePermissions()

    const [search, setSearch] = useState(filters.search ?? '')
    const [confirmAction, setConfirmAction] = useState<ConfirmAction | null>(null)
    const [acting, setActing] = useState(false)
    const [testResults, setTestResults] = useState<Record<number, QueueHealthResult>>({})

    useEffect(() => {
        setSearch(filters.search ?? '')
    }, [filters.search])

    const activeFilters: ActiveFilters = {
        search: filters.search ?? '',
        driver: filters.driver ?? '',
        state: filters.state ?? '',
        archived: filters.archived ?? 'active',
        health: filters.health ?? '',
    }

    const hasFilters =
        activeFilters.search !== ''
        || activeFilters.driver !== ''
        || activeFilters.state !== ''
        || activeFilters.archived !== 'active'
        || activeFilters.health !== ''

    const inspectableBacklogCount = backlog.queues.filter((item) => item.inspectable).length

    const pinnedWorkloads = workloads.filter(
        (workload) => workload.enabled && workload.connection_name !== null,
    )

    const backlogBlockReason = getBacklogBlockReason(backlog)
    const backlogSafe = backlogBlockReason === null

    const managedActivationBlockReason = getManagedActivationBlockReason(
        backlog,
        pinnedWorkloads,
    )

    const managedActivationSafe = managedActivationBlockReason === null

    const deploymentReturnBlockReason = !deployment_target.available
        ? 'The configured deployment Queue target is unavailable.'
        : backlogBlockReason

    const canActivate = can('admin.settings.queues.activate')
    const canForceActivate = can('admin.settings.queues.force_activate')

    const pendingJobsValue: number | string = backlog.is_complete
        ? backlog.total_pending ?? 0
        : 'Unavailable'

    const pendingJobsNote = backlog.is_complete
        ? undefined
        : inspectableBacklogCount === 0
            ? 'Backlog inspection unavailable'
            : `Partial · at least ${backlog.inspected_pending.toLocaleString()} pending observed`

    const navigate = (changes: Partial<ActiveFilters>) => {
        router.get(
            route('admin.system.queues.index'),
            compact({
                ...activeFilters,
                ...changes,
            }),
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const submitSearch = (event: FormEvent) => {
        event.preventDefault()
        navigate({ search: search.trim() })
    }

    const resetFilters = () => {
        setSearch('')

        router.get(
            route('admin.system.queues.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const setEnabled = (configuration: QueueConfiguration, desired: boolean) => {
        router.patch(
            route('admin.system.queues.enabled', configuration.id),
            {
                is_enabled: desired,
            },
            {
                preserveScroll: true,
            },
        )
    }

    const executeConfirmed = () => {
        if (!confirmAction || acting) {
            return
        }

        setActing(true)

        const { kind, configuration } = confirmAction

        const options = {
            preserveScroll: true,
            onFinish: () => {
                setActing(false)
                setConfirmAction(null)
            },
        }

        if (kind === 'activate' && configuration) {
            router.post(
                route('admin.system.queues.activate', configuration.id),
                {},
                options,
            )

            return
        }

        if (kind === 'force-activate' && configuration) {
            router.post(
                route('admin.system.queues.force-activate', configuration.id),
                {},
                options,
            )

            return
        }

        if (kind === 'activate-deployment') {
            router.post(
                route('admin.system.queues.activate-deployment'),
                {},
                options,
            )

            return
        }

        if (kind === 'force-activate-deployment') {
            router.post(
                route('admin.system.queues.force-activate-deployment'),
                {},
                options,
            )

            return
        }

        if (kind === 'archive' && configuration) {
            router.delete(
                route('admin.system.queues.destroy', configuration.id),
                options,
            )

            return
        }

        if (kind === 'restore' && configuration) {
            router.post(
                route('admin.system.queues.restore', configuration.id),
                {},
                options,
            )

            return
        }

        if (kind === 'delete' && configuration) {
            router.delete(
                route('admin.system.queues.force-delete', configuration.id),
                options,
            )
        }
    }

    return (
        <AdminLayout title="Queues">
            <Head title="Queues" />

            <div className="space-y-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <Workflow className="h-6 w-6 text-sky-700" />
                            </span>

                            <div>
                                <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Queues
                                </h1>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                    Configure, inspect and safely switch background job processing for SimpleDesk.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Link
                                href={route('admin.system.drivers.index')}
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                System Drivers
                            </Link>

                            {can('admin.settings.queues.create') ? (
                                <Link
                                    href={route('admin.system.queues.create')}
                                    className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-semibold text-white transition hover:bg-sky-700"
                                >
                                    <Plus className="h-4 w-4" />
                                    Create configuration
                                </Link>
                            ) : null}
                        </div>
                    </div>
                </header>

                <section
                    className={`overflow-hidden rounded-[28px] border bg-white shadow-sm ${
                        ownership.worker_restart_required
                            ? 'border-amber-300'
                            : 'border-gray-200'
                    }`}
                >
                    <div className="grid gap-6 p-5 sm:p-6 lg:grid-cols-[1.3fr_1fr]">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <h2 className="text-lg font-semibold text-gray-900">
                                    Current queue runtime
                                </h2>

                                <span
                                    className={`rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                                        ownership.mode === 'managed'
                                            ? 'bg-sky-50 text-sky-700 ring-sky-200'
                                            : 'bg-gray-100 text-gray-700 ring-gray-200'
                                    }`}
                                >
                                    {ownership.mode === 'managed' ? 'Managed' : 'Deployment'}
                                </span>
                            </div>

                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                {ownership.mode === 'deployment'
                                    ? 'Queue configuration is currently controlled by deployment configuration.'
                                    : active_configuration
                                        ? `The managed runtime uses “${active_configuration.name}”.`
                                        : 'Managed ownership is recorded, but no active configuration is available.'}
                            </p>

                            {ownership.worker_restart_required ? (
                                <div className="mt-4 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                    <AlertTriangle className="h-5 w-5 shrink-0 text-amber-700" />

                                    <p className="text-sm leading-6 text-amber-900">
                                        <span className="font-semibold">
                                            Worker restart required.
                                        </span>{' '}
                                        Running workers may not yet reflect the current runtime configuration.
                                    </p>
                                </div>
                            ) : null}

                            {ownership.mode === 'managed' && canActivate ? (
                                <div className="mt-5">
                                    <div
                                        className={`mb-4 rounded-2xl border p-4 ${
                                            deployment_target.available
                                                ? 'border-sky-200 bg-sky-50'
                                                : 'border-red-200 bg-red-50'
                                        }`}
                                    >
                                        <div className="flex gap-3">
                                            {deployment_target.available ? (
                                                <Database className="mt-0.5 h-5 w-5 shrink-0 text-sky-700" />
                                            ) : (
                                                <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-red-700" />
                                            )}

                                            <div>
                                                <p
                                                    className={`text-sm font-semibold ${
                                                        deployment_target.available
                                                            ? 'text-sky-900'
                                                            : 'text-red-900'
                                                    }`}
                                                >
                                                    {deployment_target.available
                                                        ? 'Deployment return target'
                                                        : 'Deployment target unavailable'}
                                                </p>

                                                {deployment_target.available ? (
                                                    <>
                                                        <p className="mt-1 text-sm text-sky-800">
                                                            {deployment_target.connection}
                                                            {' · '}
                                                            {humanize(deployment_target.driver ?? '')}
                                                        </p>

                                                        <p className="mt-1 text-xs leading-5 text-sky-700">
                                                            SimpleDesk will verify this Queue backend again immediately before switching runtime ownership.
                                                        </p>
                                                    </>
                                                ) : (
                                                    <p className="mt-1 text-sm leading-6 text-red-800">
                                                        The configured deployment Queue connection cannot currently be resolved. Return to deployment will be rejected until the deployment configuration is fixed.
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    {backlogSafe && deployment_target.available ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setConfirmAction({
                                                    kind: 'activate-deployment',
                                                })
                                            }
                                            className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                        >
                                            <Undo2 className="h-4 w-4" />
                                            Return to deployment
                                        </button>
                                    ) : deployment_target.available && canForceActivate ? (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setConfirmAction({
                                                    kind: 'force-activate-deployment',
                                                })
                                            }
                                            className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 text-sm font-semibold text-red-700 transition hover:bg-red-100 hover:text-red-800"
                                        >
                                            <AlertTriangle className="h-4 w-4" />
                                            Force return to deployment
                                        </button>
                                    ) : (
                                        <button
                                            type="button"
                                            disabled
                                            title={deploymentReturnBlockReason ?? undefined}
                                            className="inline-flex h-10 cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-amber-500"
                                        >
                                            <Undo2 className="h-4 w-4" />
                                            Return to deployment
                                        </button>
                                    )}

                                    {!backlogSafe ? (
                                        <p className="mt-2 max-w-2xl text-xs leading-5 text-amber-700">
                                            {backlogBlockReason}
                                        </p>
                                    ) : null}
                                </div>
                            ) : null}
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <RuntimeValue
                                label="Configuration source"
                                value={ownership.mode === 'managed' ? 'Managed' : 'Deployment'}
                            />

                            <RuntimeValue
                                label="Effective driver"
                                value={humanize(effective_driver)}
                            />

                            <RuntimeValue
                                label="Laravel connection"
                                value={effective_connection}
                            />

                            <RuntimeValue
                                label="Active configuration"
                                value={
                                    ownership.mode === 'managed'
                                        ? active_configuration?.name ?? 'Unavailable'
                                        : 'Not applicable'
                                }
                            />

                            <RuntimeValue
                                label="Deployment target"
                                value={
                                    deployment_target.available
                                        ? `${deployment_target.connection} · ${humanize(deployment_target.driver ?? '')}`
                                        : 'Unavailable'
                                }
                            />
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Metric
                        icon={Settings2}
                        label="Configurations"
                        value={stats.total}
                    />

                    <Metric
                        icon={CheckCircle2}
                        label="Enabled"
                        value={stats.enabled}
                        tone="emerald"
                    />

                    <Metric
                        icon={Archive}
                        label="Archived"
                        value={stats.archived}
                        tone="gray"
                    />

                    <Metric
                        icon={Inbox}
                        label="Pending jobs"
                        value={pendingJobsValue}
                        note={pendingJobsNote}
                        tone={backlog.is_complete ? 'sky' : 'amber'}
                    />
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <SectionHeader
                        icon={Boxes}
                        title="Queue workloads"
                        description="Logical SimpleDesk workloads and the physical queue each one resolves to."
                        trailing={
                            pinnedWorkloads.length > 0 ? (
                                <span className="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">
                                    {pinnedWorkloads.length} explicit connection
                                    {pinnedWorkloads.length === 1 ? '' : 's'}
                                </span>
                            ) : null
                        }
                    />

                    {pinnedWorkloads.length > 0 ? (
                        <div className="border-b border-amber-200 bg-amber-50 px-5 py-4 sm:px-6">
                            <div className="flex gap-3">
                                <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                                <div>
                                    <p className="text-sm font-semibold text-amber-900">
                                        Explicit workload routing detected
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-amber-800">
                                        Enabled workloads with an explicit Queue connection bypass the managed default connection.
                                        Normal managed activation is blocked until these routes are removed.
                                    </p>

                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {pinnedWorkloads.map((workload) => (
                                            <span
                                                key={workload.key}
                                                className="rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-xs font-medium text-amber-800"
                                            >
                                                {workload.label} → {workload.connection_name}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ) : null}

                    <div className="grid gap-3 p-5 sm:p-6 lg:grid-cols-2 xl:grid-cols-3">
                        {workloads.map((workload) => {
                            const pinned = workload.enabled && workload.connection_name !== null

                            return (
                                <article
                                    key={workload.key}
                                    className={`rounded-2xl border p-4 ${
                                        pinned
                                            ? 'border-amber-200 bg-amber-50/40'
                                            : 'border-gray-200'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="font-semibold text-gray-900">
                                                    {workload.label}
                                                </h3>

                                                {pinned ? (
                                                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">
                                                        Explicit
                                                    </span>
                                                ) : null}
                                            </div>

                                            <p className="mt-1 text-sm leading-5 text-gray-500">
                                                {workload.description}
                                            </p>
                                        </div>

                                        <StateBadge enabled={workload.enabled} />
                                    </div>

                                    <dl className="mt-4 grid grid-cols-1 gap-3 border-t border-gray-100 pt-4 text-sm sm:grid-cols-2">
                                        <InfoValue
                                            label="Queue"
                                            value={workload.queue_name}
                                        />

                                        <InfoValue
                                            label="Connection"
                                            value={workload.connection_name ?? effective_connection}
                                        />
                                    </dl>

                                    {pinned ? (
                                        <p className="mt-3 text-xs font-medium text-amber-700">
                                            Bypasses the managed default Queue connection
                                        </p>
                                    ) : workload.uses_default_connection ? (
                                        <p className="mt-3 text-xs font-medium text-sky-700">
                                            Uses the default connection
                                        </p>
                                    ) : null}
                                </article>
                            )
                        })}
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <SectionHeader
                        icon={CircleGauge}
                        title="Physical backlog"
                        description="One snapshot per unique physical connection and queue pair; logical workloads are not added together."
                        trailing={
                            <div className="text-left sm:text-right">
                                <div className="text-sm font-semibold text-gray-800">
                                    {backlog.is_complete
                                        ? `${(backlog.total_pending ?? 0).toLocaleString()} pending`
                                        : inspectableBacklogCount === 0
                                            ? 'Backlog unavailable'
                                            : `${backlog.inspected_pending.toLocaleString()}+ observed · incomplete`}
                                </div>

                                <div className="mt-0.5 text-xs text-gray-400">
                                    {formatDate(backlog.inspected_at)}
                                </div>
                            </div>
                        }
                    />

                    {backlog.queues.length ? (
                        <div className="divide-y divide-gray-100">
                            {backlog.queues.map((item) => (
                                <div
                                    key={`${item.connection}:${item.queue}`}
                                    className="grid gap-4 px-5 py-4 sm:px-6 xl:grid-cols-[1fr_1fr_140px_1.4fr] xl:items-center"
                                >
                                    <InfoValue
                                        label="Connection"
                                        value={item.connection}
                                    />

                                    <InfoValue
                                        label="Queue"
                                        value={item.queue}
                                    />

                                    <div>
                                        <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                            Pending
                                        </div>

                                        <div
                                            className={`mt-1 text-lg font-semibold ${
                                                item.inspectable
                                                    ? 'text-gray-900'
                                                    : 'text-amber-700'
                                            }`}
                                        >
                                            {item.inspectable && item.pending !== null
                                                ? item.pending.toLocaleString()
                                                : 'Not inspectable'}
                                        </div>
                                    </div>

                                    <div>
                                        <div className="flex flex-wrap gap-1.5">
                                            {item.workloads.map((workload) => (
                                                <span
                                                    key={workload.key}
                                                    className="rounded-lg bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600"
                                                >
                                                    {workload.label}
                                                </span>
                                            ))}
                                        </div>

                                        {item.error ? (
                                            <p className="mt-2 text-xs leading-5 text-amber-700">
                                                {item.error}
                                            </p>
                                        ) : null}
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <EmptyState
                            icon={Inbox}
                            title="No physical queues"
                            description="There are no queue pairs available for backlog inspection."
                        />
                    )}
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <SectionHeader
                        icon={Database}
                        title="Managed Queue configurations"
                        description="Stored configuration profiles. Activation changes the Queue backend used by new application processes."
                    />

                    <ActivationReadiness
                        backlog={backlog}
                        pinnedWorkloads={pinnedWorkloads}
                        canForce={canForceActivate}
                    />

                    <div className="border-b border-gray-200 bg-gray-50/50 p-5 sm:p-6">
                        <form
                            onSubmit={submitSearch}
                            className="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(240px,1fr)_repeat(4,minmax(130px,180px))_auto]"
                        >
                            <div className="relative md:col-span-2 xl:col-span-1">
                                <Search className="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                <input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search configurations"
                                    className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 text-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                />

                                {search ? (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setSearch('')
                                            navigate({ search: '' })
                                        }}
                                        aria-label="Clear search"
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-gray-600"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                ) : null}
                            </div>

                            <FilterSelect
                                value={activeFilters.driver}
                                placeholder="All drivers"
                                options={definitions.map((item) => ({
                                    value: item.type,
                                    label: item.label,
                                }))}
                                onChange={(driver) => navigate({ driver })}
                            />

                            <FilterSelect
                                value={activeFilters.state}
                                placeholder="Any state"
                                options={[
                                    {
                                        value: 'enabled',
                                        label: 'Enabled',
                                    },
                                    {
                                        value: 'disabled',
                                        label: 'Disabled',
                                    },
                                ]}
                                onChange={(state) => navigate({ state })}
                            />

                            <FilterSelect
                                value={activeFilters.archived}
                                placeholder="Active"
                                options={[
                                    {
                                        value: 'active',
                                        label: 'Active records',
                                    },
                                    {
                                        value: 'archived',
                                        label: 'Archived only',
                                    },
                                    {
                                        value: 'all',
                                        label: 'All records',
                                    },
                                ]}
                                onChange={(archived) => navigate({ archived })}
                            />

                            <FilterSelect
                                value={activeFilters.health}
                                placeholder="Any health"
                                options={[
                                    'healthy',
                                    'degraded',
                                    'unhealthy',
                                    'unavailable',
                                ].map((value) => ({
                                    value,
                                    label: humanize(value),
                                }))}
                                onChange={(health) => navigate({ health })}
                            />

                            <div className="flex gap-2 md:col-span-2 xl:col-span-1">
                                <button
                                    type="submit"
                                    className="inline-flex h-11 items-center justify-center rounded-xl bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800"
                                >
                                    Search
                                </button>

                                {hasFilters ? (
                                    <button
                                        type="button"
                                        onClick={resetFilters}
                                        className="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                    >
                                        <RefreshCw className="h-4 w-4" />
                                        Reset
                                    </button>
                                ) : null}
                            </div>
                        </form>
                    </div>

                    {configurations.data.length ? (
                        <div className="divide-y divide-gray-100">
                            {configurations.data.map((configuration) => {
                                const liveTestResult = testResults[configuration.id]
                                const health = liveTestResult ?? configuration.latest_health_check
                                const archived = Boolean(configuration.deleted_at)

                                const disabledActivationReason = !configuration.is_enabled
                                    ? 'Enable this configuration before activation.'
                                    : managedActivationBlockReason

                                return (
                                    <article
                                        key={configuration.id}
                                        className="p-5 sm:p-6"
                                    >
                                        <div className="grid gap-5 xl:grid-cols-[minmax(260px,1.2fr)_180px_minmax(260px,1fr)] xl:items-start">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h3 className="font-semibold text-gray-900">
                                                        {configuration.name}
                                                    </h3>

                                                    <QueueDriverBadge driver={configuration.driver} />

                                                    {configuration.is_active ? (
                                                        <span className="rounded-full bg-sky-600 px-2.5 py-1 text-xs font-semibold text-white">
                                                            Active
                                                        </span>
                                                    ) : null}

                                                    {archived ? (
                                                        <span className="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                                            Archived
                                                        </span>
                                                    ) : (
                                                        <StateBadge enabled={configuration.is_enabled} />
                                                    )}
                                                </div>

                                                <p className="mt-2 text-sm leading-5 text-gray-500">
                                                    {configurationSummary(configuration)}
                                                </p>

                                                {configuration.driver === 'redis' ? (
                                                    <RedisSummary configuration={configuration} />
                                                ) : null}
                                            </div>

                                            <div>
                                                <QueueHealthBadge status={health?.status} />

                                                <p className="mt-2 text-xs text-gray-400">
                                                    {liveTestResult
                                                        ? 'Just tested'
                                                        : configuration.latest_health_check?.created_at
                                                            ? formatDate(
                                                                configuration.latest_health_check.created_at,
                                                            )
                                                            : 'No test recorded'}
                                                </p>
                                            </div>

                                            <div className="min-w-0">
                                                {health ? (
                                                    <>
                                                        <p className="text-sm leading-5 text-gray-600">
                                                            {health.message}
                                                        </p>

                                                        {health.latency_ms !== null ? (
                                                            <p className="mt-1 text-xs text-gray-400">
                                                                {health.latency_ms} ms
                                                            </p>
                                                        ) : null}
                                                    </>
                                                ) : (
                                                    <p className="text-sm text-gray-400">
                                                        Run a test to check this configuration.
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="mt-5 flex flex-col gap-3 border-t border-gray-100 pt-4 lg:flex-row lg:items-center lg:justify-between">
                                            <div className="min-w-0 flex-1">
                                                {!archived
                                                && !configuration.is_active
                                                && configuration.is_enabled
                                                && !managedActivationSafe ? (
                                                    <div className="flex max-w-2xl gap-2.5">
                                                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />

                                                        <p className="text-xs leading-5 text-amber-700">
                                                            {managedActivationBlockReason}
                                                        </p>
                                                    </div>
                                                ) : configuration.is_active ? (
                                                    <p className="text-xs font-medium text-sky-700">
                                                        This configuration currently controls the managed Queue runtime.
                                                    </p>
                                                ) : archived ? (
                                                    <p className="text-xs text-gray-400">
                                                        Archived configuration
                                                    </p>
                                                ) : (
                                                    <p className="text-xs text-gray-400">
                                                        Configuration changes do not affect the active runtime until activation.
                                                    </p>
                                                )}
                                            </div>

                                            <div className="flex flex-wrap items-center gap-2 lg:justify-end">
                                                {can('admin.settings.queues.test') && !archived ? (
                                                    <QueueTestButton
                                                        configurationId={configuration.id}
                                                        onResult={(result) =>
                                                            setTestResults((current) => ({
                                                                ...current,
                                                                [configuration.id]: result,
                                                            }))
                                                        }
                                                    />
                                                ) : null}

                                                {!archived
                                                && !configuration.is_active
                                                && canActivate
                                                && configuration.is_enabled
                                                && managedActivationSafe ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'activate',
                                                                configuration,
                                                            })
                                                        }
                                                        className="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-sky-600 px-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                                                    >
                                                        <Power className="h-4 w-4" />
                                                        Activate
                                                    </button>
                                                ) : null}

                                                {!archived
                                                && !configuration.is_active
                                                && canActivate
                                                && !configuration.is_enabled ? (
                                                    <button
                                                        type="button"
                                                        disabled
                                                        title={disabledActivationReason ?? undefined}
                                                        className="inline-flex h-9 cursor-not-allowed items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-gray-100 px-3 text-sm font-medium text-gray-400"
                                                    >
                                                        <Power className="h-4 w-4" />
                                                        Activate
                                                    </button>
                                                ) : null}

                                                {!archived
                                                && !configuration.is_active
                                                && canActivate
                                                && configuration.is_enabled
                                                && !managedActivationSafe
                                                && !canForceActivate ? (
                                                    <button
                                                        type="button"
                                                        disabled
                                                        title={managedActivationBlockReason ?? undefined}
                                                        className="inline-flex h-9 cursor-not-allowed items-center justify-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 text-sm font-medium text-amber-500"
                                                    >
                                                        <Power className="h-4 w-4" />
                                                        Activate
                                                    </button>
                                                ) : null}

                                                {!archived
                                                && !configuration.is_active
                                                && configuration.is_enabled
                                                && !managedActivationSafe
                                                && canForceActivate ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'force-activate',
                                                                configuration,
                                                            })
                                                        }
                                                        className="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-3 text-sm font-semibold text-red-700 transition hover:bg-red-100 hover:text-red-800"
                                                    >
                                                        <AlertTriangle className="h-4 w-4" />
                                                        Force activate
                                                    </button>
                                                ) : null}

                                                {!archived
                                                && !configuration.is_active
                                                && can('admin.settings.queues.update') ? (
                                                    <>
                                                        <Link
                                                            href={route(
                                                                'admin.system.queues.edit',
                                                                configuration.id,
                                                            )}
                                                            className={actionClass}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                            Edit
                                                        </Link>

                                                        {configuration.is_enabled ? (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    setEnabled(configuration, false)
                                                                }
                                                                className={actionClass}
                                                            >
                                                                Disable
                                                            </button>
                                                        ) : (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    setEnabled(configuration, true)
                                                                }
                                                                className={actionClass}
                                                            >
                                                                Enable
                                                            </button>
                                                        )}
                                                    </>
                                                ) : null}

                                                {!archived
                                                && !configuration.is_active
                                                && can('admin.settings.queues.archive') ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'archive',
                                                                configuration,
                                                            })
                                                        }
                                                        className={`${actionClass} text-amber-700`}
                                                    >
                                                        <Archive className="h-4 w-4" />
                                                        Archive
                                                    </button>
                                                ) : null}

                                                {archived
                                                && can('admin.settings.queues.archive') ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'restore',
                                                                configuration,
                                                            })
                                                        }
                                                        className={actionClass}
                                                    >
                                                        <RotateCcw className="h-4 w-4" />
                                                        Restore
                                                    </button>
                                                ) : null}

                                                {archived
                                                && !configuration.is_active
                                                && can('admin.settings.queues.delete') ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'delete',
                                                                configuration,
                                                            })
                                                        }
                                                        className={`${actionClass} text-red-700 hover:border-red-200 hover:bg-red-50 hover:text-red-800`}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                        Delete
                                                    </button>
                                                ) : null}
                                            </div>
                                        </div>
                                    </article>
                                )
                            })}
                        </div>
                    ) : (
                        <EmptyState
                            icon={Search}
                            title="No queue configurations found"
                            description={
                                hasFilters
                                    ? 'Adjust or reset the filters to see other configurations.'
                                    : 'Create a configuration to prepare managed Queue settings.'
                            }
                        />
                    )}

                    <Pagination pagination={configurations} />
                </section>

                <Confirmation
                    action={confirmAction}
                    processing={acting}
                    backlog={backlog}
                    pinnedWorkloads={pinnedWorkloads}
                    activeConfiguration={active_configuration}
                    deploymentTarget={deployment_target}
                    onOpenChange={(open) => {
                        if (!open && !acting) {
                            setConfirmAction(null)
                        }
                    }}
                    onConfirm={executeConfirmed}
                />
            </div>
        </AdminLayout>
    )
}

function ActivationReadiness({
                                 backlog,
                                 pinnedWorkloads,
                                 canForce,
                             }: {
    backlog: QueueBacklog
    pinnedWorkloads: QueueWorkload[]
    canForce: boolean
}) {
    const backlogReason = getBacklogBlockReason(backlog)
    const pinnedReason = getPinnedWorkloadBlockReason(pinnedWorkloads)

    if (!backlogReason && !pinnedReason) {
        return (
            <div className="border-b border-emerald-200 bg-emerald-50 px-5 py-4 sm:px-6">
                <div className="flex gap-3">
                    <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-700" />

                    <div>
                        <p className="text-sm font-semibold text-emerald-900">
                            Safe managed activation available
                        </p>

                        <p className="mt-1 text-sm leading-6 text-emerald-800">
                            The current backlog is empty and all enabled workloads use the default Queue connection.
                        </p>
                    </div>
                </div>
            </div>
        )
    }

    return (
        <div className="border-b border-amber-200 bg-amber-50 px-5 py-4 sm:px-6">
            <div className="flex gap-3">
                <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                <div className="min-w-0">
                    <p className="text-sm font-semibold text-amber-900">
                        Normal managed activation blocked
                    </p>

                    <div className="mt-1 space-y-1 text-sm leading-6 text-amber-800">
                        {backlogReason ? (
                            <p>{backlogReason}</p>
                        ) : null}

                        {pinnedReason ? (
                            <p>{pinnedReason}</p>
                        ) : null}
                    </div>

                    {pinnedWorkloads.length > 0 ? (
                        <div className="mt-3 flex flex-wrap gap-2">
                            {pinnedWorkloads.map((workload) => (
                                <span
                                    key={workload.key}
                                    className="rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-xs font-medium text-amber-800"
                                >
                                    {workload.label} → {workload.connection_name}
                                </span>
                            ))}
                        </div>
                    ) : null}

                    {canForce ? (
                        <p className="mt-3 text-xs font-medium leading-5 text-red-700">
                            Emergency force activation is available and will explicitly bypass these safety checks.
                        </p>
                    ) : null}
                </div>
            </div>
        </div>
    )
}

function SectionHeader({
                           icon: Icon,
                           title,
                           description,
                           trailing,
                       }: {
    icon: LucideIcon
    title: string
    description: string
    trailing?: ReactNode
}) {
    return (
        <div className="flex flex-col gap-3 border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div className="flex gap-3">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                    <Icon className="h-5 w-5 text-sky-700" />
                </span>

                <div>
                    <h2 className="font-semibold text-gray-900">
                        {title}
                    </h2>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        {description}
                    </p>
                </div>
            </div>

            {trailing}
        </div>
    )
}

function RuntimeValue({
                          label,
                          value,
                      }: {
    label: string
    value: string
}) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-gray-50/60 p-3">
            <dt className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                {label}
            </dt>

            <dd
                className="mt-1 truncate text-sm font-semibold text-gray-800"
                title={value}
            >
                {value}
            </dd>
        </div>
    )
}

function Metric({
                    icon: Icon,
                    label,
                    value,
                    note,
                    tone = 'sky',
                }: {
    icon: LucideIcon
    label: string
    value: number | string
    note?: string
    tone?: string
}) {
    const colors: Record<string, string> = {
        sky: 'bg-sky-100 text-sky-700',
        emerald: 'bg-emerald-100 text-emerald-700',
        gray: 'bg-gray-100 text-gray-600',
        amber: 'bg-amber-100 text-amber-700',
    }

    return (
        <div className="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between gap-4">
                <div className="min-w-0">
                    <div className="text-sm font-medium text-gray-500">
                        {label}
                    </div>

                    <div className="mt-1 break-words text-2xl font-semibold text-gray-900">
                        {typeof value === 'number'
                            ? value.toLocaleString()
                            : value}
                    </div>

                    {note ? (
                        <div className="mt-1 text-xs leading-5 text-amber-700">
                            {note}
                        </div>
                    ) : null}
                </div>

                <span
                    className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ${
                        colors[tone] ?? colors.sky
                    }`}
                >
                    <Icon className="h-5 w-5" />
                </span>
            </div>
        </div>
    )
}

function InfoValue({
                       label,
                       value,
                   }: {
    label: string
    value: string
}) {
    return (
        <div className="min-w-0">
            <dt className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                {label}
            </dt>

            <dd
                className="mt-1 truncate text-sm font-medium text-gray-700"
                title={value}
            >
                {value}
            </dd>
        </div>
    )
}

function StateBadge({
                        enabled,
                    }: {
    enabled: boolean
}) {
    return (
        <span
            className={`inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                enabled
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                    : 'bg-gray-100 text-gray-600 ring-gray-200'
            }`}
        >
            {enabled ? 'Enabled' : 'Disabled'}
        </span>
    )
}

function EmptyState({
                        icon: Icon,
                        title,
                        description,
                    }: {
    icon: LucideIcon
    title: string
    description: string
}) {
    return (
        <div className="flex flex-col items-center px-6 py-14 text-center">
            <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                <Icon className="h-7 w-7 text-gray-400" />
            </span>

            <h3 className="mt-4 font-semibold text-gray-900">
                {title}
            </h3>

            <p className="mt-1 max-w-md text-sm leading-6 text-gray-500">
                {description}
            </p>
        </div>
    )
}

function FilterSelect({
                          value,
                          placeholder,
                          options,
                          onChange,
                      }: {
    value: string
    placeholder: string
    options: Array<{
        value: string
        label: string
    }>
    onChange: (value: string) => void
}) {
    return (
        <Select
            value={value || '__all'}
            onValueChange={(next) => onChange(next === '__all' ? '' : next)}
        >
            <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>

            <SelectContent>
                <SelectItem value="__all">
                    {placeholder}
                </SelectItem>

                {options.map((option) => (
                    <SelectItem
                        key={option.value}
                        value={option.value}
                    >
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    )
}

function RedisSummary({
                          configuration,
                      }: {
    configuration: QueueConfiguration
}) {
    const connection = configuration.infrastructure_connection

    if (!connection) {
        return (
            <p className="mt-2 text-xs font-medium text-red-700">
                Redis connection unavailable
            </p>
        )
    }

    const unavailable = !connection.is_enabled || Boolean(connection.deleted_at)

    return (
        <p
            className={`mt-2 text-xs ${
                unavailable
                    ? 'font-medium text-amber-700'
                    : 'text-gray-500'
            }`}
        >
            {connection.name}
            {' · '}
            {humanize(connection.source)}
            {' · '}
            {connection.deleted_at
                ? 'Archived / unavailable'
                : connection.is_enabled
                    ? 'Enabled'
                    : 'Disabled / unavailable'}
        </p>
    )
}

function Confirmation({
                          action,
                          processing,
                          backlog,
                          pinnedWorkloads,
                          activeConfiguration,
                          deploymentTarget,
                          onOpenChange,
                          onConfirm,
                      }: {
    action: ConfirmAction | null
    processing: boolean
    backlog: QueueBacklog
    pinnedWorkloads: QueueWorkload[]
    activeConfiguration: QueueConfiguration | null
    deploymentTarget: QueueDeploymentTarget
    onOpenChange: (open: boolean) => void
    onConfirm: () => void
}) {
    const configuration = action?.configuration

    const copy = action
        ? {
            activate: {
                title: 'Activate Queue configuration?',
                description: configuration
                    ? `“${configuration.name}” will become the managed Queue runtime. Queue workers will be signaled to restart after the switch.`
                    : '',
                label: 'Activate',
            },
            'force-activate': {
                title: 'Force activate Queue configuration?',
                description: configuration
                    ? forceActivationDescription(
                        configuration,
                        backlog,
                        pinnedWorkloads,
                    )
                    : '',
                label: 'Force activate',
            },
            'activate-deployment': {
                title: 'Return to deployment configuration?',
                description: deploymentActivationDescription(
                    activeConfiguration,
                    deploymentTarget,
                ),
                label: 'Return to deployment',
            },
            'force-activate-deployment': {
                title: 'Force return to deployment configuration?',
                description: forceDeploymentDescription(
                    activeConfiguration,
                    deploymentTarget,
                    backlog,
                ),
                label: 'Force return',
            },
            archive: {
                title: 'Archive queue configuration?',
                description: configuration
                    ? `“${configuration.name}” will be disabled and archived. It can be restored later.`
                    : '',
                label: 'Archive',
            },
            restore: {
                title: 'Restore queue configuration?',
                description: configuration
                    ? `“${configuration.name}” will be restored and will remain disabled.`
                    : '',
                label: 'Restore',
            },
            delete: {
                title: 'Permanently delete configuration?',
                description: configuration
                    ? `“${configuration.name}” will be permanently deleted. This action cannot be undone.`
                    : '',
                label: 'Permanently delete',
            },
        }[action.kind]
        : null

    const dangerous =
        action?.kind === 'force-activate'
        || action?.kind === 'force-activate-deployment'

    const confirmClass =
        action?.kind === 'activate'
        || action?.kind === 'activate-deployment'
        || action?.kind === 'restore'
            ? 'bg-sky-600 text-white hover:bg-sky-700'
            : dangerous || action?.kind === 'delete'
                ? 'bg-red-600 text-white hover:bg-red-700'
                : 'bg-amber-600 text-white hover:bg-amber-700'

    return (
        <AlertDialog
            open={Boolean(action)}
            onOpenChange={onOpenChange}
        >
            <AlertDialogContent>
                <div className="p-6">
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {copy?.title}
                        </AlertDialogTitle>

                        <AlertDialogDescription className="mt-2 leading-6">
                            {copy?.description}
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    {action?.kind === 'force-activate' && pinnedWorkloads.length > 0 ? (
                        <div className="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <p className="text-sm font-semibold text-amber-900">
                                Explicit workload routing
                            </p>

                            <div className="mt-2 flex flex-wrap gap-2">
                                {pinnedWorkloads.map((workload) => (
                                    <span
                                        key={workload.key}
                                        className="rounded-lg border border-amber-200 bg-white px-2.5 py-1 text-xs font-medium text-amber-800"
                                    >
                                        {workload.label} → {workload.connection_name}
                                    </span>
                                ))}
                            </div>
                        </div>
                    ) : null}

                    {(action?.kind === 'activate-deployment'
                        || action?.kind === 'force-activate-deployment')
                    && deploymentTarget.available ? (
                        <div className="mt-5 rounded-2xl border border-sky-200 bg-sky-50 p-4">
                            <p className="text-sm font-semibold text-sky-900">
                                Deployment target
                            </p>

                            <p className="mt-1 text-sm text-sky-800">
                                {deploymentTarget.connection}
                                {' · '}
                                {humanize(deploymentTarget.driver ?? '')}
                            </p>

                            <p className="mt-1 text-xs leading-5 text-sky-700">
                                The target will be checked again immediately before runtime ownership changes.
                            </p>
                        </div>
                    ) : null}

                    {dangerous ? (
                        <div className="mt-5 flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
                            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-red-700" />

                            <div>
                                <p className="text-sm font-semibold text-red-900">
                                    Emergency operation
                                </p>

                                <p className="mt-1 text-sm leading-6 text-red-800">
                                    This bypasses normal Queue safety checks. Pending jobs may be left behind and explicitly routed workloads may continue using another Queue connection.
                                </p>
                            </div>
                        </div>
                    ) : null}
                </div>

                <AlertDialogFooter className="gap-3 border-t border-gray-200 bg-gray-50/70 px-6 py-5">
                    <AlertDialogCancel disabled={processing}>
                        Cancel
                    </AlertDialogCancel>

                    <AlertDialogAction
                        onClick={(event) => {
                            event.preventDefault()
                            onConfirm()
                        }}
                        disabled={processing}
                        className={confirmClass}
                    >
                        {processing ? 'Working…' : copy?.label}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    )
}

function Pagination({
                        pagination,
                    }: {
    pagination: QueuePagination
}) {
    if (pagination.last_page <= 1) {
        return null
    }

    return (
        <div className="flex flex-col gap-3 border-t border-gray-200 bg-gray-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p className="text-sm text-gray-500">
                Showing {pagination.from}–{pagination.to} of {pagination.total}
            </p>

            <div className="flex items-center gap-1.5">
                {pagination.links.map((link, index) => {
                    const previous = index === 0
                    const next = index === pagination.links.length - 1

                    return (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url ?? '#'}
                            preserveScroll
                            preserveState
                            className={`inline-flex h-9 min-w-9 items-center justify-center rounded-xl px-2.5 text-sm font-semibold transition ${
                                link.active
                                    ? 'bg-sky-600 text-white'
                                    : link.url
                                        ? 'border border-gray-200 bg-white text-gray-600 hover:bg-sky-50 hover:text-sky-700'
                                        : 'pointer-events-none text-gray-300'
                            } ${
                                previous || next
                                    ? ''
                                    : 'hidden sm:inline-flex'
                            }`}
                        >
                            {previous ? (
                                <ChevronLeft className="h-4 w-4" />
                            ) : next ? (
                                <ChevronRight className="h-4 w-4" />
                            ) : (
                                link.label
                            )}
                        </Link>
                    )
                })}
            </div>
        </div>
    )
}

const actionClass =
    'inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700'

function getBacklogBlockReason(backlog: QueueBacklog): string | null {
    if (!backlog.is_complete) {
        if (backlog.inspected_pending > 0) {
            return `The Queue backlog could not be inspected completely. At least ${backlog.inspected_pending.toLocaleString()} pending job(s) were observed.`
        }

        return 'The Queue backlog could not be inspected completely, so the current backend cannot be confirmed empty.'
    }

    const pending = backlog.total_pending ?? 0

    if (pending > 0) {
        return `${pending.toLocaleString()} pending job(s) remain on the current Queue backend.`
    }

    return null
}

function getPinnedWorkloadBlockReason(
    pinnedWorkloads: QueueWorkload[],
): string | null {
    if (pinnedWorkloads.length === 0) {
        return null
    }

    const routes = pinnedWorkloads
        .map((workload) => `${workload.label} → ${workload.connection_name}`)
        .join(', ')

    return `Enabled workloads use explicit Queue connections: ${routes}.`
}

function getManagedActivationBlockReason(
    backlog: QueueBacklog,
    pinnedWorkloads: QueueWorkload[],
): string | null {
    const backlogReason = getBacklogBlockReason(backlog)
    const pinnedReason = getPinnedWorkloadBlockReason(pinnedWorkloads)

    if (backlogReason && pinnedReason) {
        return `${backlogReason} ${pinnedReason}`
    }

    return backlogReason ?? pinnedReason
}

function forceActivationDescription(
    configuration: QueueConfiguration,
    backlog: QueueBacklog,
    pinnedWorkloads: QueueWorkload[],
): string {
    const risks: string[] = []

    const backlogReason = getBacklogBlockReason(backlog)

    if (backlogReason) {
        risks.push(backlogReason)
    }

    if (pinnedWorkloads.length > 0) {
        risks.push(
            `${pinnedWorkloads.length} enabled workload(s) will continue using explicit Queue connections instead of the managed default.`,
        )
    }

    return `“${configuration.name}” will become the managed Queue runtime using emergency override. ${risks.join(' ')}`
}

function deploymentActivationDescription(
    activeConfiguration: QueueConfiguration | null,
    deploymentTarget: QueueDeploymentTarget,
): string {
    const source = activeConfiguration
        ? `Managed Queue configuration “${activeConfiguration.name}” will be released`
        : 'The managed Queue runtime will be released'

    if (!deploymentTarget.available) {
        return `${source}. The configured deployment Queue target is currently unavailable.`
    }

    return `${source} and SimpleDesk will return to deployment connection “${deploymentTarget.connection}”. The target will be verified immediately before the switch and Queue workers will then be signaled to restart.`
}

function forceDeploymentDescription(
    activeConfiguration: QueueConfiguration | null,
    deploymentTarget: QueueDeploymentTarget,
    backlog: QueueBacklog,
): string {
    const prefix = activeConfiguration
        ? `Managed Queue configuration “${activeConfiguration.name}” will be released`
        : 'The managed Queue runtime will be released'

    const target = deploymentTarget.available
        ? `deployment connection “${deploymentTarget.connection}”`
        : 'the deployment Queue connection'

    if (!backlog.is_complete) {
        return `${prefix} even though the current backlog could not be inspected completely. SimpleDesk will return to ${target} and jobs remaining on the managed backend may be left behind.`
    }

    const pending = backlog.total_pending ?? 0

    return `${prefix} while ${pending.toLocaleString()} pending job(s) still remain on the managed backend. SimpleDesk will return to ${target} and those jobs may be left behind.`
}

function configurationSummary(configuration: QueueConfiguration) {
    if (configuration.driver === 'database') {
        return `${
            configuration.configuration.database_connection ?? 'Default database'
        } · retry after ${
            configuration.configuration.retry_after ?? '—'
        }s`
    }

    if (configuration.driver === 'redis') {
        return `Retry after ${
            configuration.configuration.retry_after ?? '—'
        }s · block for ${
            configuration.configuration.block_for === null
                ? 'disabled'
                : `${configuration.configuration.block_for ?? '—'}s`
        }`
    }

    return 'Runs jobs synchronously in the current process'
}

function compact(filters: Record<string, string>) {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) =>
                value !== ''
                && !(value === 'active' && filters.archived === value),
        ),
    )
}

function formatDate(value: string) {
    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return 'Unknown'
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date)
}

function humanize(value: string) {
    return value
        .replace(/[._-]+/g, ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

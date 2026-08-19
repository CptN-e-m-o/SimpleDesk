import { type ReactNode, useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import {
    AlertTriangle,
    Archive,
    ArrowLeft,
    Boxes,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Database,
    Info,
    Pencil,
    Plus,
    Power,
    RefreshCw,
    RotateCcw,
    Server,
    Settings2,
    ShieldAlert,
    Trash2,
    Undo2,
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
    CacheDriverBadge,
    CacheHealthBadge,
} from './components/CacheBadges'
import CacheTestButton from './components/CacheTestButton'
import type {
    CacheConfiguration,
    CacheDefinition,
    CacheDeploymentTarget,
    CacheHealthResult,
    CacheOwnership,
    CachePagination,
} from './cacheTypes'

type Props = {
    ownership: CacheOwnership
    effective_store: string
    effective_driver: string
    deployment_target: CacheDeploymentTarget
    active_configuration: CacheConfiguration | null
    configurations: CachePagination
    definitions: CacheDefinition[]
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
    configuration?: CacheConfiguration
}

export default function Index({
                                  ownership,
                                  effective_store,
                                  effective_driver,
                                  deployment_target,
                                  active_configuration,
                                  configurations,
                                  definitions,
                                  stats,
                              }: Props) {
    const { can } = usePermissions()

    const [confirmAction, setConfirmAction] = useState<ConfirmAction | null>(null)
    const [acting, setActing] = useState(false)
    const [testResults, setTestResults] = useState<
        Record<number, CacheHealthResult>
    >({})

    const canActivate = can('admin.settings.cache.activate')
    const canForceActivate = can(
        'admin.settings.cache.force_activate',
    )

    const archivedFilter = getArchivedFilter()

    const setEnabled = (
        configuration: CacheConfiguration,
        enabled: boolean,
    ) => {
        router.patch(
            route(
                'admin.system.cache.enabled',
                configuration.id,
            ),
            {
                is_enabled: enabled,
            },
            {
                preserveScroll: true,
            },
        )
    }

    const changeArchivedFilter = (
        value: string,
    ) => {
        router.get(
            route('admin.system.cache.index'),
            value === 'active'
                ? {}
                : {
                    archived: value,
                },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const executeConfirmed = () => {
        if (!confirmAction || acting) {
            return
        }

        setActing(true)

        const {
            kind,
            configuration,
        } = confirmAction

        const options = {
            preserveScroll: true,
            onFinish: () => {
                setActing(false)
                setConfirmAction(null)
            },
        }

        if (
            kind === 'activate'
            && configuration
        ) {
            router.post(
                route(
                    'admin.system.cache.activate',
                    configuration.id,
                ),
                {},
                options,
            )

            return
        }

        if (
            kind === 'force-activate'
            && configuration
        ) {
            router.post(
                route(
                    'admin.system.cache.force-activate',
                    configuration.id,
                ),
                {},
                options,
            )

            return
        }

        if (
            kind === 'activate-deployment'
        ) {
            router.post(
                route(
                    'admin.system.cache.activate-deployment',
                ),
                {},
                options,
            )

            return
        }

        if (
            kind === 'force-activate-deployment'
        ) {
            router.post(
                route(
                    'admin.system.cache.force-activate-deployment',
                ),
                {},
                options,
            )

            return
        }

        if (
            kind === 'archive'
            && configuration
        ) {
            router.delete(
                route(
                    'admin.system.cache.destroy',
                    configuration.id,
                ),
                options,
            )

            return
        }

        if (
            kind === 'restore'
            && configuration
        ) {
            router.post(
                route(
                    'admin.system.cache.restore',
                    configuration.id,
                ),
                {},
                options,
            )

            return
        }

        if (
            kind === 'delete'
            && configuration
        ) {
            router.delete(
                route(
                    'admin.system.cache.force-delete',
                    configuration.id,
                ),
                options,
            )
        }
    }

    return (
        <AdminLayout title="Cache">
            <Head title="Cache" />

            <div className="space-y-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <Database className="h-6 w-6 text-sky-700" />
                            </span>

                            <div>
                                <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Cache
                                </h1>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                    Configure and safely switch the default Cache backend used by SimpleDesk.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Link
                                href={route(
                                    'admin.system.drivers.index',
                                )}
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                System Drivers
                            </Link>

                            {can(
                                'admin.settings.cache.create',
                            ) ? (
                                <Link
                                    href={route(
                                        'admin.system.cache.create',
                                    )}
                                    className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-semibold text-white transition hover:bg-sky-700"
                                >
                                    <Plus className="h-4 w-4" />
                                    Create configuration
                                </Link>
                            ) : null}
                        </div>
                    </div>
                </header>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="grid gap-6 p-5 sm:p-6 lg:grid-cols-[1.25fr_1fr]">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <h2 className="text-lg font-semibold text-gray-900">
                                    Current Cache runtime
                                </h2>

                                <span
                                    className={`rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                                        ownership.mode
                                        === 'managed'
                                            ? 'bg-sky-50 text-sky-700 ring-sky-200'
                                            : 'bg-gray-100 text-gray-700 ring-gray-200'
                                    }`}
                                >
                                    {ownership.mode
                                    === 'managed'
                                        ? 'Managed'
                                        : 'Deployment'}
                                </span>
                            </div>

                            <p className="mt-2 max-w-3xl text-sm leading-6 text-gray-500">
                                {ownership.mode
                                === 'managed'
                                    ? active_configuration
                                        ? `SimpleDesk is configured to use the managed Cache profile “${active_configuration.name}” on newly booted processes.`
                                        : 'Managed Cache ownership is recorded, but the active configuration is unavailable.'
                                    : 'Cache ownership remains with the deployment configuration.'}
                            </p>

                            {ownership.mode
                            === 'managed' ? (
                                <div className="mt-5">
                                    <DeploymentTargetNotice
                                        target={
                                            deployment_target
                                        }
                                    />

                                    {canActivate ? (
                                        <div className="mt-4 flex flex-wrap items-start gap-3">
                                            <button
                                                type="button"
                                                disabled={
                                                    !deployment_target.available
                                                }
                                                onClick={() =>
                                                    setConfirmAction(
                                                        {
                                                            kind: 'activate-deployment',
                                                        },
                                                    )
                                                }
                                                className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-100 disabled:text-gray-400"
                                            >
                                                <Undo2 className="h-4 w-4" />
                                                Return to deployment
                                            </button>

                                            {deployment_target.available
                                            && canForceActivate ? (
                                                <details className="group relative">
                                                    <summary className="inline-flex h-10 cursor-pointer list-none items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 text-sm font-medium text-red-700 transition hover:bg-red-100">
                                                        <ShieldAlert className="h-4 w-4" />
                                                        Emergency options
                                                    </summary>

                                                    <div className="absolute left-0 z-20 mt-2 w-80 rounded-2xl border border-red-200 bg-white p-4 shadow-xl">
                                                        <p className="text-sm font-semibold text-red-900">
                                                            Force return to deployment
                                                        </p>

                                                        <p className="mt-1 text-xs leading-5 text-gray-600">
                                                            Use this only when the deployment target is structurally valid but its live health check cannot pass.
                                                        </p>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setConfirmAction(
                                                                    {
                                                                        kind: 'force-activate-deployment',
                                                                    },
                                                                )
                                                            }
                                                            className="mt-3 inline-flex h-9 items-center justify-center gap-2 rounded-xl bg-red-600 px-3 text-sm font-semibold text-white transition hover:bg-red-700"
                                                        >
                                                            <AlertTriangle className="h-4 w-4" />
                                                            Force return
                                                        </button>
                                                    </div>
                                                </details>
                                            ) : null}
                                        </div>
                                    ) : null}
                                </div>
                            ) : (
                                <div className="mt-5">
                                    <DeploymentTargetNotice
                                        target={
                                            deployment_target
                                        }
                                    />
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <RuntimeValue
                                label="Configuration source"
                                value={
                                    ownership.mode
                                    === 'managed'
                                        ? 'Managed'
                                        : 'Deployment'
                                }
                            />

                            <RuntimeValue
                                label="Effective store"
                                value={
                                    effective_store
                                    || 'Unavailable'
                                }
                            />

                            <RuntimeValue
                                label="Effective driver"
                                value={
                                    effective_driver
                                        ? humanize(
                                            effective_driver,
                                        )
                                        : 'Unavailable'
                                }
                            />

                            <RuntimeValue
                                label="Active configuration"
                                value={
                                    ownership.mode
                                    === 'managed'
                                        ? active_configuration?.name
                                        ?? 'Unavailable'
                                        : 'Not applicable'
                                }
                            />
                        </div>
                    </div>

                    <div className="border-t border-amber-200 bg-amber-50 px-5 py-4 sm:px-6">
                        <div className="flex gap-3">
                            <Info className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                            <div>
                                <p className="text-sm font-semibold text-amber-900">
                                    Cache state is not migrated
                                </p>

                                <p className="mt-1 max-w-5xl text-sm leading-6 text-amber-800">
                                    Switching the default Cache backend does not copy cache entries, locks, rate-limit state, or other backend-local data. Existing content remains on the previous store. SimpleDesk does not flush either backend during activation.
                                </p>
                            </div>
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
                        icon={Boxes}
                        label="Managed providers"
                        value={
                            definitions.filter(
                                (definition) =>
                                    definition.available,
                            ).length
                        }
                        note={`${definitions.length} registered`}
                        tone="sky"
                    />
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <SectionHeader
                        icon={Server}
                        title="Managed Cache providers"
                        description="Providers currently exposed for SimpleDesk-managed Cache configurations."
                    />

                    <div className="grid gap-3 p-5 sm:p-6 lg:grid-cols-3">
                        {definitions.map(
                            (definition) => (
                                <ProviderCard
                                    key={
                                        definition.type
                                    }
                                    definition={
                                        definition
                                    }
                                />
                            ),
                        )}
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div className="flex gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                                <Database className="h-5 w-5 text-sky-700" />
                            </span>

                            <div>
                                <h2 className="font-semibold text-gray-900">
                                    Cache configurations
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-gray-500">
                                    Stored Cache profiles are inactive until explicitly activated.
                                </p>
                            </div>
                        </div>

                        <div className="w-full sm:w-48">
                            <Select
                                value={
                                    archivedFilter
                                }
                                onValueChange={
                                    changeArchivedFilter
                                }
                            >
                                <SelectTrigger className="h-10 w-full rounded-xl border-gray-200 bg-white">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="active">
                                        Active records
                                    </SelectItem>

                                    <SelectItem value="archived">
                                        Archived only
                                    </SelectItem>

                                    <SelectItem value="all">
                                        All records
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {configurations.data.length
                    > 0 ? (
                        <div className="divide-y divide-gray-100">
                            {configurations.data.map(
                                (
                                    configuration,
                                ) => {
                                    const health =
                                        testResults[
                                            configuration
                                                .id
                                            ]
                                        ?? configuration
                                            .latest_health_check

                                    const archived =
                                        Boolean(
                                            configuration
                                                .deleted_at,
                                        )

                                    const unavailableReason =
                                        configurationUnavailableReason(
                                            configuration,
                                            definitions,
                                        )

                                    const healthBlocksNormal =
                                        health !== null
                                        && health.status
                                        !== 'healthy'

                                    const normalActivationAvailable =
                                        !archived
                                        && !configuration
                                            .is_active
                                        && configuration
                                            .is_enabled
                                        && unavailableReason
                                        === null
                                        && !healthBlocksNormal

                                    const forceActivationAvailable =
                                        !archived
                                        && !configuration
                                            .is_active
                                        && configuration
                                            .is_enabled
                                        && unavailableReason
                                        === null
                                        && healthBlocksNormal
                                        && canForceActivate

                                    return (
                                        <article
                                            key={
                                                configuration.id
                                            }
                                            className="p-5 sm:p-6"
                                        >
                                            <div className="grid gap-5 xl:grid-cols-[minmax(280px,1.25fr)_180px_minmax(260px,1fr)] xl:items-start">
                                                <div className="min-w-0">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <h3 className="font-semibold text-gray-900">
                                                            {
                                                                configuration.name
                                                            }
                                                        </h3>

                                                        <CacheDriverBadge
                                                            driver={
                                                                configuration.driver
                                                            }
                                                        />

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
                                                            <StateBadge
                                                                enabled={
                                                                    configuration.is_enabled
                                                                }
                                                            />
                                                        )}
                                                    </div>

                                                    <p className="mt-2 text-sm leading-5 text-gray-500">
                                                        {configurationSummary(
                                                            configuration,
                                                        )}
                                                    </p>

                                                    {configuration.driver
                                                    === 'redis' ? (
                                                        <RedisSummary
                                                            configuration={
                                                                configuration
                                                            }
                                                        />
                                                    ) : null}

                                                    {unavailableReason ? (
                                                        <div className="mt-3 flex max-w-xl gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5">
                                                            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-red-700" />

                                                            <p className="text-xs leading-5 text-red-800">
                                                                {
                                                                    unavailableReason
                                                                }
                                                            </p>
                                                        </div>
                                                    ) : null}
                                                </div>

                                                <div>
                                                    <CacheHealthBadge
                                                        status={
                                                            health?.status
                                                        }
                                                    />

                                                    <p className="mt-2 text-xs text-gray-400">
                                                        {testResults[
                                                            configuration
                                                                .id
                                                            ]
                                                            ? 'Just tested'
                                                            : configuration
                                                                .latest_health_check
                                                                ?.created_at
                                                                ? formatDate(
                                                                    configuration
                                                                        .latest_health_check
                                                                        .created_at,
                                                                )
                                                                : 'No test recorded'}
                                                    </p>
                                                </div>

                                                <div className="min-w-0">
                                                    {health ? (
                                                        <>
                                                            <p className="text-sm leading-5 text-gray-600">
                                                                {
                                                                    health.message
                                                                }
                                                            </p>

                                                            {health.latency_ms
                                                            !== null
                                                            && health.latency_ms
                                                            !== undefined ? (
                                                                <p className="mt-1 text-xs text-gray-400">
                                                                    {
                                                                        health.latency_ms
                                                                    }{' '}
                                                                    ms
                                                                </p>
                                                            ) : null}
                                                        </>
                                                    ) : (
                                                        <p className="text-sm leading-5 text-gray-400">
                                                            Run a health test or activate the configuration to verify the backend.
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="mt-5 flex flex-col gap-3 border-t border-gray-100 pt-4 lg:flex-row lg:items-center lg:justify-between">
                                                <div className="min-w-0 flex-1">
                                                    {configuration.is_active ? (
                                                        <p className="text-xs font-medium leading-5 text-sky-700">
                                                            This profile currently owns the managed Cache runtime. Runtime-affecting changes are locked.
                                                        </p>
                                                    ) : archived ? (
                                                        <p className="text-xs leading-5 text-gray-400">
                                                            Archived profiles cannot be activated until restored.
                                                        </p>
                                                    ) : unavailableReason ? (
                                                        <p className="text-xs leading-5 text-red-700">
                                                            Activation is structurally unavailable and cannot be bypassed with Force Activate.
                                                        </p>
                                                    ) : healthBlocksNormal ? (
                                                        <p className="text-xs leading-5 text-amber-700">
                                                            The latest health result is {humanize(
                                                            health?.status
                                                            ?? '',
                                                        )}. Run the test again before using normal activation.
                                                        </p>
                                                    ) : (
                                                        <p className="text-xs leading-5 text-gray-400">
                                                            Activating this profile changes the default Cache store for newly booted application processes.
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="flex flex-wrap items-center gap-2 lg:justify-end">
                                                    {can(
                                                        'admin.settings.cache.test',
                                                    )
                                                    && !archived ? (
                                                        <CacheTestButton
                                                            configurationId={
                                                                configuration.id
                                                            }
                                                            onResult={(
                                                                result,
                                                            ) =>
                                                                setTestResults(
                                                                    (
                                                                        current,
                                                                    ) => ({
                                                                        ...current,
                                                                        [configuration.id]:
                                                                        result,
                                                                    }),
                                                                )
                                                            }
                                                        />
                                                    ) : null}

                                                    {canActivate
                                                    && normalActivationAvailable ? (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setConfirmAction(
                                                                    {
                                                                        kind: 'activate',
                                                                        configuration,
                                                                    },
                                                                )
                                                            }
                                                            className="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-sky-600 px-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                                                        >
                                                            <Power className="h-4 w-4" />
                                                            Activate
                                                        </button>
                                                    ) : null}

                                                    {canActivate
                                                    && !archived
                                                    && !configuration.is_active
                                                    && configuration.is_enabled
                                                    && unavailableReason
                                                    !== null ? (
                                                        <button
                                                            type="button"
                                                            disabled
                                                            title={
                                                                unavailableReason
                                                            }
                                                            className="inline-flex h-9 cursor-not-allowed items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-gray-100 px-3 text-sm font-medium text-gray-400"
                                                        >
                                                            <Power className="h-4 w-4" />
                                                            Activate
                                                        </button>
                                                    ) : null}

                                                    {canActivate
                                                    && !archived
                                                    && !configuration.is_active
                                                    && !configuration.is_enabled ? (
                                                        <button
                                                            type="button"
                                                            disabled
                                                            title="Enable this configuration before activation."
                                                            className="inline-flex h-9 cursor-not-allowed items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-gray-100 px-3 text-sm font-medium text-gray-400"
                                                        >
                                                            <Power className="h-4 w-4" />
                                                            Activate
                                                        </button>
                                                    ) : null}

                                                    {canActivate
                                                    && !archived
                                                    && !configuration.is_active
                                                    && configuration.is_enabled
                                                    && healthBlocksNormal
                                                    && unavailableReason
                                                    === null
                                                    && !canForceActivate ? (
                                                        <button
                                                            type="button"
                                                            disabled
                                                            title="The latest health result is not Healthy."
                                                            className="inline-flex h-9 cursor-not-allowed items-center justify-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 text-sm font-medium text-amber-500"
                                                        >
                                                            <Power className="h-4 w-4" />
                                                            Activate
                                                        </button>
                                                    ) : null}

                                                    {forceActivationAvailable ? (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setConfirmAction(
                                                                    {
                                                                        kind: 'force-activate',
                                                                        configuration,
                                                                    },
                                                                )
                                                            }
                                                            className="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-3 text-sm font-semibold text-red-700 transition hover:bg-red-100 hover:text-red-800"
                                                        >
                                                            <AlertTriangle className="h-4 w-4" />
                                                            Force activate
                                                        </button>
                                                    ) : null}

                                                    {can(
                                                        'admin.settings.cache.update',
                                                    )
                                                    && !archived ? (
                                                        <Link
                                                            href={route(
                                                                'admin.system.cache.edit',
                                                                configuration.id,
                                                            )}
                                                            className={
                                                                actionClass
                                                            }
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                            {configuration.is_active
                                                                ? 'View'
                                                                : 'Edit'}
                                                        </Link>
                                                    ) : null}

                                                    {can(
                                                        'admin.settings.cache.update',
                                                    )
                                                    && !archived
                                                    && !configuration.is_active ? (
                                                        configuration.is_enabled ? (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    setEnabled(
                                                                        configuration,
                                                                        false,
                                                                    )
                                                                }
                                                                className={
                                                                    actionClass
                                                                }
                                                            >
                                                                Disable
                                                            </button>
                                                        ) : (
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    setEnabled(
                                                                        configuration,
                                                                        true,
                                                                    )
                                                                }
                                                                className={
                                                                    actionClass
                                                                }
                                                            >
                                                                Enable
                                                            </button>
                                                        )
                                                    ) : null}

                                                    {can(
                                                        'admin.settings.cache.archive',
                                                    )
                                                    && !archived
                                                    && !configuration.is_active ? (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setConfirmAction(
                                                                    {
                                                                        kind: 'archive',
                                                                        configuration,
                                                                    },
                                                                )
                                                            }
                                                            className={`${actionClass} text-amber-700`}
                                                        >
                                                            <Archive className="h-4 w-4" />
                                                            Archive
                                                        </button>
                                                    ) : null}

                                                    {can(
                                                        'admin.settings.cache.archive',
                                                    )
                                                    && archived ? (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setConfirmAction(
                                                                    {
                                                                        kind: 'restore',
                                                                        configuration,
                                                                    },
                                                                )
                                                            }
                                                            className={
                                                                actionClass
                                                            }
                                                        >
                                                            <RotateCcw className="h-4 w-4" />
                                                            Restore
                                                        </button>
                                                    ) : null}

                                                    {can(
                                                        'admin.settings.cache.delete',
                                                    )
                                                    && archived
                                                    && !configuration.is_active ? (
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setConfirmAction(
                                                                    {
                                                                        kind: 'delete',
                                                                        configuration,
                                                                    },
                                                                )
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
                                },
                            )}
                        </div>
                    ) : (
                        <EmptyState
                            icon={Database}
                            title="No Cache configurations found"
                            description={
                                archivedFilter
                                === 'archived'
                                    ? 'There are no archived Cache configurations.'
                                    : 'Create a managed Cache configuration to prepare a backend for activation.'
                            }
                        />
                    )}

                    <Pagination
                        pagination={
                            configurations
                        }
                    />
                </section>

                <Confirmation
                    action={
                        confirmAction
                    }
                    processing={
                        acting
                    }
                    deploymentTarget={
                        deployment_target
                    }
                    onOpenChange={(
                        open,
                    ) => {
                        if (
                            !open
                            && !acting
                        ) {
                            setConfirmAction(
                                null,
                            )
                        }
                    }}
                    onConfirm={
                        executeConfirmed
                    }
                />
            </div>
        </AdminLayout>
    )
}

function DeploymentTargetNotice({
                                    target,
                                }: {
    target: CacheDeploymentTarget
}) {
    return (
        <div
            className={`rounded-2xl border p-4 ${
                target.available
                    ? 'border-sky-200 bg-sky-50'
                    : 'border-red-200 bg-red-50'
            }`}
        >
            <div className="flex gap-3">
                {target.available ? (
                    <Database className="mt-0.5 h-5 w-5 shrink-0 text-sky-700" />
                ) : (
                    <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-red-700" />
                )}

                <div className="min-w-0">
                    <p
                        className={`text-sm font-semibold ${
                            target.available
                                ? 'text-sky-900'
                                : 'text-red-900'
                        }`}
                    >
                        {target.available
                            ? 'Stable deployment target'
                            : 'Deployment target unavailable'}
                    </p>

                    {target.available ? (
                        <>
                            <p className="mt-1 break-words text-sm text-sky-800">
                                {target.store}
                                {target.driver
                                    ? ` · ${humanize(target.driver)}`
                                    : ''}
                            </p>

                            <p className="mt-1 text-xs leading-5 text-sky-700">
                                SimpleDesk revalidates this target immediately before returning Cache ownership to deployment configuration.
                            </p>
                        </>
                    ) : (
                        <p className="mt-1 text-sm leading-6 text-red-800">
                            {target.message
                                ?? 'The configured deployment Cache store cannot currently be resolved.'}
                        </p>
                    )}
                </div>
            </div>
        </div>
    )
}

function ProviderCard({
                          definition,
                      }: {
    definition: CacheDefinition
}) {
    return (
        <article
            className={`rounded-2xl border p-4 ${
                definition.available
                    ? 'border-gray-200 bg-white'
                    : 'border-gray-200 bg-gray-50'
            }`}
        >
            <div className="flex items-start justify-between gap-3">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <CacheDriverBadge
                            driver={
                                definition.type
                            }
                        />

                        {definition.recommended_for_production ? (
                            <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                Production
                            </span>
                        ) : null}
                    </div>

                    <h3 className="mt-3 font-semibold text-gray-900">
                        {definition.label}
                    </h3>
                </div>

                <span
                    className={`rounded-full px-2 py-1 text-[11px] font-semibold ${
                        definition.available
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'bg-gray-200 text-gray-600'
                    }`}
                >
                    {definition.available
                        ? 'Available'
                        : 'Unavailable'}
                </span>
            </div>

            <p className="mt-2 text-sm leading-6 text-gray-500">
                {definition.description}
            </p>

            {!definition.available
            && definition.unavailable_reason ? (
                <p className="mt-3 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs leading-5 text-gray-600">
                    {definition.unavailable_reason}
                </p>
            ) : null}

            {definition.type
            === 'database'
            && definition.options.database_connections
                ?.length ? (
                <div className="mt-3">
                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Allowed connections
                    </p>

                    <div className="mt-2 flex flex-wrap gap-1.5">
                        {definition.options.database_connections.map(
                            (
                                connection,
                            ) => (
                                <span
                                    key={
                                        connection
                                    }
                                    className="rounded-lg bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600"
                                >
                                    {
                                        connection
                                    }
                                </span>
                            ),
                        )}
                    </div>
                </div>
            ) : null}
        </article>
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
    tone?: 'sky' | 'emerald' | 'gray' | 'amber'
}) {
    const tones = {
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
                        {typeof value
                        === 'number'
                            ? value.toLocaleString()
                            : value}
                    </div>

                    {note ? (
                        <div className="mt-1 text-xs text-gray-400">
                            {note}
                        </div>
                    ) : null}
                </div>

                <span
                    className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ${tones[tone]}`}
                >
                    <Icon className="h-5 w-5" />
                </span>
            </div>
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
            {enabled
                ? 'Enabled'
                : 'Disabled'}
        </span>
    )
}

function RedisSummary({
                          configuration,
                      }: {
    configuration: CacheConfiguration
}) {
    const connection =
        configuration.infrastructure_connection

    if (!connection) {
        return (
            <p className="mt-2 text-xs font-medium text-red-700">
                Redis Infrastructure Connection unavailable
            </p>
        )
    }

    const unavailable =
        !connection.is_enabled
        || Boolean(
            connection.deleted_at,
        )

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
            {humanize(
                connection.source,
            )}
            {' · '}
            {connection.deleted_at
                ? 'Archived / unavailable'
                : connection.is_enabled
                    ? 'Enabled'
                    : 'Disabled / unavailable'}
        </p>
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

function Confirmation({
                          action,
                          processing,
                          deploymentTarget,
                          onOpenChange,
                          onConfirm,
                      }: {
    action: ConfirmAction | null
    processing: boolean
    deploymentTarget: CacheDeploymentTarget
    onOpenChange: (open: boolean) => void
    onConfirm: () => void
}) {
    const configuration =
        action?.configuration

    const copy = action
        ? {
            activate: {
                title: 'Activate Cache configuration?',
                description:
                    configuration
                        ? `“${configuration.name}” will become the managed default Cache backend for newly booted processes. Cache data is not migrated from the current backend.`
                        : '',
                label: 'Activate',
            },
            'force-activate': {
                title: 'Force activate Cache configuration?',
                description:
                    configuration
                        ? `“${configuration.name}” will become the managed Cache backend even though its latest operational health result is not Healthy. Structural validation still cannot be bypassed.`
                        : '',
                label: 'Force activate',
            },
            'activate-deployment': {
                title: 'Return Cache ownership to deployment?',
                description:
                    deploymentTarget.available
                        ? `SimpleDesk will return Cache ownership to deployment store “${deploymentTarget.store}”. The target will be revalidated before the switch. Cache state is not migrated.`
                        : 'The deployment Cache target is unavailable.',
                label: 'Return to deployment',
            },
            'force-activate-deployment': {
                title: 'Force return Cache ownership to deployment?',
                description:
                    deploymentTarget.available
                        ? `SimpleDesk will attempt an emergency return to deployment store “${deploymentTarget.store}”, bypassing operational target health only. Structural deployment configuration errors remain blocking.`
                        : 'The deployment Cache target is unavailable.',
                label: 'Force return',
            },
            archive: {
                title: 'Archive Cache configuration?',
                description:
                    configuration
                        ? `“${configuration.name}” will be disabled and archived. It can be restored later.`
                        : '',
                label: 'Archive',
            },
            restore: {
                title: 'Restore Cache configuration?',
                description:
                    configuration
                        ? `“${configuration.name}” will be restored and will remain disabled until you explicitly enable it.`
                        : '',
                label: 'Restore',
            },
            delete: {
                title: 'Permanently delete Cache configuration?',
                description:
                    configuration
                        ? `“${configuration.name}” will be permanently deleted together with its stored health history. This action cannot be undone.`
                        : '',
                label: 'Permanently delete',
            },
        }[action.kind]
        : null

    const dangerous =
        action?.kind
        === 'force-activate'
        || action?.kind
        === 'force-activate-deployment'
        || action?.kind
        === 'delete'

    const confirmClass =
        action?.kind
        === 'activate'
        || action?.kind
        === 'activate-deployment'
        || action?.kind
        === 'restore'
            ? 'bg-sky-600 text-white hover:bg-sky-700'
            : dangerous
                ? 'bg-red-600 text-white hover:bg-red-700'
                : 'bg-amber-600 text-white hover:bg-amber-700'

    return (
        <AlertDialog
            open={Boolean(action)}
            onOpenChange={
                onOpenChange
            }
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

                    {action?.kind
                    === 'activate'
                    || action?.kind
                    === 'force-activate'
                    || action?.kind
                    === 'activate-deployment'
                    || action?.kind
                    === 'force-activate-deployment' ? (
                        <div className="mt-5 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <Info className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                            <div>
                                <p className="text-sm font-semibold text-amber-900">
                                    Cache contents stay backend-local
                                </p>

                                <p className="mt-1 text-sm leading-6 text-amber-800">
                                    Cache entries and locks are not copied to the target backend and neither backend is flushed.
                                </p>
                            </div>
                        </div>
                    ) : null}

                    {action?.kind
                    === 'force-activate'
                    || action?.kind
                    === 'force-activate-deployment' ? (
                        <div className="mt-4 flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
                            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-red-700" />

                            <div>
                                <p className="text-sm font-semibold text-red-900">
                                    Emergency operation
                                </p>

                                <p className="mt-1 text-sm leading-6 text-red-800">
                                    Operational health failure may be bypassed. Missing, malformed, archived, disabled, or otherwise structurally invalid configuration remains blocked by the backend.
                                </p>
                            </div>
                        </div>
                    ) : null}
                </div>

                <AlertDialogFooter className="gap-3 border-t border-gray-200 bg-gray-50/70 px-6 py-5">
                    <AlertDialogCancel
                        disabled={
                            processing
                        }
                    >
                        Cancel
                    </AlertDialogCancel>

                    <AlertDialogAction
                        onClick={(
                            event,
                        ) => {
                            event.preventDefault()
                            onConfirm()
                        }}
                        disabled={
                            processing
                        }
                        className={
                            confirmClass
                        }
                    >
                        {processing
                            ? 'Working…'
                            : copy?.label}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    )
}

function Pagination({
                        pagination,
                    }: {
    pagination: CachePagination
}) {
    if (
        pagination.last_page
        <= 1
    ) {
        return null
    }

    return (
        <div className="flex flex-col gap-3 border-t border-gray-200 bg-gray-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p className="text-sm text-gray-500">
                Showing{' '}
                {pagination.from
                    ?? 0}
                –
                {pagination.to
                    ?? 0}{' '}
                of{' '}
                {pagination.total}
            </p>

            <div className="flex items-center gap-1.5">
                {pagination.links.map(
                    (
                        link,
                        index,
                    ) => {
                        const previous =
                            index
                            === 0

                        const next =
                            index
                            === pagination.links.length
                            - 1

                        return (
                            <Link
                                key={`${link.label}-${index}`}
                                href={
                                    link.url
                                    ?? '#'
                                }
                                preserveScroll
                                preserveState
                                className={`inline-flex h-9 min-w-9 items-center justify-center rounded-xl px-2.5 text-sm font-semibold transition ${
                                    link.active
                                        ? 'bg-sky-600 text-white'
                                        : link.url
                                            ? 'border border-gray-200 bg-white text-gray-600 hover:bg-sky-50 hover:text-sky-700'
                                            : 'pointer-events-none text-gray-300'
                                } ${
                                    previous
                                    || next
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
                    },
                )}
            </div>
        </div>
    )
}

function configurationUnavailableReason(
    configuration: CacheConfiguration,
    definitions: CacheDefinition[],
): string | null {
    const definition =
        definitions.find(
            (item) =>
                item.type
                === configuration.driver,
        )

    if (!definition) {
        return 'The Cache driver is no longer registered.'
    }

    if (!definition.available) {
        return definition.unavailable_reason
            ?? 'The Cache driver is currently unavailable.'
    }

    if (
        configuration.driver
        === 'redis'
    ) {
        const infrastructure =
            configuration.infrastructure_connection

        if (!infrastructure) {
            return 'The referenced Redis Infrastructure Connection no longer exists.'
        }

        if (
            infrastructure.deleted_at
        ) {
            return 'The referenced Redis Infrastructure Connection is archived.'
        }

        if (
            !infrastructure.is_enabled
        ) {
            return 'The referenced Redis Infrastructure Connection is disabled.'
        }
    }

    if (
        configuration.driver
        === 'database'
    ) {
        const connection =
            configuration.configuration.database_connection

        const allowed =
            definition.options.database_connections
            ?? []

        if (
            !connection
            || !allowed.includes(
                connection,
            )
        ) {
            return 'The configured database connection is no longer available for managed Cache.'
        }
    }

    return null
}

function configurationSummary(
    configuration: CacheConfiguration,
): string {
    if (
        configuration.driver
        === 'database'
    ) {
        return `${
            configuration.configuration.database_connection
            ?? 'Unavailable database connection'
        } · database-backed cache and locks`
    }

    if (
        configuration.driver
        === 'file'
    ) {
        return 'Isolated application-controlled filesystem cache and lock directories'
    }

    if (
        configuration.driver
        === 'redis'
    ) {
        return 'Redis-backed cache and atomic locks through Infrastructure Connections'
    }

    return humanize(
        configuration.driver,
    )
}

function getArchivedFilter(): string {
    if (
        typeof window
        === 'undefined'
    ) {
        return 'active'
    }

    const value =
        new URLSearchParams(
            window.location.search,
        ).get('archived')

    if (
        value === 'archived'
        || value === 'all'
    ) {
        return value
    }

    return 'active'
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
            /[._-]+/g,
            ' ',
        )
        .replace(
            /\b\w/g,
            (letter) =>
                letter.toUpperCase(),
        )
}

const actionClass =
    'inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700'

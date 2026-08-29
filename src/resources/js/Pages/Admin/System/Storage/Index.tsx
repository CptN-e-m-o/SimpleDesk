import { useState, type ReactNode } from 'react'
import { Head, Link, router, usePage } from '@inertiajs/react'
import axios from 'axios'
import {
    AlertTriangle,
    Archive,
    CheckCircle2,
    Cloud,
    Database,
    HardDrive,
    MoreHorizontal,
    Plus,
    RefreshCw,
    RotateCcw,
    Server,
    ShieldAlert,
    Trash2,
    XCircle,
} from 'lucide-react'
import { route } from 'ziggy-js'

import { Button } from '@/Components/ui/button'
import AdminLayout from '@/Layouts/AdminLayout'
import { usePermissions } from '@/hooks/usePermissions'

type HealthStatus =
    | 'healthy'
    | 'degraded'
    | 'unhealthy'
    | 'unavailable'

type Health = {
    status: HealthStatus | string
    latency_ms: number | null
    message: string | null
    created_at?: string | null
}

type Connection = {
    id: number
    name: string
    type: string
    is_enabled: boolean
    archived_at: string | null
}

type Configuration = {
    id: number
    name: string
    driver: string
    infrastructure_connection_id?: number | null
    configuration: {
        prefix?: string
    }
    is_enabled: boolean
    archived_at: string | null
    infrastructure_connection: Connection | null
    latest_health?: Health | null
}

type Definition = {
    driver: string
    label: string
    available: boolean
    requires_infrastructure?: boolean
    infrastructure_type?: string | null
    message?: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type PaginatedConfigurations = {
    data: Configuration[]
    links: PaginationLink[]
    current_page?: number
    last_page?: number
    from?: number | null
    to?: number | null
    total?: number
}

type DeploymentTarget = {
    disk: string | null
    driver: string | null
    available?: boolean
    structurally_valid: boolean
    message?: string | null
}

type Props = {
    ownership: {
        mode: 'deployment' | 'managed' | string
        owned?: boolean
    }
    effective_disk: string
    deployment_target: DeploymentTarget
    active_configuration: Configuration | null
    configurations: PaginatedConfigurations
    definitions: Definition[]
}

type FlashProps = {
    flash?: {
        success?: string
        error?: string
    }
}

export default function Index({
                                  ownership,
                                  effective_disk,
                                  deployment_target,
                                  active_configuration,
                                  configurations,
                                  definitions,
                              }: Props) {
    const { can } = usePermissions()
    const page = usePage<FlashProps>()
    const [testing, setTesting] = useState<number | null>(null)
    const [health, setHealth] = useState<Record<number, Health | null>>({})

    const runTest = async (configuration: Configuration) => {
        if (testing !== null) {
            return
        }

        setTesting(configuration.id)

        try {
            const response = await axios.post<Health>(
                route('admin.system.storage.test', {
                    configuration: configuration.id,
                }),
            )

            setHealth((current) => ({
                ...current,
                [configuration.id]: response.data,
            }))
        } catch {
            setHealth((current) => ({
                ...current,
                [configuration.id]: {
                    status: 'unavailable',
                    latency_ms: 0,
                    message: 'The health check request could not be completed.',
                },
            }))
        } finally {
            setTesting(null)
        }
    }

    const activate = (configuration: Configuration) => {
        router.post(
            route('admin.system.storage.activate', {
                configuration: configuration.id,
            }),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const forceActivate = (configuration: Configuration) => {
        if (
            !window.confirm(
                'Force activation bypasses operational health only. Structural validation is still required. Continue?',
            )
        ) {
            return
        }

        router.post(
            route('admin.system.storage.force-activate', {
                configuration: configuration.id,
            }),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const returnToDeployment = () => {
        router.post(
            route('admin.system.storage.activate-deployment'),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const forceReturnToDeployment = () => {
        if (
            !window.confirm(
                'Force return bypasses deployment operational health only. Structural validation is still required. Continue?',
            )
        ) {
            return
        }

        router.post(
            route('admin.system.storage.force-activate-deployment'),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const setEnabled = (configuration: Configuration) => {
        router.patch(
            route('admin.system.storage.enabled', {
                configuration: configuration.id,
            }),
            {
                is_enabled: !configuration.is_enabled,
            },
            {
                preserveScroll: true,
            },
        )
    }

    const archive = (configuration: Configuration) => {
        if (
            !window.confirm(
                'Archive this Storage profile? Files and objects in the underlying storage will not be deleted.',
            )
        ) {
            return
        }

        router.delete(
            route('admin.system.storage.destroy', {
                configuration: configuration.id,
            }),
            {
                preserveScroll: true,
            },
        )
    }

    const restore = (configuration: Configuration) => {
        router.post(
            route('admin.system.storage.restore', {
                id: configuration.id,
            }),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const forceDelete = (configuration: Configuration) => {
        if (
            !window.confirm(
                'Permanently delete this Storage profile? Only the control-plane configuration will be deleted. Stored objects will remain untouched.',
            )
        ) {
            return
        }

        router.delete(
            route('admin.system.storage.force-delete', {
                id: configuration.id,
            }),
            {
                preserveScroll: true,
            },
        )
    }

    const managed = ownership.mode === 'managed'
    const deploymentValid = deployment_target.structurally_valid

    return (
        <AdminLayout title="Storage drivers">
            <Head title="Storage drivers" />

            <div className="space-y-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 bg-gradient-to-r from-violet-50 via-white to-white p-6 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-100">
                                <HardDrive className="h-6 w-6 text-violet-700" />
                            </span>

                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-xl font-semibold text-gray-900">
                                        Storage runtime
                                    </h1>

                                    <StatusBadge
                                        tone={managed ? 'violet' : 'gray'}
                                    >
                                        {managed
                                            ? 'Managed'
                                            : 'Deployment'}
                                    </StatusBadge>
                                </div>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                    Control Laravel&apos;s private default filesystem runtime without moving or deleting existing objects.
                                </p>
                            </div>
                        </div>

                        {can('admin.settings.storage.create') ? (
                            <Button asChild>
                                <Link
                                    href={route(
                                        'admin.system.storage.create',
                                    )}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    New profile
                                </Link>
                            </Button>
                        ) : null}
                    </div>

                    <div className="grid gap-px border-t border-gray-200 bg-gray-200 md:grid-cols-3">
                        <Metric
                            label="Ownership"
                            value={
                                managed
                                    ? 'Managed by SimpleDesk'
                                    : 'Deployment configuration'
                            }
                            detail={
                                managed
                                    ? 'Persisted Storage profile owns the default filesystem.'
                                    : 'Laravel deployment configuration remains authoritative.'
                            }
                        />

                        <Metric
                            label="Effective disk"
                            value={effective_disk || 'Unknown'}
                            detail={
                                managed
                                    ? 'Managed processes should resolve to simpledesk-managed.'
                                    : 'Current Laravel default filesystem disk.'
                            }
                        />

                        <Metric
                            label="Deployment target"
                            value={
                                deployment_target.disk
                                    ? `${deployment_target.disk} · ${deployment_target.driver ?? 'unknown'}`
                                    : 'Invalid'
                            }
                            detail={
                                deploymentValid
                                    ? 'Available as the safe return target.'
                                    : deployment_target.message
                                    ?? 'Deployment target is structurally invalid.'
                            }
                            danger={!deploymentValid}
                        />
                    </div>
                </header>

                {page.props.flash?.success ? (
                    <FlashMessage tone="success">
                        {page.props.flash.success}
                    </FlashMessage>
                ) : null}

                {page.props.flash?.error ? (
                    <FlashMessage tone="error">
                        {page.props.flash.error}
                    </FlashMessage>
                ) : null}

                <section className="grid gap-4 md:grid-cols-3">
                    {definitions.map((definition) => (
                        <ProviderCard
                            key={definition.driver}
                            definition={definition}
                        />
                    ))}
                </section>

                <section className="overflow-hidden rounded-[28px] border border-amber-200 bg-amber-50">
                    <div className="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-start gap-3">
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100">
                                <AlertTriangle className="h-5 w-5 text-amber-700" />
                            </span>

                            <div>
                                <h2 className="font-semibold text-amber-950">
                                    Storage switching does not migrate data
                                </h2>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-amber-900">
                                    Activation changes future default-disk usage after new processes bootstrap. Existing objects are not copied, moved, enumerated, synchronized, or deleted. Mail continues to use its existing concrete disk identities.
                                </p>
                            </div>
                        </div>

                        {managed
                        && can(
                            'admin.settings.storage.activate',
                        ) ? (
                            <div className="flex shrink-0 flex-wrap gap-2">
                                <Button
                                    variant="outline"
                                    disabled={!deploymentValid}
                                    onClick={returnToDeployment}
                                >
                                    <RotateCcw className="mr-2 h-4 w-4" />
                                    Return to deployment
                                </Button>

                                {can(
                                    'admin.settings.storage.force_activate',
                                ) ? (
                                    <Button
                                        variant="destructive"
                                        disabled={
                                            !deploymentValid
                                        }
                                        onClick={
                                            forceReturnToDeployment
                                        }
                                    >
                                        <ShieldAlert className="mr-2 h-4 w-4" />
                                        Force return
                                    </Button>
                                ) : null}
                            </div>
                        ) : null}
                    </div>

                    {!deploymentValid ? (
                        <div className="border-t border-amber-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                            Returning to deployment is blocked because the deployment filesystem target is structurally invalid. Force activation cannot bypass this condition.
                        </div>
                    ) : null}
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="font-semibold text-gray-900">
                                Storage profiles
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                Profiles define runtime targets only. Deleting a profile never deletes stored objects.
                            </p>
                        </div>

                        <StorageFilters />
                    </div>

                    <div className="divide-y divide-gray-100">
                        {configurations.data.map(
                            (configuration) => {
                                const active =
                                    managed
                                    && active_configuration?.id
                                    === configuration.id

                                const currentHealth =
                                    health[
                                        configuration.id
                                        ]
                                    ?? configuration.latest_health
                                    ?? null

                                return (
                                    <StorageProfile
                                        key={
                                            configuration.id
                                        }
                                        configuration={
                                            configuration
                                        }
                                        health={
                                            currentHealth
                                        }
                                        active={active}
                                        testing={
                                            testing
                                            === configuration.id
                                        }
                                        anyTesting={
                                            testing !== null
                                        }
                                        canTest={can(
                                            'admin.settings.storage.test',
                                        )}
                                        canActivate={can(
                                            'admin.settings.storage.activate',
                                        )}
                                        canForceActivate={can(
                                            'admin.settings.storage.force_activate',
                                        )}
                                        canUpdate={can(
                                            'admin.settings.storage.update',
                                        )}
                                        canArchive={can(
                                            'admin.settings.storage.archive',
                                        )}
                                        canDelete={can(
                                            'admin.settings.storage.delete',
                                        )}
                                        onTest={() =>
                                            void runTest(
                                                configuration,
                                            )
                                        }
                                        onActivate={() =>
                                            activate(
                                                configuration,
                                            )
                                        }
                                        onForceActivate={() =>
                                            forceActivate(
                                                configuration,
                                            )
                                        }
                                        onToggleEnabled={() =>
                                            setEnabled(
                                                configuration,
                                            )
                                        }
                                        onArchive={() =>
                                            archive(
                                                configuration,
                                            )
                                        }
                                        onRestore={() =>
                                            restore(
                                                configuration,
                                            )
                                        }
                                        onDelete={() =>
                                            forceDelete(
                                                configuration,
                                            )
                                        }
                                    />
                                )
                            },
                        )}

                        {configurations.data.length === 0 ? (
                            <div className="px-5 py-14 text-center">
                                <HardDrive className="mx-auto h-8 w-8 text-gray-300" />

                                <p className="mt-3 text-sm font-medium text-gray-700">
                                    No Storage profiles found
                                </p>

                                <p className="mt-1 text-sm text-gray-400">
                                    Change the lifecycle filter or create a new profile.
                                </p>
                            </div>
                        ) : null}
                    </div>

                    <Pagination
                        links={configurations.links}
                        from={configurations.from}
                        to={configurations.to}
                        total={configurations.total}
                    />
                </section>
            </div>
        </AdminLayout>
    )
}

function StorageProfile({
                            configuration,
                            health,
                            active,
                            testing,
                            anyTesting,
                            canTest,
                            canActivate,
                            canForceActivate,
                            canUpdate,
                            canArchive,
                            canDelete,
                            onTest,
                            onActivate,
                            onForceActivate,
                            onToggleEnabled,
                            onArchive,
                            onRestore,
                            onDelete,
                        }: {
    configuration: Configuration
    health: Health | null
    active: boolean
    testing: boolean
    anyTesting: boolean
    canTest: boolean
    canActivate: boolean
    canForceActivate: boolean
    canUpdate: boolean
    canArchive: boolean
    canDelete: boolean
    onTest: () => void
    onActivate: () => void
    onForceActivate: () => void
    onToggleEnabled: () => void
    onArchive: () => void
    onRestore: () => void
    onDelete: () => void
}) {
    const archived = Boolean(configuration.archived_at)

    return (
        <article className="p-5 transition hover:bg-gray-50/50">
            <div className="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-semibold text-gray-900">
                            {configuration.name}
                        </h3>

                        <StatusBadge tone="gray">
                            {driverLabel(
                                configuration.driver,
                            )}
                        </StatusBadge>

                        {active ? (
                            <StatusBadge tone="violet">
                                Active
                            </StatusBadge>
                        ) : null}

                        {archived ? (
                            <StatusBadge tone="amber">
                                Archived
                            </StatusBadge>
                        ) : (
                            <StatusBadge
                                tone={
                                    configuration.is_enabled
                                        ? 'green'
                                        : 'gray'
                                }
                            >
                                {configuration.is_enabled
                                    ? 'Enabled'
                                    : 'Disabled'}
                            </StatusBadge>
                        )}
                    </div>

                    <div className="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-500">
                        <ProfileTarget
                            configuration={configuration}
                        />

                        {configuration.configuration
                            .prefix ? (
                            <span>
                                Prefix:{' '}
                                <span className="font-medium text-gray-700">
                                    {
                                        configuration
                                            .configuration
                                            .prefix
                                    }
                                </span>
                            </span>
                        ) : null}
                    </div>

                    <div className="mt-3">
                        <HealthDisplay health={health} />
                    </div>
                </div>

                <div className="flex shrink-0 flex-wrap items-center gap-2">
                    {!archived && canTest ? (
                        <Button
                            variant="outline"
                            disabled={anyTesting}
                            onClick={onTest}
                        >
                            <RefreshCw
                                className={`mr-2 h-4 w-4 ${
                                    testing
                                        ? 'animate-spin'
                                        : ''
                                }`}
                            />
                            {testing ? 'Testing…' : 'Test'}
                        </Button>
                    ) : null}

                    {!active
                    && !archived
                    && configuration.is_enabled
                    && canActivate ? (
                        <Button onClick={onActivate}>
                            Activate
                        </Button>
                    ) : null}

                    {!active
                    && !archived
                    && configuration.is_enabled
                    && canForceActivate ? (
                        <Button
                            variant="destructive"
                            onClick={
                                onForceActivate
                            }
                        >
                            <ShieldAlert className="mr-2 h-4 w-4" />
                            Force
                        </Button>
                    ) : null}

                    {!active
                    && !archived
                    && canUpdate ? (
                        <Button
                            variant="outline"
                            asChild
                        >
                            <Link
                                href={route(
                                    'admin.system.storage.edit',
                                    {
                                        configuration:
                                        configuration.id,
                                    },
                                )}
                            >
                                Edit
                            </Link>
                        </Button>
                    ) : null}

                    {!active
                    && !archived
                    && canUpdate ? (
                        <Button
                            variant="outline"
                            onClick={
                                onToggleEnabled
                            }
                        >
                            {configuration.is_enabled
                                ? 'Disable'
                                : 'Enable'}
                        </Button>
                    ) : null}

                    {!active
                    && !archived
                    && canArchive ? (
                        <Button
                            variant="outline"
                            onClick={onArchive}
                        >
                            <Archive className="mr-2 h-4 w-4" />
                            Archive
                        </Button>
                    ) : null}

                    {archived && canArchive ? (
                        <Button
                            variant="outline"
                            onClick={onRestore}
                        >
                            <RotateCcw className="mr-2 h-4 w-4" />
                            Restore
                        </Button>
                    ) : null}

                    {archived && canDelete ? (
                        <Button
                            variant="destructive"
                            onClick={onDelete}
                        >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    ) : null}
                </div>
            </div>
        </article>
    )
}

function ProfileTarget({
                           configuration,
                       }: {
    configuration: Configuration
}) {
    if (!configuration.infrastructure_connection) {
        return (
            <span className="flex items-center gap-1.5">
                <Database className="h-4 w-4" />
                Application-owned private local storage
            </span>
        )
    }

    const connection =
        configuration.infrastructure_connection

    return (
        <span className="flex flex-wrap items-center gap-1.5">
            <Cloud className="h-4 w-4" />
            <span className="font-medium text-gray-700">
                {connection.name}
            </span>
            <span>· {connectionTypeLabel(connection.type)}</span>

            {connection.archived_at ? (
                <StatusBadge tone="amber">
                    Connection archived
                </StatusBadge>
            ) : !connection.is_enabled ? (
                <StatusBadge tone="red">
                    Connection disabled
                </StatusBadge>
            ) : null}
        </span>
    )
}

function ProviderCard({
                          definition,
                      }: {
    definition: Definition
}) {
    const external =
        definition.driver !== 'local'

    return (
        <div className="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100">
                    {external ? (
                        <Cloud className="h-5 w-5 text-gray-600" />
                    ) : (
                        <Database className="h-5 w-5 text-gray-600" />
                    )}
                </span>

                <StatusBadge
                    tone={
                        definition.available
                            ? 'green'
                            : 'amber'
                    }
                >
                    {definition.available
                        ? 'Available'
                        : 'Unavailable'}
                </StatusBadge>
            </div>

            <h2 className="mt-4 font-semibold text-gray-900">
                {definition.label}
            </h2>

            <p className="mt-1 text-sm leading-6 text-gray-500">
                {definition.driver === 'local'
                    ? 'Private application-owned filesystem storage.'
                    : 'Private object storage backed by an Infrastructure Connection.'}
            </p>

            {!definition.available
            && definition.message ? (
                <p className="mt-3 text-xs leading-5 text-amber-700">
                    {definition.message}
                </p>
            ) : null}
        </div>
    )
}

function StorageFilters() {
    return (
        <div className="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1">
            <FilterLink
                href={route(
                    'admin.system.storage.index',
                    {
                        archived: 'active',
                    },
                )}
            >
                Active
            </FilterLink>

            <FilterLink
                href={route(
                    'admin.system.storage.index',
                    {
                        archived: 'archived',
                    },
                )}
            >
                Archived
            </FilterLink>

            <FilterLink
                href={route(
                    'admin.system.storage.index',
                    {
                        archived: 'all',
                    },
                )}
            >
                All
            </FilterLink>
        </div>
    )
}

function FilterLink({
                        href,
                        children,
                    }: {
    href: string
    children: ReactNode
}) {
    return (
        <Link
            href={href}
            preserveScroll
            preserveState
            className="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-white hover:text-gray-900"
        >
            {children}
        </Link>
    )
}

function HealthDisplay({
                           health,
                       }: {
    health: Health | null
}) {
    if (!health) {
        return (
            <div className="flex items-center gap-2 text-sm text-gray-400">
                <MoreHorizontal className="h-4 w-4" />
                Not tested
            </div>
        )
    }

    const status = health.status.toLowerCase()

    const icon =
        status === 'healthy' ? (
            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
        ) : (
            <XCircle
                className={`h-4 w-4 ${
                    status === 'degraded'
                        ? 'text-amber-600'
                        : 'text-red-600'
                }`}
            />
        )

    return (
        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
            {icon}

            <span className="font-medium text-gray-700">
                {healthLabel(health.status)}
            </span>

            {health.latency_ms !== null ? (
                <span className="text-gray-400">
                    · {health.latency_ms} ms
                </span>
            ) : null}

            {health.message ? (
                <span className="basis-full text-xs leading-5 text-gray-400 sm:basis-auto">
                    {health.message}
                </span>
            ) : null}
        </div>
    )
}

function Pagination({
                        links,
                        from,
                        to,
                        total,
                    }: {
    links: PaginationLink[]
    from?: number | null
    to?: number | null
    total?: number
}) {
    if (links.length <= 3) {
        return null
    }

    return (
        <div className="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-gray-500">
                {from != null
                && to != null
                && total != null
                    ? `Showing ${from}–${to} of ${total}`
                    : 'More Storage profiles are available'}
            </p>

            <div className="flex flex-wrap gap-1">
                {links.map((link, index) => {
                    const label =
                        paginationLabel(link.label)

                    if (!link.url) {
                        return (
                            <span
                                key={`${link.label}-${index}`}
                                className="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-100 px-3 text-sm text-gray-300"
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
                            className={`inline-flex h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-sm font-medium transition ${
                                link.active
                                    ? 'border-violet-200 bg-violet-50 text-violet-700'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                            }`}
                        >
                            {label}
                        </Link>
                    )
                })}
            </div>
        </div>
    )
}

function Metric({
                    label,
                    value,
                    detail,
                    danger = false,
                }: {
    label: string
    value: string
    detail: string
    danger?: boolean
}) {
    return (
        <div className="bg-white p-5">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                {label}
            </p>

            <p
                className={`mt-2 font-semibold ${
                    danger
                        ? 'text-red-700'
                        : 'text-gray-900'
                }`}
            >
                {value}
            </p>

            <p className="mt-1 text-xs leading-5 text-gray-400">
                {detail}
            </p>
        </div>
    )
}

function FlashMessage({
                          tone,
                          children,
                      }: {
    tone: 'success' | 'error'
    children: ReactNode
}) {
    return (
        <div
            className={`rounded-2xl border px-4 py-3 text-sm ${
                tone === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-red-200 bg-red-50 text-red-800'
            }`}
        >
            {children}
        </div>
    )
}

function StatusBadge({
                         tone,
                         children,
                     }: {
    tone:
        | 'gray'
        | 'green'
        | 'amber'
        | 'red'
        | 'violet'
    children: ReactNode
}) {
    const classes = {
        gray: 'bg-gray-100 text-gray-600',
        green: 'bg-emerald-50 text-emerald-700',
        amber: 'bg-amber-50 text-amber-700',
        red: 'bg-red-50 text-red-700',
        violet: 'bg-violet-50 text-violet-700',
    }

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${classes[tone]}`}
        >
            {children}
        </span>
    )
}

function driverLabel(driver: string): string {
    switch (driver) {
        case 'local':
            return 'Local'
        case 's3':
            return 'Amazon S3'
        case 's3_compatible':
            return 'S3-compatible'
        default:
            return driver
    }
}

function connectionTypeLabel(type: string): string {
    switch (type) {
        case 'aws':
            return 'Amazon S3'
        case 's3_compatible':
            return 'S3-compatible'
        default:
            return type
    }
}

function healthLabel(status: string): string {
    return status
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) =>
            letter.toUpperCase(),
        )
}

function paginationLabel(label: string): string {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .trim()
}

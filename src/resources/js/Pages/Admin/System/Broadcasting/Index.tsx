import { type ReactNode, useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import axios from 'axios'
import {
    Activity,
    AlertTriangle,
    Archive,
    ArrowLeft,
    CheckCircle2,
    Info,
    Pencil,
    Plus,
    Power,
    Radio,
    RefreshCw,
    RotateCcw,
    Server,
    ShieldAlert,
    Trash2,
    Undo2,
    Wifi,
    WifiOff,
} from 'lucide-react'
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
import { Button } from '@/Components/ui/button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'
import { usePermissions } from '@/hooks/usePermissions'
import AdminLayout from '@/Layouts/AdminLayout'
import BrowserRealtimeProbe from './BrowserRealtimeProbe'

type HealthResult = {
    status: string
    latency_ms: number
    message: string
}

type Configuration = {
    id: number
    name: string
    driver: string
    infrastructure_connection_id: number
    is_enabled: boolean
    archived_at: string | null
    latest_health?: HealthResult | null
}

type Definition = {
    type: string
    name: string
    description: string
    available: boolean
    unavailable_reason?: string | null
}

type DeploymentTarget = {
    connection: string | null
    driver: string | null
    available: boolean
    externally_delivering?: boolean
    message?: string
}

type Pagination = {
    data: Configuration[]
    current_page: number
    last_page: number
    total: number
    prev_page_url: string | null
    next_page_url: string | null
}

type Props = {
    ownership: {
        mode: string
        owned?: boolean
    }
    effective_connection: string
    deployment_target: DeploymentTarget
    configurations: Pagination
    definitions: Definition[]
    active_configuration: Configuration | null
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
    configuration?: Configuration
}

type ConfirmationTone = 'primary' | 'warning' | 'danger' | 'success'

export default function Index({
                                  ownership,
                                  effective_connection,
                                  deployment_target,
                                  configurations,
                                  definitions,
                                  active_configuration,
                              }: Props) {
    const { can } = usePermissions()
    const [confirmAction, setConfirmAction] = useState<ConfirmAction | null>(null)
    const [acting, setActing] = useState(false)
    const [testing, setTesting] = useState<number | null>(null)
    const [health, setHealth] = useState<Record<number, HealthResult>>({})

    const archivedFilter = getArchivedFilter()
    const canActivate = can('admin.settings.broadcasting.activate')
    const canForceActivate = can('admin.settings.broadcasting.force_activate')

    const changeArchivedFilter = (value: string) => {
        router.get(
            route('admin.system.broadcasting.index'),
            value === 'active' ? {} : { archived: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const setEnabled = (configuration: Configuration, enabled: boolean) => {
        router.patch(
            route('admin.system.broadcasting.enabled', {
                configuration: configuration.id,
            }),
            { is_enabled: enabled },
            { preserveScroll: true },
        )
    }

    const test = async (configuration: Configuration) => {
        if (testing !== null) {
            return
        }

        setTesting(configuration.id)

        try {
            const response = await axios.post<HealthResult>(
                route('admin.system.broadcasting.test', {
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
                route('admin.system.broadcasting.activate', {
                    configuration: configuration.id,
                }),
                {},
                options,
            )
            return
        }

        if (kind === 'force-activate' && configuration) {
            router.post(
                route('admin.system.broadcasting.force-activate', {
                    configuration: configuration.id,
                }),
                {},
                options,
            )
            return
        }

        if (kind === 'activate-deployment') {
            router.post(
                route('admin.system.broadcasting.activate-deployment'),
                {},
                options,
            )
            return
        }

        if (kind === 'force-activate-deployment') {
            router.post(
                route('admin.system.broadcasting.force-activate-deployment'),
                {},
                options,
            )
            return
        }

        if (kind === 'archive' && configuration) {
            router.delete(
                route('admin.system.broadcasting.destroy', {
                    configuration: configuration.id,
                }),
                options,
            )
            return
        }

        if (kind === 'restore' && configuration) {
            router.post(
                route('admin.system.broadcasting.restore', {
                    id: configuration.id,
                }),
                {},
                options,
            )
            return
        }

        if (kind === 'delete' && configuration) {
            router.delete(
                route('admin.system.broadcasting.force-delete', {
                    id: configuration.id,
                }),
                options,
            )
        }
    }

    return (
        <AdminLayout title="Real-time">
            <Head title="Real-time" />

            <div className="space-y-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <Radio className="h-6 w-6 text-sky-700" />
                            </span>

                            <div>
                                <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Real-time
                                </h1>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                    Configure and safely switch the broadcaster used for real-time SimpleDesk events.
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

                            {can('admin.settings.broadcasting.create') ? (
                                <Link
                                    href={route('admin.system.broadcasting.create')}
                                    className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-semibold text-white transition hover:bg-sky-700"
                                >
                                    <Plus className="h-4 w-4" />
                                    Create profile
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
                                    Current broadcasting runtime
                                </h2>

                                <span
                                    className={`rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                                        ownership.mode === 'managed'
                                            ? 'bg-sky-50 text-sky-700 ring-sky-200'
                                            : 'bg-gray-100 text-gray-700 ring-gray-200'
                                    }`}
                                >
                                    {ownership.mode === 'managed'
                                        ? 'Managed'
                                        : 'Deployment'}
                                </span>
                            </div>

                            <p className="mt-2 max-w-3xl text-sm leading-6 text-gray-500">
                                {ownership.mode === 'managed'
                                    ? active_configuration
                                        ? `Newly booted processes use the managed profile “${active_configuration.name}”.`
                                        : 'Managed broadcasting ownership is recorded, but its active configuration is unavailable.'
                                    : 'Broadcasting remains controlled by the deployment configuration.'}
                            </p>

                            <div className="mt-5">
                                <DeploymentTargetNotice
                                    target={deployment_target}
                                />
                            </div>

                            {ownership.mode === 'managed' && canActivate ? (
                                <div className="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50/70">
                                    <div className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="flex items-start gap-3">
                                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white ring-1 ring-inset ring-gray-200">
                                                <Undo2 className="h-4 w-4 text-gray-600" />
                                            </span>

                                            <div>
                                                <p className="text-sm font-semibold text-gray-900">
                                                    Return control to deployment
                                                </p>

                                                <p className="mt-1 max-w-xl text-sm leading-6 text-gray-500">
                                                    Stop using the managed profile and return newly booted processes to the deployment-defined Broadcast connection.
                                                </p>
                                            </div>
                                        </div>

                                        <Button
                                            variant="outline"
                                            disabled={!deployment_target.available}
                                            onClick={() =>
                                                setConfirmAction({
                                                    kind: 'activate-deployment',
                                                })
                                            }
                                            className="shrink-0"
                                        >
                                            <Undo2 className="h-4 w-4" />
                                            Return to deployment
                                        </Button>
                                    </div>

                                    {deployment_target.available && canForceActivate ? (
                                        <div className="flex flex-col gap-3 border-t border-amber-200 bg-amber-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="flex items-start gap-2.5">
                                                <ShieldAlert className="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />

                                                <p className="max-w-xl text-xs leading-5 text-amber-800">
                                                    If the live deployment health check cannot pass but the target is known to be operational, an emergency return is available.
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setConfirmAction({
                                                        kind: 'force-activate-deployment',
                                                    })
                                                }
                                                className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-xl border border-amber-300 bg-white px-3 text-xs font-semibold text-amber-800 transition hover:bg-amber-100"
                                            >
                                                <ShieldAlert className="h-3.5 w-3.5" />
                                                Force return
                                            </button>
                                        </div>
                                    ) : null}
                                </div>
                            ) : null}
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <RuntimeValue
                                label="Configuration source"
                                value={
                                    ownership.mode === 'managed'
                                        ? 'Managed'
                                        : 'Deployment'
                                }
                            />

                            <RuntimeValue
                                label="Effective connection"
                                value={effective_connection || 'Unavailable'}
                            />

                            <RuntimeValue
                                label="Effective driver"
                                value={
                                    ownership.mode === 'managed'
                                        ? humanize(
                                            active_configuration?.driver ?? '',
                                        )
                                        : humanize(
                                            deployment_target.driver ?? '',
                                        )
                                }
                            />

                            <RuntimeValue
                                label="Active profile"
                                value={
                                    ownership.mode === 'managed'
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
                                    Activation affects newly booted processes
                                </p>

                                <p className="mt-1 max-w-5xl text-sm leading-6 text-amber-800">
                                    Changing ownership does not mutate the broadcaster inside the current request. SimpleDesk records the new ownership and signals queue workers to restart so new processes load the selected runtime.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <BrowserRealtimeProbe />

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <SectionHeader
                        icon={Server}
                        title="Managed providers"
                        description="Broadcasters currently registered for SimpleDesk-managed real-time delivery."
                    />

                    <div className="grid gap-3 p-5 sm:p-6 lg:grid-cols-3">
                        {definitions.map((definition) => (
                            <ProviderCard
                                key={definition.type}
                                definition={definition}
                            />
                        ))}
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div className="flex gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                                <Radio className="h-5 w-5 text-sky-700" />
                            </span>

                            <div>
                                <h2 className="font-semibold text-gray-900">
                                    Real-time profiles
                                </h2>

                                <p className="mt-1 text-sm leading-6 text-gray-500">
                                    Stored profiles remain inactive until explicitly activated.
                                </p>
                            </div>
                        </div>

                        <div className="w-full sm:w-48">
                            <Select
                                value={archivedFilter}
                                onValueChange={changeArchivedFilter}
                            >
                                <SelectTrigger className="h-10 w-full rounded-xl border-gray-200 bg-white">
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
                        </div>
                    </div>

                    {configurations.data.length === 0 ? (
                        <div className="px-6 py-14 text-center">
                            <Radio className="mx-auto h-9 w-9 text-gray-300" />

                            <p className="mt-3 font-medium text-gray-700">
                                No real-time profiles
                            </p>

                            <p className="mt-1 text-sm text-gray-500">
                                There are no profiles matching the current filter.
                            </p>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-100">
                            {configurations.data.map((configuration) => {
                                const result =
                                    health[configuration.id]
                                    ?? configuration.latest_health
                                    ?? null

                                const active =
                                    active_configuration?.id
                                    === configuration.id

                                const unhealthy =
                                    result !== null
                                    && result.status !== 'healthy'

                                return (
                                    <div
                                        key={configuration.id}
                                        className="p-5 sm:p-6"
                                    >
                                        <div className="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h3 className="font-semibold text-gray-900">
                                                        {configuration.name}
                                                    </h3>

                                                    <DriverBadge
                                                        driver={
                                                            configuration.driver
                                                        }
                                                    />

                                                    {active ? (
                                                        <span className="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">
                                                            Active
                                                        </span>
                                                    ) : null}

                                                    {configuration.archived_at ? (
                                                        <span className="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                                            Archived
                                                        </span>
                                                    ) : (
                                                        <span
                                                            className={`rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                                                                configuration.is_enabled
                                                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                                                    : 'bg-gray-100 text-gray-600 ring-gray-200'
                                                            }`}
                                                        >
                                                            {configuration.is_enabled
                                                                ? 'Enabled'
                                                                : 'Disabled'}
                                                        </span>
                                                    )}

                                                    <HealthBadge
                                                        result={result}
                                                    />
                                                </div>

                                                <div className="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-500">
                                                    <span>
                                                        Provider:{' '}
                                                        {humanize(
                                                            configuration.driver,
                                                        )}
                                                    </span>

                                                    {result ? (
                                                        <>
                                                            <span>
                                                                Latency:{' '}
                                                                {result.latency_ms}{' '}
                                                                ms
                                                            </span>

                                                            <span className="max-w-2xl">
                                                                {result.message}
                                                            </span>
                                                        </>
                                                    ) : (
                                                        <span>
                                                            Health has not been checked yet.
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap items-center gap-2">
                                                {can(
                                                    'admin.settings.broadcasting.test',
                                                )
                                                && !configuration.archived_at ? (
                                                    <Button
                                                        variant="outline"
                                                        disabled={
                                                            testing !== null
                                                        }
                                                        onClick={() =>
                                                            void test(
                                                                configuration,
                                                            )
                                                        }
                                                    >
                                                        <Activity className="h-4 w-4" />

                                                        {testing
                                                        === configuration.id
                                                            ? 'Testing…'
                                                            : 'Test'}
                                                    </Button>
                                                ) : null}

                                                {canActivate
                                                && configuration.is_enabled
                                                && !configuration.archived_at
                                                && !active ? (
                                                    <Button
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'activate',
                                                                configuration,
                                                            })
                                                        }
                                                    >
                                                        <Power className="h-4 w-4" />

                                                        {unhealthy
                                                            ? 'Retry & activate'
                                                            : 'Activate'}
                                                    </Button>
                                                ) : null}

                                                {canForceActivate
                                                && unhealthy
                                                && configuration.is_enabled
                                                && !configuration.archived_at
                                                && !active ? (
                                                    <Button
                                                        variant="destructive"
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'force-activate',
                                                                configuration,
                                                            })
                                                        }
                                                    >
                                                        <ShieldAlert className="h-4 w-4" />
                                                        Force activate
                                                    </Button>
                                                ) : null}

                                                {!active
                                                && !configuration.archived_at
                                                && can(
                                                    'admin.settings.broadcasting.update',
                                                ) ? (
                                                    <>
                                                        <Button
                                                            variant="outline"
                                                            onClick={() =>
                                                                setEnabled(
                                                                    configuration,
                                                                    !configuration.is_enabled,
                                                                )
                                                            }
                                                        >
                                                            {configuration.is_enabled ? (
                                                                <>
                                                                    <WifiOff className="h-4 w-4" />
                                                                    Disable
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <Wifi className="h-4 w-4" />
                                                                    Enable
                                                                </>
                                                            )}
                                                        </Button>

                                                        <Button
                                                            variant="outline"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={route(
                                                                    'admin.system.broadcasting.edit',
                                                                    {
                                                                        configuration:
                                                                        configuration.id,
                                                                    },
                                                                )}
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                    </>
                                                ) : null}

                                                {!active
                                                && !configuration.archived_at
                                                && can(
                                                    'admin.settings.broadcasting.archive',
                                                ) ? (
                                                    <Button
                                                        variant="outline"
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'archive',
                                                                configuration,
                                                            })
                                                        }
                                                    >
                                                        <Archive className="h-4 w-4" />
                                                        Archive
                                                    </Button>
                                                ) : null}

                                                {configuration.archived_at
                                                && can(
                                                    'admin.settings.broadcasting.archive',
                                                ) ? (
                                                    <Button
                                                        variant="outline"
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'restore',
                                                                configuration,
                                                            })
                                                        }
                                                    >
                                                        <RotateCcw className="h-4 w-4" />
                                                        Restore
                                                    </Button>
                                                ) : null}

                                                {configuration.archived_at
                                                && can(
                                                    'admin.settings.broadcasting.delete',
                                                ) ? (
                                                    <Button
                                                        variant="destructive"
                                                        onClick={() =>
                                                            setConfirmAction({
                                                                kind: 'delete',
                                                                configuration,
                                                            })
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                        Delete permanently
                                                    </Button>
                                                ) : null}
                                            </div>
                                        </div>
                                    </div>
                                )
                            })}
                        </div>
                    )}

                    {configurations.last_page > 1 ? (
                        <div className="flex items-center justify-between border-t border-gray-200 bg-gray-50/60 px-5 py-4 sm:px-6">
                            <p className="text-sm text-gray-500">
                                Page {configurations.current_page} of{' '}
                                {configurations.last_page}
                                {' · '}
                                {configurations.total} records
                            </p>

                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    disabled={
                                        !configurations.prev_page_url
                                    }
                                    onClick={() => {
                                        if (
                                            configurations.prev_page_url
                                        ) {
                                            router.visit(
                                                configurations.prev_page_url,
                                                {
                                                    preserveScroll: true,
                                                    preserveState: true,
                                                },
                                            )
                                        }
                                    }}
                                >
                                    Previous
                                </Button>

                                <Button
                                    variant="outline"
                                    disabled={
                                        !configurations.next_page_url
                                    }
                                    onClick={() => {
                                        if (
                                            configurations.next_page_url
                                        ) {
                                            router.visit(
                                                configurations.next_page_url,
                                                {
                                                    preserveScroll: true,
                                                    preserveState: true,
                                                },
                                            )
                                        }
                                    }}
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    ) : null}
                </section>
            </div>

            <ConfirmDialog
                action={confirmAction}
                acting={acting}
                onCancel={() => setConfirmAction(null)}
                onConfirm={executeConfirmed}
            />
        </AdminLayout>
    )
}

function DeploymentTargetNotice({
                                    target,
                                }: {
    target: DeploymentTarget
}) {
    if (!target.available) {
        return (
            <div className="rounded-2xl border border-red-200 bg-red-50 p-4">
                <div className="flex gap-3">
                    <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-red-700" />

                    <div>
                        <p className="text-sm font-semibold text-red-900">
                            Deployment target unavailable
                        </p>

                        <p className="mt-1 text-sm leading-6 text-red-700">
                            {target.message
                                ?? 'The deployment Broadcast configuration is invalid.'}
                        </p>
                    </div>
                </div>
            </div>
        )
    }

    return (
        <div className="rounded-2xl border border-gray-200 bg-gray-50 p-4">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Deployment target
                    </p>

                    <p className="mt-1 font-semibold text-gray-900">
                        {target.connection}
                    </p>

                    <p className="mt-1 text-sm text-gray-500">
                        {humanize(target.driver ?? '')}

                        {target.externally_delivering === false
                            ? ' · intentionally no external delivery'
                            : ''}
                    </p>
                </div>

                <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
            </div>
        </div>
    )
}

function ProviderCard({
                          definition,
                      }: {
    definition: Definition
}) {
    return (
        <div
            className={`rounded-2xl border p-4 ${
                definition.available
                    ? 'border-gray-200 bg-white'
                    : 'border-gray-200 bg-gray-50'
            }`}
        >
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="font-semibold text-gray-900">
                        {definition.name}
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        {definition.description}
                    </p>
                </div>

                {definition.available ? (
                    <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600" />
                ) : (
                    <AlertTriangle className="h-5 w-5 shrink-0 text-amber-600" />
                )}
            </div>

            <div className="mt-4">
                <span
                    className={`rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                        definition.available
                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                            : 'bg-gray-100 text-gray-600 ring-gray-200'
                    }`}
                >
                    {definition.available
                        ? 'Available'
                        : 'Unavailable'}
                </span>
            </div>

            {!definition.available
            && definition.unavailable_reason ? (
                <p className="mt-3 text-xs leading-5 text-gray-500">
                    {definition.unavailable_reason}
                </p>
            ) : null}
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
        <div className="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                {label}
            </p>

            <p className="mt-2 break-words text-sm font-semibold text-gray-900">
                {value || 'Unavailable'}
            </p>
        </div>
    )
}

function SectionHeader({
                           icon: Icon,
                           title,
                           description,
                       }: {
    icon: typeof Server
    title: string
    description: string
}) {
    return (
        <div className="flex gap-3 border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
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
    )
}

function DriverBadge({
                         driver,
                     }: {
    driver: string
}) {
    return (
        <span className="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
            {humanize(driver)}
        </span>
    )
}

function HealthBadge({
                         result,
                     }: {
    result: HealthResult | null
}) {
    if (!result) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                <RefreshCw className="h-3.5 w-3.5" />
                Not tested
            </span>
        )
    }

    const classes = {
        healthy:
            'bg-emerald-50 text-emerald-700 ring-emerald-200',
        degraded:
            'bg-amber-50 text-amber-700 ring-amber-200',
        unhealthy:
            'bg-red-50 text-red-700 ring-red-200',
        unavailable:
            'bg-gray-100 text-gray-700 ring-gray-200',
    }

    const icons: Record<string, ReactNode> = {
        healthy: (
            <CheckCircle2 className="h-3.5 w-3.5" />
        ),
        degraded: (
            <AlertTriangle className="h-3.5 w-3.5" />
        ),
        unhealthy: (
            <WifiOff className="h-3.5 w-3.5" />
        ),
        unavailable: (
            <Activity className="h-3.5 w-3.5" />
        ),
    }

    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${
                classes[
                    result.status as keyof typeof classes
                    ] ?? classes.unavailable
            }`}
        >
            {icons[result.status] ?? icons.unavailable}
            {humanize(result.status)}
        </span>
    )
}

function ConfirmDialog({
                           action,
                           acting,
                           onCancel,
                           onConfirm,
                       }: {
    action: ConfirmAction | null
    acting: boolean
    onCancel: () => void
    onConfirm: () => void
}) {
    const content = confirmationContent(action)
    const tone = confirmationTone(content.tone)

    return (
        <AlertDialog
            open={action !== null}
            onOpenChange={(open) => {
                if (!open && !acting) {
                    onCancel()
                }
            }}
        >
            <AlertDialogContent className="max-w-xl overflow-hidden rounded-[28px] border border-gray-200 bg-white p-0 shadow-2xl">
                <div
                    className={`border-b px-6 py-5 ${tone.header}`}
                >
                    <div className="flex items-start gap-4">
                        <span
                            className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-inset ${tone.iconRing}`}
                        >
                            {content.icon}
                        </span>

                        <AlertDialogHeader className="min-w-0 flex-1 space-y-0 text-left">
                            <AlertDialogTitle className="text-xl font-semibold tracking-tight text-gray-900">
                                {content.title}
                            </AlertDialogTitle>

                            <AlertDialogDescription className="mt-2 text-sm leading-6 text-gray-600">
                                {content.description}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                    </div>
                </div>

                <div className="px-6 py-5">
                    {content.notice ? (
                        <div
                            className={`mb-5 rounded-2xl border px-4 py-3 text-sm leading-6 ${tone.notice}`}
                        >
                            {content.notice}
                        </div>
                    ) : null}

                    <AlertDialogFooter className="gap-3">
                        <AlertDialogCancel
                            disabled={acting}
                            className="mt-0 h-11 cursor-pointer rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Cancel
                        </AlertDialogCancel>

                        <AlertDialogAction
                            disabled={acting}
                            onClick={(event) => {
                                event.preventDefault()
                                onConfirm()
                            }}
                            className={`h-11 cursor-pointer rounded-2xl px-5 text-sm font-semibold text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-60 ${tone.action}`}
                        >
                            {acting
                                ? 'Working…'
                                : content.action}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </div>
            </AlertDialogContent>
        </AlertDialog>
    )
}

function confirmationContent(
    action: ConfirmAction | null,
): {
    title: string
    description: string
    action: string
    tone: ConfirmationTone
    notice?: string
    icon: ReactNode
} {
    const name =
        action?.configuration?.name
        ?? 'this profile'

    switch (action?.kind) {
        case 'activate':
            return {
                title: `Activate ${name}?`,
                description:
                    'SimpleDesk will perform a live preflight check before switching managed broadcasting ownership.',
                action: 'Activate',
                tone: 'primary',
                notice:
                    'Queue workers will be signaled to restart so newly booted processes load this broadcaster.',
                icon: (
                    <Power className="h-5 w-5 text-sky-700" />
                ),
            }

        case 'force-activate':
            return {
                title: `Force activate ${name}?`,
                description:
                    'The live provider health result will be bypassed. Structural validation will still be enforced.',
                action: 'Force activate',
                tone: 'danger',
                notice:
                    'Use this only when the provider is known to be operational despite a failed health probe.',
                icon: (
                    <ShieldAlert className="h-5 w-5 text-red-700" />
                ),
            }

        case 'activate-deployment':
            return {
                title: 'Return broadcasting to deployment?',
                description:
                    'SimpleDesk will release managed broadcasting ownership and return runtime selection to the deployment configuration.',
                action: 'Return to deployment',
                tone: 'primary',
                notice:
                    'Newly booted processes will use the deployment-defined Broadcast connection.',
                icon: (
                    <Undo2 className="h-5 w-5 text-sky-700" />
                ),
            }

        case 'force-activate-deployment':
            return {
                title: 'Force return to deployment?',
                description:
                    'SimpleDesk will return broadcasting ownership to deployment even though the live health check cannot pass.',
                action: 'Force return',
                tone: 'warning',
                notice:
                    'Structural validation is still enforced. This operation should only be used when the deployment target is known to be operational.',
                icon: (
                    <ShieldAlert className="h-5 w-5 text-amber-700" />
                ),
            }

        case 'archive':
            return {
                title: `Archive ${name}?`,
                description:
                    'The profile will be removed from the active profile list and will no longer be available for activation.',
                action: 'Archive',
                tone: 'warning',
                notice:
                    'The profile can be restored later.',
                icon: (
                    <Archive className="h-5 w-5 text-amber-700" />
                ),
            }

        case 'restore':
            return {
                title: `Restore ${name}?`,
                description:
                    'The archived profile will be returned to the active records list.',
                action: 'Restore',
                tone: 'success',
                notice:
                    'The restored profile remains disabled until it is explicitly enabled.',
                icon: (
                    <RotateCcw className="h-5 w-5 text-emerald-700" />
                ),
            }

        case 'delete':
            return {
                title: `Permanently delete ${name}?`,
                description:
                    'The archived profile record will be permanently removed.',
                action: 'Delete permanently',
                tone: 'danger',
                notice:
                    'This action cannot be undone. System audit history remains retained.',
                icon: (
                    <Trash2 className="h-5 w-5 text-red-700" />
                ),
            }

        default:
            return {
                title: 'Confirm action',
                description:
                    'Confirm this operation before continuing.',
                action: 'Confirm',
                tone: 'primary',
                icon: (
                    <CheckCircle2 className="h-5 w-5 text-sky-700" />
                ),
            }
    }
}

function confirmationTone(tone: ConfirmationTone) {
    switch (tone) {
        case 'danger':
            return {
                header:
                    'border-red-100 bg-gradient-to-r from-red-50 via-red-50/60 to-white',
                iconRing: 'ring-red-200',
                notice:
                    'border-red-200 bg-red-50 text-red-800',
                action:
                    'bg-red-600 hover:bg-red-700',
            }

        case 'warning':
            return {
                header:
                    'border-amber-100 bg-gradient-to-r from-amber-50 via-amber-50/60 to-white',
                iconRing: 'ring-amber-200',
                notice:
                    'border-amber-200 bg-amber-50 text-amber-800',
                action:
                    'bg-amber-600 hover:bg-amber-700',
            }

        case 'success':
            return {
                header:
                    'border-emerald-100 bg-gradient-to-r from-emerald-50 via-emerald-50/60 to-white',
                iconRing: 'ring-emerald-200',
                notice:
                    'border-emerald-200 bg-emerald-50 text-emerald-800',
                action:
                    'bg-emerald-600 hover:bg-emerald-700',
            }

        default:
            return {
                header:
                    'border-sky-100 bg-gradient-to-r from-sky-50 via-sky-50/60 to-white',
                iconRing: 'ring-sky-200',
                notice:
                    'border-sky-200 bg-sky-50 text-sky-800',
                action:
                    'bg-sky-600 hover:bg-sky-700',
            }
    }
}

function humanize(value: string) {
    if (!value) {
        return 'Unavailable'
    }

    return value
        .replace(/[_-]+/g, ' ')
        .replace(
            /\b\w/g,
            (character) => character.toUpperCase(),
        )
}

function getArchivedFilter() {
    if (typeof window === 'undefined') {
        return 'active'
    }

    const value =
        new URLSearchParams(
            window.location.search,
        ).get('archived')

    return value === 'archived'
    || value === 'all'
        ? value
        : 'active'
}

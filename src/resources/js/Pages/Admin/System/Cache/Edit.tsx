import { useState } from 'react'
import {
    Head,
    Link,
} from '@inertiajs/react'
import {
    AlertTriangle,
    ArrowLeft,
    Info,
    Pencil,
} from 'lucide-react'
import { route } from 'ziggy-js'

import { usePermissions } from '@/hooks/usePermissions'
import AdminLayout from '@/Layouts/AdminLayout'

import CacheConfigurationForm from './CacheConfigurationForm'
import {
    CacheDriverBadge,
    CacheHealthBadge,
} from './components/CacheBadges'
import CacheTestButton from './components/CacheTestButton'
import type {
    CacheConfiguration,
    CacheDefinition,
    CacheHealthResult,
    InfrastructureOption,
} from './cacheTypes'

type Props = {
    configuration: CacheConfiguration
    definitions: CacheDefinition[]
    redis_connections: InfrastructureOption[]
}

export default function Edit({
                                 configuration,
                                 definitions,
                                 redis_connections,
                             }: Props) {
    const { can } = usePermissions()

    const [testResult, setTestResult] =
        useState<CacheHealthResult | null>(null)

    const health =
        testResult
        ?? configuration.latest_health_check

    const canTest = can(
        'admin.settings.cache.test',
    )

    return (
        <AdminLayout title="Edit Cache configuration">
            <Head title="Edit Cache configuration" />

            <div className="space-y-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex min-w-0 items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <Pencil className="h-6 w-6 text-sky-700" />
                            </span>

                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Edit Cache configuration
                                    </h1>

                                    <CacheDriverBadge
                                        driver={
                                            configuration.driver
                                        }
                                    />

                                    {configuration.is_active ? (
                                        <span className="inline-flex rounded-full bg-sky-600 px-2.5 py-1 text-xs font-semibold text-white">
                                            Active
                                        </span>
                                    ) : null}

                                    {!configuration.is_enabled ? (
                                        <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                            Disabled
                                        </span>
                                    ) : null}
                                </div>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                    Update{' '}
                                    <span className="font-semibold text-gray-700">
                                        {configuration.name}
                                    </span>
                                    . Saving profile changes does not activate it or immediately modify the running Cache runtime.
                                </p>

                                <div className="mt-3 flex flex-wrap items-center gap-3">
                                    <CacheHealthBadge
                                        status={
                                            health?.status
                                        }
                                    />

                                    {health ? (
                                        <p className="min-w-0 text-xs leading-5 text-gray-500">
                                            <span className="text-gray-600">
                                                {health.message}
                                            </span>

                                            {health.latency_ms !== null
                                            && health.latency_ms !== undefined ? (
                                                <>
                                                    {' · '}
                                                    {health.latency_ms}
                                                    {' ms'}
                                                </>
                                            ) : null}

                                            {testResult ? (
                                                <>
                                                    {' · '}
                                                    <span className="font-medium text-sky-700">
                                                        Just tested
                                                    </span>
                                                </>
                                            ) : null}
                                        </p>
                                    ) : (
                                        <p className="text-xs text-gray-400">
                                            This configuration has not been tested yet.
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {canTest ? (
                                <CacheTestButton
                                    configurationId={
                                        configuration.id
                                    }
                                    onResult={
                                        setTestResult
                                    }
                                />
                            ) : null}

                            <Link
                                href={route(
                                    'admin.system.cache.index',
                                )}
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Back to Cache
                            </Link>
                        </div>
                    </div>

                    <div className="border-t border-sky-100 bg-sky-50/60 px-6 py-4">
                        <div className="flex items-start gap-3">
                            <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white ring-1 ring-inset ring-sky-200">
                                <Info className="h-4 w-4 text-sky-700" />
                            </span>

                            <div>
                                <p className="text-sm font-semibold text-sky-950">
                                    Health testing uses persisted settings
                                </p>

                                <p className="mt-1 max-w-4xl text-sm leading-6 text-sky-800/80">
                                    {configuration.is_active
                                        ? 'Testing checks the persisted active profile without changing Cache ownership or runtime configuration.'
                                        : 'If you change the form, save it before testing when you want the health check to use the new values.'}
                                </p>
                            </div>
                        </div>
                    </div>
                </header>

                {configuration.is_active ? (
                    <>
                        <section className="overflow-hidden rounded-[28px] border border-amber-200 bg-white shadow-sm">
                            <div className="flex gap-4 bg-amber-50 px-5 py-5 sm:px-6">
                                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-amber-200">
                                    <AlertTriangle className="h-5 w-5 text-amber-700" />
                                </span>

                                <div>
                                    <h2 className="font-semibold text-amber-950">
                                        Active configuration is read-only
                                    </h2>

                                    <p className="mt-1 max-w-4xl text-sm leading-6 text-amber-800">
                                        This profile currently owns the managed Cache runtime. Runtime-affecting edits, disabling, archiving, and deletion are blocked until another profile is activated or Cache ownership is returned to deployment configuration.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <ReadOnlyConfiguration
                            configuration={
                                configuration
                            }
                        />
                    </>
                ) : (
                    <CacheConfigurationForm
                        definitions={
                            definitions
                        }
                        redisConnections={
                            redis_connections
                        }
                        configuration={
                            configuration
                        }
                    />
                )}
            </div>
        </AdminLayout>
    )
}

function ReadOnlyConfiguration({
                                   configuration,
                               }: {
    configuration: CacheConfiguration
}) {
    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                <h2 className="font-semibold text-gray-900">
                    Persisted configuration
                </h2>

                <p className="mt-1 text-sm leading-6 text-gray-500">
                    Current values are shown for inspection only.
                </p>
            </div>

            <div className="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
                <ReadOnlyValue
                    label="Name"
                    value={
                        configuration.name
                    }
                />

                <ReadOnlyValue
                    label="Driver"
                    value={
                        humanize(
                            configuration.driver,
                        )
                    }
                />

                <ReadOnlyValue
                    label="State"
                    value={
                        configuration.is_enabled
                            ? 'Enabled'
                            : 'Disabled'
                    }
                />

                {configuration.driver === 'database' ? (
                    <ReadOnlyValue
                        label="Database connection"
                        value={
                            configuration.configuration
                                .database_connection
                            ?? 'Unavailable'
                        }
                    />
                ) : null}

                {configuration.driver === 'file' ? (
                    <>
                        <ReadOnlyValue
                            label="Cache data"
                            value="SimpleDesk-managed profile directory"
                        />

                        <ReadOnlyValue
                            label="Atomic locks"
                            value="Separate SimpleDesk-managed lock directory"
                        />
                    </>
                ) : null}

                {configuration.driver === 'redis' ? (
                    <>
                        <ReadOnlyValue
                            label="Infrastructure connection"
                            value={
                                configuration.infrastructure_connection
                                    ?.name
                                ?? 'Unavailable'
                            }
                        />

                        <ReadOnlyValue
                            label="Infrastructure source"
                            value={
                                configuration.infrastructure_connection
                                    ?.source
                                    ? humanize(
                                        configuration.infrastructure_connection
                                            .source,
                                    )
                                    : 'Unavailable'
                            }
                        />
                    </>
                ) : null}
            </div>
        </section>
    )
}

function ReadOnlyValue({
                           label,
                           value,
                       }: {
    label: string
    value: string
}) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                {label}
            </p>

            <p className="mt-1 break-words text-sm font-semibold text-gray-800">
                {value}
            </p>
        </div>
    )
}

function humanize(
    value: string,
): string {
    return value
        .replace(/[._-]+/g, ' ')
        .replace(
            /\b\w/g,
            (letter) => letter.toUpperCase(),
        )
}

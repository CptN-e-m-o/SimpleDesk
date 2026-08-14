import {
    useState,
} from 'react'

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

import AdminLayout from '@/Layouts/AdminLayout'
import { usePermissions } from '@/hooks/usePermissions'

import QueueConfigurationForm from './QueueConfigurationForm'

import QueueTestButton from './components/QueueTestButton'

import {
    QueueDriverBadge,
    QueueHealthBadge,
} from './components/QueueBadges'

import type {
    QueueConfiguration,
    QueueDriverDefinition,
    QueueHealthResult,
    RedisConnection,
} from './queueTypes'

type Props = {
    configuration:
        QueueConfiguration

    definitions:
        QueueDriverDefinition[]

    redis_connections:
        RedisConnection[]

    defaults: {
        minimum_retry_after: number
    }
}

export default function Edit({
                                 configuration,
                                 definitions,
                                 redis_connections,
                                 defaults,
                             }: Props) {
    const { can } =
        usePermissions()

    const [
        testResult,
        setTestResult,
    ] = useState<
        QueueHealthResult | null
    >(null)

    const health =
        testResult
        ?? configuration
            .latest_health_check

    const canTest =
        can(
            'admin.settings.queues.test',
        )

    return (
        <AdminLayout title="Edit queue configuration">
            <Head title="Edit queue configuration" />

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
                                        Edit queue configuration
                                    </h1>

                                    <QueueDriverBadge
                                        driver={
                                            configuration.driver
                                        }
                                    />

                                    {configuration
                                        .is_active ? (
                                        <span className="inline-flex rounded-full bg-sky-600 px-2.5 py-1 text-xs font-semibold text-white">
                                            Active
                                        </span>
                                    ) : null}

                                    {configuration
                                        .deleted_at ? (
                                        <span className="inline-flex rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                            Archived
                                        </span>
                                    ) : null}
                                </div>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                    Update{' '}

                                    <span className="font-semibold text-gray-700">
                                        {configuration.name}
                                    </span>

                                    . Changes are stored in this
                                    managed profile and do not
                                    change the running Queue
                                    backend.
                                </p>

                                <div className="mt-3 flex flex-wrap items-center gap-3">
                                    <QueueHealthBadge
                                        status={
                                            health
                                                ?.status
                                        }
                                    />

                                    {health ? (
                                        <p className="min-w-0 text-xs leading-5 text-gray-500">
                                            <span className="text-gray-600">
                                                {
                                                    health.message
                                                }
                                            </span>

                                            {health
                                                .latency_ms
                                            !== null ? (
                                                <>
                                                    {' · '}

                                                    {
                                                        health.latency_ms
                                                    }

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
                                            This configuration has
                                            not been tested yet.
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {canTest ? (
                                <QueueTestButton
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
                                    'admin.system.queues.index',
                                )}
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <ArrowLeft className="h-4 w-4" />

                                Back to queues
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
                                    Health testing uses the
                                    persisted configuration.
                                </p>

                                <p className="mt-1 max-w-4xl text-sm leading-6 text-sky-800/80">
                                    {configuration.is_active
                                        ? 'Testing checks the currently stored settings and does not modify the active Queue runtime.'
                                        : 'Save any form changes before testing if you want the health check to use those new values.'}
                                </p>
                            </div>
                        </div>
                    </div>
                </header>

                {configuration.is_active ? (
                    <section className="overflow-hidden rounded-[28px] border border-amber-200 bg-white shadow-sm">
                        <div className="flex gap-4 bg-amber-50 px-5 py-5 sm:px-6">
                            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-amber-200">
                                <AlertTriangle className="h-5 w-5 text-amber-700" />
                            </span>

                            <div>
                                <h2 className="font-semibold text-amber-950">
                                    Active configuration is
                                    read-only
                                </h2>

                                <p className="mt-1 max-w-4xl text-sm leading-6 text-amber-800">
                                    This profile currently backs
                                    the managed Queue runtime.
                                    Editing it is blocked until
                                    safe runtime reconfiguration
                                    and worker restart
                                    orchestration are available.
                                    You can still test its
                                    persisted settings above.
                                </p>
                            </div>
                        </div>
                    </section>
                ) : (
                    <QueueConfigurationForm
                        definitions={
                            definitions
                        }
                        redisConnections={
                            redis_connections
                        }
                        minimumRetryAfter={
                            defaults
                                .minimum_retry_after
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

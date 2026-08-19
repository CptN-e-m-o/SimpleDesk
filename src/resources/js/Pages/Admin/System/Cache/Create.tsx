import {
    Head,
    Link,
} from '@inertiajs/react'
import {
    ArrowLeft,
    Info,
    PlusCircle,
} from 'lucide-react'
import { route } from 'ziggy-js'

import AdminLayout from '@/Layouts/AdminLayout'

import CacheConfigurationForm from './CacheConfigurationForm'
import type {
    CacheDefinition,
    InfrastructureOption,
} from './cacheTypes'

type Props = {
    definitions: CacheDefinition[]
    redis_connections: InfrastructureOption[]
}

export default function Create({
                                   definitions,
                                   redis_connections,
                               }: Props) {
    return (
        <AdminLayout title="Create Cache configuration">
            <Head title="Create Cache configuration" />

            <div className="space-y-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <PlusCircle className="h-6 w-6 text-sky-700" />
                            </span>

                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Create Cache configuration
                                    </h1>

                                    <span className="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                        Managed profile
                                    </span>
                                </div>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                    Prepare a reusable managed Cache profile without changing the currently running Cache backend.
                                </p>
                            </div>
                        </div>

                        <Link
                            href={route(
                                'admin.system.cache.index',
                            )}
                            className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Cache
                        </Link>
                    </div>

                    <div className="border-t border-sky-100 bg-sky-50/60 px-6 py-4">
                        <div className="flex items-start gap-3">
                            <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white ring-1 ring-inset ring-sky-200">
                                <Info className="h-4 w-4 text-sky-700" />
                            </span>

                            <div>
                                <p className="text-sm font-semibold text-sky-950">
                                    Creating a profile does not activate it
                                </p>

                                <p className="mt-1 max-w-4xl text-sm leading-6 text-sky-800/80">
                                    The current deployment or managed Cache runtime remains unchanged. Activation is a separate operation with health preflight and worker restart signaling.
                                </p>
                            </div>
                        </div>
                    </div>
                </header>

                <CacheConfigurationForm
                    definitions={definitions}
                    redisConnections={
                        redis_connections
                    }
                />
            </div>
        </AdminLayout>
    )
}

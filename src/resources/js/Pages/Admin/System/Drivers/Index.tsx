import { Head, Link } from '@inertiajs/react'
import {
    ArrowRight,
    Cable,
    CheckCircle2,
    Database,
    HardDrive,
    Info,
    Radio,
    Search,
    ServerCog,
    TriangleAlert,
    Workflow,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { route } from 'ziggy-js'

import { usePermissions } from '@/hooks/usePermissions'
import AdminLayout from '@/Layouts/AdminLayout'

type DriverCategoryKey =
    | 'queue'
    | 'cache'
    | 'broadcasting'
    | 'search'
    | 'storage'

type ImplementedCategoryKey =
    | 'queue'
    | 'cache'

type DriverCategory = {
    key: DriverCategoryKey
    name: string
    description: string
    details: string
    examples: string[]
    icon: LucideIcon
    implemented: boolean
}

type DriverState = {
    mode:
        | 'deployment'
        | 'managed'
        | 'unavailable'
    active_configuration: string | null
    active_driver: string | null
    requires_attention: boolean
}

type Props = {
    categories: string[]
    states: Partial<
        Record<
            ImplementedCategoryKey,
            DriverState
        >
    >
}

const categoryDefinitions: Record<
    DriverCategoryKey,
    DriverCategory
> = {
    queue: {
        key: 'queue',
        name: 'Queues',
        description:
            'Deliver asynchronous application work reliably.',
        details:
            'Controls how background jobs are dispatched and processed across SimpleDesk workloads.',
        examples: [
            'Database',
            'Redis',
            'Sync',
            'Amazon SQS',
            'Beanstalkd',
        ],
        icon: Workflow,
        implemented: true,
    },

    cache: {
        key: 'cache',
        name: 'Cache',
        description:
            'Accelerate reads and coordinate shared application state.',
        details:
            'Provides application caching, atomic locks, rate-limit coordination, and other shared runtime state.',
        examples: [
            'Database',
            'File',
            'Redis',
        ],
        icon: Database,
        implemented: true,
    },

    broadcasting: {
        key: 'broadcasting',
        name: 'Real-time',
        description:
            'Broadcast live application events to connected clients.',
        details:
            'Provides real-time updates for tickets, notifications, presence, and other interactive features.',
        examples: [
            'Laravel Reverb',
            'Pusher',
            'Ably',
        ],
        icon: Radio,
        implemented: false,
    },

    search: {
        key: 'search',
        name: 'Search',
        description:
            'Power indexed discovery across SimpleDesk data.',
        details:
            'Controls how searchable documents are indexed and queried while the database remains the source of truth.',
        examples: [
            'Database',
            'Meilisearch',
            'Typesense',
            'Algolia',
        ],
        icon: Search,
        implemented: false,
    },

    storage: {
        key: 'storage',
        name: 'Storage',
        description:
            'Store private files, exports, and application objects.',
        details:
            'Controls where SimpleDesk persists attachments and other file-backed resources.',
        examples: [
            'Local storage',
            'S3-compatible storage',
        ],
        icon: HardDrive,
        implemented: false,
    },
}

export default function Index({
                                  categories,
                                  states,
                              }: Props) {
    const { can } = usePermissions()

    const visibleCategories =
        categories
            .map((category) =>
                getCategoryDefinition(
                    category,
                ),
            )
            .filter(
                (
                    category,
                ): category is DriverCategory =>
                    category !== null,
            )

    return (
        <AdminLayout title="System Drivers">
            <Head title="System Drivers" />

            <div className="space-y-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white px-6 py-5 lg:flex-row lg:items-center">
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <ServerCog className="h-6 w-6 text-sky-700" />
                            </div>

                            <div>
                                <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                    System Drivers
                                </h1>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                    Configure how SimpleDesk subsystems use infrastructure resources for queues, caching, real-time communication, search, and file storage.
                                </p>
                            </div>
                        </div>

                        {can(
                            'admin.settings.infrastructure_connections.view',
                        ) ? (
                            <Link
                                href={route(
                                    'admin.system.connections.index',
                                )}
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <Cable className="h-4 w-4" />
                                Infrastructure connections
                            </Link>
                        ) : null}
                    </div>

                    <div className="border-t border-gray-200 bg-gray-50/60 px-6 py-4">
                        <div className="flex items-start gap-3">
                            <Info className="mt-0.5 h-4 w-4 shrink-0 text-sky-600" />

                            <p className="text-sm leading-6 text-gray-600">
                                Deployment ownership remains unchanged until a subsystem is explicitly switched to a managed configuration. Queue and Cache can be managed independently.
                            </p>
                        </div>
                    </div>
                </header>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
                        <h2 className="font-semibold text-gray-900">
                            Driver categories
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-gray-500">
                            Each subsystem owns its driver configuration while reusing shared Infrastructure Connections where appropriate.
                        </p>
                    </div>

                    {visibleCategories.length > 0 ? (
                        <div className="grid gap-5 p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-3">
                            {visibleCategories.map(
                                (category) => (
                                    <CategoryCard
                                        key={
                                            category.key
                                        }
                                        category={
                                            category
                                        }
                                        state={
                                            isImplementedCategory(
                                                category.key,
                                            )
                                                ? states[
                                                    category.key
                                                    ]
                                                : undefined
                                        }
                                        accessible={
                                            canOpenCategory(
                                                category.key,
                                                can,
                                            )
                                        }
                                    />
                                ),
                            )}
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                                <ServerCog className="h-7 w-7 text-gray-400" />
                            </div>

                            <h3 className="mt-4 font-semibold text-gray-900">
                                No driver categories available
                            </h3>

                            <p className="mt-1 max-w-md text-sm leading-6 text-gray-500">
                                SimpleDesk did not expose any supported System Driver categories.
                            </p>
                        </div>
                    )}
                </section>
            </div>
        </AdminLayout>
    )
}

function CategoryCard({
                          category,
                          state,
                          accessible,
                      }: {
    category: DriverCategory
    state?: DriverState
    accessible: boolean
}) {
    const Icon = category.icon

    const href =
        category.key === 'queue'
            ? route(
                'admin.system.queues.index',
            )
            : category.key === 'cache'
                ? route(
                    'admin.system.cache.index',
                )
                : null

    return (
        <article
            className={`group relative flex min-h-[310px] flex-col overflow-hidden rounded-[24px] border bg-white transition ${
                accessible
                    ? 'border-gray-200 hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md'
                    : 'border-gray-200'
            }`}
        >
            {accessible && href ? (
                <Link
                    href={href}
                    aria-label={`Open ${category.name} management`}
                    className="absolute inset-0 z-10"
                />
            ) : null}

            <div className="flex flex-1 flex-col p-5">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100 transition group-hover:bg-sky-100">
                        <Icon className="h-6 w-6 text-sky-600" />
                    </div>

                    <CategoryStatus
                        category={
                            category
                        }
                        state={
                            state
                        }
                    />
                </div>

                <div className="mt-5">
                    <h3 className="text-lg font-semibold text-gray-900">
                        {category.name}
                    </h3>

                    <p className="mt-1 text-sm font-medium text-gray-600">
                        {category.description}
                    </p>

                    <p className="mt-3 text-sm leading-6 text-gray-500">
                        {category.details}
                    </p>
                </div>

                <div className="mt-5 flex flex-wrap gap-2">
                    {category.examples.map(
                        (example) => (
                            <span
                                key={
                                    example
                                }
                                className="rounded-lg bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200"
                            >
                                {example}
                            </span>
                        ),
                    )}
                </div>
            </div>

            <div className="flex min-h-[65px] items-center justify-between gap-4 border-t border-gray-100 bg-gray-50/60 px-5 py-4">
                <CategorySummary
                    category={
                        category
                    }
                    state={
                        state
                    }
                />

                <ArrowRight
                    className={`h-4 w-4 shrink-0 ${
                        accessible
                            ? 'text-sky-500'
                            : 'text-gray-300'
                    }`}
                />
            </div>
        </article>
    )
}

function CategoryStatus({
                            category,
                            state,
                        }: {
    category: DriverCategory
    state?: DriverState
}) {
    if (!category.implemented) {
        return (
            <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 ring-1 ring-inset ring-gray-200">
                Planned
            </span>
        )
    }

    if (
        !state
        || state.mode
        === 'unavailable'
    ) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                <Info className="h-3.5 w-3.5" />
                Unavailable
            </span>
        )
    }

    if (
        state.requires_attention
    ) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                <TriangleAlert className="h-3.5 w-3.5" />
                Requires attention
            </span>
        )
    }

    if (
        state.mode
        === 'managed'
    ) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">
                <CheckCircle2 className="h-3.5 w-3.5" />
                Managed
            </span>
        )
    }

    return (
        <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
            Deployment
        </span>
    )
}

function CategorySummary({
                             category,
                             state,
                         }: {
    category: DriverCategory
    state?: DriverState
}) {
    if (!category.implemented) {
        return (
            <div className="min-w-0">
                <p className="text-xs font-medium text-gray-400">
                    Management UI not implemented yet
                </p>
            </div>
        )
    }

    if (
        !state
        || state.mode
        === 'unavailable'
    ) {
        return (
            <div className="min-w-0">
                <p className="text-xs font-medium text-gray-500">
                    Runtime state unavailable
                </p>
            </div>
        )
    }

    if (
        state.requires_attention
    ) {
        return (
            <div className="min-w-0">
                <p className="text-xs font-semibold text-red-700">
                    Managed ownership is inconsistent
                </p>

                <p className="mt-0.5 truncate text-xs text-red-600">
                    Review subsystem configuration
                </p>
            </div>
        )
    }

    if (
        state.mode
        === 'managed'
    ) {
        return (
            <div className="min-w-0">
                <p className="text-xs font-semibold text-gray-600">
                    {state.active_configuration
                        ?? 'Managed configuration'}
                </p>

                {state.active_driver ? (
                    <p className="mt-0.5 truncate text-xs text-gray-400">
                        {humanize(
                            state.active_driver,
                        )}
                    </p>
                ) : null}
            </div>
        )
    }

    return (
        <div className="min-w-0">
            <p className="text-xs font-medium text-gray-500">
                Deployment configuration owns runtime
            </p>
        </div>
    )
}

function canOpenCategory(
    key: DriverCategoryKey,
    can: (
        permission: string,
    ) => boolean,
): boolean {
    if (key === 'queue') {
        return can(
            'admin.settings.queues.view',
        )
    }

    if (key === 'cache') {
        return can(
            'admin.settings.cache.view',
        )
    }

    return false
}

function getCategoryDefinition(
    value: string,
): DriverCategory | null {
    if (
        !isDriverCategoryKey(value)
    ) {
        return null
    }

    return categoryDefinitions[
        value
        ]
}

function isDriverCategoryKey(
    value: string,
): value is DriverCategoryKey {
    return Object.prototype.hasOwnProperty.call(
        categoryDefinitions,
        value,
    )
}

function isImplementedCategory(
    value: DriverCategoryKey,
): value is ImplementedCategoryKey {
    return (
        value === 'queue'
        || value === 'cache'
    )
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

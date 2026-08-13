import AdminLayout from '@/Layouts/AdminLayout'
import { usePermissions } from '@/hooks/usePermissions'
import {
    Head,
    Link,
} from '@inertiajs/react'
import {
    ArrowRight,
    Cable,
    Database,
    HardDrive,
    Info,
    Radio,
    Search,
    ServerCog,
    Workflow,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { route } from 'ziggy-js'

type DriverCategoryKey =
    | 'queue'
    | 'cache'
    | 'broadcasting'
    | 'search'
    | 'storage'

type DriverCategory = {
    key: DriverCategoryKey
    name: string
    description: string
    details: string
    examples: string[]
    icon: LucideIcon
}

type Props = {
    categories: string[]
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
            'Amazon SQS',
            'Beanstalkd',
            'Sync',
        ],
        icon: Workflow,
    },

    cache: {
        key: 'cache',
        name: 'Cache',
        description:
            'Accelerate reads and coordinate shared application state.',
        details:
            'Provides application caching, locks, rate-limiting coordination, and other shared runtime state.',
        examples: [
            'File',
            'Database',
            'Redis',
            'Memcached',
            'DynamoDB',
        ],
        icon: Database,
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
    },
}

export default function Index({
                                  categories,
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
                                    Configure how SimpleDesk
                                    subsystems use infrastructure
                                    resources for queues, caching,
                                    real-time communication, search,
                                    and file storage.
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
                                Until a subsystem is explicitly
                                configured through System Drivers,
                                its existing deployment
                                configuration remains unchanged.
                            </p>
                        </div>
                    </div>
                </header>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
                        <div>
                            <h2 className="font-semibold text-gray-900">
                                Driver categories
                            </h2>

                            <p className="mt-1 text-sm leading-6 text-gray-500">
                                Each category owns its own driver
                                configuration while reusing shared
                                infrastructure connections where
                                appropriate.
                            </p>
                        </div>
                    </div>

                    {visibleCategories.length > 0 ? (
                        <div className="grid gap-5 p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-3">
                            {visibleCategories.map(
                                ({
                                     key,
                                     name,
                                     description,
                                     details,
                                     examples,
                                     icon: Icon,
                                 }) => (
                                    <article
                                        key={key}
                                        className="group flex min-h-[290px] flex-col overflow-hidden rounded-[24px] border border-gray-200 bg-white transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md"
                                    >
                                        <div className="flex flex-1 flex-col p-5">
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100 transition group-hover:bg-sky-100">
                                                    <Icon className="h-6 w-6 text-sky-600" />
                                                </div>

                                                <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 ring-1 ring-inset ring-gray-200">
                                                    System-managed
                                                    setup inactive
                                                </span>
                                            </div>

                                            <div className="mt-5">
                                                <h3 className="text-lg font-semibold text-gray-900">
                                                    {name}
                                                </h3>

                                                <p className="mt-1 text-sm font-medium text-gray-600">
                                                    {description}
                                                </p>

                                                <p className="mt-3 text-sm leading-6 text-gray-500">
                                                    {details}
                                                </p>
                                            </div>

                                            <div className="mt-5 flex flex-wrap gap-2">
                                                {examples.map(
                                                    (
                                                        example,
                                                    ) => (
                                                        <span
                                                            key={
                                                                example
                                                            }
                                                            className="rounded-lg bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200"
                                                        >
                                                            {
                                                                example
                                                            }
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between border-t border-gray-100 bg-gray-50/60 px-5 py-4">
                                            <span className="text-xs font-medium text-gray-400">
                                                Managed configuration
                                                is not active yet
                                            </span>

                                            <ArrowRight className="h-4 w-4 text-gray-300" />
                                        </div>
                                    </article>
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
                                SimpleDesk did not expose any
                                supported system driver categories.
                            </p>
                        </div>
                    )}
                </section>
            </div>
        </AdminLayout>
    )
}

function getCategoryDefinition(
    value: string,
): DriverCategory | null {
    if (
        !isDriverCategoryKey(value)
    ) {
        return null
    }

    return categoryDefinitions[value]
}

function isDriverCategoryKey(
    value: string,
): value is DriverCategoryKey {
    return Object.prototype.hasOwnProperty.call(
        categoryDefinitions,
        value,
    )
}

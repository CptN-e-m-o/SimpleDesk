import {
    AlertTriangle,
    CheckCircle2,
    CircleHelp,
    Database,
    FileArchive,
    Server,
    XCircle,
} from 'lucide-react'

import type {
    CacheDriverType,
    CacheHealthStatus,
} from '../cacheTypes'

export function CacheDriverBadge({
                                     driver,
                                 }: {
    driver: CacheDriverType
}) {
    const definitions = {
        database: {
            label: 'Database',
            icon: Database,
        },
        file: {
            label: 'File',
            icon: FileArchive,
        },
        redis: {
            label: 'Redis',
            icon: Server,
        },
    } satisfies Record<
        CacheDriverType,
        {
            label: string
            icon: typeof Database
        }
    >

    const definition = definitions[driver]
    const Icon = definition.icon

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
            <Icon className="h-3.5 w-3.5" />
            {definition.label}
        </span>
    )
}

export function CacheHealthBadge({
                                     status,
                                 }: {
    status?: CacheHealthStatus | null
}) {
    if (!status) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 ring-1 ring-inset ring-gray-200">
                <CircleHelp className="h-3.5 w-3.5" />
                Not tested
            </span>
        )
    }

    const definitions = {
        healthy: {
            label: 'Healthy',
            icon: CheckCircle2,
            className:
                'bg-emerald-50 text-emerald-700 ring-emerald-200',
        },
        degraded: {
            label: 'Degraded',
            icon: AlertTriangle,
            className:
                'bg-amber-50 text-amber-700 ring-amber-200',
        },
        unhealthy: {
            label: 'Unhealthy',
            icon: XCircle,
            className:
                'bg-red-50 text-red-700 ring-red-200',
        },
        unavailable: {
            label: 'Unavailable',
            icon: CircleHelp,
            className:
                'bg-gray-100 text-gray-600 ring-gray-200',
        },
    } satisfies Record<
        CacheHealthStatus,
        {
            label: string
            icon: typeof CheckCircle2
            className: string
        }
    >

    const definition = definitions[status]
    const Icon = definition.icon

    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${definition.className}`}
        >
            <Icon className="h-3.5 w-3.5" />
            {definition.label}
        </span>
    )
}

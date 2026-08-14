import {
    AlertTriangle,
    CheckCircle2,
    CircleHelp,
    Database,
    Gauge,
    ServerOff,
    Workflow,
} from 'lucide-react'

import type {
    LucideIcon,
} from 'lucide-react'

import type {
    QueueDriver,
    QueueHealthStatus,
} from '../queueTypes'

type BadgeDefinition = {
    label: string
    icon: LucideIcon
    className: string
}

const driverBadges = {
    database: {
        label: 'Database',
        icon: Database,
        className:
            'bg-indigo-50 text-indigo-700 ring-indigo-200',
    },

    redis: {
        label: 'Redis',
        icon: Gauge,
        className:
            'bg-rose-50 text-rose-700 ring-rose-200',
    },

    sync: {
        label: 'Sync',
        icon: Workflow,
        className:
            'bg-amber-50 text-amber-700 ring-amber-200',
    },
} satisfies Record<
    QueueDriver,
    BadgeDefinition
>

const healthBadges = {
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
        icon: ServerOff,
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
    QueueHealthStatus,
    BadgeDefinition
>

const neverTestedBadge:
    BadgeDefinition = {
    label:
        'Never tested',

    icon:
    CircleHelp,

    className:
        'bg-gray-100 text-gray-600 ring-gray-200',
}

export function QueueDriverBadge({
                                     driver,
                                 }: {
    driver: QueueDriver
}) {
    const details =
        driverBadges[
            driver
            ]

    return (
        <Badge
            details={
                details
            }
            ariaLabel={
                `Queue driver: ${details.label}`
            }
        />
    )
}

export function QueueHealthBadge({
                                     status,
                                 }: {
    status?:
        QueueHealthStatus | null
}) {
    const details =
        status
            ? healthBadges[
                status
                ]
            : neverTestedBadge

    return (
        <Badge
            details={
                details
            }
            ariaLabel={
                `Queue health: ${details.label}`
            }
        />
    )
}

function Badge({
                   details,
                   ariaLabel,
               }: {
    details:
        BadgeDefinition

    ariaLabel:
        string
}) {
    const Icon =
        details.icon

    return (
        <span
            aria-label={
                ariaLabel
            }
            title={
                ariaLabel
            }
            className={
                `inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${details.className}`
            }
        >
            <Icon
                aria-hidden="true"
                className="h-3.5 w-3.5 shrink-0"
            />

            {details.label}
        </span>
    )
}

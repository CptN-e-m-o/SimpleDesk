export type QueueDriver =
    | 'database'
    | 'redis'
    | 'sync'

export type QueueHealthStatus =
    | 'healthy'
    | 'degraded'
    | 'unhealthy'
    | 'unavailable'

export type QueueInfrastructureSource =
    | 'managed'
    | 'deployment'

export type QueueConfigurationState =
    | 'enabled'
    | 'disabled'

export type QueueArchiveFilter =
    | 'active'
    | 'archived'
    | 'all'

export type QueueDriverDefinition = {
    type: QueueDriver
    label: string
    description: string
    requires_infrastructure: boolean
    infrastructure_type: string | null
    recommended_for_production: boolean

    options: {
        database_connections?: string[]
    }
}

export type RedisConnection = {
    id: number
    name: string
    type: 'redis'
    source: QueueInfrastructureSource
    is_enabled: boolean
    deleted_at: string | null
}

export type QueueHealthDetails =
    Record<string, unknown>

export type QueueHealthResult = {
    status: QueueHealthStatus
    latency_ms: number | null
    message: string
    details: QueueHealthDetails
}

export type QueueHealthCheck =
    QueueHealthResult & {
    tested_by: number | null
    created_at: string | null
}

export type QueueConfigurationValues = {
    database_connection?: string

    retry_after?:
        | number
        | ''

    block_for?:
        | number
        | null
        | ''

    after_commit?: boolean
}

export type QueueConfiguration = {
    id: number
    name: string
    driver: QueueDriver

    infrastructure_connection_id:
        number | null

    configuration:
        QueueConfigurationValues

    is_enabled: boolean
    is_active: boolean

    deleted_at: string | null
    created_at: string | null
    updated_at: string | null

    latest_health_check:
        QueueHealthCheck | null

    infrastructure_connection?:
        RedisConnection | null
}

export type QueueWorkload = {
    key: string
    label: string
    description: string
    queue_name: string
    connection_name: string | null
    uses_default_connection: boolean
    enabled: boolean
}

export type QueueBacklogWorkload = {
    key: string
    label: string
}

export type BacklogQueue = {
    connection: string
    queue: string
    pending: number | null
    inspectable: boolean
    error: string | null
    workloads: QueueBacklogWorkload[]
}

export type QueueBacklog = {
    queues: BacklogQueue[]
    total_pending: number
    has_errors: boolean
    inspected_at: string
}

export type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

export type QueuePagination = {
    data: QueueConfiguration[]
    links: PaginationLink[]
    current_page: number
    last_page: number
    from: number | null
    to: number | null
    total: number
}

export type QueueFilters = {
    search?: string | null

    driver?:
        QueueDriver | '' | null

    state?:
        QueueConfigurationState | '' | null

    archived?:
        QueueArchiveFilter | '' | null

    health?:
        QueueHealthStatus | '' | null
}

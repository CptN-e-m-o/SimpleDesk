export type CacheDriverType = 'database' | 'file' | 'redis'

export type CacheHealthStatus =
    | 'healthy'
    | 'degraded'
    | 'unhealthy'
    | 'unavailable'

export type CacheDefinition = {
    type: CacheDriverType
    label: string
    description: string
    requires_infrastructure: boolean
    infrastructure_type: string | null
    recommended_for_production: boolean
    available: boolean
    unavailable_reason: string | null
    options: {
        database_connections?: string[]
    }
}

export type InfrastructureOption = {
    id: number
    name: string
    source: string
    is_enabled: boolean
    deleted_at: string | null
}

export type CacheHealthResult = {
    status: CacheHealthStatus
    latency_ms: number | null
    message: string
    details?: Record<string, unknown>
    created_at?: string
}

export type CacheConfiguration = {
    id: number
    name: string
    driver: CacheDriverType
    infrastructure_connection_id: number | null
    configuration: {
        database_connection?: string
    }
    infrastructure_connection: InfrastructureOption | null
    is_enabled: boolean
    deleted_at: string | null
    is_active: boolean
    latest_health_check: CacheHealthResult | null
}

export type CacheDeploymentTarget = {
    store: string | null
    driver: string | null
    available: boolean
    message?: string
}

export type CacheOwnership = {
    mode: 'deployment' | 'managed'
    owned: boolean
}

export type CachePagination = {
    data: CacheConfiguration[]
    current_page: number
    first_page_url: string
    from: number | null
    last_page: number
    last_page_url: string
    links: Array<{
        url: string | null
        label: string
        active: boolean
    }>
    next_page_url: string | null
    path: string
    per_page: number
    prev_page_url: string | null
    to: number | null
    total: number
}

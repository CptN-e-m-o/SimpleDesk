import type { PageProps as InertiaPageProps } from '@inertiajs/core'

export interface AuthUser {
    id: number
    name: string
    email: string
    roles: string[]
}

export type BroadcastClientConfiguration =
    | {
    available: false
    ownership: 'managed' | 'deployment'
    message: string
}
    | {
    available: true
    ownership: 'managed'
    broadcaster: 'reverb' | 'pusher'
    app_key: string
    public_host: string | null
    public_port: number | null
    public_scheme: 'http' | 'https' | null
    cluster: string | null
}

export interface SharedData extends InertiaPageProps {
    auth: {
        user: AuthUser | null
    }
    flash: {
        success?: string | null
        error?: string | null
    }
    broadcastingClient?: BroadcastClientConfiguration | null
    agentStatus?: {
        current: {
            id: number
            name: string
            icon: string
            color: string
            availability: string
            expires_at?: string | null
        }
        options: Array<{
            id: number
            name: string
            icon: string
            color: string
            availability: string
            default_duration_minutes?: number | null
        }>
    } | null
}

import type { PageProps as InertiaPageProps } from '@inertiajs/core'

export interface AuthUser {
    id: number
    name: string
    email: string
    roles: string[]
}

export interface SharedData extends InertiaPageProps {
    auth: {
        user: AuthUser | null
    }
    flash: {
        success?: string | null
        error?: string | null
    }
    agentStatus?: {
        current: { id: number; name: string; icon: string; color: string; availability: string; expires_at?: string | null }
        options: Array<{ id: number; name: string; icon: string; color: string; availability: string; default_duration_minutes?: number | null }>
    } | null
}

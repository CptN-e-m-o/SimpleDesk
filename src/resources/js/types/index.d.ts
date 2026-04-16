import type { PageProps as InertiaPageProps } from '@inertiajs/core'

export interface AuthUser {
    id: number
    name: string
    email: string
}

export interface SharedData extends InertiaPageProps {
    auth: {
        user: AuthUser | null
    }
    flash: {
        success?: string | null
        error?: string | null
    }
}

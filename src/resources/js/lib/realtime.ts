import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

import type { BroadcastClientConfiguration } from '@/types'

type AvailableConfiguration = Extract<
    BroadcastClientConfiguration,
    { available: true }
>

let instance: ReturnType<typeof createEcho> | null = null
let fingerprint: string | null = null

export function configureRealtime(
    configuration: BroadcastClientConfiguration | null | undefined,
): void {
    const nextFingerprint = configuration
        ? JSON.stringify(configuration)
        : null

    if (nextFingerprint === fingerprint) {
        return
    }

    instance?.disconnect()
    instance = null
    fingerprint = nextFingerprint

    if (!configuration?.available) {
        return
    }

    instance = createEcho(configuration)
}

export function realtime(): ReturnType<typeof createEcho> | null {
    return instance
}

function createEcho(configuration: AvailableConfiguration) {
    const runtime = globalThis as typeof globalThis & {
        Pusher: typeof Pusher
    }

    runtime.Pusher = Pusher

    if (configuration.broadcaster === 'reverb') {
        const secure = configuration.public_scheme === 'https'
        const port =
            configuration.public_port
            ?? (secure ? 443 : 80)

        return new Echo({
            broadcaster: 'reverb',
            key: configuration.app_key,
            wsHost:
                configuration.public_host
                ?? globalThis.location.hostname,
            wsPort: port,
            wssPort: port,
            forceTLS: secure,
            enabledTransports: ['ws', 'wss'],
        })
    }

    const secure = configuration.public_scheme !== 'http'

    if (configuration.public_host) {
        const port =
            configuration.public_port
            ?? (secure ? 443 : 80)

        return new Echo({
            broadcaster: 'pusher',
            key: configuration.app_key,
            cluster: configuration.cluster ?? '',
            wsHost: configuration.public_host,
            wsPort: port,
            wssPort: port,
            forceTLS: secure,
            enabledTransports: ['ws', 'wss'],
        })
    }

    if (!configuration.cluster) {
        throw new Error(
            'Pusher client configuration requires a cluster or public host.',
        )
    }

    return new Echo({
        broadcaster: 'pusher',
        key: configuration.app_key,
        cluster: configuration.cluster,
        forceTLS: secure,
        enabledTransports: ['ws', 'wss'],
    })
}

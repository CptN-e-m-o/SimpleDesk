import { useEffect, useMemo, useRef, useState } from 'react'
import { usePage } from '@inertiajs/react'
import axios from 'axios'
import {
    Activity,
    CheckCircle2,
    RadioTower,
    ShieldCheck,
    WifiOff,
} from 'lucide-react'
import { route } from 'ziggy-js'

import { Button } from '@/Components/ui/button'
import { realtime } from '@/lib/realtime'
import type { SharedData } from '@/types'

type ChannelState =
    | 'connecting'
    | 'ready'
    | 'error'
    | 'unavailable'

type ProbeState =
    | 'idle'
    | 'sending'
    | 'success'
    | 'error'

type ProbePayload = {
    probe_id: string
    sent_at: string
}

const eventName = '.system.broadcasting.browser-probe'

export default function BrowserRealtimeProbe() {
    const { auth, broadcastingClient } = usePage<SharedData>().props

    const [channelState, setChannelState] =
        useState<ChannelState>('connecting')

    const [probeState, setProbeState] =
        useState<ProbeState>('idle')

    const [message, setMessage] = useState(
        'Waiting for private channel authorization.',
    )

    const pendingProbe = useRef<string | null>(null)
    const startedAt = useRef<number | null>(null)
    const timeout = useRef<number | null>(null)

    const runtimeFingerprint = useMemo(
        () => JSON.stringify(broadcastingClient ?? null),
        [broadcastingClient],
    )

    useEffect(() => {
        const userId = auth.user?.id
        const echo = realtime()

        if (
            !userId
            || !broadcastingClient?.available
            || !echo
        ) {
            setChannelState('unavailable')
            setMessage(
                'Managed browser broadcasting is currently unavailable.',
            )
            return
        }

        setChannelState('connecting')
        setMessage(
            'Authorizing the private browser channel.',
        )

        let mounted = true

        const channel = echo.private(
            `users.${userId}`,
        )

        const handleProbe = (payload: ProbePayload) => {
            if (
                !mounted
                || payload.probe_id !== pendingProbe.current
            ) {
                return
            }

            if (timeout.current !== null) {
                window.clearTimeout(timeout.current)
                timeout.current = null
            }

            const latency =
                startedAt.current === null
                    ? null
                    : Math.round(
                        performance.now() - startedAt.current,
                    )

            pendingProbe.current = null
            startedAt.current = null

            setProbeState('success')
            setMessage(
                latency === null
                    ? 'Private browser delivery received successfully.'
                    : `Private browser delivery received in ${latency} ms.`,
            )
        }

        channel.listen(
            eventName,
            handleProbe,
        )

        channel.subscribed(() => {
            if (!mounted) {
                return
            }

            setChannelState('ready')
            setMessage(
                'Private channel authorized and ready for an end-to-end probe.',
            )
        })

        channel.error(() => {
            if (!mounted) {
                return
            }

            setChannelState('error')
            setMessage(
                'Private channel authorization or subscription failed.',
            )
        })

        return () => {
            mounted = false

            channel.stopListening(
                eventName,
                handleProbe,
            )

            if (timeout.current !== null) {
                window.clearTimeout(timeout.current)
                timeout.current = null
            }
        }
    }, [
        auth.user?.id,
        broadcastingClient?.available,
        runtimeFingerprint,
    ])

    const runProbe = async () => {
        if (
            channelState !== 'ready'
            || probeState === 'sending'
        ) {
            return
        }

        const probeId = crypto.randomUUID()

        pendingProbe.current = probeId
        startedAt.current = performance.now()

        setProbeState('sending')
        setMessage(
            'Sending an event through the active broadcaster.',
        )

        timeout.current = window.setTimeout(() => {
            if (pendingProbe.current !== probeId) {
                return
            }

            pendingProbe.current = null
            startedAt.current = null
            timeout.current = null

            setProbeState('error')
            setMessage(
                'No matching WebSocket event was received within 5 seconds.',
            )
        }, 5000)

        try {
            await axios.post(
                route(
                    'admin.system.broadcasting.browser-probe',
                ),
                {
                    probe_id: probeId,
                },
            )
        } catch {
            if (pendingProbe.current !== probeId) {
                return
            }

            if (timeout.current !== null) {
                window.clearTimeout(timeout.current)
                timeout.current = null
            }

            pendingProbe.current = null
            startedAt.current = null

            setProbeState('error')
            setMessage(
                'The browser probe request could not be completed.',
            )
        }
    }

    const healthy =
        channelState === 'ready'
        && probeState !== 'error'

    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex items-start gap-4">
                    <span
                        className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ring-1 ring-inset ${
                            healthy
                                ? 'bg-emerald-50 ring-emerald-200'
                                : 'bg-gray-50 ring-gray-200'
                        }`}
                    >
                        {probeState === 'success' ? (
                            <CheckCircle2 className="h-5 w-5 text-emerald-700" />
                        ) : channelState === 'error'
                        || probeState === 'error' ? (
                            <WifiOff className="h-5 w-5 text-red-600" />
                        ) : (
                            <RadioTower className="h-5 w-5 text-sky-700" />
                        )}
                    </span>

                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="font-semibold text-gray-900">
                                Browser delivery probe
                            </h2>

                            <ProbeBadge
                                channelState={channelState}
                                probeState={probeState}
                            />
                        </div>

                        <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                            {message}
                        </p>

                        <div className="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500">
                            <span className="inline-flex items-center gap-1.5">
                                <ShieldCheck className="h-3.5 w-3.5" />
                                Private user channel
                            </span>

                            <span>
                                users.{auth.user?.id ?? '—'}
                            </span>
                        </div>
                    </div>
                </div>

                <Button
                    disabled={
                        channelState !== 'ready'
                        || probeState === 'sending'
                    }
                    onClick={() => void runProbe()}
                    className="shrink-0"
                >
                    <Activity className="h-4 w-4" />

                    {probeState === 'sending'
                        ? 'Running probe…'
                        : 'Run browser probe'}
                </Button>
            </div>
        </section>
    )
}

function ProbeBadge({
                        channelState,
                        probeState,
                    }: {
    channelState: ChannelState
    probeState: ProbeState
}) {
    if (probeState === 'success') {
        return (
            <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                Delivered
            </span>
        )
    }

    if (
        channelState === 'error'
        || probeState === 'error'
    ) {
        return (
            <span className="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200">
                Failed
            </span>
        )
    }

    if (channelState === 'ready') {
        return (
            <span className="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">
                Ready
            </span>
        )
    }

    if (channelState === 'unavailable') {
        return (
            <span className="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                Unavailable
            </span>
        )
    }

    return (
        <span className="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
            Connecting
        </span>
    )
}

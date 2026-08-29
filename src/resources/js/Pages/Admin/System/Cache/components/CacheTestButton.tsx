import {
    useEffect,
    useId,
    useRef,
    useState,
} from 'react'
import {
    FlaskConical,
    LoaderCircle,
} from 'lucide-react'
import { route } from 'ziggy-js'

import type {
    CacheHealthResult,
    CacheHealthStatus,
} from '../cacheTypes'

type Props = {
    configurationId: number
    onResult?: (result: CacheHealthResult) => void
    className?: string
}

const healthStatuses = new Set<string>([
    'healthy',
    'degraded',
    'unhealthy',
    'unavailable',
])

export default function CacheTestButton({
                                            configurationId,
                                            onResult,
                                            className = '',
                                        }: Props) {
    const [testing, setTesting] = useState(false)
    const [error, setError] = useState<string | null>(null)

    const errorId = useId()
    const abortControllerRef = useRef<AbortController | null>(null)
    const requestInProgressRef = useRef(false)

    useEffect(() => {
        return () => {
            abortControllerRef.current?.abort()
        }
    }, [])

    const test = async () => {
        if (testing || requestInProgressRef.current) {
            return
        }

        const csrfToken =
            document.querySelector<HTMLMetaElement>(
                'meta[name="csrf-token"]',
            )?.content

        if (!csrfToken) {
            setError(
                'The security token is unavailable. Refresh the page and try again.',
            )

            return
        }

        const controller = new AbortController()

        abortControllerRef.current = controller
        requestInProgressRef.current = true

        setTesting(true)
        setError(null)

        try {
            const response = await fetch(
                route(
                    'admin.system.cache.test',
                    configurationId,
                ),
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    signal: controller.signal,
                },
            )

            const payload = await readResponse(
                response,
            )

            if (!response.ok) {
                throw new Error(
                    responseErrorMessage(
                        response.status,
                        payload,
                    ),
                )
            }

            if (!isCacheHealthResult(payload)) {
                throw new Error(
                    'The Cache health check returned an unexpected response.',
                )
            }

            onResult?.(payload)
        } catch (reason) {
            if (isAbortError(reason)) {
                return
            }

            setError(
                reason instanceof Error
                    ? reason.message
                    : 'The Cache test could not be completed.',
            )
        } finally {
            if (
                abortControllerRef.current
                === controller
            ) {
                abortControllerRef.current = null
            }

            requestInProgressRef.current = false
            setTesting(false)
        }
    }

    return (
        <div className="inline-flex min-w-0 flex-col items-start gap-1.5">
            <button
                type="button"
                onClick={test}
                disabled={testing}
                aria-busy={testing}
                aria-describedby={
                    error
                        ? errorId
                        : undefined
                }
                className={`inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-100 disabled:cursor-wait disabled:opacity-60 ${className}`}
            >
                {testing ? (
                    <LoaderCircle className="h-4 w-4 shrink-0 animate-spin" />
                ) : (
                    <FlaskConical className="h-4 w-4 shrink-0" />
                )}

                {testing
                    ? 'Testing…'
                    : 'Test'}
            </button>

            {error ? (
                <span
                    id={errorId}
                    role="alert"
                    aria-live="polite"
                    className="max-w-72 text-xs leading-5 text-red-600"
                >
                    {error}
                </span>
            ) : null}
        </div>
    )
}

async function readResponse(
    response: Response,
): Promise<unknown> {
    const text = await response.text()

    if (text.trim() === '') {
        return null
    }

    try {
        return JSON.parse(text) as unknown
    } catch {
        return null
    }
}

function responseErrorMessage(
    status: number,
    payload: unknown,
): string {
    const serverMessage =
        extractMessage(payload)

    if (serverMessage) {
        return serverMessage
    }

    if (status === 419) {
        return 'Your session has expired. Refresh the page and try again.'
    }

    if (status === 403) {
        return 'You do not have permission to test this Cache configuration.'
    }

    if (status === 404) {
        return 'This Cache configuration is no longer available.'
    }

    return 'The Cache test could not be completed.'
}

function extractMessage(
    payload: unknown,
): string | null {
    if (!isRecord(payload)) {
        return null
    }

    if (
        typeof payload.message
        !== 'string'
    ) {
        return null
    }

    const message =
        payload.message.trim()

    return message !== ''
        ? message
        : null
}

function isCacheHealthResult(
    payload: unknown,
): payload is CacheHealthResult {
    if (!isRecord(payload)) {
        return false
    }

    if (
        !isCacheHealthStatus(
            payload.status,
        )
    ) {
        return false
    }

    if (
        typeof payload.message
        !== 'string'
    ) {
        return false
    }

    if (
        payload.latency_ms !== null
        && payload.latency_ms !== undefined
        && typeof payload.latency_ms !== 'number'
    ) {
        return false
    }

    return true
}

function isCacheHealthStatus(
    value: unknown,
): value is CacheHealthStatus {
    return (
        typeof value === 'string'
        && healthStatuses.has(value)
    )
}

function isRecord(
    value: unknown,
): value is Record<string, unknown> {
    return (
        typeof value === 'object'
        && value !== null
        && !Array.isArray(value)
    )
}

function isAbortError(
    reason: unknown,
): boolean {
    return (
        reason instanceof DOMException
        && reason.name === 'AbortError'
    )
}

import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    ArrowLeft,
    Check,
    CircleAlert,
    CircleCheck,
    Inbox,
    LoaderCircle,
    Mail,
    Play,
    Send,
    Server,
    ShieldCheck,
    TriangleAlert,
} from 'lucide-react'
import { ReactNode, useState } from 'react'
import { route } from 'ziggy-js'
import SetupSteps from './SetupSteps'

type DepartmentData = {
    id: number
    name: string
}

type MailboxData = {
    id: number
    name: string
    email_address: string
    display_name: string | null
    department: DepartmentData | null
    is_active: boolean
    is_default_outgoing: boolean
}

type ReviewChannel = {
    id: number
    name: string
    driver: string
    auth_type: string
    health_status: string
    is_enabled: boolean
    is_primary: boolean
    host: string | null
    port: number | null
    encryption: string | null
    username: string | null
    credentials_configured: boolean
    last_checked_at: string | null
    last_success_at: string | null
    last_error_at: string | null
}

type Props = {
    readonly mailbox: MailboxData
    readonly incoming_channel?: ReviewChannel | null
    readonly outgoing_channel?: ReviewChannel | null
}

type ConnectionState = {
    status:
        | 'idle'
        | 'loading'
        | 'success'
        | 'error'
    message: string
}

type ChannelCardProps = {
    title: string
    description: string
    channel: ReviewChannel | null
    icon: ReactNode
    state: ConnectionState
    onTest: () => void
}

const idleState: ConnectionState = {
    status: 'idle',
    message: '',
}

function formatValue(
    value: string | null,
): string {
    if (!value) {
        return '—'
    }

    return value
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (character) =>
            character.toUpperCase(),
        )
}

function statusClasses(status: string): string {
    switch (status.toLowerCase()) {
        case 'healthy':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-200'

        case 'warning':
            return 'bg-amber-50 text-amber-700 ring-amber-200'

        case 'failed':
            return 'bg-rose-50 text-rose-700 ring-rose-200'

        default:
            return 'bg-gray-100 text-gray-600 ring-gray-200'
    }
}

function ChannelCard({
                         title,
                         description,
                         channel,
                         icon,
                         state,
                         onTest,
                     }: ChannelCardProps) {
    return (
        <section className="overflow-hidden rounded-[24px] border border-gray-200 bg-white">
            <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                        {icon}
                    </div>

                    <div>
                        <h2 className="text-base font-semibold text-gray-900">
                            {title}
                        </h2>

                        <p className="mt-1 text-sm text-gray-500">
                            {description}
                        </p>
                    </div>
                </div>
            </div>

            {channel ? (
                <div className="space-y-5 p-5">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p className="font-semibold text-gray-900">
                                {channel.name}
                            </p>

                            <p className="mt-1 text-sm text-gray-500">
                                {formatValue(channel.driver)} ·{' '}
                                {formatValue(
                                    channel.auth_type,
                                )}
                            </p>
                        </div>

                        <span
                            className={`inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${statusClasses(
                                channel.health_status,
                            )}`}
                        >
                            {formatValue(
                                channel.health_status,
                            )}
                        </span>
                    </div>

                    <dl className="grid gap-3 sm:grid-cols-2">
                        <div className="rounded-2xl bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-200">
                            <dt className="text-xs uppercase tracking-wide text-gray-400">
                                Server
                            </dt>

                            <dd className="mt-1 break-all text-sm font-medium text-gray-900">
                                {channel.host ?? '—'}
                                {channel.port
                                    ? `:${channel.port}`
                                    : ''}
                            </dd>
                        </div>

                        <div className="rounded-2xl bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-200">
                            <dt className="text-xs uppercase tracking-wide text-gray-400">
                                Encryption
                            </dt>

                            <dd className="mt-1 text-sm font-medium text-gray-900">
                                {formatValue(
                                    channel.encryption,
                                )}
                            </dd>
                        </div>

                        <div className="rounded-2xl bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-200">
                            <dt className="text-xs uppercase tracking-wide text-gray-400">
                                Username
                            </dt>

                            <dd className="mt-1 break-all text-sm font-medium text-gray-900">
                                {channel.username ?? '—'}
                            </dd>
                        </div>

                        <div className="rounded-2xl bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-200">
                            <dt className="text-xs uppercase tracking-wide text-gray-400">
                                Credentials
                            </dt>

                            <dd
                                className={`mt-1 text-sm font-medium ${
                                    channel.credentials_configured
                                        ? 'text-emerald-700'
                                        : 'text-rose-700'
                                }`}
                            >
                                {channel.credentials_configured
                                    ? 'Configured'
                                    : 'Missing'}
                            </dd>
                        </div>
                    </dl>

                    <div className="flex flex-wrap gap-2">
                        <span className="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                            {channel.is_enabled
                                ? 'Enabled'
                                : 'Disabled'}
                        </span>

                        {channel.is_primary ? (
                            <span className="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200">
                                Primary
                            </span>
                        ) : null}
                    </div>

                    {state.status !== 'idle' ? (
                        <div
                            className={`rounded-2xl border px-4 py-3 text-sm ${
                                state.status === 'success'
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                                    : state.status === 'error'
                                        ? 'border-rose-200 bg-rose-50 text-rose-800'
                                        : 'border-sky-200 bg-sky-50 text-sky-800'
                            }`}
                        >
                            <div className="flex items-center gap-2">
                                {state.status ===
                                'loading' ? (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                ) : state.status ===
                                'success' ? (
                                    <CircleCheck className="h-4 w-4" />
                                ) : (
                                    <CircleAlert className="h-4 w-4" />
                                )}

                                <span>
                                    {state.message}
                                </span>
                            </div>
                        </div>
                    ) : null}

                    <button
                        type="button"
                        onClick={onTest}
                        disabled={
                            state.status === 'loading'
                        }
                        className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 text-sm font-medium text-sky-700 transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {state.status === 'loading' ? (
                            <LoaderCircle className="h-4 w-4 animate-spin" />
                        ) : (
                            <Play className="h-4 w-4" />
                        )}

                        Test Connection
                    </button>
                </div>
            ) : (
                <div className="flex flex-col items-center justify-center px-6 py-12 text-center">
                    <div className="flex h-14 w-14 items-center justify-center rounded-3xl bg-gray-100 text-gray-400">
                        <TriangleAlert className="h-6 w-6" />
                    </div>

                    <h3 className="mt-4 text-base font-semibold text-gray-900">
                        Channel not configured
                    </h3>

                    <p className="mt-2 max-w-sm text-sm leading-6 text-gray-500">
                        This mailbox can still be saved, but the
                        corresponding email direction will remain
                        unavailable.
                    </p>
                </div>
            )}
        </section>
    )
}

export default function Review({
                                   mailbox,
                                   incoming_channel = null,
                                   outgoing_channel = null,
                               }: Props) {
    const [incomingState, setIncomingState] =
        useState<ConnectionState>(idleState)

    const [outgoingState, setOutgoingState] =
        useState<ConnectionState>(idleState)

    const [finishing, setFinishing] =
        useState(false)

    async function testConnection(
        channel: ReviewChannel,
        setState: (
            state: ConnectionState,
        ) => void,
    ) {
        setState({
            status: 'loading',
            message: 'Testing connection...',
        })

        const csrfToken =
            document
                .querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )
                ?.getAttribute('content') ?? ''

        try {
            const response = await fetch(
                route(
                    'admin.email.channels.test',
                    channel.id,
                ),
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type':
                            'application/json',
                        'X-Requested-With':
                            'XMLHttpRequest',
                        'X-CSRF-TOKEN':
                        csrfToken,
                    },
                    body: JSON.stringify({}),
                },
            )

            const payload = await response
                .json()
                .catch(() => ({}))

            const result =
                payload?.data ?? payload

            const successful = Boolean(
                result?.successful ??
                result?.success,
            )

            const message =
                typeof result?.message === 'string'
                    ? result.message
                    : successful
                        ? 'Connection test completed successfully.'
                        : 'Connection test failed.'

            setState({
                status:
                    response.ok && successful
                        ? 'success'
                        : 'error',

                message,
            })
        } catch {
            setState({
                status: 'error',
                message:
                    'Unable to perform the connection test.',
            })
        }
    }

    function finishSetup() {
        setFinishing(true)

        router.post(
            route(
                'admin.email.settings.mailboxes.setup.finish',
                mailbox.id,
            ),
            {},
            {
                preserveScroll: true,

                onFinish: () =>
                    setFinishing(false),
            },
        )
    }

    const configuredDirections =
        Number(incoming_channel !== null) +
        Number(outgoing_channel !== null)

    return (
        <AdminLayout title="Review Mailbox">
            <Head title="Review Mailbox Setup" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 ring-1 ring-inset ring-emerald-100">
                                <ShieldCheck className="h-6 w-6 text-emerald-600" />
                            </div>

                            <div>
                                <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Review Mailbox Setup
                                </h1>

                                <p className="mt-1 text-sm text-gray-500">
                                    Review connections and test
                                    them before finishing setup.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-5">
                        <SetupSteps currentStep={4} />
                    </div>

                    <div className="space-y-6 p-6">
                        <section className="rounded-[24px] border border-gray-200 bg-white p-5">
                            <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                <div className="flex min-w-0 gap-4">
                                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                                        <Mail className="h-6 w-6" />
                                    </div>

                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="text-lg font-semibold text-gray-900">
                                                {mailbox.name}
                                            </h2>

                                            {mailbox.is_default_outgoing ? (
                                                <span className="inline-flex rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                                                    Default Outgoing
                                                </span>
                                            ) : null}
                                        </div>

                                        <p className="mt-1 break-all text-sm font-medium text-gray-700">
                                            {
                                                mailbox.email_address
                                            }
                                        </p>

                                        <p className="mt-1 text-sm text-gray-500">
                                            {mailbox.display_name ??
                                                'No display name'}
                                        </p>
                                    </div>
                                </div>

                                <span
                                    className={`inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${
                                        mailbox.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                            : 'bg-gray-100 text-gray-600 ring-gray-200'
                                    }`}
                                >
                                    {mailbox.is_active
                                        ? 'Active'
                                        : 'Disabled'}
                                </span>
                            </div>

                            <dl className="mt-5 grid gap-3 sm:grid-cols-3">
                                <div className="rounded-2xl bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-200">
                                    <dt className="text-xs uppercase tracking-wide text-gray-400">
                                        Department
                                    </dt>

                                    <dd className="mt-1 text-sm font-medium text-gray-900">
                                        {mailbox.department
                                                ?.name ??
                                            'Not assigned'}
                                    </dd>
                                </div>

                                <div className="rounded-2xl bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-200">
                                    <dt className="text-xs uppercase tracking-wide text-gray-400">
                                        Connections
                                    </dt>

                                    <dd className="mt-1 text-sm font-medium text-gray-900">
                                        {
                                            configuredDirections
                                        }{' '}
                                        of 2 configured
                                    </dd>
                                </div>

                                <div className="rounded-2xl bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-200">
                                    <dt className="text-xs uppercase tracking-wide text-gray-400">
                                        Setup Status
                                    </dt>

                                    <dd
                                        className={`mt-1 text-sm font-medium ${
                                            configuredDirections ===
                                            2
                                                ? 'text-emerald-700'
                                                : 'text-amber-700'
                                        }`}
                                    >
                                        {configuredDirections ===
                                        2
                                            ? 'Complete'
                                            : 'Partially configured'}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        {configuredDirections < 2 ? (
                            <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                                <div className="flex items-start gap-3">
                                    <TriangleAlert className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />

                                    <p className="text-sm leading-6 text-amber-800">
                                        The mailbox does not have
                                        both incoming and outgoing
                                        channels. You can finish
                                        setup, but some mail
                                        functions will remain
                                        unavailable.
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                                <div className="flex items-start gap-3">
                                    <CircleCheck className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />

                                    <p className="text-sm leading-6 text-emerald-800">
                                        Both incoming and outgoing
                                        channels are configured.
                                        Test each connection before
                                        finishing setup.
                                    </p>
                                </div>
                            </div>
                        )}

                        <div className="grid gap-5 xl:grid-cols-2">
                            <ChannelCard
                                title="Incoming Email"
                                description="IMAP message retrieval."
                                channel={incoming_channel}
                                icon={
                                    <Inbox className="h-5 w-5" />
                                }
                                state={incomingState}
                                onTest={() => {
                                    if (incoming_channel) {
                                        void testConnection(
                                            incoming_channel,
                                            setIncomingState,
                                        )
                                    }
                                }}
                            />

                            <ChannelCard
                                title="Outgoing Email"
                                description="SMTP message delivery."
                                channel={outgoing_channel}
                                icon={
                                    <Send className="h-5 w-5" />
                                }
                                state={outgoingState}
                                onTest={() => {
                                    if (outgoing_channel) {
                                        void testConnection(
                                            outgoing_channel,
                                            setOutgoingState,
                                        )
                                    }
                                }}
                            />
                        </div>
                    </div>

                    <div className="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <Link
                            href={route(
                                'admin.email.settings.mailboxes.setup.outgoing',
                                mailbox.id,
                            )}
                            className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back
                        </Link>

                        <button
                            type="button"
                            onClick={finishSetup}
                            disabled={finishing}
                            className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {finishing ? (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            ) : (
                                <Check className="h-4 w-4" />
                            )}

                            {finishing
                                ? 'Finishing...'
                                : 'Finish Setup'}
                        </button>
                    </div>
                </section>
            </div>
        </AdminLayout>
    )
}

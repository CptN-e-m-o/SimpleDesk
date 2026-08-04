import AdminLayout from '@/Layouts/AdminLayout'
import { usePermissions } from '@/hooks/usePermissions'
import { Head, Link, router } from '@inertiajs/react'
import axios, { AxiosError } from 'axios'
import {
    Activity,
    Bug,
    CheckCircle2,
    Clock3,
    Inbox,
    LoaderCircle,
    MailWarning,
    RefreshCw,
    ServerCog,
    ShieldCheck,
    Stethoscope,
} from 'lucide-react'
import { useState } from 'react'
import type { ReactNode } from 'react'
import { route } from 'ziggy-js'

type OverallStatus = 'healthy' | 'warning' | 'critical'
type HealthStatus = 'healthy' | 'warning' | 'failed' | 'unknown' | 'disabled'

type Summary = {
    overall_status: OverallStatus
    overall_message: string
    mailboxes_total: number
    mailboxes_active: number
    channels_total: number
    channels_healthy: number
    channels_degraded: number
    channels_failed: number
    messages_last_24_hours: number
    failed_messages_last_24_hours: number
    stuck_messages: number
    antivirus_status: string
}

type Channel = {
    id: number
    mailbox_id: number
    mailbox_name: string | null
    mailbox_email: string | null
    name: string
    direction: string
    driver: string
    is_enabled: boolean
    is_available: boolean
    health_status: HealthStatus
    consecutive_failures: number | null
    last_success_at: string | null
    last_failure_at: string | null
    last_checked_at: string | null
    last_error_message: string | null
    cooldown_until: string | null
    can_test: boolean
}

type MessageStatistics = {
    incoming_last_24_hours: number
    outgoing_last_24_hours: number
    processed_last_24_hours: number
    sent_last_24_hours: number
    failed_last_24_hours: number
    currently_processing: number
    currently_sending: number
    stuck_preparing: number
    stuck_queued: number
    stuck_processing: number
    stuck_sending: number
}

type RecentFailure = {
    id: number
    direction: string
    subject: string | null
    mailbox_id: number | null
    mailbox_name: string | null
    failure_code: string | null
    failure_message: string | null
    failed_at: string | null
    ticket_id: number | null
}

type Antivirus = {
    configured: boolean
    status: string
    last_checked_at: string | null
    message: string
}

type System = {
    mail_ticketing_enabled: boolean
    outgoing_replies_enabled: boolean
    incoming_queue: string
    incoming_queue_connection: string
    outgoing_queue: string
    outgoing_queue_connection: string
    attachment_scanning_enabled: boolean
    reply_parsing_enabled: boolean
}

type ConnectionTestResult = {
    successful: boolean
    message: string
    latency_ms: number | null
    tested_at: string
}

type Props = {
    readonly generated_at: string
    readonly summary: Summary
    readonly channels: Channel[]
    readonly message_statistics: MessageStatistics
    readonly recent_failures: RecentFailure[]
    readonly antivirus: Antivirus
    readonly system: System
}

function formatDate(value: string | null): string {
    if (!value) return 'Never'
    const date = new Date(value)
    return Number.isNaN(date.getTime())
        ? 'Unknown'
        : new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}

function healthClasses(status: string): string {
    if (status === 'healthy') return 'bg-emerald-50 text-emerald-700 ring-emerald-200'
    if (status === 'failed' || status === 'critical') return 'bg-rose-50 text-rose-700 ring-rose-200'
    if (status === 'warning' || status === 'degraded') return 'bg-amber-50 text-amber-700 ring-amber-200'
    return 'bg-gray-100 text-gray-600 ring-gray-200'
}

function StatusBadge({ status }: { status: string }) {
    return <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize ring-1 ring-inset ${healthClasses(status)}`}>{status.replace(/_/g, ' ')}</span>
}

function SummaryCard({ label, value, icon }: { label: string; value: number; icon: ReactNode }) {
    return <div className="rounded-[22px] border border-gray-200 bg-white p-5"><div className="flex items-center justify-between text-gray-500"><span className="text-sm font-medium">{label}</span>{icon}</div><p className="mt-3 text-3xl font-semibold text-gray-900">{value}</p></div>
}

function BooleanValue({ value }: { value: boolean }) {
    return <span className={value ? 'font-medium text-emerald-700' : 'font-medium text-gray-500'}>{value ? 'Enabled' : 'Disabled'}</span>
}

export default function Index({ generated_at, summary, channels, message_statistics: messages, recent_failures: failures, antivirus, system }: Props) {
    const { can } = usePermissions()
    const canTest = can('admin.mail.test_connections')
    const [testingChannel, setTestingChannel] = useState<number | null>(null)
    const [channelResults, setChannelResults] = useState<Record<number, ConnectionTestResult>>({})
    const [channelErrors, setChannelErrors] = useState<Record<number, string>>({})
    const [testingAntivirus, setTestingAntivirus] = useState(false)
    const [antivirusResult, setAntivirusResult] = useState<ConnectionTestResult | null>(null)
    const [antivirusError, setAntivirusError] = useState<string | null>(null)

    async function testChannel(channelId: number) {
        setTestingChannel(channelId)
        setChannelErrors((current) => ({ ...current, [channelId]: '' }))
        try {
            const response = await axios.post<{ data: ConnectionTestResult }>(route('admin.email.diagnostics.channels.test', channelId))
            setChannelResults((current) => ({ ...current, [channelId]: response.data.data }))
            router.reload({
                only: [
                    'summary',
                    'channels',
                ],
            })
        } catch (error: unknown) {
            const failure = error as AxiosError<{ message?: string }>
            setChannelErrors((current) => ({ ...current, [channelId]: failure.response?.data.message ?? 'Connection test failed safely.' }))
        } finally {
            setTestingChannel(null)
        }
    }

    async function testAntivirus() {
        setTestingAntivirus(true)
        setAntivirusError(null)

        try {
            const response = await axios.post<{
                data: ConnectionTestResult
            }>(
                route(
                    'admin.email.diagnostics.antivirus.test',
                ),
            )

            setAntivirusResult(
                response.data.data,
            )

            router.reload({
                only: [
                    'summary',
                    'antivirus',
                ],
                onSuccess: () => {
                    setAntivirusResult(null)
                },
            })
        } catch (error: unknown) {
            const failure =
                error as AxiosError<{
                    message?: string
                }>

            setAntivirusError(
                failure.response?.data.message ??
                'Antivirus test failed safely.',
            )
        } finally {
            setTestingAntivirus(false)
        }
    }

    return <AdminLayout title="Email Diagnostics"><Head title="Email Diagnostics" /><div className="space-y-6">
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm"><div className="bg-gradient-to-r from-gray-50 to-white px-6 py-5"><div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div className="flex items-center gap-3"><div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-sky-100"><Stethoscope className="h-6 w-6 text-sky-600" /></div><div><h1 className="text-xl font-semibold text-gray-900">Email Diagnostics</h1><p className="mt-1 text-sm text-gray-500">Monitor mail health without running network checks automatically.</p></div></div><button
            type="button"
            onClick={() =>
                router.reload()
            } className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50"><RefreshCw className="h-4 w-4" />Refresh Page</button></div></div></section>

        <section className={`rounded-[28px] border p-6 ${summary.overall_status === 'healthy' ? 'border-emerald-200 bg-emerald-50' : summary.overall_status === 'warning' ? 'border-amber-200 bg-amber-50' : 'border-rose-200 bg-rose-50'}`}><div className="flex items-start gap-4"><Activity className="mt-1 h-7 w-7 shrink-0" /><div><div className="flex flex-wrap items-center gap-3"><h2 className="text-2xl font-semibold capitalize text-gray-900">{summary.overall_status}</h2><StatusBadge status={summary.overall_status} /></div><p className="mt-2 text-sm text-gray-700">{summary.overall_message}</p><p className="mt-2 text-xs text-gray-500">Snapshot generated {formatDate(generated_at)}</p></div></div></section>

        <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><SummaryCard label="Active Mailboxes" value={summary.mailboxes_active} icon={<Inbox className="h-5 w-5 text-sky-600" />} /><SummaryCard label="Healthy Channels" value={summary.channels_healthy} icon={<CheckCircle2 className="h-5 w-5 text-emerald-600" />} /><SummaryCard label="Failed Messages (24h)" value={summary.failed_messages_last_24_hours} icon={<MailWarning className="h-5 w-5 text-rose-600" />} /><SummaryCard label="Stuck Messages" value={summary.stuck_messages} icon={<Clock3 className="h-5 w-5 text-amber-600" />} /></section>

        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm"><div className="border-b border-gray-200 bg-gray-50 px-6 py-5"><h2 className="text-lg font-semibold text-gray-900">Channel Health</h2><p className="mt-1 text-sm text-gray-500">Tests run only for the selected channel.</p></div><div className="p-6">{channels.length === 0 ? <div className="rounded-2xl border border-dashed border-gray-300 px-6 py-12 text-center"><ServerCog className="mx-auto h-10 w-10 text-gray-400" /><p className="mt-3 font-medium text-gray-800">No mail channels configured</p><p className="mt-1 text-sm text-gray-500">Create a mailbox and configure its incoming or outgoing channel first.</p><Link href={route('admin.email.settings.index')} className="mt-4 inline-flex text-sm font-medium text-sky-700">Open Email Settings</Link></div> : <><div className="hidden overflow-x-auto rounded-2xl border border-gray-200 xl:block"><table className="min-w-full divide-y divide-gray-200"><thead className="bg-gray-50"><tr>{['Mailbox / Channel','Direction / Driver','Availability','Health','Last Check','Last Error','Actions'].map((label) => <th key={label} className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{label}</th>)}</tr></thead><tbody className="divide-y divide-gray-100">{channels.map((channel) => <ChannelRow key={channel.id} channel={channel} canTest={canTest} testing={testingChannel === channel.id} result={channelResults[channel.id]} error={channelErrors[channel.id]} onTest={testChannel} />)}</tbody></table></div><div className="grid gap-4 xl:hidden">{channels.map((channel) => <ChannelCard key={channel.id} channel={channel} canTest={canTest} testing={testingChannel === channel.id} result={channelResults[channel.id]} error={channelErrors[channel.id]} onTest={testChannel} />)}</div></>}</div></section>

        <section className="grid gap-6 xl:grid-cols-2"><div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm"><h2 className="text-lg font-semibold text-gray-900">Message Processing</h2><div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4"><Metric label="Incoming 24h" value={messages.incoming_last_24_hours} /><Metric label="Outgoing 24h" value={messages.outgoing_last_24_hours} /><Metric label="Failed 24h" value={messages.failed_last_24_hours} danger={messages.failed_last_24_hours > 0} /><Metric label="Processing" value={messages.currently_processing + messages.currently_sending} /></div>{messages.stuck_preparing > 0 ||
        messages.stuck_queued > 0 ||
        messages.stuck_processing > 0 ||
        messages.stuck_sending > 0 ? (
            <div className="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm leading-6 text-rose-700">
                <p className="font-medium">
                    Stuck email processing detected
                </p>

                <p className="mt-1">
                    Preparing: {messages.stuck_preparing}.
                    {' '}Queued: {messages.stuck_queued}.
                    {' '}Processing: {messages.stuck_processing}.
                    {' '}Sending: {messages.stuck_sending}.
                </p>
            </div>
        ) : null}</div>
        <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm"><div className="flex items-start justify-between gap-4"><div><h2 className="flex items-center gap-2 text-lg font-semibold text-gray-900"><Bug className="h-5 w-5 text-sky-600" />Antivirus</h2><div className="mt-3 flex gap-2"><StatusBadge status={antivirusResult ? (antivirusResult.successful ? 'healthy' : 'failed') : antivirus.status} /><span className="text-sm text-gray-500">{antivirus.configured ? 'Configured' : 'Optional'}</span></div></div>{canTest && antivirus.configured ? <button type="button" onClick={testAntivirus} disabled={testingAntivirus} className="inline-flex h-10 items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-4 text-sm font-medium text-sky-700 disabled:opacity-60">{testingAntivirus ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <Activity className="h-4 w-4" />}Test Antivirus</button> : null}</div><p className="mt-4 text-sm text-gray-600">{antivirusResult?.message ?? antivirus.message}</p><p className="mt-2 text-xs text-gray-400">Last checked: {formatDate(antivirus.last_checked_at)}</p>{antivirusError ? <p className="mt-3 rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{antivirusError}</p> : null}</div></section>

        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm"><div className="border-b border-gray-200 bg-gray-50 px-6 py-5"><h2 className="text-lg font-semibold text-gray-900">Recent Failures</h2></div><div className="p-6">{failures.length === 0 ? <div className="rounded-2xl border border-dashed border-emerald-200 bg-emerald-50 py-10 text-center"><ShieldCheck className="mx-auto h-9 w-9 text-emerald-600" /><p className="mt-3 font-medium text-emerald-800">No recent failed messages</p></div> : <div className="space-y-3">{failures.map((failure) => <div key={failure.id} className="grid gap-3 rounded-2xl border border-rose-100 bg-rose-50/40 p-4 lg:grid-cols-[100px_1fr_1fr_2fr_auto]"><div><p className="text-xs text-gray-400">Message</p><p className="font-medium text-gray-800">#{failure.id}</p><p className="text-xs capitalize text-gray-500">{failure.direction}</p></div><div><p className="text-xs text-gray-400">Mailbox</p><p className="text-sm text-gray-700">{failure.mailbox_name ?? 'Deleted mailbox'}</p></div><div className="min-w-0"><p className="text-xs text-gray-400">Subject / Code</p><p className="truncate text-sm text-gray-700">{failure.subject ?? '(No subject)'}</p><code className="text-xs text-rose-700">{failure.failure_code ?? 'unknown'}</code></div><div><p className="text-xs text-gray-400">Safe error</p><p className="line-clamp-2 text-sm text-gray-600">{failure.failure_message ?? 'No error message recorded.'}</p></div><div className="text-right text-xs text-gray-500"><p>{formatDate(failure.failed_at)}</p>{failure.ticket_id ? <Link href={route('tickets.show', failure.ticket_id)} className="mt-2 inline-flex text-sky-700">Ticket #{failure.ticket_id}</Link> : null}</div></div>)}</div>}</div></section>

        <section className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm"><h2 className="flex items-center gap-2 text-lg font-semibold text-gray-900"><ServerCog className="h-5 w-5 text-sky-600" />System Configuration</h2><dl className="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><Config label="Mail ticketing"><BooleanValue value={system.mail_ticketing_enabled} /></Config><Config label="Outgoing replies"><BooleanValue value={system.outgoing_replies_enabled} /></Config><Config label="Incoming queue"><span>{system.incoming_queue} ({system.incoming_queue_connection})</span></Config><Config label="Outgoing queue"><span>{system.outgoing_queue} ({system.outgoing_queue_connection})</span></Config><Config label="Attachment scanning"><BooleanValue value={system.attachment_scanning_enabled} /></Config><Config label="Reply parsing"><BooleanValue value={system.reply_parsing_enabled} /></Config></dl></section>
    </div></AdminLayout>
}

type ChannelActionProps = { channel: Channel; canTest: boolean; testing: boolean; result?: ConnectionTestResult; error?: string; onTest: (id: number) => Promise<void> }
function TestButton({ channel, canTest, testing, onTest }: ChannelActionProps) { return canTest && channel.can_test ? <button type="button" onClick={() => void onTest(channel.id)} disabled={testing} className="inline-flex h-9 items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3 text-xs font-medium text-sky-700 disabled:opacity-60">{testing ? <LoaderCircle className="h-3.5 w-3.5 animate-spin" /> : <Activity className="h-3.5 w-3.5" />}Test Connection</button> : <span className="text-xs text-gray-400">Not available</span> }
function ChannelRow(props: ChannelActionProps) { const { channel, result, error } = props; return <tr><td className="px-4 py-4"><p className="font-medium text-gray-900">{channel.mailbox_name}</p><p className="text-xs text-gray-500">{channel.mailbox_email} · {channel.name}</p></td><td className="px-4 py-4 text-sm capitalize text-gray-600">{channel.direction}<br/><span className="text-xs uppercase text-gray-400">{channel.driver}</span></td><td className="px-4 py-4"><StatusBadge status={channel.is_available ? 'available' : 'unavailable'} /></td><td className="px-4 py-4"><StatusBadge status={channel.health_status} /></td><td className="px-4 py-4 text-xs text-gray-500">{formatDate(channel.last_checked_at)}</td><td className="max-w-xs px-4 py-4 text-xs text-gray-500"><p className="line-clamp-2">{channel.last_error_message ?? '—'}</p>{channel.cooldown_until ? <p className="mt-1 text-amber-600">Cooldown until {formatDate(channel.cooldown_until)}</p> : null}{result ? <p className={result.successful ? 'mt-1 text-emerald-700' : 'mt-1 text-rose-700'}>{result.message}</p> : null}{error ? <p className="mt-1 text-rose-700">{error}</p> : null}</td><td className="px-4 py-4"><TestButton {...props} /></td></tr> }
function ChannelCard(props: ChannelActionProps) { const { channel, result, error } = props; return <article className="rounded-[22px] border border-gray-200 bg-gray-50 p-5"><div className="flex justify-between gap-3"><div><h3 className="font-semibold text-gray-900">{channel.mailbox_name}</h3><p className="text-sm text-gray-500">{channel.name} · {channel.direction} / {channel.driver}</p></div><StatusBadge status={channel.health_status} /></div><div className="mt-4 flex flex-wrap gap-2"><StatusBadge status={channel.is_available ? 'available' : 'unavailable'} /></div><p className="mt-3 text-xs text-gray-500">Last check: {formatDate(channel.last_checked_at)}</p>{channel.last_error_message ? <p className="mt-2 text-sm text-rose-700">{channel.last_error_message}</p> : null}{result ? <p className={result.successful ? 'mt-2 text-sm text-emerald-700' : 'mt-2 text-sm text-rose-700'}>{result.message}</p> : null}{error ? <p className="mt-2 text-sm text-rose-700">{error}</p> : null}<div className="mt-4"><TestButton {...props} /></div></article> }
function Metric({ label, value, danger = false }: { label: string; value: number; danger?: boolean }) { return <div className={`rounded-2xl p-4 ${danger ? 'bg-rose-50 text-rose-700' : 'bg-gray-50 text-gray-700'}`}><p className="text-xs">{label}</p><p className="mt-1 text-2xl font-semibold">{value}</p></div> }
function Config({ label, children }: { label: string; children: ReactNode }) { return <div className="rounded-2xl bg-gray-50 p-4"><dt className="text-xs uppercase tracking-wide text-gray-400">{label}</dt><dd className="mt-2 break-words text-sm text-gray-700">{children}</dd></div> }

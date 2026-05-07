import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link } from '@inertiajs/react'
import {
    ArrowLeft,
    BarChart3,
    ClipboardList,
    Eye,
    FileText,
    Lock,
    Info,
    Monitor,
    Pencil,
    Globe2,
    Shield,
    UserCog,
    UserRound,
} from 'lucide-react'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog'
import { route } from 'ziggy-js'
import { useState } from 'react'

import { Agent, getMobile, getPhone, getStatusClasses, getStatusLabel } from './shared'

type LoginSession = {
    id: number
    guard?: string | null
    device_type?: string | null
    device_name?: string | null
    platform?: string | null
    platform_version?: string | null
    browser?: string | null
    browser_version?: string | null
    ip_address?: string | null
    country?: string | null
    region?: string | null
    city?: string | null
    user_agent?: string | null
    logged_in_at?: string | null
    last_activity_at?: string | null
    logged_out_at?: string | null
    is_current: boolean
    is_successful: boolean
}

type LoginSessionGroup = {
    key: string
    device_name: string
    platform?: string | null
    browser?: string | null
    ip_address?: string | null
    location: string
    latestSession: LoginSession
    sessions: LoginSession[]
}

type Props = {
    readonly agent: Agent
    readonly viewAs: 'agent' | 'user'
    readonly loginSessions?: LoginSession[]
}

function groupLoginSessions(sessions: LoginSession[]): LoginSessionGroup[] {
    const groups = new Map<string, LoginSession[]>()

    sessions.forEach((session) => {
        const key = [
            session.device_name || 'Unknown Device',
            session.platform || 'Unknown OS',
            session.browser || 'Unknown Browser',
            session.ip_address || 'Unknown IP',
        ].join('|')

        groups.set(key, [...(groups.get(key) ?? []), session])
    })

    return Array.from(groups.entries()).map(([key, groupSessions]) => {
        const sortedSessions = [...groupSessions].sort((a, b) => {
            return (
                new Date(b.logged_in_at ?? 0).getTime() -
                new Date(a.logged_in_at ?? 0).getTime()
            )
        })

        const latestSession = sortedSessions[0]

        return {
            key,
            device_name: latestSession.device_name || 'Unknown Device',
            platform: latestSession.platform,
            browser: latestSession.browser,
            ip_address: latestSession.ip_address,
            location: formatLocation(latestSession),
            latestSession,
            sessions: sortedSessions,
        }
    })
}

export default function Show({ agent, viewAs, loginSessions = [] }: Props) {
    const isAgentView = viewAs === 'agent'
    const title = isAgentView ? 'Agent Profile' : 'User Profile'
    return (
        <AdminLayout title={title}>
            <Head title={`${agent.name || agent.username} - ${isAgentView ? 'Agent' : 'User'}`} />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-sm font-medium text-sky-600">
                            <Shield className="h-4 w-4" />
                            Agent Management
                        </div>

                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                            {agent.name || agent.username}
                        </h1>

                        <div className="mt-3 flex flex-wrap items-center gap-2">
                            <span
                                className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getStatusClasses(agent)}`}
                            >
                                {getStatusLabel(agent)}
                            </span>

                            {agent.email_verified_at ? (
                                <span className="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                    Email verified
                                </span>
                            ) : (
                                <span className="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                    Email not verified
                                </span>
                            )}

                            {agent.roles.map((role) => (
                                <span
                                    key={role.id}
                                    className="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200"
                                >
                                    {role.label}
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-3">
                        <Link
                            href={route('admin.agents.index')}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back
                        </Link>

                        {isAgentView ? (
                            <Link
                                href={route('admin.users.show', agent.id)}
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                            >
                                <Eye className="h-4 w-4" />
                                View as user
                            </Link>
                        ) : (
                            <Link
                                href={route('admin.agents.show', agent.id)}
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                            >
                                <Eye className="h-4 w-4" />
                                View as agent
                            </Link>
                        )}

                        <Link
                            href={route('admin.agents.edit', agent.id)}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                        >
                            <Pencil className="h-4 w-4" />
                            Edit Agent
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
                    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div className="flex items-center gap-3">
                                <div className="flex h-14 w-14 items-center justify-center rounded-3xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <UserCog className="h-7 w-7 text-sky-600" />
                                </div>

                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900">
                                        Agent Summary
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Profile and contact details.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="p-6">
                            <div className="rounded-[24px] bg-gray-50 p-5 ring-1 ring-inset ring-gray-200">
                                <div className="text-center">
                                    <div className="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-inset ring-gray-200">
                                        <UserRound className="h-12 w-12 text-gray-400" />
                                    </div>

                                    <h3 className="mt-4 text-2xl font-semibold tracking-tight text-gray-900">
                                        {agent.name || agent.username}
                                    </h3>

                                    <p className="mt-1 text-sm text-gray-500">
                                        @{agent.username}
                                    </p>
                                </div>

                                <div className="mt-6 space-y-4">
                                    <SummaryRow label="Username" value={agent.username} />
                                    <SummaryRow label="Email" value={agent.email} />
                                    <SummaryRow label="Work Phone" value={getPhone(agent)} />
                                    <SummaryRow label="Mobile" value={getMobile(agent)} />
                                    <SummaryRow label="Location" value={agent.location ?? '—'} />
                                    <SummaryRow label="Agent Time Zone" value={agent.timezone} />

                                    <div className="border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Roles
                                        </span>

                                        <div className="mt-2 flex flex-wrap gap-2">
                                            {agent.roles.length > 0 ? (
                                                agent.roles.map((role) => (
                                                    <span
                                                        key={role.id}
                                                        className="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200"
                                                    >
                                                        {role.label}
                                                    </span>
                                                ))
                                            ) : (
                                                <span className="text-sm font-semibold text-gray-900">
                                                    —
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href={route('admin.agents.edit', agent.id)}
                                    className="mt-6 inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                >
                                    <Pencil className="h-4 w-4" />
                                    Edit Agent
                                </Link>
                            </div>
                        </div>
                    </section>

                    <div className="space-y-6">
                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Agent Tickets
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Tickets assigned to this agent.
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <TicketTab label="Open" count={0} active />
                                        <TicketTab label="Closed" count={0} />
                                        <TicketTab label="Unapproved" count={0} />
                                        <TicketTab label="Deleted" count={0} />
                                    </div>
                                </div>
                            </div>

                            <div className="p-6">
                                <div className="rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
                                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-sm ring-1 ring-inset ring-gray-200">
                                        <ClipboardList className="h-8 w-8 text-gray-400" />
                                    </div>

                                    <h3 className="mt-5 text-lg font-semibold text-gray-900">
                                        No tickets yet
                                    </h3>

                                    <p className="mt-2 text-sm leading-6 text-gray-500">
                                        Ticket data can be connected here when the ticket module is ready.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <LoginLogsBlock
                            sessions={loginSessions}
                            ownerLabel={isAgentView ? 'agent' : 'user'}
                        />

                        {viewAs === 'user' && (
                            <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                                <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                    <div>
                                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Packages
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Orders, invoices, credits, and package expiry details.
                                        </p>
                                    </div>
                                </div>

                                <div className="p-6">
                                    <div className="flex border-b border-gray-200">
                                        <button
                                            type="button"
                                            className="rounded-t-2xl border border-b-0 border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700"
                                        >
                                            Orders
                                            <span className="ml-2 rounded-full bg-sky-600 px-2 py-0.5 text-xs font-semibold text-white">
                        0
                    </span>
                                        </button>

                                        <button
                                            type="button"
                                            className="px-4 py-3 text-sm font-medium text-gray-500 transition hover:text-sky-700"
                                        >
                                            Invoice
                                            <span className="ml-2 rounded-full bg-sky-600 px-2 py-0.5 text-xs font-semibold text-white">
                        0
                    </span>
                                        </button>
                                    </div>

                                    <div className="mt-4 flex justify-end">
                                        <input
                                            type="text"
                                            placeholder="Type and press enter to search..."
                                            className="h-11 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100 md:w-80"
                                        />
                                    </div>

                                    <div className="mt-4 hidden overflow-hidden rounded-[24px] border border-gray-200 lg:block">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Package name
                                                </th>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Invoice
                                                </th>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Credit type
                                                </th>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Credit
                                                </th>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Total Amount
                                                </th>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Expiry Date Monthly Wise
                                                </th>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Expiry Date
                                                </th>
                                            </tr>
                                            </thead>

                                            <tbody className="divide-y divide-gray-100 bg-white">
                                            <tr>
                                                <td
                                                    colSpan={7}
                                                    className="px-6 py-10 text-center text-sm text-gray-500"
                                                >
                                                    No matching records
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </section>
                        )}

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 ring-1 ring-inset ring-emerald-100">
                                        <BarChart3 className="h-5 w-5 text-emerald-600" />
                                    </div>

                                    <div>
                                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Agent Report
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Placeholder analytics block for future reporting.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-6">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <ReportCard label="Open Tickets" value="0" icon={ClipboardList} />
                                    <ReportCard label="Closed Tickets" value="0" icon={FileText} />
                                    <ReportCard label="SLA Performance" value="—" icon={BarChart3} />
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </AdminLayout>
    )
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
            <span className="text-sm font-medium text-gray-500">{label}</span>
            <span className="break-words text-right text-sm font-semibold text-gray-900">
                {value}
            </span>
        </div>
    )
}

function TicketTab({
                       label,
                       count,
                       active = false,
                   }: {
    label: string
    count: number
    active?: boolean
}) {
    return (
        <button
            type="button"
            className={`inline-flex h-9 items-center gap-2 rounded-2xl px-3 text-sm font-medium ring-1 ring-inset transition ${
                active
                    ? 'bg-sky-50 text-sky-700 ring-sky-200'
                    : 'bg-white text-gray-600 ring-gray-200 hover:bg-gray-50'
            }`}
        >
            {label}
            <span className="rounded-full bg-sky-600 px-2 py-0.5 text-xs font-semibold text-white">
                {count}
            </span>
        </button>
    )
}

function ReportCard({
                        label,
                        value,
                        icon: Icon,
                    }: {
    label: string
    value: string
    icon: typeof ClipboardList
}) {
    return (
        <div className="rounded-[24px] border border-gray-200 bg-gray-50 p-5">
            <div className="flex items-center gap-3">
                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-gray-200">
                    <Icon className="h-5 w-5 text-sky-600" />
                </div>

                <div>
                    <p className="text-sm font-medium text-gray-500">{label}</p>
                    <p className="mt-1 text-2xl font-semibold text-gray-900">
                        {value}
                    </p>
                </div>
            </div>
        </div>
    )
}

function LoginLogsBlock({
                            sessions,
                            ownerLabel,
                        }: {
    sessions: LoginSession[]
    ownerLabel: string
}) {
    const [selectedGroup, setSelectedGroup] =
        useState<LoginSessionGroup | null>(null)
    const [selectedSession, setSelectedSession] =
        useState<LoginSession | null>(null)

    const groups = groupLoginSessions(sessions)

    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                <div className="flex items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 ring-1 ring-inset ring-violet-100">
                        <Lock className="h-5 w-5 text-violet-600" />
                    </div>

                    <div>
                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                            Login Logs
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Recent web sessions for this {ownerLabel}.
                        </p>
                    </div>
                </div>
            </div>

            <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                <button
                    type="button"
                    className="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2 text-sm font-semibold text-sky-700 ring-1 ring-inset ring-sky-200"
                >
                    Web Sessions
                    <span className="rounded-full bg-sky-600 px-2 py-0.5 text-xs font-semibold text-white">
                        {groups.length}
                    </span>
                </button>
            </div>

            <div className="p-6">
                {groups.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2">
                        {groups.map((group) => (
                            <LoginSessionGroupCard
                                key={group.key}
                                group={group}
                                onShowGroup={setSelectedGroup}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center">
                        <p className="text-sm font-medium text-gray-700">
                            No login sessions yet
                        </p>
                    </div>
                )}
            </div>

            <LoginSessionGroupDialog
                group={selectedGroup}
                onClose={() => setSelectedGroup(null)}
                onShowSession={setSelectedSession}
            />

            <LoginSessionDialog
                session={selectedSession}
                onClose={() => setSelectedSession(null)}
            />
        </section>
    )
}

function LoginSessionGroupCard({
                                   group,
                                   onShowGroup,
                               }: {
    group: LoginSessionGroup
    onShowGroup: (group: LoginSessionGroup) => void
}) {
    return (
        <div className="rounded-[24px] border border-gray-200 bg-gray-50 p-5">
            <div className="flex items-start justify-between gap-4">
                <div className="flex items-start gap-3">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-gray-200">
                        <Monitor className="h-5 w-5 text-sky-600" />
                    </div>

                    <div>
                        <p className="font-semibold text-gray-900">
                            {group.device_name}
                        </p>

                        <p className="mt-1 text-sm text-gray-500">
                            Last logged in {formatDateTime(group.latestSession.logged_in_at)}
                        </p>

                        <p className="mt-1 text-xs text-gray-400">
                            {group.sessions.length} session
                            {group.sessions.length === 1 ? '' : 's'}
                        </p>

                        {group.sessions.some(isSessionActive) ? (
                            <span className="mt-3 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                Active session
                            </span>
                                                ) : (
                            <span className="mt-3 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
                                Ended sessions
                            </span>
                        )}
                    </div>
                </div>

                <button
                    type="button"
                    onClick={() => onShowGroup(group)}
                    className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                >
                    <Info className="h-4 w-4" />
                </button>
            </div>
        </div>
    )
}

function LoginSessionGroupDialog({
                                     group,
                                     onClose,
                                     onShowSession,
                                 }: {
    group: LoginSessionGroup | null
    onClose: () => void
    onShowSession: (session: LoginSession) => void
}) {
    return (
        <Dialog open={group !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="rounded-[28px] border border-gray-200 bg-white sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle className="text-xl font-semibold tracking-tight text-gray-900">
                        Web Sessions
                    </DialogTitle>
                </DialogHeader>

                {group && (
                    <div className="mt-4">
                        <div className="mb-4 rounded-[24px] bg-gray-50 p-5 ring-1 ring-inset ring-gray-200">
                            <p className="font-semibold text-gray-900">
                                {group.device_name}
                            </p>
                            <p className="mt-1 text-sm text-gray-500">
                                {formatVersion(group.platform, null)} ·{' '}
                                {formatVersion(group.browser, null)} ·{' '}
                                {group.location}
                            </p>
                        </div>

                        <div className="hidden overflow-hidden rounded-[24px] border border-gray-200 lg:block">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Device
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Platform/OS
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Browser
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Location
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Login at
                                    </th>
                                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Info
                                    </th>
                                </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-100 bg-white">
                                {group.sessions.map((session) => (
                                    <tr
                                        key={session.id}
                                        className="transition hover:bg-sky-50/40"
                                    >
                                        <td className="px-6 py-5 text-sm font-medium text-gray-900">
                                            {session.device_name || 'Unknown Device'}
                                        </td>
                                        <td className="px-6 py-5 text-sm text-gray-600">
                                            {formatVersion(session.platform, session.platform_version)}
                                        </td>
                                        <td className="px-6 py-5 text-sm text-gray-600">
                                            {formatVersion(session.browser, session.browser_version)}
                                        </td>
                                        <td className="px-6 py-5 text-sm text-gray-600">
                                            {formatLocation(session)}
                                        </td>
                                        <td className="px-6 py-5 text-sm text-gray-600">
                                            {formatDateTime(session.logged_in_at)}
                                        </td>
                                        <td className="px-6 py-5">
                                            {isSessionActive(session) ? (
                                                <span className="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                    Active
                                                </span>
                                            ) : (
                                                <span className="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
                                                    Ended
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-6 py-5">
                                            <div className="flex justify-center">
                                                <button
                                                    type="button"
                                                    onClick={() => onShowSession(session)}
                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                >
                                                    <Info className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                </tbody>
                            </table>
                        </div>

                        <p className="mt-4 text-sm text-gray-500">
                            {group.sessions.length} record
                            {group.sessions.length === 1 ? '' : 's'}
                        </p>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    )
}

function LoginSessionCard({
                              session,
                              onShowDetails,
                          }: {
    session: LoginSession
    onShowDetails: (session: LoginSession) => void
}) {
    return (
        <div className="rounded-[24px] border border-gray-200 bg-gray-50 p-5">
            <div className="flex items-start justify-between gap-4">
                <div className="flex items-start gap-3">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-gray-200">
                        <Monitor className="h-5 w-5 text-sky-600" />
                    </div>

                    <div>
                        <p className="font-semibold text-gray-900">
                            {session.device_name || 'Unknown Device'}
                        </p>

                        <p className="mt-1 text-sm text-gray-500">
                            {formatDateTime(session.logged_in_at)}
                        </p>

                        <div className="mt-3 flex flex-wrap gap-2">
                            <span className="inline-flex rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200">
                                {session.platform || 'Unknown OS'}
                            </span>

                            <span className="inline-flex rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200">
                                {session.browser || 'Unknown Browser'}
                            </span>

                            {session.is_current && (
                                <span className="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                    Current
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    onClick={() => onShowDetails(session)}
                    className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                >
                    <Info className="h-4 w-4" />
                </button>
            </div>
        </div>
    )
}

function LoginSessionDialog({
                                session,
                                onClose,
                            }: {
    session: LoginSession | null
    onClose: () => void
}) {
    return (
        <Dialog open={session !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="rounded-[28px] border border-gray-200 bg-white sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="text-xl font-semibold tracking-tight text-gray-900">
                        Session Details
                    </DialogTitle>
                </DialogHeader>

                {session && (
                    <div className="mt-4 space-y-4">
                        <div className="rounded-[24px] bg-gray-50 p-5 ring-1 ring-inset ring-gray-200">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-gray-200">
                                    <Globe2 className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <p className="font-semibold text-gray-900">
                                        {session.device_name || 'Unknown Device'}
                                    </p>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {session.ip_address || 'Unknown IP'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2">
                            <DetailRow label="Login At" value={formatDateTime(session.logged_in_at)} />
                            <DetailRow label="Last Activity" value={formatDateTime(session.last_activity_at)} />
                            <DetailRow label="Logout At" value={formatDateTime(session.logged_out_at)} />
                            <DetailRow label="Status" value={isSessionActive(session) ? 'Active' : 'Ended'} />
                            <DetailRow label="Operating System" value={formatVersion(session.platform, session.platform_version)} />
                            <DetailRow label="Browser" value={formatVersion(session.browser, session.browser_version)} />
                            <DetailRow label="Device Type" value={session.device_type || '—'} />
                            <DetailRow label="Guard" value={session.guard || '—'} />
                            <DetailRow label="IP Address" value={session.ip_address || '—'} />
                            <DetailRow label="Location" value={formatLocation(session)} />
                        </div>

                        <div className="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                User Agent
                            </p>
                            <p className="mt-2 break-words text-sm text-gray-700">
                                {session.user_agent || '—'}
                            </p>
                        </div>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    )
}

function DetailRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                {label}
            </p>
            <p className="mt-1 break-words text-sm font-semibold text-gray-900">
                {value}
            </p>
        </div>
    )
}

function formatDateTime(value?: string | null) {
    if (!value) return '—'

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
}

function formatVersion(name?: string | null, version?: string | null) {
    if (!name) return '—'
    if (!version) return name

    return `${name} v${version}`
}

function formatLocation(session: LoginSession) {
    return [session.city, session.region, session.country]
        .filter(Boolean)
        .join(', ') || '—'
}

function isSessionActive(session: LoginSession) {
    return session.is_current && !session.logged_out_at
}

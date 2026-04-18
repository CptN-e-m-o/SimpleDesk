import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    CalendarDays,
    ChevronRight,
    CircleDot,
    Clock3,
    FolderOpen,
    LifeBuoy,
    MessageSquare,
    SendHorizonal,
    Shield,
    Tag,
    User2,
} from 'lucide-react'
import type { FormEvent } from 'react'

import UserLayout from '@/Layouts/UserLayout'
import { route } from 'ziggy-js'

type TicketCategory = {
    id: number
    name: string
} | null

type TicketUser = {
    id: number
    name: string
    email: string
} | null

type TicketReply = {
    id: number
    message: string
    is_internal: boolean
    created_at: string | null
    user: TicketUser
}

type TicketData = {
    id: number
    ticket_number: string
    subject: string
    description: string
    status: string
    priority: string
    service: string | null
    source: string
    created_at: string | null
    updated_at: string | null
    last_reply_at: string | null
    resolved_at: string | null
    closed_at: string | null
    category: TicketCategory
    requester: TicketUser
    assignee: TicketUser
    replies: TicketReply[]
}

type Props = {
    ticket: TicketData
}

function formatDate(value: string | null) {
    if (!value) return '—'

    return new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

function humanize(value: string | null) {
    if (!value) return '—'

    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ')
}

function getStatusClasses(status: string) {
    switch (status) {
        case 'open':
            return 'border-sky-200 bg-sky-100 text-sky-700'
        case 'in_progress':
            return 'border-amber-200 bg-amber-100 text-amber-700'
        case 'waiting_for_customer':
            return 'border-violet-200 bg-violet-100 text-violet-700'
        case 'resolved':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700'
        case 'closed':
            return 'border-gray-200 bg-gray-100 text-gray-700'
        default:
            return 'border-gray-200 bg-gray-100 text-gray-700'
    }
}

function getPriorityClasses(priority: string) {
    switch (priority) {
        case 'low':
            return 'bg-gray-100 text-gray-700'
        case 'medium':
            return 'bg-sky-100 text-sky-700'
        case 'high':
            return 'bg-amber-100 text-amber-700'
        case 'urgent':
            return 'bg-rose-100 text-rose-700'
        default:
            return 'bg-gray-100 text-gray-700'
    }
}

function getInitials(name?: string | null) {
    if (!name) return 'U'

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('')
}

export default function TicketsShow({ ticket }: Props) {
    const form = useForm({
        message: '',
    })

    function submitReply(e: FormEvent) {
        e.preventDefault()

        form.post(route('tickets.replies.store', ticket.id), {
            preserveScroll: true,
            onSuccess: () => form.reset('message'),
        })
    }

    const visibleReplies = ticket.replies.filter((reply) => !reply.is_internal)

    return (
        <UserLayout>
            <Head title={`${ticket.ticket_number} · ${ticket.subject}`} />

            <div className="space-y-8">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="grid gap-8 px-6 py-8 sm:px-8 lg:grid-cols-[1.15fr_0.85fr] lg:px-10 lg:py-10">
                        <div>
                            <div className="mb-4 flex flex-wrap items-center gap-3">
                                <div className="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                                    <LifeBuoy className="h-3.5 w-3.5" />
                                    Ticket details
                                </div>

                                <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                    {ticket.ticket_number}
                                </span>
                            </div>

                            <div className="mb-4 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                <Link
                                    href={route('tickets.index')}
                                    className="inline-flex items-center gap-2 transition hover:text-gray-900"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    My tickets
                                </Link>
                                <ChevronRight className="h-4 w-4" />
                                <span className="font-medium text-gray-900">
                                    {ticket.ticket_number}
                                </span>
                            </div>

                            <h1 className="text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl">
                                {ticket.subject}
                            </h1>

                            <p className="mt-4 max-w-2xl text-base leading-7 text-gray-600">
                                Review the original request, follow the discussion, and add a reply
                                if you need to share more details.
                            </p>

                            <div className="mt-6 flex flex-wrap items-center gap-2">
                                <span
                                    className={`inline-flex rounded-full border px-3 py-1 text-xs font-semibold ${getStatusClasses(ticket.status)}`}
                                >
                                    {humanize(ticket.status)}
                                </span>

                                <span
                                    className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${getPriorityClasses(ticket.priority)}`}
                                >
                                    {humanize(ticket.priority)}
                                </span>

                                {ticket.category?.name && (
                                    <span className="inline-flex rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
                                        {ticket.category.name}
                                    </span>
                                )}

                                {ticket.service && (
                                    <span className="inline-flex rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
                                        {ticket.service}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div className="rounded-3xl border border-gray-200 bg-gray-50 p-6">
                                <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-sky-700">
                                    <Clock3 className="h-6 w-6" />
                                </div>

                                <h2 className="text-lg font-semibold text-gray-900">
                                    Last activity
                                </h2>

                                <p className="mt-2 text-sm leading-6 text-gray-600">
                                    {formatDate(
                                        ticket.last_reply_at || ticket.updated_at || ticket.created_at,
                                    )}
                                </p>
                            </div>

                            <div className="rounded-3xl border border-gray-200 bg-gray-900 p-6 text-white">
                                <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10">
                                    <FolderOpen className="h-6 w-6" />
                                </div>

                                <h2 className="text-lg font-semibold">Need another request?</h2>

                                <p className="mt-2 text-sm leading-6 text-gray-300">
                                    Create a separate ticket if you have a different issue.
                                </p>

                                <Link
                                    href={route('tickets.create')}
                                    className="mt-5 inline-flex items-center gap-2 text-sm font-medium text-sky-300 transition hover:text-sky-200"
                                >
                                    Create ticket
                                    <ChevronRight className="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-8 lg:grid-cols-[1fr_320px]">
                    <div className="space-y-6">
                        <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                            <div className="mb-6">
                                <h2 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    Original request
                                </h2>
                                <p className="mt-2 text-sm leading-6 text-gray-600">
                                    The issue details you submitted when creating this ticket.
                                </p>
                            </div>

                            <div className="rounded-[24px] border border-sky-100 bg-sky-50/70 p-5">
                                <div className="flex items-start gap-4">
                                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-sm font-semibold text-sky-700 shadow-sm">
                                        {getInitials(ticket.requester?.name)}
                                    </div>

                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <div className="text-sm font-semibold text-gray-900">
                                                    {ticket.requester?.name || 'Requester'}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    Submitted ticket
                                                </div>
                                            </div>

                                            <div className="text-xs text-gray-500">
                                                {formatDate(ticket.created_at)}
                                            </div>
                                        </div>

                                        <div className="mt-4 whitespace-pre-wrap text-sm leading-7 text-gray-700">
                                            {ticket.description}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                            <div className="mb-6 flex items-center justify-between gap-4">
                                <div>
                                    <h2 className="text-2xl font-semibold tracking-tight text-gray-900">
                                        Comments
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 text-gray-600">
                                        Replies from you and the support team.
                                    </p>
                                </div>
                            </div>

                            {visibleReplies.length > 0 ? (
                                <div className="space-y-4">
                                    {visibleReplies.map((reply) => {
                                        const isRequester =
                                            reply.user?.id && ticket.requester?.id
                                                ? reply.user.id === ticket.requester.id
                                                : false

                                        return (
                                            <div
                                                key={reply.id}
                                                className={`rounded-[24px] border p-5 ${
                                                    isRequester
                                                        ? 'border-gray-200 bg-gray-50'
                                                        : 'border-emerald-100 bg-emerald-50/70'
                                                }`}
                                            >
                                                <div className="flex items-start gap-4">
                                                    <div
                                                        className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-sm font-semibold shadow-sm ${
                                                            isRequester
                                                                ? 'bg-white text-gray-700'
                                                                : 'bg-white text-emerald-700'
                                                        }`}
                                                    >
                                                        {getInitials(reply.user?.name)}
                                                    </div>

                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                            <div>
                                                                <div className="text-sm font-semibold text-gray-900">
                                                                    {reply.user?.name || 'User'}
                                                                </div>
                                                                <div className="text-xs text-gray-500">
                                                                    {isRequester
                                                                        ? 'You replied'
                                                                        : 'Support reply'}
                                                                </div>
                                                            </div>

                                                            <div className="text-xs text-gray-500">
                                                                {formatDate(reply.created_at)}
                                                            </div>
                                                        </div>

                                                        <div className="mt-4 whitespace-pre-wrap text-sm leading-7 text-gray-700">
                                                            {reply.message}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )
                                    })}
                                </div>
                            ) : (
                                <div className="rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center">
                                    <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-sky-700 shadow-sm">
                                        <MessageSquare className="h-7 w-7" />
                                    </div>

                                    <h3 className="mt-5 text-lg font-semibold text-gray-900">
                                        No comments yet
                                    </h3>

                                    <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">
                                        There are no replies yet. Add a comment below if you want to
                                        provide more details.
                                    </p>
                                </div>
                            )}
                        </div>

                        <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                            <div className="mb-6">
                                <h2 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    Add reply
                                </h2>
                                <p className="mt-2 text-sm leading-6 text-gray-600">
                                    Share new details, updates, or follow-up questions about this issue.
                                </p>
                            </div>

                            <form onSubmit={submitReply} className="space-y-4">
                                <div>
                                    <label
                                        htmlFor="message"
                                        className="mb-2 block text-sm font-medium text-gray-800"
                                    >
                                        Message <span className="text-rose-500">*</span>
                                    </label>

                                    <textarea
                                        id="message"
                                        rows={6}
                                        value={form.data.message}
                                        onChange={(e) => form.setData('message', e.target.value)}
                                        placeholder="Write your reply here..."
                                        className="w-full rounded-3xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                                    />

                                    {form.errors.message && (
                                        <p className="mt-2 text-sm text-rose-600">
                                            {form.errors.message}
                                        </p>
                                    )}
                                </div>

                                <div className="rounded-3xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                                    Avoid sharing passwords, payment card details, or other sensitive
                                    credentials in your reply.
                                </div>

                                <div className="flex justify-end">
                                    <button
                                        type="submit"
                                        disabled={form.processing}
                                        className="inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-70"
                                    >
                                        <SendHorizonal className="h-4 w-4" />
                                        {form.processing ? 'Sending...' : 'Send reply'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <aside className="space-y-4">
                        <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 className="text-lg font-semibold text-gray-900">Ticket summary</h3>

                            <div className="mt-5 space-y-4">
                                <div className="flex items-start gap-3">
                                    <div className="mt-0.5 rounded-xl bg-gray-100 p-2 text-gray-600">
                                        <Tag className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                            Ticket number
                                        </div>
                                        <div className="mt-1 text-sm font-medium text-gray-900">
                                            {ticket.ticket_number}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <div className="mt-0.5 rounded-xl bg-gray-100 p-2 text-gray-600">
                                        <CircleDot className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                            Status
                                        </div>
                                        <div className="mt-1 text-sm font-medium text-gray-900">
                                            {humanize(ticket.status)}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <div className="mt-0.5 rounded-xl bg-gray-100 p-2 text-gray-600">
                                        <Shield className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                            Priority
                                        </div>
                                        <div className="mt-1 text-sm font-medium text-gray-900">
                                            {humanize(ticket.priority)}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <div className="mt-0.5 rounded-xl bg-gray-100 p-2 text-gray-600">
                                        <FolderOpen className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                            Category
                                        </div>
                                        <div className="mt-1 text-sm font-medium text-gray-900">
                                            {ticket.category?.name || '—'}
                                        </div>
                                    </div>
                                </div>

                                {ticket.service && (
                                    <div className="flex items-start gap-3">
                                        <div className="mt-0.5 rounded-xl bg-gray-100 p-2 text-gray-600">
                                            <Tag className="h-4 w-4" />
                                        </div>
                                        <div>
                                            <div className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                                Product / area
                                            </div>
                                            <div className="mt-1 text-sm font-medium text-gray-900">
                                                {ticket.service}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="flex items-start gap-3">
                                    <div className="mt-0.5 rounded-xl bg-gray-100 p-2 text-gray-600">
                                        <CalendarDays className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                            Created
                                        </div>
                                        <div className="mt-1 text-sm font-medium text-gray-900">
                                            {formatDate(ticket.created_at)}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <div className="mt-0.5 rounded-xl bg-gray-100 p-2 text-gray-600">
                                        <Clock3 className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                            Last activity
                                        </div>
                                        <div className="mt-1 text-sm font-medium text-gray-900">
                                            {formatDate(ticket.last_reply_at || ticket.updated_at)}
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <div className="mt-0.5 rounded-xl bg-gray-100 p-2 text-gray-600">
                                        <User2 className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                            Assigned to
                                        </div>
                                        <div className="mt-1 text-sm font-medium text-gray-900">
                                            {ticket.assignee?.name || 'Not assigned yet'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Reply tips
                            </h3>

                            <div className="mt-4 space-y-3">
                                <div className="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                                    <div className="text-sm font-medium text-gray-900">
                                        Keep everything in one thread
                                    </div>
                                    <div className="mt-1 text-sm leading-6 text-gray-600">
                                        Reply here instead of creating duplicate tickets for the same issue.
                                    </div>
                                </div>

                                <div className="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                                    <div className="text-sm font-medium text-gray-900">
                                        Be specific
                                    </div>
                                    <div className="mt-1 text-sm leading-6 text-gray-600">
                                        Include exact error text, recent changes, and steps to reproduce the issue.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </section>
            </div>
        </UserLayout>
    )
}

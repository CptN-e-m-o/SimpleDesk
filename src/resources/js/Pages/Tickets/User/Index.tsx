import { Head, Link, router, useForm } from '@inertiajs/react'
import {
    ChevronRight,
    Clock3,
    FolderOpen,
    Plus,
    Search,
    Ticket,
    X,
    House,
} from 'lucide-react'
import Breadcrumbs from '@/Components/Breadcrumbs'
import { FormEvent } from 'react'

import UserLayout from '@/Layouts/UserLayout'
import { route } from 'ziggy-js'

type TicketCategory = {
    id: number
    name: string
} | null

type TicketItem = {
    id: number
    ticket_number: string
    subject: string
    status: string
    priority: string
    service: string | null
    created_at: string | null
    last_reply_at: string | null
    category: TicketCategory
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type PaginatedTickets = {
    data: TicketItem[]
    links: PaginationLink[]
    total: number
    from: number | null
    to: number | null
}

type FilterOption = {
    value: string
    label: string
}

type Props = {
    tickets: PaginatedTickets
    filters: {
        search: string
        status: string
        priority: string
    }
    statusOptions: FilterOption[]
    priorityOptions: FilterOption[]
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

function humanize(value: string) {
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

export default function TicketsIndex({
                                         tickets,
                                         filters,
                                         statusOptions,
                                         priorityOptions,
                                     }: Props) {
    const hasTickets = tickets.data.length > 0

    const form = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
        priority: filters.priority ?? '',
    })

    function submit(e: FormEvent) {
        e.preventDefault()

        form.get(route('tickets.index'), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    }

    function resetFilters() {
        form.setData({
            search: '',
            status: '',
            priority: '',
        })

        router.get(
            route('tickets.index'),
            {
                search: '',
                status: '',
                priority: '',
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    const hasActiveFilters =
        form.data.search !== '' || form.data.status !== '' || form.data.priority !== ''

    return (
        <UserLayout>
            <Head title="My Tickets" />

            <div className="space-y-8">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="grid gap-8 px-6 py-8 sm:px-8 lg:grid-cols-[1.15fr_0.85fr] lg:px-10 lg:py-10">
                        <div>
                            <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                                <Ticket className="h-3.5 w-3.5" />
                                Support history
                            </div>

                            <Breadcrumbs
                                className="mb-4"
                                items={[
                                    {
                                        label: 'Home',
                                        href: route('home'),
                                        icon: <House className="h-4 w-4" />,
                                    },
                                    {
                                        label: 'My tickets',
                                    },
                                ]}
                            />

                            <h1 className="text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
                                My tickets
                            </h1>

                            <p className="mt-4 max-w-2xl text-base leading-7 text-gray-600 sm:text-lg">
                                Track your support requests, monitor statuses, and find recent
                                updates in one place.
                            </p>

                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Link
                                    href={route('tickets.create')}
                                    className="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-gray-900 px-5 text-sm font-medium text-white transition hover:bg-gray-800"
                                >
                                    <Plus className="h-4 w-4" />
                                    Create ticket
                                </Link>

                                <Link
                                    href="/knowledge-base"
                                    className="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                                >
                                    Browse knowledge base
                                </Link>
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div className="rounded-3xl border border-gray-200 bg-gray-50 p-6">
                                <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-sky-700">
                                    <Clock3 className="h-6 w-6" />
                                </div>

                                <h2 className="text-lg font-semibold text-gray-900">
                                    {tickets.total} total ticket{tickets.total === 1 ? '' : 's'}
                                </h2>

                                <p className="mt-2 text-sm leading-6 text-gray-600">
                                    All requests you have submitted through the support center.
                                </p>
                            </div>

                            <div className="rounded-3xl border border-gray-200 bg-gray-900 p-6 text-white">
                                <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10">
                                    <FolderOpen className="h-6 w-6" />
                                </div>

                                <h2 className="text-lg font-semibold">Need a quick answer?</h2>

                                <p className="mt-2 text-sm leading-6 text-gray-300">
                                    Search articles and guides before opening a new request.
                                </p>

                                <Link
                                    href="/knowledge-base"
                                    className="mt-5 inline-flex items-center gap-2 text-sm font-medium text-sky-300 transition hover:text-sky-200"
                                >
                                    Open knowledge base
                                    <ChevronRight className="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div className="border-b border-gray-100 pb-6">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h2 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    Ticket list
                                </h2>
                                <p className="mt-2 text-sm leading-6 text-gray-600">
                                    Search and filter your requests by status or priority.
                                </p>
                            </div>
                        </div>

                        <form onSubmit={submit} className="mt-6 flex flex-col gap-3 xl:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    value={form.data.search}
                                    onChange={(e) => form.setData('search', e.target.value)}
                                    placeholder="Search by ticket number, subject, or description..."
                                    className="h-11 w-full rounded-2xl border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                                />
                            </div>

                            <select
                                value={form.data.status}
                                onChange={(e) => form.setData('status', e.target.value)}
                                className="h-11 rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-700 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                            >
                                {statusOptions.map((option) => (
                                    <option key={option.value || 'all-statuses'} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>

                            <select
                                value={form.data.priority}
                                onChange={(e) => form.setData('priority', e.target.value)}
                                className="h-11 rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-700 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                            >
                                {priorityOptions.map((option) => (
                                    <option key={option.value || 'all-priorities'} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>

                            <button
                                type="submit"
                                className="inline-flex h-11 items-center justify-center rounded-2xl bg-gray-900 px-5 text-sm font-medium text-white transition hover:bg-gray-800"
                            >
                                Apply
                            </button>

                            {hasActiveFilters && (
                                <button
                                    type="button"
                                    onClick={resetFilters}
                                    className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                                >
                                    <X className="h-4 w-4" />
                                    Reset
                                </button>
                            )}
                        </form>
                    </div>

                    {hasTickets ? (
                        <div className="mt-6 space-y-4">
                            {tickets.data.map((ticket) => (
                                <Link
                                    key={ticket.id}
                                    href={route('tickets.show', ticket.id)}
                                    className="group block rounded-[24px] border border-gray-200 bg-gray-50 p-5 transition hover:border-sky-200 hover:bg-white hover:shadow-sm"
                                >
                                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-500 ring-1 ring-inset ring-gray-200">
                                                    {ticket.ticket_number}
                                                </span>

                                                {ticket.category?.name && (
                                                    <span className="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
                                                        {ticket.category.name}
                                                    </span>
                                                )}

                                                {ticket.service && (
                                                    <span className="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
                                                        {ticket.service}
                                                    </span>
                                                )}
                                            </div>

                                            <h3 className="mt-4 text-lg font-semibold text-gray-900 transition group-hover:text-sky-800">
                                                {ticket.subject}
                                            </h3>

                                            <div className="mt-4 flex flex-wrap items-center gap-3 text-sm text-gray-500">
                                                <span>Created: {formatDate(ticket.created_at)}</span>
                                                <span className="hidden sm:inline">•</span>
                                                <span>
                                                    Last activity:{' '}
                                                    {formatDate(ticket.last_reply_at || ticket.created_at)}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="flex shrink-0 flex-wrap items-center gap-2">
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
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    ) : (
                        <div className="mt-6 rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
                            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-sky-700 shadow-sm">
                                <Ticket className="h-7 w-7" />
                            </div>

                            <h3 className="mt-5 text-xl font-semibold text-gray-900">
                                No matching tickets
                            </h3>

                            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-600">
                                We couldn’t find any tickets for the selected filters. Try adjusting
                                your search or create a new request.
                            </p>

                            <div className="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                                {hasActiveFilters && (
                                    <button
                                        type="button"
                                        onClick={resetFilters}
                                        className="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                                    >
                                        Clear filters
                                    </button>
                                )}

                                <Link
                                    href={route('tickets.create')}
                                    className="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-gray-900 px-5 text-sm font-medium text-white transition hover:bg-gray-800"
                                >
                                    <Plus className="h-4 w-4" />
                                    Create ticket
                                </Link>
                            </div>
                        </div>
                    )}

                    {tickets.links.length > 3 && (
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-2 border-t border-gray-100 pt-6">
                            {tickets.links.map((link, index) => (
                                <Link
                                    key={`${link.label}-${index}`}
                                    href={link.url || '#'}
                                    preserveScroll
                                    className={`inline-flex min-w-10 items-center justify-center rounded-xl px-3 py-2 text-sm font-medium transition ${
                                        link.active
                                            ? 'bg-gray-900 text-white'
                                            : link.url
                                                ? 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                                : 'cursor-not-allowed border border-gray-100 bg-gray-100 text-gray-400'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </UserLayout>
    )
}

import AdminLayout from '@/Layouts/AdminLayout'
import { Head } from '@inertiajs/react'
import {
    Activity,
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    CircleAlert,
    CircleCheck,
    Eye,
    Inbox,
    Mailbox as MailboxIcon,
    Pencil,
    Plus,
    Search,
    Send,
    Trash2,
    TriangleAlert,
} from 'lucide-react'
import { ReactNode, useMemo, useState } from 'react'
import {
    Mailbox,
    MailboxChannelSummary,
    MailboxSummary,
    formatDateTime,
    formatDriver,
    formatHealthStatus,
    getChannelHealthClasses,
    getMailboxOverallStatus,
    getMailboxSearchText,
    getMailboxStatusClasses,
    getMailboxStatusLabel,
    isMailboxConfigured,
} from './shared'

type Props = {
    readonly mailboxes?: Mailbox[]
    readonly summary?: MailboxSummary
    readonly system_mail_configured?: boolean
}

type SortField =
    | 'mailbox'
    | 'department'
    | 'incoming'
    | 'outgoing'
    | 'status'
    | 'updated_at'

type SortDirection = 'asc' | 'desc'

type SummaryCardProps = {
    label: string
    value: number
    icon: ReactNode
    iconClasses: string
}

type ChannelCellProps = {
    channel: MailboxChannelSummary | null
    direction: 'incoming' | 'outgoing'
}

function SummaryCard({
                         label,
                         value,
                         icon,
                         iconClasses,
                     }: SummaryCardProps) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-white px-4 py-4">
        <div className="flex items-center justify-between gap-4">
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
            {label}
            </p>

            <p className="mt-2 text-2xl font-semibold text-gray-900">
        {value}
        </p>
        </div>

        <div
    className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ${iconClasses}`}
>
    {icon}
    </div>
    </div>
    </div>
)
}

function ChannelCell({
                         channel,
                         direction,
                     }: ChannelCellProps) {
    const DirectionIcon =
        direction === 'incoming'
            ? Inbox
            : Send

    if (!channel) {
        return (
            <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
            <DirectionIcon className="h-4 w-4" />
            </div>

            <div>
            <p className="text-sm font-medium text-gray-500">
                Not configured
        </p>

        <p className="mt-0.5 text-xs text-gray-400">
            No {direction} channel
        </p>
        </div>
        </div>
    )
    }

    return (
        <div className="flex min-w-0 items-center gap-3">
        <div
            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl ${
            channel.is_enabled
                ? 'bg-sky-50 text-sky-600'
                : 'bg-gray-100 text-gray-400'
        }`}
>
    <DirectionIcon className="h-4 w-4" />
        </div>

        <div className="min-w-0">
    <div className="flex flex-wrap items-center gap-2">
    <p className="truncate text-sm font-semibold text-gray-900">
        {formatDriver(channel.driver)}
    </p>

    {channel.is_primary ? (
        <span className="inline-flex rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 ring-1 ring-inset ring-sky-200">
            Primary
            </span>
    ) : null}
    </div>

    <div className="mt-1 flex flex-wrap items-center gap-2">
    <span
        className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset ${getChannelHealthClasses(
        channel.health_status,
    )}`}
>
    {channel.is_enabled
        ? formatHealthStatus(
            channel.health_status,
        )
        : 'Disabled'}
    </span>

    <span className="truncate text-xs text-gray-400">
        {channel.name}
        </span>
        </div>
        </div>
        </div>
)
}

function sortValue(
    mailbox: Mailbox,
    field: SortField,
): string {
    switch (field) {
        case 'mailbox':
            return mailbox.name.toLowerCase()

        case 'department':
            return (
                mailbox.department?.name.toLowerCase() ??
                ''
            )

        case 'incoming':
            return (
                mailbox.incoming_channel?.driver?.toLowerCase() ??
                ''
            )

        case 'outgoing':
            return (
                mailbox.outgoing_channel?.driver?.toLowerCase() ??
                ''
            )

        case 'status':
            return getMailboxOverallStatus(mailbox)

        case 'updated_at':
            return mailbox.updated_at ?? ''

        default:
            return ''
    }
}

function calculateSummary(
    mailboxes: Mailbox[],
): MailboxSummary {
    return {
        total: mailboxes.length,

        active: mailboxes.filter(
            (mailbox) => mailbox.is_active,
        ).length,

        configured: mailboxes.filter(
            isMailboxConfigured,
        ).length,

        healthy: mailboxes.filter(
            (mailbox) =>
                getMailboxOverallStatus(mailbox) ===
                'healthy',
        ).length,

        needs_attention: mailboxes.filter(
            (mailbox) =>
                mailbox.is_active &&
                getMailboxOverallStatus(mailbox) !==
                'healthy',
        ).length,
    }
}

export default function Index({
                                  mailboxes = [],
                                  summary,
                                  system_mail_configured = false,
                              }: Props) {
    const [search, setSearch] = useState('')
    const [sortField, setSortField] =
        useState<SortField>('mailbox')
    const [sortDirection, setSortDirection] =
        useState<SortDirection>('asc')

    const effectiveSummary = useMemo(
        () => summary ?? calculateSummary(mailboxes),
        [mailboxes, summary],
    )

    const filteredMailboxes = useMemo(() => {
        const query = search
            .trim()
            .toLowerCase()

        if (!query) {
            return mailboxes
        }

        return mailboxes.filter((mailbox) =>
            getMailboxSearchText(mailbox).includes(
                query,
            ),
        )
    }, [mailboxes, search])

    const sortedMailboxes = useMemo(() => {
        const result = [...filteredMailboxes]

        result.sort((first, second) => {
            const firstValue = sortValue(
                first,
                sortField,
            )

            const secondValue = sortValue(
                second,
                sortField,
            )

            const comparison =
                firstValue.localeCompare(
                    secondValue,
                    undefined,
                    {
                        numeric: true,
                    },
                )

            return sortDirection === 'asc'
                ? comparison
                : -comparison
        })

        return result
    }, [
        filteredMailboxes,
        sortDirection,
        sortField,
    ])

    function handleSort(field: SortField) {
        if (sortField === field) {
            setSortDirection((current) =>
                current === 'asc'
                    ? 'desc'
                    : 'asc',
            )

            return
        }

        setSortField(field)
        setSortDirection('asc')
    }

    function renderSortIcon(field: SortField) {
        if (sortField !== field) {
            return (
                <ArrowUpDown className="h-4 w-4 text-gray-400" />
            )
        }

        return sortDirection === 'asc' ? (
            <ArrowUp className="h-4 w-4 text-sky-600" />
        ) : (
            <ArrowDown className="h-4 w-4 text-sky-600" />
        )
    }

    function sortableHeader(
        label: string,
        field: SortField,
    ) {
        return (
            <button
                type="button"
        onClick={() => handleSort(field)}
        className="inline-flex cursor-pointer items-center gap-2 transition hover:text-sky-700"
            >
            {label}
        {renderSortIcon(field)}
        </button>
    )
    }

    return (
        <AdminLayout title="Email Settings">
        <Head title="Email Settings" />

        <div className="space-y-6">
            {!system_mail_configured ? (
        <section className="rounded-[24px] border border-amber-200 bg-amber-50 px-5 py-4">
        <div className="flex items-start gap-3">
        <TriangleAlert className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />

        <div>
            <h2 className="text-sm font-semibold text-amber-900">
            System email is not fully configured
    </h2>

    <p className="mt-1 text-sm leading-6 text-amber-800">
        SimpleDesk cannot reliably fetch
    incoming tickets or deliver outgoing
    replies until at least one active
    mailbox has enabled incoming and
    outgoing channels.
    </p>
    </div>
    </div>
    </section>
) : null}

    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div className="min-w-0">
    <div className="flex items-center gap-3">
    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
    <MailboxIcon className="h-6 w-6 text-sky-600" />
    </div>

    <div>
    <h1 className="text-xl font-semibold tracking-tight text-gray-900">
        Email Settings
    </h1>

    <p className="mt-1 text-sm text-gray-500">
        Configure support mailboxes
    and monitor incoming and
    outgoing email channels.
    </p>
    </div>
    </div>
    </div>

    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
    <div className="relative w-full sm:w-96">
    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

    <input
        type="text"
    value={search}
    onChange={(event) =>
    setSearch(
        event.target.value,
    )
}
    placeholder="Search mailboxes, addresses, departments, or status..."
    className="h-11 w-full rounded-2xl border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
        />
        </div>

        <button
    type="button"
    disabled
    title="Mailbox creation will be implemented on the next step."
    className="inline-flex h-11 cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white opacity-60"
    >
    <Plus className="h-4 w-4" />
        New Mailbox
    </button>
    </div>
    </div>
    </div>

    <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
    <SummaryCard
        label="Total mailboxes"
    value={
        effectiveSummary.total
    }
    icon={
        <MailboxIcon className="h-5 w-5" />
}
    iconClasses="bg-slate-100 text-slate-600"
    />

    <SummaryCard
        label="Active"
    value={
        effectiveSummary.active
    }
    icon={
        <Activity className="h-5 w-5" />
}
    iconClasses="bg-sky-50 text-sky-600"
    />

    <SummaryCard
        label="Configured"
    value={
        effectiveSummary.configured
    }
    icon={
        <CircleCheck className="h-5 w-5" />
}
    iconClasses="bg-indigo-50 text-indigo-600"
    />

    <SummaryCard
        label="Healthy"
    value={
        effectiveSummary.healthy
    }
    icon={
        <CircleCheck className="h-5 w-5" />
}
    iconClasses="bg-emerald-50 text-emerald-600"
    />

    <SummaryCard
        label="Need attention"
    value={
        effectiveSummary.needs_attention
    }
    icon={
        <CircleAlert className="h-5 w-5" />
}
    iconClasses="bg-amber-50 text-amber-600"
        />
        </div>
        </div>

        <div
    id="mailboxes-list"
    className="p-6"
        >
        {sortedMailboxes.length > 0 ? (
                <>
                    <div className="hidden overflow-x-auto rounded-[24px] border border-gray-200 xl:block">
                <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                <tr>
                    <th className="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {sortableHeader(
                        'Mailbox',
                        'mailbox',
            )}
        </th>

        <th className="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        {sortableHeader(
            'Department',
            'department',
)}
    </th>

    <th className="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        {sortableHeader(
            'Incoming',
            'incoming',
)}
    </th>

    <th className="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        {sortableHeader(
            'Outgoing',
            'outgoing',
)}
    </th>

    <th className="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        {sortableHeader(
            'Status',
            'status',
)}
    </th>

    <th className="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        {sortableHeader(
            'Updated',
            'updated_at',
)}
    </th>

    <th className="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
        Actions
        </th>
        </tr>
        </thead>

        <tbody className="divide-y divide-gray-100 bg-white">
    {sortedMailboxes.map(
            (mailbox) => (
                <tr
                    key={
                    mailbox.id
                }
        className={`transition ${
            mailbox.is_active
                ? 'hover:bg-sky-50/40'
                : 'bg-gray-50/70 hover:bg-gray-100/80'
        }`}
    >
    <td className="px-5 py-5">
    <div className="min-w-56">
    <div className="flex flex-wrap items-center gap-2">
    <p className="font-semibold text-gray-900">
        {
            mailbox.name
        }
        </p>

    {mailbox.is_default_outgoing ? (
        <span className="inline-flex rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-violet-700 ring-1 ring-inset ring-violet-200">
            Default
            </span>
    ) : null}
    </div>

    <p className="mt-1 text-sm text-gray-600">
        {
            mailbox.email_address
        }
        </p>

        <p className="mt-1 text-xs text-gray-400">
        {mailbox.display_name ||
                `Mailbox #${mailbox.id}`}
        </p>
        </div>
        </td>

        <td className="px-5 py-5 text-sm text-gray-600">
        {mailbox
            .department
                    ?.name ??
                '—'}
        </td>

        <td className="px-5 py-5">
    <ChannelCell
        channel={
        mailbox.incoming_channel
    }
    direction="incoming"
        />
        </td>

        <td className="px-5 py-5">
    <ChannelCell
        channel={
        mailbox.outgoing_channel
    }
    direction="outgoing"
        />
        </td>

        <td className="px-5 py-5">
    <div className="flex flex-col items-start gap-2">
    <span
        className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getMailboxStatusClasses(
        mailbox,
    )}`}
>
    {getMailboxStatusLabel(
        mailbox,
    )}
    </span>

    <span className="text-xs text-gray-400">
        {
            mailbox.channels_count
        }{' '}
    {mailbox.channels_count ===
    1
        ? 'channel'
        : 'channels'}
    </span>
    </div>
    </td>

    <td className="px-5 py-5 text-sm text-gray-500">
        {formatDateTime(
                mailbox.updated_at,
)}
    </td>

    <td className="px-5 py-5">
    <div className="flex items-center justify-end gap-2">
    <button
        type="button"
    disabled
    title="Mailbox details page will be added next."
    className="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-400 opacity-60"
    >
    <Eye className="h-4 w-4" />
        </button>

        <button
    type="button"
    disabled
    title="Mailbox editing page will be added next."
    className="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-400 opacity-60"
    >
    <Pencil className="h-4 w-4" />
        </button>

        <button
    type="button"
    disabled
    title="Mailbox deletion will be connected after the edit page."
    className="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-400 opacity-60"
    >
    <Trash2 className="h-4 w-4" />
        </button>
        </div>
        </td>
        </tr>
),
)}
    </tbody>
    </table>
    </div>

    <div className="grid gap-4 xl:hidden">
    {sortedMailboxes.map(
            (mailbox) => (
                <article
                    key={mailbox.id}
        className={`rounded-[24px] border p-5 ${
            mailbox.is_active
                ? 'border-gray-200 bg-white'
                : 'border-gray-200 bg-gray-50'
        }`}
    >
    <div className="flex items-start justify-between gap-4">
    <div className="min-w-0">
    <div className="flex flex-wrap items-center gap-2">
    <h2 className="truncate text-base font-semibold text-gray-900">
        {
            mailbox.name
        }
        </h2>

    {mailbox.is_default_outgoing ? (
        <span className="inline-flex rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-violet-700 ring-1 ring-inset ring-violet-200">
            Default
            </span>
    ) : null}
    </div>

    <p className="mt-1 break-all text-sm text-gray-600">
        {
            mailbox.email_address
        }
        </p>

        <p className="mt-1 text-xs text-gray-400">
        {mailbox.display_name ||
                `Mailbox #${mailbox.id}`}
        </p>
        </div>

        <span
    className={`inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getMailboxStatusClasses(
        mailbox,
    )}`}
>
    {getMailboxStatusLabel(
        mailbox,
    )}
    </span>
    </div>

    <div className="mt-4 rounded-2xl bg-gray-50 px-4 py-3 ring-1 ring-inset ring-gray-200">
    <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
        Department
        </p>

        <p className="mt-1 text-sm font-medium text-gray-900">
        {mailbox
            .department
                    ?.name ??
                'Not assigned'}
        </p>
        </div>

        <div className="mt-3 grid gap-3 md:grid-cols-2">
    <div className="rounded-2xl bg-gray-50 px-4 py-4 ring-1 ring-inset ring-gray-200">
    <p className="mb-3 text-xs font-medium uppercase tracking-wide text-gray-400">
        Incoming
        </p>

        <ChannelCell
    channel={
        mailbox.incoming_channel
    }
    direction="incoming"
        />
        </div>

        <div className="rounded-2xl bg-gray-50 px-4 py-4 ring-1 ring-inset ring-gray-200">
    <p className="mb-3 text-xs font-medium uppercase tracking-wide text-gray-400">
        Outgoing
        </p>

        <ChannelCell
    channel={
        mailbox.outgoing_channel
    }
    direction="outgoing"
        />
        </div>
        </div>

        <div className="mt-3 flex flex-col gap-2 rounded-2xl bg-gray-50 px-4 py-3 text-sm text-gray-500 ring-1 ring-inset ring-gray-200 sm:flex-row sm:items-center sm:justify-between">
        <span>
            {
                mailbox.channels_count
            }{' '}
    {mailbox.channels_count ===
    1
        ? 'channel'
        : 'channels'}
    </span>

    <span>
    Updated{' '}
    {formatDateTime(
        mailbox.updated_at,
    )}
    </span>
    </div>

    <div className="mt-4 flex items-center gap-2">
    <button
        type="button"
    disabled
    title="Mailbox details page will be added next."
    className="inline-flex h-10 flex-1 cursor-not-allowed items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white text-sm font-medium text-gray-400 opacity-60"
    >
    <Eye className="h-4 w-4" />
        View
        </button>

        <button
    type="button"
    disabled
    title="Mailbox editing page will be added next."
    className="inline-flex h-10 flex-1 cursor-not-allowed items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white text-sm font-medium text-gray-400 opacity-60"
    >
    <Pencil className="h-4 w-4" />
        Edit
        </button>

        <button
    type="button"
    disabled
    title="Mailbox deletion will be connected later."
    className="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-400 opacity-60"
    >
    <Trash2 className="h-4 w-4" />
        </button>
        </div>
        </article>
),
)}
    </div>

    <div className="mt-4 flex flex-col gap-2 px-1 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between">
        <span>
            Showing{' '}
    {sortedMailboxes.length}{' '}
    of {mailboxes.length}{' '}
    mailboxes
    </span>

    {search ? (
            <button
                type="button"
        onClick={() =>
        setSearch('')
    }
        className="cursor-pointer font-medium text-sky-600 transition hover:text-sky-700"
            >
            Clear search
    </button>
    ) : null}
    </div>
    </>
) : (
        <div className="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
        <div className="flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-sm ring-1 ring-inset ring-gray-200">
        <MailboxIcon className="h-8 w-8 text-gray-400" />
            </div>

            <h2 className="mt-5 text-lg font-semibold text-gray-900">
    {search
        ? 'No mailboxes found'
        : 'No mailboxes configured'}
    </h2>

    <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
    {search
        ? 'Try changing your search query or clear the search field.'
        : 'Create the first support mailbox to start receiving tickets and sending agent replies by email.'}
    </p>

    {search ? (
            <button
                type="button"
        onClick={() =>
        setSearch('')
    }
        className="mt-6 inline-flex h-11 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
            >
            Clear Search
    </button>
    ) : (
        <button
            type="button"
        disabled
        title="Mailbox creation will be implemented on the next step."
        className="mt-6 inline-flex h-11 cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white opacity-60"
        >
        <Plus className="h-4 w-4" />
            Create Mailbox
    </button>
    )}
    </div>
)}
    </div>
    </section>
    </div>
    </AdminLayout>
)
}

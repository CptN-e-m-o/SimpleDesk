import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link } from '@inertiajs/react'
import {
    Activity,
    ArrowLeft,
    Building2,
    CalendarDays,
    CircleCheck,
    CircleX,
    FileText,
    Inbox,
    Mail,
    Mailbox as MailboxIcon,
    Pencil,
    Send,
    Server,
    ShieldCheck,
    Trash2,
} from 'lucide-react'
import { route } from 'ziggy-js'
import {
    Mailbox,
    formatDateTime,
    formatDriver,
    formatHealthStatus,
    getChannelHealthClasses,
    getMailboxStatusClasses,
    getMailboxStatusLabel,
} from './shared'

type MailboxShowChannel = {
    id: number
    name: string
    direction: string | null
    driver: string | null
    auth_type: string | null
    is_enabled: boolean
    is_primary: boolean
    failover_order: number
    health_status: string | null
    configuration: Record<string, unknown>
    created_at: string | null
    updated_at: string | null
}

type MailboxShow = Mailbox & {
    internal_notes: string | null
    email_messages_count: number
    channels: MailboxShowChannel[]
    created_at: string | null
}

type Props = {
    readonly mailbox: MailboxShow
}

type InformationItemProps = {
    label: string
    value: string
    icon: React.ReactNode
}

function InformationItem({
                             label,
                             value,
                             icon,
                         }: InformationItemProps) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
            <div className="flex items-start gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-gray-500 ring-1 ring-inset ring-gray-200">
                    {icon}
                </div>

                <div className="min-w-0">
                    <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                        {label}
                    </p>

                    <p className="mt-1 break-words text-sm font-medium text-gray-900">
                        {value}
                    </p>
                </div>
            </div>
        </div>
    )
}

function formatConfigurationKey(
    value: string,
): string {
    return value
        .replace(/_/g, ' ')
        .replace(
            /\b\w/g,
            (character: string) =>
                character.toUpperCase(),
        )
}

function formatConfigurationValue(
    value: unknown,
): string {
    if (value === null || value === undefined) {
        return '—'
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No'
    }

    if (typeof value === 'number') {
        return value.toString()
    }

    if (typeof value === 'string') {
        return value || '—'
    }

    try {
        return JSON.stringify(value)
    } catch {
        return '—'
    }
}

function formatAuthType(
    value: string | null,
): string {
    if (!value) {
        return 'None'
    }

    switch (value.toLowerCase()) {
        case 'password':
            return 'Username and password'

        case 'oauth2':
            return 'OAuth 2.0'

        case 'none':
            return 'No authentication'

        default:
            return value
    }
}

function ChannelCard({
                         channel,
                     }: {
    readonly channel: MailboxShowChannel
}) {
    const incoming =
        channel.direction === 'incoming'

    const DirectionIcon = incoming
        ? Inbox
        : Send

    const configurationEntries = Object.entries(
        channel.configuration ?? {},
    )

    return (
        <article className="overflow-hidden rounded-[24px] border border-gray-200 bg-white">
            <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex min-w-0 items-start gap-3">
                        <div
                            className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ${
                                incoming
                                    ? 'bg-sky-50 text-sky-600'
                                    : 'bg-violet-50 text-violet-600'
                            }`}
                        >
                            <DirectionIcon className="h-5 w-5" />
                        </div>

                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <h3 className="truncate font-semibold text-gray-900">
                                    {channel.name}
                                </h3>

                                {channel.is_primary ? (
                                    <span className="inline-flex rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 ring-1 ring-inset ring-sky-200">
                                        Primary
                                    </span>
                                ) : null}
                            </div>

                            <p className="mt-1 text-sm text-gray-500">
                                {incoming
                                    ? 'Incoming email channel'
                                    : 'Outgoing email channel'}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <span
                            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${
                                channel.is_enabled
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                    : 'bg-gray-100 text-gray-600 ring-gray-200'
                            }`}
                        >
                            {channel.is_enabled
                                ? 'Enabled'
                                : 'Disabled'}
                        </span>

                        <span
                            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getChannelHealthClasses(
                                channel.health_status,
                            )}`}
                        >
                            {formatHealthStatus(
                                channel.health_status,
                            )}
                        </span>
                    </div>
                </div>
            </div>

            <div className="space-y-5 px-5 py-5">
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <InformationItem
                        label="Driver"
                        value={formatDriver(
                            channel.driver ?? '',
                        )}
                        icon={
                            <Server className="h-4 w-4" />
                        }
                    />

                    <InformationItem
                        label="Authentication"
                        value={formatAuthType(
                            channel.auth_type,
                        )}
                        icon={
                            <ShieldCheck className="h-4 w-4" />
                        }
                    />

                    <InformationItem
                        label="Failover order"
                        value={channel.failover_order.toString()}
                        icon={
                            <Activity className="h-4 w-4" />
                        }
                    />

                    <InformationItem
                        label="Updated"
                        value={formatDateTime(
                            channel.updated_at,
                        )}
                        icon={
                            <CalendarDays className="h-4 w-4" />
                        }
                    />
                </div>

                <div>
                    <h4 className="text-sm font-semibold text-gray-900">
                        Connection Configuration
                    </h4>

                    {configurationEntries.length > 0 ? (
                        <div className="mt-3 overflow-hidden rounded-2xl border border-gray-200">
                            <dl className="divide-y divide-gray-100">
                                {configurationEntries.map(
                                    ([key, value]) => (
                                        <div
                                            key={key}
                                            className="grid gap-1 bg-white px-4 py-3 sm:grid-cols-[220px_minmax(0,1fr)] sm:gap-4"
                                        >
                                            <dt className="text-sm font-medium text-gray-500">
                                                {formatConfigurationKey(
                                                    key,
                                                )}
                                            </dt>

                                            <dd className="break-all text-sm text-gray-900">
                                                {formatConfigurationValue(
                                                    value,
                                                )}
                                            </dd>
                                        </div>
                                    ),
                                )}
                            </dl>
                        </div>
                    ) : (
                        <div className="mt-3 rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-sm text-gray-500">
                            No public configuration values are available.
                        </div>
                    )}
                </div>
            </div>
        </article>
    )
}

export default function Show({
                                 mailbox,
                             }: Props) {
    return (
        <AdminLayout title={mailbox.name}>
            <Head title={mailbox.name} />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div className="flex min-w-0 items-start gap-4">
                                <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-[20px] bg-sky-50 text-sky-600 ring-1 ring-inset ring-sky-100">
                                    <MailboxIcon className="h-7 w-7" />
                                </div>

                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h1 className="break-words text-xl font-semibold tracking-tight text-gray-900">
                                            {mailbox.name}
                                        </h1>

                                        {mailbox.is_deleted ? (
                                            <span className="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                                Deleted
                                            </span>
                                        ) : (
                                            <span
                                                className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getMailboxStatusClasses(
                                                    mailbox,
                                                )}`}
                                            >
                                                {getMailboxStatusLabel(
                                                    mailbox,
                                                )}
                                            </span>
                                        )}

                                        {mailbox.is_default_outgoing &&
                                        !mailbox.is_deleted ? (
                                            <span className="inline-flex rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                                                Default outgoing
                                            </span>
                                        ) : null}
                                    </div>

                                    <p className="mt-2 break-all text-sm text-gray-600">
                                        {mailbox.email_address}
                                    </p>

                                    <p className="mt-1 text-sm text-gray-500">
                                        {mailbox.display_name ||
                                            'No display name'}
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Link
                                    href={route(
                                        'admin.email.settings.index',
                                    )}
                                    className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    Back
                                </Link>

                                {!mailbox.is_deleted ? (
                                    <Link
                                        href={route(
                                            'admin.email.settings.mailboxes.edit',
                                            mailbox.id,
                                        )}
                                        className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                    >
                                        <Pencil className="h-4 w-4" />
                                        Edit Mailbox
                                    </Link>
                                ) : null}
                            </div>
                        </div>
                    </div>

                    {mailbox.is_deleted ? (
                        <div className="border-b border-rose-200 bg-rose-50 px-6 py-4">
                            <div className="flex items-start gap-3">
                                <Trash2 className="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />

                                <div>
                                    <p className="text-sm font-semibold text-rose-900">
                                        This mailbox has been deleted
                                    </p>

                                    <p className="mt-1 text-sm text-rose-800">
                                        It can be restored from the Email
                                        Settings list or permanently removed.
                                        Deleted at{' '}
                                        {formatDateTime(
                                            mailbox.deleted_at,
                                        )}.
                                    </p>
                                </div>
                            </div>
                        </div>
                    ) : null}

                    <div className="grid gap-6 px-6 py-6 xl:grid-cols-[minmax(0,1fr)_340px]">
                        <div>
                            <h2 className="text-base font-semibold text-gray-900">
                                Mailbox Information
                            </h2>

                            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                <InformationItem
                                    label="Email address"
                                    value={mailbox.email_address}
                                    icon={
                                        <Mail className="h-4 w-4" />
                                    }
                                />

                                <InformationItem
                                    label="Display name"
                                    value={
                                        mailbox.display_name ||
                                        'Not specified'
                                    }
                                    icon={
                                        <MailboxIcon className="h-4 w-4" />
                                    }
                                />

                                <InformationItem
                                    label="Department"
                                    value={
                                        mailbox.department?.name ||
                                        'Not assigned'
                                    }
                                    icon={
                                        <Building2 className="h-4 w-4" />
                                    }
                                />

                                <InformationItem
                                    label="State"
                                    value={
                                        mailbox.is_deleted
                                            ? 'Deleted'
                                            : mailbox.is_active
                                                ? 'Active'
                                                : 'Disabled'
                                    }
                                    icon={
                                        mailbox.is_active &&
                                        !mailbox.is_deleted ? (
                                            <CircleCheck className="h-4 w-4" />
                                        ) : (
                                            <CircleX className="h-4 w-4" />
                                        )
                                    }
                                />

                                <InformationItem
                                    label="Channels"
                                    value={`${mailbox.channels_count} configured`}
                                    icon={
                                        <Server className="h-4 w-4" />
                                    }
                                />

                                <InformationItem
                                    label="Email messages"
                                    value={mailbox.email_messages_count.toString()}
                                    icon={
                                        <FileText className="h-4 w-4" />
                                    }
                                />

                                <InformationItem
                                    label="Created"
                                    value={formatDateTime(
                                        mailbox.created_at,
                                    )}
                                    icon={
                                        <CalendarDays className="h-4 w-4" />
                                    }
                                />

                                <InformationItem
                                    label="Updated"
                                    value={formatDateTime(
                                        mailbox.updated_at,
                                    )}
                                    icon={
                                        <CalendarDays className="h-4 w-4" />
                                    }
                                />
                            </div>
                        </div>

                        <aside>
                            <h2 className="text-base font-semibold text-gray-900">
                                Internal Notes
                            </h2>

                            <div className="mt-4 min-h-40 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
                                {mailbox.internal_notes ? (
                                    <p className="whitespace-pre-wrap break-words text-sm leading-6 text-gray-700">
                                        {mailbox.internal_notes}
                                    </p>
                                ) : (
                                    <p className="text-sm text-gray-400">
                                        No internal notes have been added.
                                    </p>
                                )}
                            </div>
                        </aside>
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 px-6 py-5">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                <Server className="h-5 w-5" />
                            </div>

                            <div>
                                <h2 className="text-base font-semibold text-gray-900">
                                    Mail Channels
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Incoming and outgoing connections configured
                                    for this mailbox.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4 px-6 py-6">
                        {mailbox.channels.length > 0 ? (
                            mailbox.channels.map(
                                (channel) => (
                                    <ChannelCard
                                        key={channel.id}
                                        channel={channel}
                                    />
                                ),
                            )
                        ) : (
                            <div className="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-12 text-center">
                                <div className="flex h-14 w-14 items-center justify-center rounded-3xl bg-white text-gray-400 shadow-sm ring-1 ring-inset ring-gray-200">
                                    <Server className="h-6 w-6" />
                                </div>

                                <h3 className="mt-4 font-semibold text-gray-900">
                                    No channels configured
                                </h3>

                                <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
                                    This mailbox does not have incoming or
                                    outgoing email channels yet.
                                </p>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </AdminLayout>
    )
}

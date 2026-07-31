export type MailboxChannelSummary = {
    id: number
    name: string
    direction: string
    driver: string | null
    health_status: string | null
    is_primary: boolean
    is_enabled: boolean
    failover_order: number
    last_checked_at: string | null
    last_success_at: string | null
    last_error_at: string | null
}

export type MailboxDepartment = {
    id: number
    name: string
}

export type Mailbox = {
    id: number
    name: string
    email_address: string
    display_name: string | null
    is_active: boolean
    is_default_outgoing: boolean
    department: MailboxDepartment | null
    incoming_channel: MailboxChannelSummary | null
    outgoing_channel: MailboxChannelSummary | null
    channels_count: number
    created_at: string | null
    updated_at: string | null
}

export type MailboxSummary = {
    total: number
    active: number
    configured: number
    healthy: number
    needs_attention: number
}

export type MailboxOverallStatus =
    | 'disabled'
    | 'not_configured'
    | 'healthy'
    | 'warning'
    | 'failed'
    | 'unknown'

export function formatDriver(driver: string | null): string {
    if (!driver) {
        return 'Unknown driver'
    }

    const drivers: Record<string, string> = {
        smtp: 'SMTP',
        imap: 'IMAP',
        pop3: 'POP3',
        mailgun: 'Mailgun',
        ses: 'Amazon SES',
        sendgrid: 'SendGrid',
        postmark: 'Postmark',
        microsoft_graph: 'Microsoft Graph',
        gmail_api: 'Gmail API',
    }

    return (
        drivers[driver.toLowerCase()] ??
        driver
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, (character) => character.toUpperCase())
    )
}

export function formatHealthStatus(status: string | null): string {
    if (!status) {
        return 'Unknown'
    }

    const statuses: Record<string, string> = {
        healthy: 'Healthy',
        warning: 'Warning',
        failed: 'Failed',
        disabled: 'Disabled',
        unknown: 'Unknown',
    }

    return (
        statuses[status.toLowerCase()] ??
        status
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, (character) => character.toUpperCase())
    )
}

export function getChannelHealthClasses(
    status: string | null,
): string {
    switch (status?.toLowerCase()) {
        case 'healthy':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-200'

        case 'warning':
            return 'bg-amber-50 text-amber-700 ring-amber-200'

        case 'failed':
            return 'bg-rose-50 text-rose-700 ring-rose-200'

        case 'disabled':
            return 'bg-gray-100 text-gray-600 ring-gray-200'

        default:
            return 'bg-slate-50 text-slate-600 ring-slate-200'
    }
}

export function isMailboxConfigured(mailbox: Mailbox): boolean {
    return (
        mailbox.is_active &&
        mailbox.incoming_channel !== null &&
        mailbox.outgoing_channel !== null &&
        mailbox.incoming_channel.is_enabled &&
        mailbox.outgoing_channel.is_enabled
    )
}

export function getMailboxOverallStatus(
    mailbox: Mailbox,
): MailboxOverallStatus {
    if (!mailbox.is_active) {
        return 'disabled'
    }

    if (!isMailboxConfigured(mailbox)) {
        return 'not_configured'
    }

    const statuses = [
        mailbox.incoming_channel?.health_status?.toLowerCase(),
        mailbox.outgoing_channel?.health_status?.toLowerCase(),
    ]

    if (statuses.includes('failed')) {
        return 'failed'
    }

    if (statuses.includes('warning')) {
        return 'warning'
    }

    if (
        statuses.length === 2 &&
        statuses.every((status) => status === 'healthy')
    ) {
        return 'healthy'
    }

    return 'unknown'
}

export function getMailboxStatusLabel(
    mailbox: Mailbox,
): string {
    const status = getMailboxOverallStatus(mailbox)

    const labels: Record<MailboxOverallStatus, string> = {
        disabled: 'Disabled',
        not_configured: 'Not configured',
        healthy: 'Healthy',
        warning: 'Warning',
        failed: 'Failed',
        unknown: 'Unknown',
    }

    return labels[status]
}

export function getMailboxStatusClasses(
    mailbox: Mailbox,
): string {
    const status = getMailboxOverallStatus(mailbox)

    switch (status) {
        case 'healthy':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-200'

        case 'warning':
            return 'bg-amber-50 text-amber-700 ring-amber-200'

        case 'failed':
            return 'bg-rose-50 text-rose-700 ring-rose-200'

        case 'not_configured':
            return 'bg-orange-50 text-orange-700 ring-orange-200'

        case 'disabled':
            return 'bg-gray-100 text-gray-600 ring-gray-200'

        default:
            return 'bg-slate-50 text-slate-600 ring-slate-200'
    }
}

export function formatDateTime(
    value: string | null,
): string {
    if (!value) {
        return '—'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return new Intl.DateTimeFormat('en', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date)
}

export function getMailboxSearchText(
    mailbox: Mailbox,
): string {
    const incoming = mailbox.incoming_channel
    const outgoing = mailbox.outgoing_channel

    return [
        mailbox.name,
        mailbox.email_address,
        mailbox.display_name,
        mailbox.department?.name,
        mailbox.is_active ? 'active' : 'disabled',
        mailbox.is_default_outgoing
            ? 'default outgoing'
            : '',
        getMailboxStatusLabel(mailbox),
        incoming?.name,
        incoming?.driver,
        incoming?.health_status,
        incoming?.is_primary ? 'primary incoming' : '',
        outgoing?.name,
        outgoing?.driver,
        outgoing?.health_status,
        outgoing?.is_primary ? 'primary outgoing' : '',
    ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
}

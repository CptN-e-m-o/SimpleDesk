import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    Building2,
    CalendarDays,
    CheckCircle2,
    CircleAlert,
    Inbox,
    Mail,
    Mailbox,
    Save,
    Send,
    Settings2,
} from 'lucide-react'
import { FormEvent, ReactNode } from 'react'
import { route } from 'ziggy-js'

type DepartmentOption = {
    id: number
    name: string
}

type MailboxData = {
    id: number
    name: string
    email_address: string
    display_name: string | null
    department_id: number | null
    is_active: boolean
    is_default_outgoing: boolean
    internal_notes: string | null
    incoming_configured: boolean
    outgoing_configured: boolean
    created_at: string | null
    updated_at: string | null
}

type Props = {
    readonly mailbox: MailboxData
    readonly departments?: DepartmentOption[]
}

type MailboxFormData = {
    name: string
    email_address: string
    display_name: string
    department_id: number | null
    is_active: boolean
    is_default_outgoing: boolean
    internal_notes: string
}

type FieldErrorProps = {
    message?: string
}

type ConnectionCardProps = {
    title: string
    description: string
    configured: boolean
    href: string
    icon: ReactNode
}

function FieldError({
                        message,
                    }: FieldErrorProps) {
    if (!message) {
        return null
    }

    return (
        <p className="mt-2 text-sm text-rose-600">
            {message}
        </p>
    )
}

function formatDateTime(
    value: string | null,
): string {
    if (!value) {
        return '—'
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return '—'
    }

    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date)
}

function ConnectionCard({
                            title,
                            description,
                            configured,
                            href,
                            icon,
                        }: ConnectionCardProps) {
    return (
        <section className="rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div
                    className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ${
                        configured
                            ? 'bg-emerald-50 text-emerald-600'
                            : 'bg-amber-50 text-amber-600'
                    }`}
                >
                    {icon}
                </div>

                <span
                    className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${
                        configured
                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                            : 'bg-amber-50 text-amber-700 ring-amber-200'
                    }`}
                >
                    {configured
                        ? 'Configured'
                        : 'Not configured'}
                </span>
            </div>

            <h2 className="mt-4 text-base font-semibold text-gray-900">
                {title}
            </h2>

            <p className="mt-2 text-sm leading-6 text-gray-500">
                {description}
            </p>

            <Link
                href={href}
                className="mt-5 inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
            >
                <Settings2 className="h-4 w-4" />

                {configured
                    ? 'Edit Configuration'
                    : 'Configure'}
            </Link>
        </section>
    )
}

export default function Edit({
                                 mailbox,
                                 departments = [],
                             }: Props) {
    const {
        data,
        setData,
        put,
        processing,
        errors,
        isDirty,
    } = useForm<MailboxFormData>({
        name: mailbox.name,
        email_address: mailbox.email_address,
        display_name:
            mailbox.display_name ?? '',
        department_id:
        mailbox.department_id,
        is_active:
        mailbox.is_active,
        is_default_outgoing:
        mailbox.is_default_outgoing,
        internal_notes:
            mailbox.internal_notes ?? '',
    })

    function handleSubmit(
        event: FormEvent<HTMLFormElement>,
    ) {
        event.preventDefault()

        put(
            route(
                'admin.email.settings.mailboxes.update',
                mailbox.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    function handleActiveChange(
        checked: boolean,
    ) {
        setData((current) => ({
            ...current,
            is_active: checked,
            is_default_outgoing:
                checked
                    ? current.is_default_outgoing
                    : false,
        }))
    }

    return (
        <AdminLayout title="Edit Mailbox">
            <Head title={`Edit ${mailbox.name}`} />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <Mailbox className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Edit Mailbox
                                        </h1>

                                        <span
                                            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${
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

                                    <p className="mt-1 text-sm text-gray-500">
                                        Update the mailbox identity,
                                        department and availability.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route(
                                    'admin.email.settings.index',
                                )}
                                className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Back to Mailboxes
                            </Link>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="grid gap-6 p-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                            <section className="overflow-hidden rounded-[24px] border border-gray-200">
                                <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                                            <Mail className="h-5 w-5" />
                                        </div>

                                        <div>
                                            <h2 className="text-base font-semibold text-gray-900">
                                                Mailbox Information
                                            </h2>

                                            <p className="mt-1 text-sm text-gray-500">
                                                General information
                                                used throughout
                                                SimpleDesk.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-6 p-6">
                                    <div className="grid gap-6 lg:grid-cols-2">
                                        <div>
                                            <label
                                                htmlFor="name"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Mailbox Name
                                                <span className="ml-1 text-rose-500">
                                                    *
                                                </span>
                                            </label>

                                            <input
                                                id="name"
                                                type="text"
                                                value={data.name}
                                                maxLength={120}
                                                onChange={(event) =>
                                                    setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="SimpleDesk Support"
                                                className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:ring-4 ${
                                                    errors.name
                                                        ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                        : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                            />

                                            <FieldError
                                                message={errors.name}
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="email_address"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Email Address
                                                <span className="ml-1 text-rose-500">
                                                    *
                                                </span>
                                            </label>

                                            <input
                                                id="email_address"
                                                type="email"
                                                value={
                                                    data.email_address
                                                }
                                                maxLength={254}
                                                onChange={(event) =>
                                                    setData(
                                                        'email_address',
                                                        event.target.value,
                                                    )
                                                }
                                                autoComplete="email"
                                                placeholder="support@example.com"
                                                className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:ring-4 ${
                                                    errors.email_address
                                                        ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                        : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                            />

                                            <FieldError
                                                message={
                                                    errors.email_address
                                                }
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="display_name"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Display Name
                                            </label>

                                            <input
                                                id="display_name"
                                                type="text"
                                                value={
                                                    data.display_name
                                                }
                                                maxLength={120}
                                                onChange={(event) =>
                                                    setData(
                                                        'display_name',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="SimpleDesk Customer Support"
                                                className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:ring-4 ${
                                                    errors.display_name
                                                        ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                        : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                            />

                                            <p className="mt-2 text-xs leading-5 text-gray-500">
                                                Sender name displayed
                                                to email recipients.
                                            </p>

                                            <FieldError
                                                message={
                                                    errors.display_name
                                                }
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="department_id"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Department
                                            </label>

                                            <div className="relative mt-2">
                                                <Building2 className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                                <select
                                                    id="department_id"
                                                    value={
                                                        data.department_id ??
                                                        ''
                                                    }
                                                    onChange={(event) =>
                                                        setData(
                                                            'department_id',
                                                            event.target
                                                                .value ===
                                                            ''
                                                                ? null
                                                                : Number(
                                                                    event
                                                                        .target
                                                                        .value,
                                                                ),
                                                        )
                                                    }
                                                    className={`h-11 w-full appearance-none rounded-2xl border bg-white pl-10 pr-10 text-sm text-gray-700 outline-none transition focus:ring-4 ${
                                                        errors.department_id
                                                            ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                            : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                    }`}
                                                >
                                                    <option value="">
                                                        No department
                                                    </option>

                                                    {departments.map(
                                                        (
                                                            department,
                                                        ) => (
                                                            <option
                                                                key={
                                                                    department.id
                                                                }
                                                                value={
                                                                    department.id
                                                                }
                                                            >
                                                                {
                                                                    department.name
                                                                }
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                            </div>

                                            <FieldError
                                                message={
                                                    errors.department_id
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            htmlFor="internal_notes"
                                            className="text-sm font-medium text-gray-700"
                                        >
                                            Internal Notes
                                        </label>

                                        <textarea
                                            id="internal_notes"
                                            value={
                                                data.internal_notes
                                            }
                                            maxLength={10000}
                                            rows={6}
                                            onChange={(event) =>
                                                setData(
                                                    'internal_notes',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Optional administrative notes..."
                                            className={`mt-2 w-full resize-y rounded-2xl border bg-white px-4 py-3 text-sm leading-6 text-gray-700 outline-none transition placeholder:text-gray-400 focus:ring-4 ${
                                                errors.internal_notes
                                                    ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                    : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                            }`}
                                        />

                                        <div className="mt-2 flex items-start justify-between gap-4">
                                            <FieldError
                                                message={
                                                    errors.internal_notes
                                                }
                                            />

                                            <span className="ml-auto text-xs text-gray-400">
                                                {
                                                    data
                                                        .internal_notes
                                                        .length
                                                }
                                                /10000
                                            </span>
                                        </div>
                                    </div>

                                    <div className="grid gap-4 lg:grid-cols-2">
                                        <label
                                            className={`flex cursor-pointer items-start gap-4 rounded-2xl border px-4 py-4 transition ${
                                                data.is_active
                                                    ? 'border-emerald-200 bg-emerald-50/60'
                                                    : 'border-gray-200 bg-gray-50 hover:border-emerald-200'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={
                                                    data.is_active
                                                }
                                                onChange={(event) =>
                                                    handleActiveChange(
                                                        event.target
                                                            .checked,
                                                    )
                                                }
                                                className="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            />

                                            <div>
                                                <p className="text-sm font-semibold text-gray-900">
                                                    Active Mailbox
                                                </p>

                                                <p className="mt-1 text-xs leading-5 text-gray-500">
                                                    Allow configured
                                                    channels to send
                                                    and receive mail.
                                                </p>
                                            </div>
                                        </label>

                                        <label
                                            className={`flex items-start gap-4 rounded-2xl border px-4 py-4 transition ${
                                                data.is_active
                                                    ? data.is_default_outgoing
                                                        ? 'cursor-pointer border-violet-200 bg-violet-50/60'
                                                        : 'cursor-pointer border-gray-200 bg-gray-50 hover:border-violet-200'
                                                    : 'cursor-not-allowed border-gray-200 bg-gray-100 opacity-60'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={
                                                    data.is_default_outgoing
                                                }
                                                disabled={
                                                    !data.is_active
                                                }
                                                onChange={(event) =>
                                                    setData(
                                                        'is_default_outgoing',
                                                        event.target
                                                            .checked,
                                                    )
                                                }
                                                className="mt-1 h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                            />

                                            <div>
                                                <p className="text-sm font-semibold text-gray-900">
                                                    Default Outgoing
                                                    Mailbox
                                                </p>

                                                <p className="mt-1 text-xs leading-5 text-gray-500">
                                                    Use this mailbox
                                                    when no specific
                                                    sender is selected.
                                                </p>
                                            </div>
                                        </label>
                                    </div>

                                    <FieldError
                                        message={
                                            errors.is_active
                                        }
                                    />

                                    <FieldError
                                        message={
                                            errors.is_default_outgoing
                                        }
                                    />
                                </div>
                            </section>

                            <aside className="space-y-5">
                                <section className="rounded-[24px] border border-gray-200 bg-gray-50/70 p-5">
                                    <h2 className="text-sm font-semibold text-gray-900">
                                        Mailbox Status
                                    </h2>

                                    <div className="mt-4 space-y-3">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm text-gray-500">
                                                Incoming
                                            </span>

                                            <span
                                                className={`inline-flex items-center gap-1.5 text-sm font-medium ${
                                                    mailbox.incoming_configured
                                                        ? 'text-emerald-700'
                                                        : 'text-amber-700'
                                                }`}
                                            >
                                                {mailbox.incoming_configured ? (
                                                    <CheckCircle2 className="h-4 w-4" />
                                                ) : (
                                                    <CircleAlert className="h-4 w-4" />
                                                )}

                                                {mailbox.incoming_configured
                                                    ? 'Configured'
                                                    : 'Missing'}
                                            </span>
                                        </div>

                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm text-gray-500">
                                                Outgoing
                                            </span>

                                            <span
                                                className={`inline-flex items-center gap-1.5 text-sm font-medium ${
                                                    mailbox.outgoing_configured
                                                        ? 'text-emerald-700'
                                                        : 'text-amber-700'
                                                }`}
                                            >
                                                {mailbox.outgoing_configured ? (
                                                    <CheckCircle2 className="h-4 w-4" />
                                                ) : (
                                                    <CircleAlert className="h-4 w-4" />
                                                )}

                                                {mailbox.outgoing_configured
                                                    ? 'Configured'
                                                    : 'Missing'}
                                            </span>
                                        </div>
                                    </div>
                                </section>

                                <section className="rounded-[24px] border border-gray-200 bg-white p-5">
                                    <div className="flex items-center gap-2 text-gray-500">
                                        <CalendarDays className="h-4 w-4" />
                                        <span className="text-xs font-semibold uppercase tracking-wide">
                                            Created
                                        </span>
                                    </div>

                                    <p className="mt-2 text-sm font-medium text-gray-900">
                                        {formatDateTime(
                                            mailbox.created_at,
                                        )}
                                    </p>

                                    <div className="mt-5 flex items-center gap-2 text-gray-500">
                                        <CalendarDays className="h-4 w-4" />
                                        <span className="text-xs font-semibold uppercase tracking-wide">
                                            Last Updated
                                        </span>
                                    </div>

                                    <p className="mt-2 text-sm font-medium text-gray-900">
                                        {formatDateTime(
                                            mailbox.updated_at,
                                        )}
                                    </p>
                                </section>
                            </aside>
                        </div>

                        <div className="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <Link
                                href={route(
                                    'admin.email.settings.index',
                                )}
                                className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                disabled={
                                    processing || !isDirty
                                }
                                className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-5 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <Save className="h-4 w-4" />

                                {processing
                                    ? 'Saving...'
                                    : 'Save Changes'}
                            </button>
                        </div>
                    </form>
                </section>

                <section>
                    <div className="mb-4">
                        <h2 className="text-base font-semibold text-gray-900">
                            Connection Settings
                        </h2>

                        <p className="mt-1 text-sm text-gray-500">
                            Update incoming and outgoing transport
                            configuration separately.
                        </p>
                    </div>

                    <div className="grid gap-5 lg:grid-cols-2">
                        <ConnectionCard
                            title="Incoming Email"
                            description="Configure IMAP server, credentials, folders and synchronization limits."
                            configured={
                                mailbox.incoming_configured
                            }
                            href={route(
                                'admin.email.settings.mailboxes.setup.incoming',
                                mailbox.id,
                            )}
                            icon={
                                <Inbox className="h-5 w-5" />
                            }
                        />

                        <ConnectionCard
                            title="Outgoing Email"
                            description="Configure SMTP server, credentials, encryption and delivery limits."
                            configured={
                                mailbox.outgoing_configured
                            }
                            href={route(
                                'admin.email.settings.mailboxes.setup.outgoing',
                                mailbox.id,
                            )}
                            icon={
                                <Send className="h-5 w-5" />
                            }
                        />
                    </div>
                </section>
            </div>
        </AdminLayout>
    )
}

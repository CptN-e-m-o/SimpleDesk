import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    Building2,
    Check,
    ChevronRight,
    CircleCheck,
    Inbox,
    Info,
    Mail,
    Mailbox,
    Send,
    Settings2,
    TriangleAlert,
} from 'lucide-react'
import { FormEvent, ReactNode } from 'react'
import { route } from 'ziggy-js'

type DepartmentOption = {
    id: number
    name: string
}

type Props = {
    readonly departments?: DepartmentOption[]
    readonly system_mail_configured?: boolean
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

type StepProps = {
    number: number
    title: string
    description: string
    icon: ReactNode
    active?: boolean
}

type FieldErrorProps = {
    message?: string
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

function SetupStep({
                       number,
                       title,
                       description,
                       icon,
                       active = false,
                   }: StepProps) {
    return (
        <div
            className={`relative flex min-w-0 items-start gap-3 rounded-2xl border px-4 py-4 ${
                active
                    ? 'border-sky-200 bg-sky-50'
                    : 'border-gray-200 bg-white'
            }`}
        >
            <div
                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl ${
                    active
                        ? 'bg-sky-600 text-white'
                        : 'bg-gray-100 text-gray-500'
                }`}
            >
                {icon}
            </div>

            <div className="min-w-0">
                <div className="flex items-center gap-2">
                    <span
                        className={`text-xs font-semibold uppercase tracking-wide ${
                            active
                                ? 'text-sky-600'
                                : 'text-gray-400'
                        }`}
                    >
                        Step {number}
                    </span>

                    {active ? (
                        <span className="inline-flex rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 ring-1 ring-inset ring-sky-200">
                            Current
                        </span>
                    ) : null}
                </div>

                <p className="mt-1 text-sm font-semibold text-gray-900">
                    {title}
                </p>

                <p className="mt-1 text-xs leading-5 text-gray-500">
                    {description}
                </p>
            </div>
        </div>
    )
}

export default function Create({
                                   departments = [],
                                   system_mail_configured = false,
                               }: Props) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm<MailboxFormData>({
        name: '',
        email_address: '',
        display_name: '',
        department_id: null,
        is_active: true,
        is_default_outgoing: false,
        internal_notes: '',
    })

    function handleSubmit(
        event: FormEvent<HTMLFormElement>,
    ) {
        event.preventDefault()

        post(
            route(
                'admin.email.settings.mailboxes.store'
            ),
            {
                preserveScroll: true,
            },
        )
    }

    return (
        <AdminLayout title="Create Mailbox">
            <Head title="Create Mailbox" />

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
                                    Create a mailbox first. Incoming
                                    and outgoing channels will be
                                    configured on the following
                                    setup stages.
                                </p>
                            </div>
                        </div>
                    </section>
                ) : null}

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <Mailbox className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Create Mailbox
                                    </h1>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Add the mailbox identity
                                        before configuring incoming
                                        and outgoing connections.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route(
                                    'admin.email.settings.index'
                                )}
                                className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Back to Mailboxes
                            </Link>
                        </div>
                    </div>

                    <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-5">
                        <div className="grid gap-3 lg:grid-cols-4">
                            <SetupStep
                                number={1}
                                title="Mailbox Information"
                                description="Name, email address and ownership."
                                icon={
                                    <Mail className="h-5 w-5" />
                                }
                                active
                            />

                            <SetupStep
                                number={2}
                                title="Incoming Email"
                                description="IMAP connection and synchronization."
                                icon={
                                    <Inbox className="h-5 w-5" />
                                }
                            />

                            <SetupStep
                                number={3}
                                title="Outgoing Email"
                                description="SMTP connection and sender settings."
                                icon={
                                    <Send className="h-5 w-5" />
                                }
                            />

                            <SetupStep
                                number={4}
                                title="Review"
                                description="Connection tests and activation."
                                icon={
                                    <CircleCheck className="h-5 w-5" />
                                }
                            />
                        </div>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="p-6">
                            <div className="rounded-[24px] border border-gray-200 bg-white">
                                <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                                            <Settings2 className="h-5 w-5" />
                                        </div>

                                        <div>
                                            <h2 className="text-base font-semibold text-gray-900">
                                                Mailbox Information
                                            </h2>

                                            <p className="mt-1 text-sm text-gray-500">
                                                General mailbox
                                                information used by
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
                                                onChange={(event) =>
                                                    setData(
                                                        'name',
                                                        event
                                                            .target
                                                            .value,
                                                    )
                                                }
                                                maxLength={120}
                                                placeholder="SimpleDesk Support"
                                                autoComplete="off"
                                                className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:ring-4 ${
                                                    errors.name
                                                        ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                        : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                            />

                                            <FieldError
                                                message={
                                                    errors.name
                                                }
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
                                                onChange={(event) =>
                                                    setData(
                                                        'email_address',
                                                        event
                                                            .target
                                                            .value,
                                                    )
                                                }
                                                maxLength={254}
                                                placeholder="support@example.com"
                                                autoComplete="email"
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
                                                onChange={(event) =>
                                                    setData(
                                                        'display_name',
                                                        event
                                                            .target
                                                            .value,
                                                    )
                                                }
                                                maxLength={120}
                                                placeholder="SimpleDesk Customer Support"
                                                autoComplete="off"
                                                className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:ring-4 ${
                                                    errors.display_name
                                                        ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                        : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                            />

                                            <p className="mt-2 text-xs leading-5 text-gray-500">
                                                The sender name that
                                                customers will see in
                                                outgoing email.
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
                                                    onChange={(
                                                        event,
                                                    ) =>
                                                        setData(
                                                            'department_id',
                                                            event
                                                                .target
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

                                            <p className="mt-2 text-xs leading-5 text-gray-500">
                                                Incoming tickets can
                                                inherit this mailbox
                                                department.
                                            </p>

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
                                            onChange={(event) =>
                                                setData(
                                                    'internal_notes',
                                                    event
                                                        .target
                                                        .value,
                                                )
                                            }
                                            maxLength={10000}
                                            rows={5}
                                            placeholder="Optional administrative notes about this mailbox..."
                                            className={`mt-2 w-full resize-y rounded-2xl border bg-white px-4 py-3 text-sm leading-6 text-gray-700 outline-none transition placeholder:text-gray-400 focus:ring-4 ${
                                                errors.internal_notes
                                                    ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                    : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                            }`}
                                        />

                                        <div className="mt-2 flex items-center justify-between gap-4">
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
                                        <label className="flex cursor-pointer items-start gap-4 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 transition hover:border-sky-200 hover:bg-sky-50/50">
                                            <input
                                                type="checkbox"
                                                checked={
                                                    data.is_active
                                                }
                                                onChange={(
                                                    event,
                                                ) =>
                                                    setData(
                                                        'is_active',
                                                        event
                                                            .target
                                                            .checked,
                                                    )
                                                }
                                                className="mt-1 h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500"
                                            />

                                            <div>
                                                <p className="text-sm font-semibold text-gray-900">
                                                    Active Mailbox
                                                </p>

                                                <p className="mt-1 text-xs leading-5 text-gray-500">
                                                    Active mailboxes
                                                    can receive and
                                                    send email after
                                                    their channels
                                                    are configured.
                                                </p>
                                            </div>
                                        </label>

                                        <label
                                            className={`flex items-start gap-4 rounded-2xl border px-4 py-4 transition ${
                                                data.is_active
                                                    ? 'cursor-pointer border-gray-200 bg-gray-50 hover:border-violet-200 hover:bg-violet-50/50'
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
                                                onChange={(
                                                    event,
                                                ) =>
                                                    setData(
                                                        'is_default_outgoing',
                                                        event
                                                            .target
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
                                                    outgoing mailbox
                                                    has been
                                                    selected.
                                                </p>
                                            </div>
                                        </label>
                                    </div>

                                    <FieldError
                                        message={
                                            errors.is_default_outgoing
                                        }
                                    />

                                    <div className="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4">
                                        <div className="flex items-start gap-3">
                                            <Info className="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />

                                            <div>
                                                <p className="text-sm font-semibold text-sky-900">
                                                    Connection
                                                    credentials are
                                                    configured later
                                                </p>

                                                <p className="mt-1 text-sm leading-6 text-sky-800">
                                                    IMAP, SMTP,
                                                    username,
                                                    password and
                                                    OAuth settings
                                                    belong to
                                                    mailbox channels
                                                    and provider
                                                    connections.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <Link
                                href={route(
                                    'admin.email.settings.index'
                                )}
                                className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-5 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? (
                                    <>
                                        <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                        Creating...
                                    </>
                                ) : (
                                    <>
                                        Save and Continue
                                        <ChevronRight className="h-4 w-4" />
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-[24px] border border-gray-200 bg-white p-5">
                        <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                            <Mail className="h-5 w-5" />
                        </div>

                        <h2 className="mt-4 text-sm font-semibold text-gray-900">
                            Mailbox identity
                        </h2>

                        <p className="mt-2 text-sm leading-6 text-gray-500">
                            Defines the address and sender name
                            used by SimpleDesk.
                        </p>
                    </div>

                    <div className="rounded-[24px] border border-gray-200 bg-white p-5">
                        <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                            <Inbox className="h-5 w-5" />
                        </div>

                        <h2 className="mt-4 text-sm font-semibold text-gray-900">
                            Incoming channel
                        </h2>

                        <p className="mt-2 text-sm leading-6 text-gray-500">
                            IMAP and initial synchronization
                            settings will be added next.
                        </p>
                    </div>

                    <div className="rounded-[24px] border border-gray-200 bg-white p-5">
                        <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                            <Check className="h-5 w-5" />
                        </div>

                        <h2 className="mt-4 text-sm font-semibold text-gray-900">
                            Connection verification
                        </h2>

                        <p className="mt-2 text-sm leading-6 text-gray-500">
                            Mailbox activation will include
                            incoming and outgoing connection
                            tests.
                        </p>
                    </div>
                </section>
            </div>
        </AdminLayout>
    )
}

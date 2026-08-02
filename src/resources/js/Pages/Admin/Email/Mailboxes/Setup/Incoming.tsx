import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link, useForm } from '@inertiajs/react'
import {
ArrowLeft,
ChevronRight,
Eye,
EyeOff,
Inbox,
Info,
LockKeyhole,
Server,
Settings2,
ShieldCheck,
} from 'lucide-react'
import { FormEvent, ReactNode, useState } from 'react'
import { route } from 'ziggy-js'
import SetupSteps from './SetupSteps'

type MailboxSetupData = {
id: number
name: string
email_address: string
}

type EncryptionOption = {
value: string
label: string
default_port: number
}

type IncomingChannelData = {
id: number
name: string
auth_type: string
host: string
port: number
encryption: string
username: string
password_configured: boolean
validate_cert: boolean
folder: string
processed_folder: string
create_processed_folder: boolean
expunge_on_delete: boolean
store_raw_message: boolean
max_raw_message_mb: number
max_attachment_mb: number
is_enabled: boolean
is_primary: boolean
failover_order: number
}

type Props = {
readonly mailbox: MailboxSetupData
readonly channel?: IncomingChannelData | null
readonly encryption_options?: EncryptionOption[]
readonly defaults?: {
encryption: string
port: number
}
}

type IncomingFormData = {
name: string
auth_type: string
host: string
port: number
encryption: string
username: string
password: string
validate_cert: boolean
folder: string
processed_folder: string
create_processed_folder: boolean
expunge_on_delete: boolean
store_raw_message: boolean
max_raw_message_mb: number
max_attachment_mb: number
is_enabled: boolean
is_primary: boolean
failover_order: number
}

type FieldErrorProps = {
message?: string
}

type ToggleCardProps = {
checked: boolean
onChange: (checked: boolean) => void
title: string
description: string
disabled?: boolean
icon: ReactNode
}

function FieldError({ message }: FieldErrorProps) {
if (!message) {
return null
}

return (
<p className="mt-2 text-sm text-rose-600">
    {message}
</p>
)
}

function ToggleCard({
checked,
onChange,
title,
description,
disabled = false,
icon,
}: ToggleCardProps) {
return (
<label
    className={`flex items-start gap-4 rounded-2xl border px-4 py-4 transition ${
    disabled
    ? 'cursor-not-allowed border-gray-200 bg-gray-100 opacity-60'
: checked
? 'cursor-pointer border-sky-200 bg-sky-50/70'
: 'cursor-pointer border-gray-200 bg-gray-50 hover:border-sky-200'
}`}
>
<input
    type="checkbox"
    checked={checked}
    disabled={disabled}
    onChange={(event) =>
onChange(event.target.checked)
}
className="mt-1 h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500"
/>

<div className="flex min-w-0 gap-3">
    <div
        className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${
        checked
        ? 'bg-sky-600 text-white'
    : 'bg-white text-gray-500 ring-1 ring-inset ring-gray-200'
    }`}
    >
    {icon}
</div>

<div>
    <p className="text-sm font-semibold text-gray-900">
        {title}
    </p>

    <p className="mt-1 text-xs leading-5 text-gray-500">
        {description}
    </p>
</div>
</div>
</label>
)
}

export default function Incoming({
mailbox,
channel = null,
encryption_options = [],
defaults = {
encryption: 'tls',
port: 993,
},
}: Props) {
const [showPassword, setShowPassword] =
useState(false)

const {
data,
setData,
post,
processing,
errors,
} = useForm<IncomingFormData>({
    name:
    channel?.name ??
    'Primary IMAP',

    auth_type:
    channel?.auth_type ??
    'password',

    host:
    channel?.host ??
    '',

    port:
    channel?.port ??
    defaults.port,

    encryption:
    channel?.encryption ??
    defaults.encryption,

    username:
    channel?.username ??
    mailbox.email_address,

    password:
    '',

    validate_cert:
    channel?.validate_cert ??
    true,

    folder:
    channel?.folder ??
    'INBOX',

    processed_folder:
    channel?.processed_folder ??
    'Processed',

    create_processed_folder:
    channel?.create_processed_folder ??
    true,

    expunge_on_delete:
    channel?.expunge_on_delete ??
    true,

    store_raw_message:
    channel?.store_raw_message ??
    true,

    max_raw_message_mb:
    channel?.max_raw_message_mb ??
    50,

    max_attachment_mb:
    channel?.max_attachment_mb ??
    25,

    is_enabled:
    channel?.is_enabled ??
    true,

    is_primary:
    channel?.is_primary ??
    true,

    failover_order:
    channel?.failover_order ??
    0,
    })

    const credentialsRequired =
    data.auth_type === 'password'

    function handleEncryptionChange(
    encryptionValue: string,
    ) {
    const option = encryption_options.find(
    (item) =>
    item.value === encryptionValue,
    )

    setData((current) => ({
    ...current,
    encryption: encryptionValue,
    port:
    option?.default_port ??
    current.port,
    }))
    }

    function handleSubmit(
    event: FormEvent<HTMLFormElement>,
        ) {
        event.preventDefault()

        post(
        route(
        'admin.email.settings.mailboxes.setup.incoming.store',
        mailbox.id,
        ),
        {
        preserveScroll: true,
        },
        )
        }

        return (
        <AdminLayout title="Incoming Email">
            <Head title="Configure Incoming Email" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <Inbox className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Configure Incoming Email
                                    </h1>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Configure IMAP retrieval for{' '}
                                        <span className="font-medium text-gray-700">
                                            {mailbox.email_address}
                                        </span>
                                        .
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

                    <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-5">
                        <SetupSteps currentStep={2} />
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="space-y-6 p-6">
                            <section className="overflow-hidden rounded-[24px] border border-gray-200">
                                <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                                            <Server className="h-5 w-5" />
                                        </div>

                                        <div>
                                            <h2 className="text-base font-semibold text-gray-900">
                                                IMAP Connection
                                            </h2>

                                            <p className="mt-1 text-sm text-gray-500">
                                                Server address,
                                                encryption and
                                                authentication
                                                credentials.
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
                                                Channel Name
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
                                            event.target.value,
                                            )
                                            }
                                            maxLength={120}
                                            placeholder="Primary IMAP"
                                            className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm outline-none transition focus:ring-4 ${
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
                                                htmlFor="auth_type"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Authentication
                                                <span className="ml-1 text-rose-500">
                                                    *
                                                </span>
                                            </label>

                                            <select
                                                id="auth_type"
                                                value={data.auth_type}
                                                onChange={(event) =>
                                            setData(
                                            'auth_type',
                                            event.target.value,
                                            )
                                            }
                                            className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm outline-none transition focus:ring-4 ${
                                            errors.auth_type
                                            ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                            : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                            }`}
                                            >
                                            <option value="password">
                                                Username and Password
                                            </option>

                                            <option value="none">
                                                No Authentication
                                            </option>
                                            </select>

                                            <FieldError
                                                message={
                                                errors.auth_type
                                                }
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="host"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                IMAP Host
                                                <span className="ml-1 text-rose-500">
                                                    *
                                                </span>
                                            </label>

                                            <input
                                                id="host"
                                                type="text"
                                                value={data.host}
                                                onChange={(event) =>
                                            setData(
                                            'host',
                                            event.target.value,
                                            )
                                            }
                                            placeholder="imap.example.com"
                                            className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm outline-none transition focus:ring-4 ${
                                            errors.host
                                            ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                            : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                            }`}
                                            />

                                            <FieldError
                                                message={errors.host}
                                            />
                                        </div>

                                        <div className="grid grid-cols-2 gap-3">
                                            <div>
                                                <label
                                                    htmlFor="encryption"
                                                    className="text-sm font-medium text-gray-700"
                                                >
                                                    Encryption
                                                </label>

                                                <select
                                                    id="encryption"
                                                    value={
                                                    data.encryption
                                                    }
                                                    onChange={(event) =>
                                                handleEncryptionChange(
                                                event.target
                                                .value,
                                                )
                                                }
                                                className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm outline-none transition focus:ring-4 ${
                                                errors.encryption
                                                ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                                >
                                                {encryption_options.map(
                                                (option) => (
                                                <option
                                                    key={
                                                    option.value
                                                    }
                                                    value={
                                                    option.value
                                                    }
                                                >
                                                    {
                                                    option.label
                                                    }
                                                </option>
                                                ),
                                                )}
                                                </select>

                                                <FieldError
                                                    message={
                                                    errors.encryption
                                                    }
                                                />
                                            </div>

                                            <div>
                                                <label
                                                    htmlFor="port"
                                                    className="text-sm font-medium text-gray-700"
                                                >
                                                    Port
                                                </label>

                                                <input
                                                    id="port"
                                                    type="number"
                                                    min={1}
                                                    max={65535}
                                                    value={data.port}
                                                    onChange={(event) =>
                                                setData(
                                                'port',
                                                Number(
                                                event
                                                .target
                                                .value,
                                                ),
                                                )
                                                }
                                                className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm outline-none transition focus:ring-4 ${
                                                errors.port
                                                ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                                />

                                                <FieldError
                                                    message={
                                                    errors.port
                                                    }
                                                />
                                            </div>
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="username"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Username
                                                {credentialsRequired ? (
                                                <span className="ml-1 text-rose-500">
                                                        *
                                                    </span>
                                                ) : null}
                                            </label>

                                            <input
                                                id="username"
                                                type="text"
                                                value={data.username}
                                                disabled={
                                                !credentialsRequired
                                                }
                                                onChange={(event) =>
                                            setData(
                                            'username',
                                            event.target.value,
                                            )
                                            }
                                            autoComplete="username"
                                            placeholder="support@example.com"
                                            className={`mt-2 h-11 w-full rounded-2xl border px-4 text-sm outline-none transition focus:ring-4 disabled:cursor-not-allowed disabled:bg-gray-100 ${
                                            errors.username
                                            ? 'border-rose-300 bg-white focus:border-rose-400 focus:ring-rose-100'
                                            : 'border-gray-200 bg-white focus:border-sky-300 focus:ring-sky-100'
                                            }`}
                                            />

                                            <FieldError
                                                message={
                                                errors.username
                                                }
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="password"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Password
                                                {credentialsRequired &&
                                                !channel?.password_configured ? (
                                                <span className="ml-1 text-rose-500">
                                                        *
                                                    </span>
                                                ) : null}
                                            </label>

                                            <div className="relative mt-2">
                                                <LockKeyhole className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                                <input
                                                    id="password"
                                                    type={
                                                    showPassword
                                                    ? 'text'
                                                : 'password'
                                                }
                                                value={
                                                data.password
                                                }
                                                disabled={
                                                !credentialsRequired
                                                }
                                                onChange={(event) =>
                                                setData(
                                                'password',
                                                event.target
                                                .value,
                                                )
                                                }
                                                autoComplete="new-password"
                                                placeholder={
                                                channel?.password_configured
                                                ? 'Leave blank to keep the current password'
                                                : 'Enter IMAP password'
                                                }
                                                className={`h-11 w-full rounded-2xl border pl-10 pr-11 text-sm outline-none transition focus:ring-4 disabled:cursor-not-allowed disabled:bg-gray-100 ${
                                                errors.password
                                                ? 'border-rose-300 bg-white focus:border-rose-400 focus:ring-rose-100'
                                                : 'border-gray-200 bg-white focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                                />

                                                <button
                                                    type="button"
                                                    disabled={
                                                    !credentialsRequired
                                                    }
                                                    onClick={() =>
                                                setShowPassword(
                                                (current) =>
                                                !current,
                                                )
                                                }
                                                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-gray-700 disabled:cursor-not-allowed"
                                                >
                                                {showPassword ? (
                                                <EyeOff className="h-4 w-4" />
                                                ) : (
                                                <Eye className="h-4 w-4" />
                                                )}
                                                </button>
                                            </div>

                                            {channel?.password_configured &&
                                            credentialsRequired ? (
                                            <p className="mt-2 text-xs text-emerald-600">
                                                A password is already configured.
                                            </p>
                                            ) : null}

                                            <FieldError
                                                message={
                                                errors.password
                                                }
                                            />
                                        </div>
                                    </div>

                                    <ToggleCard
                                        checked={data.validate_cert}
                                        onChange={(checked) =>
                                    setData(
                                    'validate_cert',
                                    checked,
                                    )
                                    }
                                    title="Validate TLS Certificate"
                                    description="Verify that the IMAP server presents a trusted certificate."
                                    icon={
                                    <ShieldCheck className="h-4 w-4" />
                                    }
                                    />

                                    <FieldError
                                        message={
                                        errors.validate_cert
                                        }
                                    />
                                </div>
                            </section>

                            <section className="overflow-hidden rounded-[24px] border border-gray-200">
                                <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                            <Settings2 className="h-5 w-5" />
                                        </div>

                                        <div>
                                            <h2 className="text-base font-semibold text-gray-900">
                                                Synchronization Settings
                                            </h2>

                                            <p className="mt-1 text-sm text-gray-500">
                                                Folder handling and
                                                storage limits for
                                                incoming email.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-6 p-6">
                                    <div className="grid gap-6 lg:grid-cols-2">
                                        <div>
                                            <label
                                                htmlFor="folder"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Source Folder
                                            </label>

                                            <input
                                                id="folder"
                                                type="text"
                                                value={data.folder}
                                                onChange={(event) =>
                                            setData(
                                            'folder',
                                            event.target.value,
                                            )
                                            }
                                            placeholder="INBOX"
                                            className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm outline-none transition focus:ring-4 ${
                                            errors.folder
                                            ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                            : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                            }`}
                                            />

                                            <FieldError
                                                message={
                                                errors.folder
                                                }
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="processed_folder"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Processed Folder
                                            </label>

                                            <input
                                                id="processed_folder"
                                                type="text"
                                                value={
                                                data.processed_folder
                                                }
                                                onChange={(event) =>
                                            setData(
                                            'processed_folder',
                                            event.target.value,
                                            )
                                            }
                                            placeholder="Processed"
                                            className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm outline-none transition focus:ring-4 ${
                                            errors.processed_folder
                                            ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                            : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                            }`}
                                            />

                                            <FieldError
                                                message={
                                                errors.processed_folder
                                                }
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="max_raw_message_mb"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Maximum Raw Message
                                            </label>

                                            <div className="relative mt-2">
                                                <input
                                                    id="max_raw_message_mb"
                                                    type="number"
                                                    min={1}
                                                    max={1024}
                                                    value={
                                                    data.max_raw_message_mb
                                                    }
                                                    onChange={(event) =>
                                                setData(
                                                'max_raw_message_mb',
                                                Number(
                                                event
                                                .target
                                                .value,
                                                ),
                                                )
                                                }
                                                className={`h-11 w-full rounded-2xl border bg-white px-4 pr-14 text-sm outline-none transition focus:ring-4 ${
                                                errors.max_raw_message_mb
                                                ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                                />

                                                <span className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">
                                                    MB
                                                </span>
                                            </div>

                                            <FieldError
                                                message={
                                                errors.max_raw_message_mb
                                                }
                                            />
                                        </div>

                                        <div>
                                            <label
                                                htmlFor="max_attachment_mb"
                                                className="text-sm font-medium text-gray-700"
                                            >
                                                Maximum Attachment
                                            </label>

                                            <div className="relative mt-2">
                                                <input
                                                    id="max_attachment_mb"
                                                    type="number"
                                                    min={1}
                                                    max={1024}
                                                    value={
                                                    data.max_attachment_mb
                                                    }
                                                    onChange={(event) =>
                                                setData(
                                                'max_attachment_mb',
                                                Number(
                                                event
                                                .target
                                                .value,
                                                ),
                                                )
                                                }
                                                className={`h-11 w-full rounded-2xl border bg-white px-4 pr-14 text-sm outline-none transition focus:ring-4 ${
                                                errors.max_attachment_mb
                                                ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                                : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                                }`}
                                                />

                                                <span className="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">
                                                    MB
                                                </span>
                                            </div>

                                            <FieldError
                                                message={
                                                errors.max_attachment_mb
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-4 lg:grid-cols-3">
                                        <ToggleCard
                                            checked={
                                            data.create_processed_folder
                                            }
                                            onChange={(checked) =>
                                        setData(
                                        'create_processed_folder',
                                        checked,
                                        )
                                        }
                                        title="Create Processed Folder"
                                        description="Automatically create the destination folder if it does not exist."
                                        icon={
                                        <Inbox className="h-4 w-4" />
                                        }
                                        />

                                        <ToggleCard
                                            checked={
                                            data.expunge_on_delete
                                            }
                                            onChange={(checked) =>
                                        setData(
                                        'expunge_on_delete',
                                        checked,
                                        )
                                        }
                                        title="Expunge Deleted Messages"
                                        description="Permanently remove messages marked for deletion."
                                        icon={
                                        <Settings2 className="h-4 w-4" />
                                        }
                                        />

                                        <ToggleCard
                                            checked={
                                            data.store_raw_message
                                            }
                                            onChange={(checked) =>
                                        setData(
                                        'store_raw_message',
                                        checked,
                                        )
                                        }
                                        title="Store Raw Message"
                                        description="Keep the original MIME message for diagnostics and audit."
                                        icon={
                                        <Server className="h-4 w-4" />
                                        }
                                        />
                                    </div>
                                </div>
                            </section>

                            <section className="overflow-hidden rounded-[24px] border border-gray-200">
                                <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
                                    <h2 className="text-base font-semibold text-gray-900">
                                        Channel Behaviour
                                    </h2>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Enable this connection and
                                        define its failover position.
                                    </p>
                                </div>

                                <div className="space-y-5 p-6">
                                    <div className="grid gap-4 lg:grid-cols-2">
                                        <ToggleCard
                                            checked={data.is_enabled}
                                            onChange={(checked) => {
                                        setData((current) => ({
                                        ...current,
                                        is_enabled:
                                        checked,
                                        is_primary:
                                        checked
                                        ? current.is_primary
                                        : false,
                                        }))
                                        }}
                                        title="Enabled"
                                        description="Allow SimpleDesk to use this IMAP channel."
                                        icon={
                                        <Server className="h-4 w-4" />
                                        }
                                        />

                                        <ToggleCard
                                            checked={data.is_primary}
                                            disabled={
                                            !data.is_enabled
                                            }
                                            onChange={(checked) =>
                                        setData(
                                        'is_primary',
                                        checked,
                                        )
                                        }
                                        title="Primary Incoming Channel"
                                        description="Use this connection before any incoming fallback channels."
                                        icon={
                                        <Inbox className="h-4 w-4" />
                                        }
                                        />
                                    </div>

                                    <div className="max-w-sm">
                                        <label
                                            htmlFor="failover_order"
                                            className="text-sm font-medium text-gray-700"
                                        >
                                            Failover Order
                                        </label>

                                        <input
                                            id="failover_order"
                                            type="number"
                                            min={0}
                                            max={32767}
                                            value={
                                            data.failover_order
                                            }
                                            onChange={(event) =>
                                        setData(
                                        'failover_order',
                                        Number(
                                        event.target
                                        .value,
                                        ),
                                        )
                                        }
                                        className={`mt-2 h-11 w-full rounded-2xl border bg-white px-4 text-sm outline-none transition focus:ring-4 ${
                                        errors.failover_order
                                        ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                                        : 'border-gray-200 focus:border-sky-300 focus:ring-sky-100'
                                        }`}
                                        />

                                        <p className="mt-2 text-xs leading-5 text-gray-500">
                                            Lower values are attempted
                                            before higher values.
                                        </p>

                                        <FieldError
                                            message={
                                            errors.failover_order
                                            }
                                        />
                                    </div>
                                </div>
                            </section>

                            <div className="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4">
                                <div className="flex items-start gap-3">
                                    <Info className="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />

                                    <p className="text-sm leading-6 text-sky-800">
                                        OAuth connections will be
                                        configured later through the
                                        dedicated OAuth Integrations
                                        section.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/70 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <Link
                                href={route(
                            'admin.email.settings.index',
                            )}
                            className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                            >
                            <ArrowLeft className="h-4 w-4" />
                            Exit Setup
                            </Link>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Link
                                    href={route(
                                'admin.email.settings.mailboxes.setup.outgoing',
                                mailbox.id,
                                )}
                                className="inline-flex h-11 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                                >
                                Skip for Now
                                </Link>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-5 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {processing
                                    ? 'Saving...'
                                    : 'Save and Continue'}

                                    {!processing ? (
                                    <ChevronRight className="h-4 w-4" />
                                    ) : null}
                                </button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        </AdminLayout>
        )
        }

import { type FormEvent } from 'react'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    Cloud,
    Database,
    HardDrive,
    Info,
    LockKeyhole,
    Network,
    Save,
} from 'lucide-react'
import { route } from 'ziggy-js'

import InputError from '@/Components/InputError'
import { Button } from '@/Components/ui/button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'
import AdminLayout from '@/Layouts/AdminLayout'

type Definition = {
    driver: string
    label: string
    available: boolean
    requires_infrastructure: boolean
    infrastructure_type: string | null
    message: string | null
}

type Connection = {
    id: number
    name: string
    type: string
    is_enabled: boolean
    deleted_at: string | null
}

type Configuration = {
    id: number
    name: string
    driver: string
    infrastructure_connection_id: number | null
    configuration: {
        prefix?: string
    }
    is_enabled: boolean
}

type Props = {
    configuration: Configuration | null
    definitions: Definition[]
    connections: Connection[]
}

type FormData = {
    name: string
    driver: string
    infrastructure_connection_id: number | null
    configuration: {
        prefix?: string
    }
    is_enabled: boolean
}

type FormErrors = Record<string, string | undefined>

export default function Form({
                                 configuration,
                                 definitions,
                                 connections,
                             }: Props) {
    const editing = configuration !== null

    const form = useForm<FormData>({
        name: configuration?.name ?? '',
        driver:
            configuration?.driver
            ?? definitions.find(
                (item) => item.available,
            )?.driver
            ?? 'local',
        infrastructure_connection_id:
            configuration?.infrastructure_connection_id
            ?? null,
        configuration:
            configuration?.configuration ?? {},
        is_enabled:
            configuration?.is_enabled ?? true,
    })

    const errors =
        form.errors as FormErrors

    const definition = definitions.find(
        (item) =>
            item.driver === form.data.driver,
    )

    const allowedConnections =
        connections.filter(
            (connection) =>
                connection.type
                === definition?.infrastructure_type
                || connection.id
                === configuration
                    ?.infrastructure_connection_id,
        )

    const selectedConnection =
        allowedConnections.find(
            (connection) =>
                connection.id
                === form.data
                    .infrastructure_connection_id,
        )

    const validSelectedConnection =
        selectedConnection
        && selectedConnection.type
        === definition?.infrastructure_type
        && selectedConnection.is_enabled
        && !selectedConnection.deleted_at

    const canSubmit =
        !form.processing
        && Boolean(definition?.available)
        && (
            !definition?.requires_infrastructure
            || Boolean(
                validSelectedConnection,
            )
        )

    const submit = (
        event: FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault()

        form.transform((data) => {
            if (data.driver === 'local') {
                return {
                    ...data,
                    infrastructure_connection_id: null,
                    configuration: {},
                }
            }

            const prefix =
                data.configuration.prefix
                    ?.trim()
                ?? ''

            return {
                ...data,
                configuration:
                    prefix === ''
                        ? {}
                        : {
                            prefix,
                        },
            }
        })

        if (editing) {
            form.put(
                route(
                    'admin.system.storage.update',
                    {
                        configuration:
                        configuration.id,
                    },
                ),
            )

            return
        }

        form.post(
            route(
                'admin.system.storage.store',
            ),
        )
    }

    const changeDriver = (driver: string) => {
        form.setData((data) => ({
            ...data,
            driver,
            infrastructure_connection_id: null,
            configuration: {},
        }))

        form.clearErrors()
    }

    return (
        <AdminLayout
            title={
                editing
                    ? 'Edit Storage profile'
                    : 'Create Storage profile'
            }
        >
            <Head
                title={
                    editing
                        ? 'Edit Storage profile'
                        : 'Create Storage profile'
                }
            />

            <div className="space-y-6">
                <header className="rounded-[28px] border border-gray-200 bg-gradient-to-r from-violet-50 via-white to-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="flex items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-100">
                                <HardDrive className="h-6 w-6 text-violet-700" />
                            </span>

                            <div>
                                <h1 className="text-xl font-semibold text-gray-900">
                                    {editing
                                        ? 'Edit Storage profile'
                                        : 'Create Storage profile'}
                                </h1>

                                <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                    Configure a private filesystem runtime target. Saving this profile does not activate it.
                                </p>
                            </div>
                        </div>

                        <Button
                            variant="outline"
                            asChild
                        >
                            <Link
                                href={route(
                                    'admin.system.storage.index',
                                )}
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back
                            </Link>
                        </Button>
                    </div>
                </header>

                <form
                    onSubmit={submit}
                    className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]"
                >
                    <div className="space-y-6">
                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <SectionHeader
                                icon={HardDrive}
                                title="Profile"
                                description="Choose the Storage driver and give this runtime profile a recognizable name."
                            />

                            <div className="space-y-5 p-6">
                                <Field
                                    label="Name"
                                    required
                                    error={
                                        errors.name
                                    }
                                >
                                    <input
                                        value={
                                            form.data
                                                .name
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            form.setData(
                                                'name',
                                                event
                                                    .target
                                                    .value,
                                            )
                                        }
                                        className={inputClass(
                                            Boolean(
                                                errors.name,
                                            ),
                                        )}
                                        placeholder="Production private storage"
                                        autoComplete="off"
                                    />
                                </Field>

                                <Field
                                    label="Storage driver"
                                    required
                                    error={
                                        errors.driver
                                    }
                                >
                                    {editing ? (
                                        <ReadOnlyDriver
                                            definition={
                                                definition
                                            }
                                            driver={
                                                form.data
                                                    .driver
                                            }
                                        />
                                    ) : (
                                        <Select
                                            value={
                                                form.data
                                                    .driver
                                            }
                                            onValueChange={
                                                changeDriver
                                            }
                                        >
                                            <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                                                <SelectValue placeholder="Choose a Storage driver" />
                                            </SelectTrigger>

                                            <SelectContent>
                                                {definitions.map(
                                                    (
                                                        item,
                                                    ) => (
                                                        <SelectItem
                                                            key={
                                                                item.driver
                                                            }
                                                            value={
                                                                item.driver
                                                            }
                                                            disabled={
                                                                !item.available
                                                            }
                                                        >
                                                            {
                                                                item.label
                                                            }
                                                            {item.available
                                                                ? ''
                                                                : ' · unavailable'}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    )}

                                    {definition
                                        ?.message ? (
                                        <p className="mt-2 text-xs leading-5 text-amber-700">
                                            {
                                                definition.message
                                            }
                                        </p>
                                    ) : null}
                                </Field>
                            </div>
                        </section>

                        {definition
                            ?.requires_infrastructure ? (
                            <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                                <SectionHeader
                                    icon={
                                        Network
                                    }
                                    title="Infrastructure"
                                    description="Select the bucket connection used by this Storage profile."
                                />

                                <div className="space-y-5 p-6">
                                    <Field
                                        label="Infrastructure Connection"
                                        required
                                        error={
                                            errors.infrastructure_connection_id
                                        }
                                    >
                                        <Select
                                            value={
                                                form.data
                                                    .infrastructure_connection_id
                                                    ? String(
                                                        form.data
                                                            .infrastructure_connection_id,
                                                    )
                                                    : ''
                                            }
                                            onValueChange={(
                                                value,
                                            ) =>
                                                form.setData(
                                                    'infrastructure_connection_id',
                                                    Number(
                                                        value,
                                                    ),
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                                                <SelectValue placeholder="Select an Infrastructure Connection" />
                                            </SelectTrigger>

                                            <SelectContent>
                                                {allowedConnections.map(
                                                    (
                                                        connection,
                                                    ) => {
                                                        const valid =
                                                            connection.type
                                                            === definition.infrastructure_type
                                                            && connection.is_enabled
                                                            && !connection.deleted_at

                                                        return (
                                                            <SelectItem
                                                                key={
                                                                    connection.id
                                                                }
                                                                value={String(
                                                                    connection.id,
                                                                )}
                                                                disabled={
                                                                    !valid
                                                                }
                                                            >
                                                                {
                                                                    connection.name
                                                                }
                                                                {connection.deleted_at
                                                                    ? ' · archived'
                                                                    : !connection.is_enabled
                                                                        ? ' · disabled'
                                                                        : ''}
                                                            </SelectItem>
                                                        )
                                                    },
                                                )}
                                            </SelectContent>
                                        </Select>

                                        {allowedConnections.length
                                        === 0 ? (
                                            <div className="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm leading-6 text-amber-800">
                                                No usable {definition.infrastructure_type === 'aws' ? 'Amazon S3' : 'S3-compatible'} Infrastructure Connections are available.
                                            </div>
                                        ) : null}
                                    </Field>

                                    <div className="rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                                        <div className="flex items-start gap-3">
                                            <Cloud className="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />

                                            <div>
                                                <p className="text-sm font-semibold text-sky-900">
                                                    Bucket access belongs to Infrastructure Connections
                                                </p>

                                                <p className="mt-1 text-sm leading-6 text-sky-800">
                                                    Region, bucket, endpoint and encrypted credentials are managed independently from this Storage profile.
                                                </p>

                                                <Link
                                                    href={route(
                                                        'admin.system.connections.create',
                                                    )}
                                                    className="mt-2 inline-flex text-sm font-semibold text-sky-700 hover:text-sky-900"
                                                >
                                                    Create Infrastructure Connection
                                                </Link>
                                            </div>
                                        </div>
                                    </div>

                                    <Field
                                        label="Object prefix"
                                        error={
                                            errors[
                                                'configuration.prefix'
                                                ]
                                        }
                                        hint="Optional relative namespace inside the selected bucket, for example simpledesk or production/files."
                                    >
                                        <input
                                            value={
                                                form
                                                    .data
                                                    .configuration
                                                    .prefix
                                                ?? ''
                                            }
                                            onChange={(
                                                event,
                                            ) =>
                                                form.setData(
                                                    'configuration',
                                                    {
                                                        ...form
                                                            .data
                                                            .configuration,
                                                        prefix:
                                                        event
                                                            .target
                                                            .value,
                                                    },
                                                )
                                            }
                                            className={inputClass(
                                                Boolean(
                                                    errors[
                                                        'configuration.prefix'
                                                        ],
                                                ),
                                            )}
                                            placeholder="simpledesk"
                                            autoComplete="off"
                                        />
                                    </Field>
                                </div>
                            </section>
                        ) : (
                            <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                                <SectionHeader
                                    icon={
                                        Database
                                    }
                                    title="Local private storage"
                                    description="The local driver uses an application-owned deterministic private path."
                                />

                                <div className="p-6">
                                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                        <div className="flex items-start gap-3">
                                            <Database className="mt-0.5 h-5 w-5 shrink-0 text-emerald-700" />

                                            <div>
                                                <p className="text-sm font-semibold text-emerald-950">
                                                    storage/app/private
                                                </p>

                                                <p className="mt-1 text-sm leading-6 text-emerald-800">
                                                    Administrators cannot provide arbitrary server paths. Managed Local Storage is restricted to SimpleDesk&apos;s private application storage root.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        )}

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <SectionHeader
                                icon={Save}
                                title="Availability"
                                description="Disabled profiles remain configured but cannot be activated."
                            />

                            <div className="p-6">
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={
                                        form.data
                                            .is_enabled
                                    }
                                    onClick={() =>
                                        form.setData(
                                            'is_enabled',
                                            !form.data
                                                .is_enabled,
                                        )
                                    }
                                    className="flex w-full items-center justify-between gap-5 rounded-2xl border border-gray-200 bg-gray-50/60 p-4 text-left transition hover:border-gray-300 hover:bg-gray-50"
                                >
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900">
                                            Enable profile
                                        </p>

                                        <p className="mt-1 text-sm leading-6 text-gray-500">
                                            An enabled profile may be tested and explicitly activated.
                                        </p>
                                    </div>

                                    <Switch
                                        enabled={
                                            form.data
                                                .is_enabled
                                        }
                                    />
                                </button>

                                <InputError
                                    className="mt-2"
                                    message={
                                        errors.is_enabled
                                    }
                                />
                            </div>
                        </section>

                        <div className="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                asChild
                            >
                                <Link
                                    href={route(
                                        'admin.system.storage.index',
                                    )}
                                >
                                    Cancel
                                </Link>
                            </Button>

                            <Button
                                type="submit"
                                disabled={!canSubmit}
                            >
                                <Save className="mr-2 h-4 w-4" />

                                {form.processing
                                    ? 'Saving…'
                                    : editing
                                        ? 'Save changes'
                                        : 'Create profile'}
                            </Button>
                        </div>
                    </div>

                    <aside className="space-y-4">
                        <div className="rounded-[28px] border border-amber-200 bg-amber-50 p-5">
                            <div className="flex items-start gap-3">
                                <Info className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                                <div>
                                    <h2 className="font-semibold text-amber-950">
                                        Control-plane boundary
                                    </h2>

                                    <div className="mt-3 space-y-3 text-sm leading-6 text-amber-900">
                                        <p>
                                            Saving this profile does not activate it.
                                        </p>

                                        <p>
                                            Switching Storage profiles does not migrate, copy, move, enumerate, synchronize or delete objects.
                                        </p>

                                        <p>
                                            Existing Mail storage keeps its persisted concrete disk identity and is not switched by this Storage runtime.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-[28px] border border-gray-200 bg-white p-5 shadow-sm">
                            <div className="flex items-start gap-3">
                                <LockKeyhole className="mt-0.5 h-5 w-5 shrink-0 text-gray-500" />

                                <div>
                                    <h2 className="font-semibold text-gray-900">
                                        Security
                                    </h2>

                                    <p className="mt-2 text-sm leading-6 text-gray-500">
                                        Storage profiles never contain object-storage credentials. Provider secrets remain encrypted in Infrastructure Connections and are never returned to this page.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {definition
                            ?.requires_infrastructure
                        && selectedConnection
                        && !validSelectedConnection ? (
                            <div className="rounded-[28px] border border-red-200 bg-red-50 p-5">
                                <h2 className="font-semibold text-red-900">
                                    Connection unavailable
                                </h2>

                                <p className="mt-2 text-sm leading-6 text-red-700">
                                    The currently referenced Infrastructure Connection is disabled, archived, or no longer matches this Storage provider. Choose a valid connection before saving.
                                </p>
                            </div>
                        ) : null}
                    </aside>
                </form>
            </div>
        </AdminLayout>
    )
}

function SectionHeader({
                           icon: Icon,
                           title,
                           description,
                       }: {
    icon: typeof HardDrive
    title: string
    description: string
}) {
    return (
        <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
            <div className="flex items-start gap-3">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                    <Icon className="h-5 w-5 text-violet-700" />
                </span>

                <div>
                    <h2 className="font-semibold text-gray-900">
                        {title}
                    </h2>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        {description}
                    </p>
                </div>
            </div>
        </div>
    )
}

function ReadOnlyDriver({
                            definition,
                            driver,
                        }: {
    definition?: Definition
    driver: string
}) {
    return (
        <div className="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white ring-1 ring-inset ring-gray-200">
                {driver === 'local' ? (
                    <Database className="h-4 w-4 text-gray-500" />
                ) : (
                    <Cloud className="h-4 w-4 text-gray-500" />
                )}
            </span>

            <div>
                <p className="text-sm font-semibold text-gray-800">
                    {definition?.label
                        ?? driver}
                </p>

                <p className="mt-0.5 text-xs text-gray-400">
                    Storage driver cannot be changed after profile creation.
                </p>
            </div>
        </div>
    )
}

function Field({
                   label,
                   required = false,
                   hint,
                   error,
                   children,
               }: {
    label: string
    required?: boolean
    hint?: string
    error?: string
    children: React.ReactNode
}) {
    return (
        <div>
            <label className="mb-2 block text-sm font-semibold text-gray-700">
                {label}

                {required ? (
                    <span className="ml-1 text-red-500">
                        *
                    </span>
                ) : null}
            </label>

            {children}

            {hint && !error ? (
                <p className="mt-1.5 text-xs leading-5 text-gray-400">
                    {hint}
                </p>
            ) : null}

            <InputError
                message={error}
                className="mt-1.5"
            />
        </div>
    )
}

function Switch({
                    enabled,
                }: {
    enabled: boolean
}) {
    return (
        <span
            className={`relative inline-flex h-6 w-11 shrink-0 rounded-full transition ${
                enabled
                    ? 'bg-violet-600'
                    : 'bg-gray-300'
            }`}
        >
            <span
                className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform ${
                    enabled
                        ? 'translate-x-[22px]'
                        : 'translate-x-0.5'
                }`}
            />
        </span>
    )
}

function inputClass(error: boolean): string {
    return `h-11 w-full rounded-xl border bg-white px-3 text-sm outline-none transition focus:ring-2 ${
        error
            ? 'border-red-300 focus:border-red-400 focus:ring-red-100'
            : 'border-gray-200 focus:border-violet-400 focus:ring-violet-100'
    }`
}

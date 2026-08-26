import { type FormEvent } from 'react'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    CheckCircle2,
    Info,
    LockKeyhole,
    Radio,
    Server,
    ShieldCheck,
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
    type: string
    name: string
    description: string
    available: boolean
    unavailable_reason?: string | null
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
    infrastructure_connection_id: number
    is_enabled: boolean
}

type Props = {
    configuration: Configuration | null
    definitions: Definition[]
    connections: Connection[]
}

export default function Form({
                                 configuration,
                                 definitions,
                                 connections,
                             }: Props) {
    const editing = configuration !== null

    const defaultDriver =
        configuration?.driver
        ?? definitions.find((definition) => definition.available)?.type
        ?? definitions[0]?.type
        ?? 'reverb'

    const form = useForm({
        name: configuration?.name ?? '',
        driver: defaultDriver,
        infrastructure_connection_id:
            configuration?.infrastructure_connection_id ?? 0,
        configuration: {},
        is_enabled: configuration?.is_enabled ?? true,
    })

    const selectedDefinition = definitions.find(
        (definition) => definition.type === form.data.driver,
    )

    const selectedConnection = connections.find(
        (connection) =>
            connection.id === form.data.infrastructure_connection_id,
    )

    const allowedConnections = connections.filter(
        (connection) =>
            connection.type === form.data.driver
            || connection.id === configuration?.infrastructure_connection_id,
    )

    const selectableConnections = allowedConnections.filter(
        (connection) =>
            connection.type === form.data.driver
            && connection.is_enabled
            && !connection.deleted_at,
    )

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (editing) {
            form.put(
                route('admin.system.broadcasting.update', {
                    configuration: configuration.id,
                }),
                {
                    preserveScroll: true,
                },
            )

            return
        }

        form.post(
            route('admin.system.broadcasting.store'),
            {
                preserveScroll: true,
            },
        )
    }

    const changeDriver = (value: string) => {
        form.setData((current) => ({
            ...current,
            driver: value,
            infrastructure_connection_id: 0,
        }))

        form.clearErrors(
            'driver',
            'infrastructure_connection_id',
            'configuration',
        )
    }

    const title = editing
        ? 'Edit Real-time profile'
        : 'Create Real-time profile'

    return (
        <AdminLayout title={title}>
            <Head title={title} />

            <div className="space-y-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-4">
                            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                <Radio className="h-6 w-6 text-sky-700" />
                            </span>

                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                        {title}
                                    </h1>

                                    <span className="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                        Managed profile
                                    </span>
                                </div>

                                <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                    {editing
                                        ? 'Update the stored profile without moving provider credentials or endpoint settings into the Broadcasting subsystem.'
                                        : 'Prepare a reusable broadcaster profile without changing the currently active real-time runtime.'}
                                </p>
                            </div>
                        </div>

                        <Link
                            href={route('admin.system.broadcasting.index')}
                            className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Real-time
                        </Link>
                    </div>

                    <div className="border-t border-sky-100 bg-sky-50/60 px-6 py-4">
                        <div className="flex items-start gap-3">
                            <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white ring-1 ring-inset ring-sky-200">
                                <Info className="h-4 w-4 text-sky-700" />
                            </span>

                            <div>
                                <p className="text-sm font-semibold text-sky-950">
                                    Saving a profile does not activate it
                                </p>

                                <p className="mt-1 max-w-4xl text-sm leading-6 text-sky-800/80">
                                    Broadcasting ownership remains unchanged until the profile is explicitly activated. Activation performs its own structural and live health checks before switching managed ownership.
                                </p>
                            </div>
                        </div>
                    </div>
                </header>

                <form
                    onSubmit={submit}
                    className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]"
                >
                    <div className="space-y-6">
                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <SectionHeader
                                icon={Radio}
                                title="Profile"
                                description="Define how this managed broadcaster profile should be identified and which provider it uses."
                            />

                            <div className="space-y-6 p-5 sm:p-6">
                                <div>
                                    <label
                                        htmlFor="name"
                                        className="text-sm font-semibold text-gray-800"
                                    >
                                        Name
                                    </label>

                                    <p className="mt-1 text-sm leading-6 text-gray-500">
                                        Use a name that identifies the purpose or environment of this profile.
                                    </p>

                                    <input
                                        id="name"
                                        type="text"
                                        value={form.data.name}
                                        onChange={(event) =>
                                            form.setData(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Production Reverb"
                                        className="mt-3 h-11 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={form.errors.name}
                                    />
                                </div>

                                <div>
                                    <label className="text-sm font-semibold text-gray-800">
                                        Provider
                                    </label>

                                    <p className="mt-1 text-sm leading-6 text-gray-500">
                                        The provider determines which compatible Infrastructure Connection can be attached.
                                    </p>

                                    <Select
                                        disabled={editing}
                                        value={form.data.driver}
                                        onValueChange={changeDriver}
                                    >
                                        <SelectTrigger className="mt-3 h-11 w-full rounded-xl border-gray-200 bg-white">
                                            <SelectValue placeholder="Select provider" />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {definitions.map((definition) => (
                                                <SelectItem
                                                    key={definition.type}
                                                    value={definition.type}
                                                    disabled={!definition.available}
                                                >
                                                    {definition.name}
                                                    {definition.available
                                                        ? ''
                                                        : ' · unavailable'}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>

                                    {editing ? (
                                        <div className="mt-3 flex items-start gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                                            <LockKeyhole className="mt-0.5 h-4 w-4 shrink-0 text-gray-500" />

                                            <p className="text-xs leading-5 text-gray-600">
                                                The provider is immutable after profile creation. Create a new profile to use another broadcaster.
                                            </p>
                                        </div>
                                    ) : null}

                                    <InputError
                                        className="mt-2"
                                        message={form.errors.driver}
                                    />
                                </div>

                                {selectedDefinition ? (
                                    <div
                                        className={`rounded-2xl border p-4 ${
                                            selectedDefinition.available
                                                ? 'border-sky-200 bg-sky-50/60'
                                                : 'border-amber-200 bg-amber-50'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3">
                                            {selectedDefinition.available ? (
                                                <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-sky-700" />
                                            ) : (
                                                <Info className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />
                                            )}

                                            <div>
                                                <p
                                                    className={`text-sm font-semibold ${
                                                        selectedDefinition.available
                                                            ? 'text-sky-950'
                                                            : 'text-amber-950'
                                                    }`}
                                                >
                                                    {selectedDefinition.name}
                                                </p>

                                                <p
                                                    className={`mt-1 text-sm leading-6 ${
                                                        selectedDefinition.available
                                                            ? 'text-sky-800/80'
                                                            : 'text-amber-800'
                                                    }`}
                                                >
                                                    {selectedDefinition.description}
                                                </p>

                                                {!selectedDefinition.available
                                                && selectedDefinition.unavailable_reason ? (
                                                    <p className="mt-2 text-xs leading-5 text-amber-700">
                                                        {selectedDefinition.unavailable_reason}
                                                    </p>
                                                ) : null}
                                            </div>
                                        </div>
                                    </div>
                                ) : null}
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <SectionHeader
                                icon={Server}
                                title="Infrastructure"
                                description="Attach an existing connection that owns the provider endpoint and credentials."
                            />

                            <div className="space-y-5 p-5 sm:p-6">
                                <div>
                                    <label className="text-sm font-semibold text-gray-800">
                                        Infrastructure connection
                                    </label>

                                    <p className="mt-1 text-sm leading-6 text-gray-500">
                                        Only enabled {selectedDefinition?.name ?? 'provider'} connections are selectable.
                                    </p>

                                    <Select
                                        value={
                                            form.data.infrastructure_connection_id
                                                ? String(
                                                    form.data
                                                        .infrastructure_connection_id,
                                                )
                                                : ''
                                        }
                                        onValueChange={(value) => {
                                            form.setData(
                                                'infrastructure_connection_id',
                                                Number(value),
                                            )

                                            form.clearErrors(
                                                'infrastructure_connection_id',
                                            )
                                        }}
                                    >
                                        <SelectTrigger className="mt-3 h-11 w-full rounded-xl border-gray-200 bg-white">
                                            <SelectValue placeholder="Select infrastructure connection" />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {allowedConnections.map(
                                                (connection) => (
                                                    <SelectItem
                                                        key={connection.id}
                                                        value={String(
                                                            connection.id,
                                                        )}
                                                        disabled={
                                                            connection.type
                                                            !== form.data.driver
                                                            || !connection.is_enabled
                                                            || Boolean(
                                                                connection.deleted_at,
                                                            )
                                                        }
                                                    >
                                                        {connection.name}
                                                        {connection.deleted_at
                                                            ? ' · archived'
                                                            : !connection.is_enabled
                                                                ? ' · disabled'
                                                                : connection.type
                                                                !== form.data.driver
                                                                    ? ` · ${humanize(connection.type)}`
                                                                    : ''}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>

                                    <InputError
                                        className="mt-2"
                                        message={
                                            form.errors
                                                .infrastructure_connection_id
                                        }
                                    />
                                </div>

                                {selectableConnections.length === 0 ? (
                                    <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                        <div className="flex gap-3">
                                            <Info className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                                            <div>
                                                <p className="text-sm font-semibold text-amber-950">
                                                    No selectable connection
                                                </p>

                                                <p className="mt-1 text-sm leading-6 text-amber-800">
                                                    Create and enable a matching Infrastructure Connection before this profile can be saved with a usable provider target.
                                                </p>

                                                <Link
                                                    href={route(
                                                        'admin.system.connections.create',
                                                    )}
                                                    className="mt-3 inline-flex text-sm font-semibold text-amber-900 underline decoration-amber-300 underline-offset-4 hover:decoration-amber-600"
                                                >
                                                    Open Infrastructure Connections
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                ) : null}

                                {selectedConnection ? (
                                    <ConnectionSummary
                                        connection={selectedConnection}
                                        matches={
                                            selectedConnection.type
                                            === form.data.driver
                                        }
                                    />
                                ) : null}

                                <InputError
                                    className="mt-2"
                                    message={form.errors.configuration}
                                />
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <SectionHeader
                                icon={ShieldCheck}
                                title="Availability"
                                description="Control whether this stored profile can be selected for activation."
                            />

                            <div className="p-5 sm:p-6">
                                <div className="flex items-center justify-between gap-6 rounded-2xl border border-gray-200 bg-gray-50/60 p-5">
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-gray-900">
                                            Enabled
                                        </p>

                                        <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                            Enabled profiles may be tested and activated. Disabling a profile does not automatically change current runtime ownership.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked={form.data.is_enabled}
                                        aria-label="Enable profile"
                                        onClick={() =>
                                            form.setData(
                                                'is_enabled',
                                                !form.data.is_enabled,
                                            )
                                        }
                                        className={`relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 ${
                                            form.data.is_enabled
                                                ? 'bg-sky-600'
                                                : 'bg-gray-300'
                                        }`}
                                    >
                                        <span
                                            className={`pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200 ${
                                                form.data.is_enabled
                                                    ? 'translate-x-5'
                                                    : 'translate-x-0'
                                            }`}
                                        />
                                    </button>
                                </div>

                                <InputError
                                    className="mt-2"
                                    message={form.errors.is_enabled}
                                />
                            </div>
                        </section>

                        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                asChild
                            >
                                <Link
                                    href={route(
                                        'admin.system.broadcasting.index',
                                    )}
                                >
                                    Cancel
                                </Link>
                            </Button>

                            <Button
                                type="submit"
                                disabled={
                                    form.processing
                                    || !selectedDefinition?.available
                                }
                            >
                                {form.processing
                                    ? 'Saving…'
                                    : editing
                                        ? 'Save changes'
                                        : 'Create profile'}
                            </Button>
                        </div>
                    </div>

                    <aside className="space-y-4 xl:sticky xl:top-6 xl:self-start">
                        <div className="rounded-[28px] border border-gray-200 bg-white p-5 shadow-sm">
                            <div className="flex items-start gap-3">
                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                                    <ShieldCheck className="h-5 w-5 text-violet-700" />
                                </span>

                                <div>
                                    <h2 className="font-semibold text-gray-900">
                                        Profile boundaries
                                    </h2>

                                    <p className="mt-1 text-sm leading-6 text-gray-500">
                                        A Broadcast profile deliberately contains very little configuration.
                                    </p>
                                </div>
                            </div>

                            <div className="mt-5 space-y-4">
                                <BoundaryItem
                                    title="Stored here"
                                    text="Profile name, provider type, enabled state, and the Infrastructure Connection reference."
                                />

                                <BoundaryItem
                                    title="Stored in Infrastructure"
                                    text="Application ID, publisher endpoint, public WebSocket endpoint, application key, and application secret."
                                />

                                <BoundaryItem
                                    title="Activation"
                                    text="Saving does not switch the active broadcaster. Runtime ownership is changed separately from the Real-time page."
                                />
                            </div>
                        </div>

                        <div className="rounded-[28px] border border-gray-200 bg-gray-50 p-5">
                            <div className="flex items-start gap-3">
                                <LockKeyhole className="mt-0.5 h-5 w-5 shrink-0 text-gray-600" />

                                <div>
                                    <p className="text-sm font-semibold text-gray-900">
                                        Secrets stay out of this form
                                    </p>

                                    <p className="mt-1 text-sm leading-6 text-gray-600">
                                        Provider credentials are never copied into the Broadcast profile. Rotating or replacing credentials remains an Infrastructure Connection operation.
                                    </p>
                                </div>
                            </div>
                        </div>
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
    icon: typeof Radio
    title: string
    description: string
}) {
    return (
        <div className="flex gap-3 border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                <Icon className="h-5 w-5 text-sky-700" />
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
    )
}

function ConnectionSummary({
                               connection,
                               matches,
                           }: {
    connection: Connection
    matches: boolean
}) {
    const available =
        matches
        && connection.is_enabled
        && !connection.deleted_at

    return (
        <div
            className={`rounded-2xl border p-4 ${
                available
                    ? 'border-emerald-200 bg-emerald-50/60'
                    : 'border-amber-200 bg-amber-50'
            }`}
        >
            <div className="flex items-start gap-3">
                {available ? (
                    <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-700" />
                ) : (
                    <Info className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />
                )}

                <div className="min-w-0">
                    <p
                        className={`text-sm font-semibold ${
                            available
                                ? 'text-emerald-950'
                                : 'text-amber-950'
                        }`}
                    >
                        {connection.name}
                    </p>

                    <div
                        className={`mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm ${
                            available
                                ? 'text-emerald-800'
                                : 'text-amber-800'
                        }`}
                    >
                        <span>
                            Provider: {humanize(connection.type)}
                        </span>

                        <span>
                            {connection.deleted_at
                                ? 'Archived'
                                : connection.is_enabled
                                    ? 'Enabled'
                                    : 'Disabled'}
                        </span>
                    </div>

                    {!available ? (
                        <p className="mt-2 text-xs leading-5 text-amber-700">
                            This connection is retained here because the profile already references it, but it cannot be selected as a usable target in its current state.
                        </p>
                    ) : null}
                </div>
            </div>
        </div>
    )
}

function BoundaryItem({
                          title,
                          text,
                      }: {
    title: string
    text: string
}) {
    return (
        <div>
            <p className="text-sm font-semibold text-gray-800">
                {title}
            </p>

            <p className="mt-1 text-sm leading-6 text-gray-500">
                {text}
            </p>
        </div>
    )
}

function humanize(value: string) {
    return value
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase())
}

import InputError from '@/Components/InputError'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'
import ManagedPusherProtocolConfiguration, {
    type InfrastructureConfigurationValue,
} from './components/ManagedPusherProtocolConfiguration'
import { Link, useForm } from '@inertiajs/react'
import {
    CheckCircle2,
    CloudCog,
    Database,
    Eye,
    EyeOff,
    KeyRound,
    LockKeyhole,
    Network,
    Save,
    Server,
    Settings2,
    ShieldCheck,
    Trash2,
    X,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { useMemo, useState } from 'react'
import type { FormEvent, ReactNode } from 'react'
import { route } from 'ziggy-js'

export type Definition = {
    type: string
    label: string
    description: string
    sources: string[]
    available: boolean
    options: {
        deployment_connections?: string[]
    }
}

export type ConnectionFormValue = {
    id: number
    name: string
    type: string
    source: string
    is_enabled: boolean
    configuration: Record<string, InfrastructureConfigurationValue>
    credential_flags?: {
        password_configured?: boolean
        app_key_configured?: boolean
        app_secret_configured?: boolean
    }
}

type FormData = {
    name: string
    type: string
    source: string
    configuration: Record<string, InfrastructureConfigurationValue>
    credentials: {
        password: string
        app_key: string
        app_secret: string
    }
    remove_credentials: string[]
    is_enabled: boolean
}

type Props = {
    definitions: Definition[]
    connection?: ConnectionFormValue
}

type FormErrors = Record<string, string | undefined>

export default function Form({ definitions, connection }: Props) {
    const editing = Boolean(connection)

    const initialDefinition =
        definitions.find((definition) => definition.type === connection?.type)
        ?? definitions.find((definition) => definition.available)
        ?? definitions[0]

    const initialSource = connection?.source ?? getDefaultSource(initialDefinition)

    const form = useForm<FormData>({
        name: connection?.name ?? '',
        type: connection?.type ?? initialDefinition?.type ?? '',
        source: initialSource,
        configuration: connection?.configuration ?? createDefaultConfiguration(initialDefinition),
        credentials: {
            password: '',
            app_key: '',
            app_secret: '',
        },
        remove_credentials: [],
        is_enabled: connection?.is_enabled ?? true,
    })

    const [showPassword, setShowPassword] = useState(false)

    const definition = useMemo(
        () => definitions.find((item) => item.type === form.data.type),
        [definitions, form.data.type],
    )

    const errors = form.errors as FormErrors
    const passwordConfigured = Boolean(connection?.credential_flags?.password_configured)
    const removePassword = form.data.remove_credentials.includes('password')
    const availableSources = definition?.sources ?? []
    const deploymentConnections = definition?.options.deployment_connections ?? []

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (connection) {
            form.put(route('admin.system.connections.update', connection.id))
            return
        }

        form.post(route('admin.system.connections.store'))
    }

    const changeType = (type: string) => {
        const nextDefinition = definitions.find((item) => item.type === type)
        const source = getDefaultSource(nextDefinition)

        form.setData((data) => ({
            ...data,
            type,
            source,
            configuration: createDefaultConfiguration(nextDefinition),
            credentials: {
                password: '',
                app_key: '',
                app_secret: '',
            },
            remove_credentials: [],
        }))

        form.clearErrors()
    }

    const changeSource = (source: string) => {
        form.setData((data) => ({
            ...data,
            source,
            remove_credentials: source === 'managed' ? data.remove_credentials : [],
            credentials: source === 'managed'
                ? data.credentials
                : {
                    password: '',
                    app_key: '',
                    app_secret: '',
                },
        }))
    }

    const setConfiguration = (key: string, value: InfrastructureConfigurationValue) => {
        form.setData('configuration', {
            ...form.data.configuration,
            [key]: value,
        })
    }

    const setPassword = (password: string) => {
        form.setData((data) => ({
            ...data,
            credentials: {
                ...data.credentials,
                password,
            },
            remove_credentials: password !== ''
                ? data.remove_credentials.filter((item) => item !== 'password')
                : data.remove_credentials,
        }))
    }

    const toggleRemovePassword = (remove: boolean) => {
        form.setData((data) => ({
            ...data,
            credentials: {
                ...data.credentials,
                password: remove ? '' : data.credentials.password,
            },
            remove_credentials: remove
                ? [...data.remove_credentials.filter((item) => item !== 'password'), 'password']
                : data.remove_credentials.filter((item) => item !== 'password'),
        }))
    }

    const setProviderCredential = (key: 'app_key' | 'app_secret', value: string) => {
        form.setData((data) => ({
            ...data,
            credentials: {
                ...data.credentials,
                [key]: value,
            },
            remove_credentials: data.remove_credentials.filter((item) => item !== key),
        }))
    }

    return (
        <form onSubmit={submit} className="space-y-6">
            <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                    <div className="flex items-start gap-3">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                            <Settings2 className="h-5 w-5 text-sky-600" />
                        </div>

                        <div>
                            <h2 className="font-semibold text-gray-900">Connection details</h2>
                            <p className="mt-1 text-sm leading-6 text-gray-500">
                                Name the connection and choose the infrastructure adapter SimpleDesk should use.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="space-y-5 p-5 sm:p-6">
                    <Field label="Name" required error={errors.name}>
                        <input
                            type="text"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            placeholder={connectionNamePlaceholder(form.data.type)}
                            autoComplete="off"
                            className={inputClass(Boolean(errors.name))}
                        />
                    </Field>

                    <Field label="Connection type" required error={errors.type}>
                        {editing ? (
                            <ReadOnlyType definition={definition} type={form.data.type} />
                        ) : (
                            <Select value={form.data.type} onValueChange={changeType}>
                                <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                                    <SelectValue placeholder="Choose a connection type" />
                                </SelectTrigger>

                                <SelectContent>
                                    {definitions.map((item) => (
                                        <SelectItem
                                            key={item.type}
                                            value={item.type}
                                            disabled={!item.available}
                                        >
                                            {item.label}{item.available ? '' : ' · unavailable'}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}

                        {definition?.description ? (
                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                {definition.description}
                            </p>
                        ) : null}
                    </Field>
                </div>
            </section>

            <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                    <div className="flex items-start gap-3">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                            <Network className="h-5 w-5 text-violet-600" />
                        </div>

                        <div>
                            <h2 className="font-semibold text-gray-900">Configuration source</h2>
                            <p className="mt-1 text-sm leading-6 text-gray-500">
                                Choose whether SimpleDesk manages this connection or references deployment-provided configuration.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="p-5 sm:p-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        {availableSources.includes('managed') ? (
                            <SourceCard
                                title="Managed by SimpleDesk"
                                description="Store connection settings in SimpleDesk. Sensitive credentials remain encrypted."
                                icon={CloudCog}
                                selected={form.data.source === 'managed'}
                                onClick={() => changeSource('managed')}
                            />
                        ) : null}

                        {availableSources.includes('deployment') ? (
                            <SourceCard
                                title="Deployment configuration"
                                description="Use infrastructure configuration supplied by the application deployment."
                                icon={Server}
                                selected={form.data.source === 'deployment'}
                                onClick={() => changeSource('deployment')}
                            />
                        ) : null}
                    </div>

                    <InputError message={errors.source} className="mt-2" />
                </div>
            </section>

            {form.data.source === 'deployment' ? (
                <DeploymentConfiguration
                    deploymentConnections={deploymentConnections}
                    connectionName={String(form.data.configuration.connection_name ?? '')}
                    error={errors['configuration.connection_name']}
                    onChange={(value) => setConfiguration('connection_name', value)}
                />
            ) : form.data.type === 'redis' ? (
                <ManagedRedisConfiguration
                    configuration={form.data.configuration}
                    password={form.data.credentials.password}
                    passwordConfigured={passwordConfigured}
                    removePassword={removePassword}
                    showPassword={showPassword}
                    errors={errors}
                    onConfigurationChange={setConfiguration}
                    onPasswordChange={setPassword}
                    onTogglePassword={() => setShowPassword((current) => !current)}
                    onRemovePasswordChange={toggleRemovePassword}
                />
            ) : isPusherProtocolType(form.data.type) ? (
                <ManagedPusherProtocolConfiguration
                    type={form.data.type}
                    configuration={form.data.configuration}
                    credentials={{
                        app_key: form.data.credentials.app_key,
                        app_secret: form.data.credentials.app_secret,
                    }}
                    credentialFlags={connection?.credential_flags}
                    errors={errors}
                    onConfigurationChange={setConfiguration}
                    onCredentialChange={setProviderCredential}
                />
            ) : (
                <section className="rounded-[28px] border border-amber-200 bg-amber-50 p-5">
                    <p className="font-semibold text-amber-900">Managed configuration unavailable</p>
                    <p className="mt-1 text-sm leading-6 text-amber-700">
                        This infrastructure type does not currently expose a managed configuration form.
                    </p>
                </section>
            )}

            <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                    <div className="flex items-start gap-3">
                        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100">
                            <CheckCircle2 className="h-5 w-5 text-emerald-600" />
                        </div>

                        <div>
                            <h2 className="font-semibold text-gray-900">Availability</h2>
                            <p className="mt-1 text-sm leading-6 text-gray-500">
                                Control whether this connection can be used by SimpleDesk subsystems.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="p-5 sm:p-6">
                    <button
                        type="button"
                        role="switch"
                        aria-checked={form.data.is_enabled}
                        onClick={() => form.setData('is_enabled', !form.data.is_enabled)}
                        className="flex w-full items-center justify-between gap-5 rounded-2xl border border-gray-200 bg-gray-50/60 p-4 text-left transition hover:border-gray-300 hover:bg-gray-50"
                    >
                        <div>
                            <div className="font-semibold text-gray-900">Enable connection</div>
                            <p className="mt-1 text-sm leading-6 text-gray-500">
                                Disabled connections remain configured but cannot be selected for normal subsystem use.
                            </p>
                        </div>

                        <Switch enabled={form.data.is_enabled} />
                    </button>

                    <InputError message={errors.is_enabled} className="mt-2" />
                </div>
            </section>

            <div className="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                <Link
                    href={route('admin.system.connections.index')}
                    className="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                >
                    <X className="h-4 w-4" />
                    Cancel
                </Link>

                <button
                    type="submit"
                    disabled={form.processing || !definition?.available}
                    className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <Save className="h-4 w-4" />
                    {form.processing
                        ? connection ? 'Saving…' : 'Creating…'
                        : connection ? 'Save changes' : 'Create connection'}
                </button>
            </div>
        </form>
    )
}

function ManagedRedisConfiguration({
                                       configuration,
                                       password,
                                       passwordConfigured,
                                       removePassword,
                                       showPassword,
                                       errors,
                                       onConfigurationChange,
                                       onPasswordChange,
                                       onTogglePassword,
                                       onRemovePasswordChange,
                                   }: {
    configuration: Record<string, InfrastructureConfigurationValue>
    password: string
    passwordConfigured: boolean
    removePassword: boolean
    showPassword: boolean
    errors: FormErrors
    onConfigurationChange: (key: string, value: InfrastructureConfigurationValue) => void
    onPasswordChange: (password: string) => void
    onTogglePassword: () => void
    onRemovePasswordChange: (remove: boolean) => void
}) {
    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100">
                        <Database className="h-5 w-5 text-rose-600" />
                    </div>

                    <div>
                        <h2 className="font-semibold text-gray-900">Redis connection</h2>
                        <p className="mt-1 text-sm leading-6 text-gray-500">
                            Configure the Redis endpoint and authentication details managed by SimpleDesk.
                        </p>
                    </div>
                </div>
            </div>

            <div className="space-y-6 p-5 sm:p-6">
                <div className="grid gap-5 sm:grid-cols-2">
                    <Field label="Host" required error={errors['configuration.host']}>
                        <input
                            type="text"
                            value={stringValue(configuration.host)}
                            onChange={(event) => onConfigurationChange('host', event.target.value)}
                            placeholder="redis"
                            autoComplete="off"
                            className={inputClass(Boolean(errors['configuration.host']))}
                        />
                    </Field>

                    <Field label="Port" required error={errors['configuration.port']}>
                        <input
                            type="number"
                            min={1}
                            max={65535}
                            value={inputValue(configuration.port)}
                            onChange={(event) => onConfigurationChange('port', numberValue(event.target.value))}
                            className={inputClass(Boolean(errors['configuration.port']))}
                        />
                    </Field>

                    <Field
                        label="Database"
                        required
                        error={errors['configuration.database']}
                        hint="Logical Redis database number."
                    >
                        <input
                            type="number"
                            min={0}
                            value={inputValue(configuration.database)}
                            onChange={(event) => onConfigurationChange('database', numberValue(event.target.value))}
                            className={inputClass(Boolean(errors['configuration.database']))}
                        />
                    </Field>

                    <Field
                        label="Connection timeout"
                        required
                        error={errors['configuration.connect_timeout_seconds']}
                        hint="Maximum time to establish the Redis connection."
                        suffix="seconds"
                    >
                        <input
                            type="number"
                            min={1}
                            step="1"
                            value={inputValue(configuration.connect_timeout_seconds)}
                            onChange={(event) =>
                                onConfigurationChange('connect_timeout_seconds', numberValue(event.target.value))
                            }
                            className={inputClass(
                                Boolean(errors['configuration.connect_timeout_seconds']),
                                true,
                            )}
                        />
                    </Field>

                    <Field
                        label="Username"
                        error={errors['configuration.username']}
                        hint="Optional Redis ACL username."
                    >
                        <input
                            type="text"
                            value={stringValue(configuration.username)}
                            onChange={(event) => onConfigurationChange('username', event.target.value)}
                            placeholder="Optional"
                            autoComplete="off"
                            className={inputClass(Boolean(errors['configuration.username']))}
                        />
                    </Field>

                    <Field label="Password" error={errors['credentials.password']}>
                        <div className="relative">
                            <input
                                type={showPassword ? 'text' : 'password'}
                                value={password}
                                disabled={removePassword}
                                onChange={(event) => onPasswordChange(event.target.value)}
                                placeholder={
                                    passwordConfigured
                                        ? 'Leave blank to keep current password'
                                        : 'Optional'
                                }
                                autoComplete="new-password"
                                className={`${inputClass(Boolean(errors['credentials.password']))} pr-11 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400`}
                            />

                            <button
                                type="button"
                                onClick={onTogglePassword}
                                disabled={removePassword}
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                                title={showPassword ? 'Hide password' : 'Show password'}
                                className="absolute right-2.5 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                {showPassword ? (
                                    <EyeOff className="h-4 w-4" />
                                ) : (
                                    <Eye className="h-4 w-4" />
                                )}
                            </button>
                        </div>

                        {passwordConfigured && !removePassword ? (
                            <div className="mt-2 flex items-start gap-2 rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2.5">
                                <LockKeyhole className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                                <p className="text-xs leading-5 text-emerald-700">
                                    A password is already configured. Leave this field empty to keep it, or enter a new password to replace it.
                                </p>
                            </div>
                        ) : null}
                    </Field>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <button
                        type="button"
                        role="switch"
                        aria-checked={Boolean(configuration.tls)}
                        onClick={() => onConfigurationChange('tls', !Boolean(configuration.tls))}
                        className="flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50/60 p-4 text-left transition hover:border-gray-300 hover:bg-gray-50"
                    >
                        <div className="flex items-start gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                                <ShieldCheck className="h-4 w-4 text-sky-600" />
                            </div>

                            <div>
                                <div className="text-sm font-semibold text-gray-900">TLS</div>
                                <p className="mt-1 text-xs leading-5 text-gray-500">
                                    Encrypt the connection to the Redis server.
                                </p>
                            </div>
                        </div>

                        <Switch enabled={Boolean(configuration.tls)} />
                    </button>

                    {passwordConfigured ? (
                        <button
                            type="button"
                            role="switch"
                            aria-checked={removePassword}
                            onClick={() => onRemovePasswordChange(!removePassword)}
                            className={`flex items-center justify-between gap-4 rounded-2xl border p-4 text-left transition ${
                                removePassword
                                    ? 'border-red-200 bg-red-50/70'
                                    : 'border-gray-200 bg-gray-50/60 hover:border-gray-300 hover:bg-gray-50'
                            }`}
                        >
                            <div className="flex items-start gap-3">
                                <div
                                    className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${
                                        removePassword ? 'bg-red-100' : 'bg-gray-100'
                                    }`}
                                >
                                    <Trash2
                                        className={`h-4 w-4 ${
                                            removePassword ? 'text-red-600' : 'text-gray-500'
                                        }`}
                                    />
                                </div>

                                <div>
                                    <div
                                        className={`text-sm font-semibold ${
                                            removePassword ? 'text-red-800' : 'text-gray-900'
                                        }`}
                                    >
                                        Remove password
                                    </div>

                                    <p
                                        className={`mt-1 text-xs leading-5 ${
                                            removePassword ? 'text-red-600' : 'text-gray-500'
                                        }`}
                                    >
                                        Explicitly remove the currently stored Redis password.
                                    </p>
                                </div>
                            </div>

                            <Switch enabled={removePassword} danger={removePassword} />
                        </button>
                    ) : null}
                </div>

                <InputError message={errors.remove_credentials} />
            </div>
        </section>
    )
}

function DeploymentConfiguration({
                                     deploymentConnections,
                                     connectionName,
                                     error,
                                     onChange,
                                 }: {
    deploymentConnections: string[]
    connectionName: string
    error?: string
    onChange: (value: string) => void
}) {
    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                        <Server className="h-5 w-5 text-violet-600" />
                    </div>

                    <div>
                        <h2 className="font-semibold text-gray-900">Deployment Redis</h2>
                        <p className="mt-1 text-sm leading-6 text-gray-500">
                            Reference an existing Laravel Redis connection. Credentials remain outside SimpleDesk.
                        </p>
                    </div>
                </div>
            </div>

            <div className="p-5 sm:p-6">
                {deploymentConnections.length > 0 ? (
                    <Field
                        label="Laravel Redis connection"
                        required
                        error={error}
                        hint="Connection names are read from the application's Redis configuration."
                    >
                        <Select value={connectionName} onValueChange={onChange}>
                            <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                                <SelectValue placeholder="Select Redis connection" />
                            </SelectTrigger>

                            <SelectContent>
                                {deploymentConnections.map((connection) => (
                                    <SelectItem key={connection} value={connection}>
                                        {connection}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                ) : (
                    <div className="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <Server className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />

                        <div>
                            <div className="text-sm font-semibold text-amber-900">
                                No deployment Redis connections
                            </div>
                            <p className="mt-1 text-sm leading-6 text-amber-700">
                                SimpleDesk could not find any configured Laravel Redis connections that can be referenced here.
                            </p>
                        </div>
                    </div>
                )}

                <div className="mt-5 flex items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                    <KeyRound className="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />
                    <p className="text-sm leading-6 text-sky-800">
                        Deployment-managed credentials are never copied into the SimpleDesk database. Connection settings and secrets continue to be controlled by the application deployment.
                    </p>
                </div>
            </div>
        </section>
    )
}

function SourceCard({
                        title,
                        description,
                        icon: Icon,
                        selected,
                        onClick,
                    }: {
    title: string
    description: string
    icon: LucideIcon
    selected: boolean
    onClick: () => void
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`relative flex items-start gap-4 rounded-2xl border p-4 text-left transition ${
                selected
                    ? 'border-sky-300 bg-sky-50/70 ring-4 ring-sky-50'
                    : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
            }`}
        >
            <div
                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                    selected ? 'bg-sky-100' : 'bg-gray-100'
                }`}
            >
                <Icon className={`h-5 w-5 ${selected ? 'text-sky-700' : 'text-gray-500'}`} />
            </div>

            <div className="min-w-0 pr-7">
                <div className={`font-semibold ${selected ? 'text-sky-900' : 'text-gray-900'}`}>
                    {title}
                </div>
                <p className="mt-1 text-sm leading-6 text-gray-500">{description}</p>
            </div>

            <span
                className={`absolute right-4 top-4 flex h-5 w-5 items-center justify-center rounded-full border ${
                    selected
                        ? 'border-sky-600 bg-sky-600'
                        : 'border-gray-300 bg-white'
                }`}
            >
                {selected ? <CheckCircle2 className="h-3.5 w-3.5 text-white" /> : null}
            </span>
        </button>
    )
}

function ReadOnlyType({
                          definition,
                          type,
                      }: {
    definition?: Definition
    type: string
}) {
    return (
        <div className="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white ring-1 ring-inset ring-gray-200">
                <Database className="h-4 w-4 text-gray-500" />
            </div>

            <div>
                <div className="text-sm font-semibold text-gray-800">
                    {definition?.label ?? humanize(type)}
                </div>
                <div className="mt-0.5 text-xs text-gray-400">
                    Connection type cannot be changed after creation.
                </div>
            </div>
        </div>
    )
}

function Field({
                   label,
                   required = false,
                   hint,
                   error,
                   suffix,
                   children,
               }: {
    label: string
    required?: boolean
    hint?: string
    error?: string
    suffix?: string
    children: ReactNode
}) {
    return (
        <div>
            <label className="mb-2 block text-sm font-semibold text-gray-700">
                {label}
                {required ? <span className="ml-1 text-red-500">*</span> : null}
            </label>

            <div className="relative">
                {children}

                {suffix ? (
                    <span className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">
                        {suffix}
                    </span>
                ) : null}
            </div>

            {hint && !error ? (
                <p className="mt-1.5 text-xs leading-5 text-gray-400">{hint}</p>
            ) : null}

            <InputError message={error} className="mt-1.5" />
        </div>
    )
}

function Switch({
                    enabled,
                    danger = false,
                }: {
    enabled: boolean
    danger?: boolean
}) {
    return (
        <span
            className={`relative inline-flex h-6 w-11 shrink-0 rounded-full transition ${
                enabled
                    ? danger ? 'bg-red-600' : 'bg-sky-600'
                    : 'bg-gray-300'
            }`}
        >
            <span
                className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform ${
                    enabled ? 'translate-x-[22px]' : 'translate-x-0.5'
                }`}
            />
        </span>
    )
}

function createDefaultConfiguration(
    definition?: Definition,
): Record<string, InfrastructureConfigurationValue> {
    if (definition?.type === 'reverb') {
        return {
            app_id: '',
            host: '127.0.0.1',
            port: 8080,
            scheme: 'http',
            cluster: '',
            public_host: '',
            public_port: null,
            public_scheme: '',
        }
    }

    if (definition?.type === 'pusher') {
        return {
            app_id: '',
            host: '',
            port: null,
            scheme: 'https',
            cluster: '',
            public_host: '',
            public_port: null,
            public_scheme: '',
        }
    }

    return {
        host: '127.0.0.1',
        port: 6379,
        database: 0,
        username: '',
        tls: false,
        connect_timeout_seconds: 5,
        connection_name: definition?.options.deployment_connections?.[0] ?? '',
    }
}

function getDefaultSource(definition?: Definition): string {
    if (definition?.sources.includes('managed')) {
        return 'managed'
    }

    return definition?.sources[0] ?? 'managed'
}

function isPusherProtocolType(type: string): type is 'reverb' | 'pusher' {
    return type === 'reverb' || type === 'pusher'
}

function numberValue(value: string): number | string {
    if (value === '') {
        return ''
    }

    const number = Number(value)

    return Number.isNaN(number) ? value : number
}

function stringValue(value: InfrastructureConfigurationValue | undefined): string {
    return typeof value === 'string' ? value : ''
}

function inputValue(
    value: InfrastructureConfigurationValue | undefined,
): string | number {
    return typeof value === 'string' || typeof value === 'number' ? value : ''
}

function connectionNamePlaceholder(type: string): string {
    if (type === 'reverb') {
        return 'Production Reverb'
    }

    if (type === 'pusher') {
        return 'Production Pusher'
    }

    return 'Production Redis'
}

function inputClass(error = false, withSuffix = false): string {
    return `h-11 w-full rounded-xl border bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 ${
        withSuffix ? 'pr-20' : ''
    } ${
        error
            ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
            : 'border-gray-200 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'
    }`
}

function humanize(value: string): string {
    return value
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/[._-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/\b\w/g, (character) => character.toUpperCase())
}

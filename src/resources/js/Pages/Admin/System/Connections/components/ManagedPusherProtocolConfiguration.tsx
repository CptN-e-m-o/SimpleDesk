import InputError from '@/Components/InputError'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'
import { Eye, EyeOff, KeyRound, LockKeyhole, RadioTower } from 'lucide-react'
import { useState } from 'react'
import type { ReactNode } from 'react'

export type InfrastructureConfigurationScalar =
    | string
    | number
    | boolean
    | null

export type InfrastructureConfigurationObject = Record<
    string,
    InfrastructureConfigurationScalar
>

export type InfrastructureConfigurationValue =
    | InfrastructureConfigurationScalar
    | InfrastructureConfigurationObject[]

type Credentials = {
    app_key: string
    app_secret: string
}

type CredentialFlags = {
    app_key_configured?: boolean
    app_secret_configured?: boolean
}

type Props = {
    type: 'reverb' | 'pusher'
    configuration: Record<string, InfrastructureConfigurationValue>
    credentials: Credentials
    credentialFlags?: CredentialFlags
    errors: Record<string, string | undefined>
    onConfigurationChange: (key: string, value: InfrastructureConfigurationValue) => void
    onCredentialChange: (key: keyof Credentials, value: string) => void
}

export default function ManagedPusherProtocolConfiguration({
                                                               type,
                                                               configuration,
                                                               credentials,
                                                               credentialFlags,
                                                               errors,
                                                               onConfigurationChange,
                                                               onCredentialChange,
                                                           }: Props) {
    const [showKey, setShowKey] = useState(false)
    const [showSecret, setShowSecret] = useState(false)

    const reverb = type === 'reverb'
    const publicHost = stringValue(configuration.public_host)
    const publicScheme = stringValue(configuration.public_scheme)

    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                        <RadioTower className="h-5 w-5 text-violet-600" />
                    </div>

                    <div>
                        <h2 className="font-semibold text-gray-900">
                            {reverb ? 'Reverb endpoint' : 'Pusher endpoint'}
                        </h2>
                        <p className="mt-1 text-sm leading-6 text-gray-500">
                            Configure the server-side publisher endpoint and credentials. Browser-facing connection settings are kept separate from the publisher endpoint.
                        </p>
                    </div>
                </div>
            </div>

            <div className="space-y-7 p-5 sm:p-6">
                <div>
                    <h3 className="text-sm font-semibold text-gray-900">Publisher</h3>
                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        Laravel uses these settings when publishing broadcast events.
                    </p>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    <Field label="Application ID" required error={errors['configuration.app_id']}>
                        <input
                            type="text"
                            value={stringValue(configuration.app_id)}
                            onChange={(event) => onConfigurationChange('app_id', event.target.value)}
                            autoComplete="off"
                            placeholder="Application ID"
                            className={inputClass(Boolean(errors['configuration.app_id']))}
                        />
                    </Field>

                    {!reverb ? (
                        <Field
                            label="Cluster"
                            error={errors['configuration.cluster']}
                            hint="Required for standard Pusher Channels unless a custom publisher host is configured."
                        >
                            <input
                                type="text"
                                value={stringValue(configuration.cluster)}
                                onChange={(event) => onConfigurationChange('cluster', event.target.value)}
                                autoComplete="off"
                                placeholder="eu"
                                className={inputClass(Boolean(errors['configuration.cluster']))}
                            />
                        </Field>
                    ) : null}

                    <Field
                        label="Publisher host"
                        required={reverb}
                        error={errors['configuration.host']}
                        hint={
                            reverb
                                ? 'Internal or network-reachable Reverb publisher endpoint.'
                                : 'Optional custom Pusher-compatible publisher endpoint.'
                        }
                    >
                        <input
                            type="text"
                            value={stringValue(configuration.host)}
                            onChange={(event) => onConfigurationChange('host', event.target.value)}
                            autoComplete="off"
                            placeholder={reverb ? 'reverb.internal' : 'Optional custom host'}
                            className={inputClass(Boolean(errors['configuration.host']))}
                        />
                    </Field>

                    <Field label="Publisher port" error={errors['configuration.port']}>
                        <input
                            type="number"
                            min={1}
                            max={65535}
                            value={inputValue(configuration.port)}
                            onChange={(event) => onConfigurationChange('port', numberOrNull(event.target.value))}
                            placeholder="Automatic"
                            className={inputClass(Boolean(errors['configuration.port']))}
                        />
                    </Field>

                    <Field label="Publisher scheme" required error={errors['configuration.scheme']}>
                        <Select
                            value={stringValue(configuration.scheme) || 'https'}
                            onValueChange={(value) => onConfigurationChange('scheme', value)}
                        >
                            <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem value="https">HTTPS</SelectItem>
                                <SelectItem value="http">HTTP</SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                </div>

                <div className="border-t border-gray-200 pt-6">
                    <h3 className="text-sm font-semibold text-gray-900">Credentials</h3>
                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        Credentials are encrypted at rest. The application key may later be exposed through the dedicated safe client configuration because browser clients require it. The application secret is never exposed.
                    </p>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    <SecretField
                        label="Application key"
                        required
                        value={credentials.app_key}
                        configured={Boolean(credentialFlags?.app_key_configured)}
                        visible={showKey}
                        error={errors['credentials.app_key']}
                        placeholder="Application key"
                        onChange={(value) => onCredentialChange('app_key', value)}
                        onToggle={() => setShowKey((current) => !current)}
                    />

                    <SecretField
                        label="Application secret"
                        required
                        value={credentials.app_secret}
                        configured={Boolean(credentialFlags?.app_secret_configured)}
                        visible={showSecret}
                        error={errors['credentials.app_secret']}
                        placeholder="Application secret"
                        onChange={(value) => onCredentialChange('app_secret', value)}
                        onToggle={() => setShowSecret((current) => !current)}
                    />
                </div>

                <div className="border-t border-gray-200 pt-6">
                    <h3 className="text-sm font-semibold text-gray-900">Browser client endpoint</h3>
                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        These values are safe connection metadata for future Laravel Echo clients. They do not change the server-side publisher endpoint.
                    </p>
                </div>

                {reverb && publicHost === '' ? (
                    <div className="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                        Outbound Reverb publishing can work without a public host, but browser real-time connections will remain unavailable until a public WebSocket endpoint is configured.
                    </div>
                ) : null}

                <div className="grid gap-5 sm:grid-cols-2">
                    <Field
                        label="Public WebSocket host"
                        error={errors['configuration.public_host']}
                        hint={
                            reverb
                                ? 'For example realtime.example.com.'
                                : 'Optional when standard Pusher cluster routing is used.'
                        }
                    >
                        <input
                            type="text"
                            value={publicHost}
                            onChange={(event) => onConfigurationChange('public_host', event.target.value)}
                            autoComplete="off"
                            placeholder={reverb ? 'realtime.example.com' : 'Optional custom host'}
                            className={inputClass(Boolean(errors['configuration.public_host']))}
                        />
                    </Field>

                    <Field label="Public WebSocket port" error={errors['configuration.public_port']}>
                        <input
                            type="number"
                            min={1}
                            max={65535}
                            disabled={publicHost === ''}
                            value={inputValue(configuration.public_port)}
                            onChange={(event) => onConfigurationChange('public_port', numberOrNull(event.target.value))}
                            placeholder="Automatic"
                            className={`${inputClass(Boolean(errors['configuration.public_port']))} disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400`}
                        />
                    </Field>

                    <Field label="Public WebSocket scheme" error={errors['configuration.public_scheme']}>
                        <Select
                            disabled={publicHost === ''}
                            value={publicScheme || '__publisher__'}
                            onValueChange={(value) =>
                                onConfigurationChange(
                                    'public_scheme',
                                    value === '__publisher__' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem value="__publisher__">Same as publisher</SelectItem>
                                <SelectItem value="https">HTTPS / WSS</SelectItem>
                                <SelectItem value="http">HTTP / WS</SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                </div>

                <div className="flex items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                    <KeyRound className="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />
                    <p className="text-sm leading-6 text-sky-800">
                        A Real-time profile stores only this Infrastructure Connection ID. Provider credentials are never copied into Broadcast profile configuration.
                    </p>
                </div>
            </div>
        </section>
    )
}

function SecretField({
                         label,
                         required = false,
                         value,
                         configured,
                         visible,
                         error,
                         placeholder,
                         onChange,
                         onToggle,
                     }: {
    label: string
    required?: boolean
    value: string
    configured: boolean
    visible: boolean
    error?: string
    placeholder: string
    onChange: (value: string) => void
    onToggle: () => void
}) {
    return (
        <Field label={label} required={required} error={error}>
            <div className="relative">
                <input
                    type={visible ? 'text' : 'password'}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    autoComplete="new-password"
                    placeholder={configured ? 'Leave blank to keep current value' : placeholder}
                    className={`${inputClass(Boolean(error))} pr-11`}
                />

                <button
                    type="button"
                    onClick={onToggle}
                    aria-label={visible ? `Hide ${label}` : `Show ${label}`}
                    title={visible ? `Hide ${label}` : `Show ${label}`}
                    className="absolute right-2.5 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                >
                    {visible ? (
                        <EyeOff className="h-4 w-4" />
                    ) : (
                        <Eye className="h-4 w-4" />
                    )}
                </button>
            </div>

            {configured ? (
                <div className="mt-2 flex items-start gap-2 rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2.5">
                    <LockKeyhole className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                    <p className="text-xs leading-5 text-emerald-700">
                        A value is already configured. Leave this field blank to keep it, or enter a new value to rotate it.
                    </p>
                </div>
            ) : null}
        </Field>
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
    children: ReactNode
}) {
    return (
        <div>
            <label className="mb-2 block text-sm font-semibold text-gray-700">
                {label}
                {required ? <span className="ml-1 text-red-500">*</span> : null}
            </label>

            {children}

            {hint && !error ? (
                <p className="mt-1.5 text-xs leading-5 text-gray-400">{hint}</p>
            ) : null}

            <InputError message={error} className="mt-1.5" />
        </div>
    )
}

function stringValue(value: InfrastructureConfigurationValue | undefined): string {
    return typeof value === 'string' ? value : ''
}

function inputValue(value: InfrastructureConfigurationValue | undefined): string | number {
    return typeof value === 'string' || typeof value === 'number' ? value : ''
}

function numberOrNull(value: string): number | null {
    if (value === '') {
        return null
    }

    const parsed = Number(value)

    return Number.isFinite(parsed) ? parsed : null
}

function inputClass(error = false): string {
    return `h-11 w-full rounded-xl border bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 ${
        error
            ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
            : 'border-gray-200 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'
    }`
}

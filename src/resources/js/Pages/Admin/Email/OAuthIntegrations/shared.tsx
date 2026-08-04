import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link, useForm } from '@inertiajs/react'
import { Check, Copy, KeyRound, LoaderCircle, Save, ShieldCheck } from 'lucide-react'
import { FormEvent, ReactNode, useState } from 'react'
import { route } from 'ziggy-js'

export type Provider = 'google' | 'microsoft'
export type TenantMode = 'common' | 'organizations' | 'specific'

export type OAuthIntegration = {
    id: number
    name: string
    provider: Provider
    client_id: string
    tenant_mode: TenantMode
    tenant_id: string | null
    is_active: boolean
    has_client_secret: boolean
    connected: boolean
    connected_email: string | null
    scopes: string[]
    token_expires_at: string | null
    connected_at: string | null
    last_refreshed_at: string | null
    last_checked_at: string | null
    health_status: string
    last_error_code: string | null
    last_error_message: string | null
    channels_count: number
    deleted_at: string | null
}

type Props = {
    integration?: OAuthIntegration
    redirect_url: string
    provider_scopes: Record<Provider, string[]>
}

type FormData = {
    name: string
    provider: Provider
    client_id: string
    client_secret: string
    tenant_mode: TenantMode
    tenant_id: string
    is_active: boolean
}

export function OAuthIntegrationForm({
                                         integration,
                                         redirect_url,
                                         provider_scopes,
                                     }: Props) {
    const editing = integration !== undefined
    const [copied, setCopied] = useState(false)

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
    } = useForm<FormData>({
        name: integration?.name ?? '',
        provider: integration?.provider ?? 'google',
        client_id: integration?.client_id ?? '',
        client_secret: '',
        tenant_mode: integration?.tenant_mode ?? 'common',
        tenant_id: integration?.tenant_id ?? '',
        is_active: integration?.is_active ?? true,
    })

    const tenantModes: Array<{
        value: TenantMode
        title: string
        description: string
    }> = [
        {
            value: 'common',
            title: 'Common',
            description: 'Personal and work Microsoft accounts.',
        },
        {
            value: 'organizations',
            title: 'Organizations',
            description: 'Only Microsoft Entra work accounts.',
        },
        {
            value: 'specific',
            title: 'Specific tenant',
            description: 'Restrict authorization to one tenant.',
        },
    ]

    const redirectUrl = redirect_url
    const scopes = provider_scopes[data.provider] ?? []

    function submit(
        event: FormEvent<HTMLFormElement>
    ) {
        event.preventDefault()

        if (editing) {
            put(
                route(
                    'admin.email.oauth-integrations.update',
                    integration.id
                )
            )

            return
        }

        post(
            route(
                'admin.email.oauth-integrations.store'
            )
        )
    }

    function selectProvider(
        provider: Provider
    ) {
        setData(
            'provider',
            provider
        )

        if (provider === 'google') {
            setData(
                'tenant_mode',
                'common'
            )

            setData(
                'tenant_id',
                ''
            )
        }
    }

    function selectTenantMode(
        tenantMode: TenantMode
    ) {
        setData(
            'tenant_mode',
            tenantMode
        )

        if (tenantMode !== 'specific') {
            setData(
                'tenant_id',
                ''
            )
        }
    }

    async function copyRedirect() {
        await navigator.clipboard.writeText(
            redirectUrl
        )

        setCopied(true)

        window.setTimeout(
            () => setCopied(false),
            1500
        )
    }

    function inputClass(
        error?: string
    ) {
        return [
            'mt-2 h-12 w-full rounded-2xl border bg-white px-4',
            'text-sm text-gray-900 shadow-sm outline-none transition',
            'placeholder:text-gray-400',
            'focus:ring-4',
            error
                ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-100'
                : 'border-gray-200 hover:border-gray-300 focus:border-sky-400 focus:ring-sky-100',
        ].join(' ')
    }

    return (
        <AdminLayout title="OAuth Integrations">
            <Head
                title={
                    editing
                        ? 'Edit OAuth Integration'
                        : 'Create OAuth Integration'
                }
            />

            <form
                onSubmit={submit}
                className="space-y-6"
            >
                <section className="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-sm">
                    <header className="border-b border-gray-200 bg-gradient-to-br from-sky-50 via-white to-white px-6 py-6 sm:px-8">
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 shadow-sm">
                                <KeyRound className="h-5 w-5" />
                            </div>

                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-xl font-semibold tracking-tight text-gray-950">
                                        {editing
                                            ? 'Edit OAuth Integration'
                                            : 'Create OAuth Integration'}
                                    </h1>

                                    <span className="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">
                                        OAuth 2.0
                                    </span>
                                </div>

                                <p className="mt-1.5 max-w-2xl text-sm leading-6 text-gray-500">
                                    Configure secure authorization for existing
                                    IMAP and SMTP mailbox channels.
                                </p>
                            </div>
                        </div>
                    </header>

                    <div className="space-y-8 p-6 sm:p-8">
                        <section>
                            <div>
                                <h2 className="text-sm font-semibold text-gray-950">
                                    OAuth provider
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Select the service that owns the mailbox.
                                </p>
                            </div>

                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                {(
                                    [
                                        'google',
                                        'microsoft',
                                    ] as Provider[]
                                ).map(
                                    (provider) => {
                                        const selected =
                                            data.provider
                                            === provider

                                        const google =
                                            provider
                                            === 'google'

                                        return (
                                            <button
                                                key={provider}
                                                type="button"
                                                aria-pressed={
                                                    selected
                                                }
                                                onClick={() =>
                                                    selectProvider(
                                                        provider
                                                    )
                                                }
                                                className={[
                                                    'group relative min-h-32 rounded-[24px] border p-5 text-left',
                                                    'transition duration-200',
                                                    'focus:outline-none focus:ring-4 focus:ring-sky-100',
                                                    selected
                                                        ? 'border-sky-400 bg-sky-50 shadow-sm'
                                                        : 'border-gray-200 bg-white hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md',
                                                ].join(
                                                    ' '
                                                )}
                                            >
                                                <div className="flex items-start justify-between gap-4">
                                                    <div>
                                                        <p className="font-semibold text-gray-950">
                                                            {google
                                                                ? 'Google Workspace / Gmail'
                                                                : 'Microsoft 365 / Outlook'}
                                                        </p>

                                                        <p className="mt-2 text-sm leading-6 text-gray-500">
                                                            {google
                                                                ? 'Secure OAuth2 authorization for Gmail IMAP and SMTP.'
                                                                : 'Secure OAuth2 authorization for Exchange Online IMAP and SMTP.'}
                                                        </p>
                                                    </div>

                                                    <span
                                                        className={[
                                                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full border transition',
                                                            selected
                                                                ? 'border-sky-600 bg-sky-600 text-white'
                                                                : 'border-gray-200 bg-white text-transparent group-hover:border-gray-300',
                                                        ].join(
                                                            ' '
                                                        )}
                                                    >
                                                        <Check className="h-4 w-4" />
                                                    </span>
                                                </div>
                                            </button>
                                        )
                                    }
                                )}
                            </div>

                            {errors.provider ? (
                                <p className="mt-2 text-sm text-rose-600">
                                    {errors.provider}
                                </p>
                            ) : null}
                        </section>

                        <div className="h-px bg-gray-100" />

                        <section className="space-y-5">
                            <div>
                                <h2 className="text-sm font-semibold text-gray-950">
                                    Application credentials
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Enter the credentials issued by the selected
                                    OAuth provider.
                                </p>
                            </div>

                            <div className="grid gap-5 md:grid-cols-2">
                                <Field
                                    label="Integration Name"
                                    error={errors.name}
                                >
                                    <input
                                        value={data.name}
                                        onChange={(event) =>
                                            setData(
                                                'name',
                                                event.target.value
                                            )
                                        }
                                        className={inputClass(
                                            errors.name
                                        )}
                                        placeholder="Support mailbox OAuth"
                                        autoComplete="off"
                                    />
                                </Field>

                                <Field
                                    label="Client ID"
                                    error={errors.client_id}
                                >
                                    <input
                                        value={data.client_id}
                                        onChange={(event) =>
                                            setData(
                                                'client_id',
                                                event.target.value
                                            )
                                        }
                                        className={inputClass(
                                            errors.client_id
                                        )}
                                        placeholder="OAuth application client ID"
                                        autoComplete="off"
                                        spellCheck={false}
                                    />
                                </Field>
                            </div>

                            <Field
                                label={
                                    editing
                                        ? 'Client Secret'
                                        : 'Client Secret'
                                }
                                error={errors.client_secret}
                            >
                                <input
                                    type="password"
                                    value={data.client_secret}
                                    onChange={(event) =>
                                        setData(
                                            'client_secret',
                                            event.target.value
                                        )
                                    }
                                    className={inputClass(
                                        errors.client_secret
                                    )}
                                    placeholder={
                                        editing
                                            ? 'Leave blank to keep the current secret'
                                            : 'Enter the OAuth client secret'
                                    }
                                    autoComplete="new-password"
                                />

                                <div className="mt-2 flex items-start gap-2 text-xs leading-5 text-gray-500">
                                    <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />

                                    <span>
                                        The client secret is encrypted before
                                        storage and is never displayed again.
                                    </span>
                                </div>
                            </Field>
                        </section>

                        {data.provider === 'microsoft' ? (
                            <>
                                <div className="h-px bg-gray-100" />

                                <section className="space-y-5">
                                    <div>
                                        <h2 className="text-sm font-semibold text-gray-950">
                                            Microsoft tenant
                                        </h2>

                                        <p className="mt-1 text-sm text-gray-500">
                                            Choose which Microsoft accounts may
                                            authorize this integration.
                                        </p>
                                    </div>

                                    <Field
                                        label="Tenant Mode"
                                        error={errors.tenant_mode}
                                    >
                                        <div className="mt-3 grid gap-3 lg:grid-cols-3">
                                            {tenantModes.map(
                                                (tenantMode) => {
                                                    const selected =
                                                        data.tenant_mode
                                                        === tenantMode.value

                                                    return (
                                                        <button
                                                            key={
                                                                tenantMode.value
                                                            }
                                                            type="button"
                                                            aria-pressed={
                                                                selected
                                                            }
                                                            onClick={() =>
                                                                selectTenantMode(
                                                                    tenantMode.value
                                                                )
                                                            }
                                                            className={[
                                                                'relative rounded-[20px] border p-4 text-left transition',
                                                                'focus:outline-none focus:ring-4 focus:ring-sky-100',
                                                                selected
                                                                    ? 'border-sky-400 bg-sky-50'
                                                                    : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50',
                                                            ].join(
                                                                ' '
                                                            )}
                                                        >
                                                            <div className="flex items-start justify-between gap-3">
                                                                <div>
                                                                    <p className="text-sm font-semibold text-gray-950">
                                                                        {
                                                                            tenantMode.title
                                                                        }
                                                                    </p>

                                                                    <p className="mt-1 text-xs leading-5 text-gray-500">
                                                                        {
                                                                            tenantMode.description
                                                                        }
                                                                    </p>
                                                                </div>

                                                                <span
                                                                    className={[
                                                                        'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border',
                                                                        selected
                                                                            ? 'border-sky-600 bg-sky-600 text-white'
                                                                            : 'border-gray-300 text-transparent',
                                                                    ].join(
                                                                        ' '
                                                                    )}
                                                                >
                                                                    <Check className="h-3 w-3" />
                                                                </span>
                                                            </div>
                                                        </button>
                                                    )
                                                }
                                            )}
                                        </div>
                                    </Field>

                                    {data.tenant_mode
                                    === 'specific' ? (
                                        <Field
                                            label="Tenant ID or domain"
                                            error={errors.tenant_id}
                                        >
                                            <input
                                                value={
                                                    data.tenant_id
                                                }
                                                onChange={(
                                                    event
                                                ) =>
                                                    setData(
                                                        'tenant_id',
                                                        event
                                                            .target
                                                            .value
                                                    )
                                                }
                                                className={inputClass(
                                                    errors.tenant_id
                                                )}
                                                placeholder="00000000-0000-0000-0000-000000000000"
                                                autoComplete="off"
                                                spellCheck={
                                                    false
                                                }
                                            />

                                            <p className="mt-2 text-xs leading-5 text-gray-500">
                                                Enter the Microsoft Entra
                                                tenant ID or verified tenant
                                                domain.
                                            </p>
                                        </Field>
                                    ) : null}
                                </section>
                            </>
                        ) : null}

                        <div className="h-px bg-gray-100" />

                        <section className="space-y-5">
                            <div>
                                <h2 className="text-sm font-semibold text-gray-950">
                                    Provider configuration
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Add this callback URL to the OAuth
                                    application configuration.
                                </p>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-gray-700">
                                    Redirect URL
                                </label>

                                <div className="mt-2 flex flex-col gap-2 sm:flex-row">
                                    <input
                                        readOnly
                                        value={redirectUrl}
                                        className="h-12 min-w-0 flex-1 rounded-2xl border border-gray-200 bg-gray-50 px-4 font-mono text-xs text-gray-700 outline-none"
                                    />

                                    <button
                                        type="button"
                                        onClick={copyRedirect}
                                        className={[
                                            'inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-2xl border px-5',
                                            'text-sm font-medium transition',
                                            copied
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50',
                                        ].join(
                                            ' '
                                        )}
                                    >
                                        {copied ? (
                                            <Check className="h-4 w-4" />
                                        ) : (
                                            <Copy className="h-4 w-4" />
                                        )}

                                        {copied
                                            ? 'Copied'
                                            : 'Copy URL'}
                                    </button>
                                </div>
                            </div>

                            <div className="rounded-[24px] border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50/50 p-5">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                        <ShieldCheck className="h-5 w-5" />
                                    </div>

                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-amber-950">
                                            Security and provider requirements
                                        </p>

                                        <p className="mt-1 text-sm leading-6 text-amber-900/80">
                                            Tokens and client secrets stay
                                            encrypted on the server. Production
                                            applications may require provider
                                            verification before they can access
                                            user mailboxes.
                                        </p>

                                        <div className="mt-4 flex flex-wrap gap-2">
                                            {scopes.map(
                                                (scope) => (
                                                    <span
                                                        key={
                                                            scope
                                                        }
                                                        className="max-w-full break-all rounded-xl border border-amber-200 bg-white/70 px-3 py-2 font-mono text-[11px] leading-4 text-amber-900"
                                                    >
                                                        {
                                                            scope
                                                        }
                                                    </span>
                                                )
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div className="h-px bg-gray-100" />

                        <section>
                            <label className="flex cursor-pointer items-center justify-between gap-5 rounded-[22px] border border-gray-200 bg-gray-50/70 p-5 transition hover:border-gray-300 hover:bg-gray-50">
                                <span>
                                    <span className="block text-sm font-semibold text-gray-950">
                                        Enable integration
                                    </span>

                                    <span className="mt-1 block text-xs leading-5 text-gray-500">
                                        Allows account authorization and use by
                                        linked IMAP and SMTP channels.
                                    </span>
                                </span>

                                <span className="relative shrink-0">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(event) =>
                                            setData(
                                                'is_active',
                                                event.target.checked
                                            )
                                        }
                                        className="peer sr-only"
                                    />

                                    <span className="block h-7 w-12 rounded-full bg-gray-300 transition peer-checked:bg-sky-600 peer-focus:ring-4 peer-focus:ring-sky-100" />

                                    <span className="absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow-sm transition peer-checked:translate-x-5" />
                                </span>
                            </label>
                        </section>

                        {editing ? (
                            <section
                                className={[
                                    'rounded-[22px] border p-5',
                                    integration.connected
                                        ? 'border-emerald-200 bg-emerald-50'
                                        : 'border-gray-200 bg-gray-50',
                                ].join(' ')}
                            >
                                <div className="flex items-start gap-3">
                                    <span
                                        className={[
                                            'mt-1 h-2.5 w-2.5 shrink-0 rounded-full',
                                            integration.connected
                                                ? 'bg-emerald-500'
                                                : 'bg-gray-400',
                                        ].join(' ')}
                                    />

                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-gray-950">
                                            {integration.connected
                                                ? 'OAuth account connected'
                                                : 'OAuth account is not connected'}
                                        </p>

                                        <p className="mt-1 break-all text-sm text-gray-600">
                                            {integration.connected_email
                                                ?? 'No provider-verified account email is available.'}
                                        </p>

                                        {integration.last_error_message ? (
                                            <p className="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm leading-5 text-rose-700">
                                                {
                                                    integration.last_error_message
                                                }
                                            </p>
                                        ) : null}
                                    </div>
                                </div>
                            </section>
                        ) : null}
                    </div>
                </section>

                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link
                        href={route(
                            'admin.email.oauth-integrations.index'
                        )}
                        className="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-6 text-sm font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex h-12 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-6 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {processing ? (
                            <LoaderCircle className="h-4 w-4 animate-spin" />
                        ) : (
                            <Save className="h-4 w-4" />
                        )}

                        {editing
                            ? 'Save Changes'
                            : 'Create Integration'}
                    </button>
                </div>
            </form>
        </AdminLayout>
    )
}
function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return <label className="block text-sm font-medium text-gray-700">{label}{children}{error ? <span className="mt-2 block text-sm text-rose-600">{error}</span> : null}</label>
}

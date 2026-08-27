import InputError from '@/Components/InputError'
import { Eye, EyeOff, KeyRound, LockKeyhole, Search } from 'lucide-react'
import { useState } from 'react'
import type { ReactNode } from 'react'

import type { InfrastructureConfigurationValue } from './ManagedPusherProtocolConfiguration'

type Props = {
    type: 'meilisearch' | 'algolia'
    configuration: Record<string, InfrastructureConfigurationValue>
    apiKey: string
    apiKeyConfigured: boolean
    errors: Record<string, string | undefined>
    onConfigurationChange: (
        key: string,
        value: InfrastructureConfigurationValue,
    ) => void
    onApiKeyChange: (value: string) => void
}

export default function ManagedSearchProviderConfiguration({
                                                               type,
                                                               configuration,
                                                               apiKey,
                                                               apiKeyConfigured,
                                                               errors,
                                                               onConfigurationChange,
                                                               onApiKeyChange,
                                                           }: Props) {
    const [showApiKey, setShowApiKey] = useState(false)
    const meilisearch = type === 'meilisearch'

    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                        <Search className="h-5 w-5 text-violet-600" />
                    </div>

                    <div>
                        <h2 className="font-semibold text-gray-900">
                            {meilisearch ? 'Meilisearch connection' : 'Algolia connection'}
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-gray-500">
                            {meilisearch
                                ? 'Configure the Meilisearch server endpoint and server-side API credential.'
                                : 'Configure the Algolia application and server-side API credential used by SimpleDesk.'}
                        </p>
                    </div>
                </div>
            </div>

            <div className="space-y-6 p-5 sm:p-6">
                {meilisearch ? (
                    <Field
                        label="Host"
                        required
                        error={errors['configuration.host']}
                        hint="Full HTTP or HTTPS URL of the Meilisearch server."
                    >
                        <input
                            type="url"
                            value={stringValue(configuration.host)}
                            onChange={(event) =>
                                onConfigurationChange('host', event.target.value)
                            }
                            placeholder="http://meilisearch:7700"
                            autoComplete="off"
                            className={inputClass(Boolean(errors['configuration.host']))}
                        />
                    </Field>
                ) : (
                    <Field
                        label="Application ID"
                        required
                        error={errors['configuration.application_id']}
                        hint="Algolia application identifier. This value is not secret."
                    >
                        <input
                            type="text"
                            value={stringValue(configuration.application_id)}
                            onChange={(event) =>
                                onConfigurationChange(
                                    'application_id',
                                    event.target.value,
                                )
                            }
                            placeholder="Your Algolia Application ID"
                            autoComplete="off"
                            className={inputClass(
                                Boolean(errors['configuration.application_id']),
                            )}
                        />
                    </Field>
                )}

                <Field
                    label="API key"
                    required={!apiKeyConfigured}
                    error={errors['credentials.api_key']}
                    hint={
                        apiKeyConfigured
                            ? undefined
                            : meilisearch
                                ? 'Use a server-side Meilisearch key with the permissions required by SimpleDesk.'
                                : 'Use a server-side Algolia API key. Do not use a browser-only search key here.'
                    }
                >
                    <div className="relative">
                        <input
                            type={showApiKey ? 'text' : 'password'}
                            value={apiKey}
                            onChange={(event) => onApiKeyChange(event.target.value)}
                            placeholder={
                                apiKeyConfigured
                                    ? 'Leave blank to keep current API key'
                                    : 'Enter API key'
                            }
                            autoComplete="new-password"
                            className={`${inputClass(
                                Boolean(errors['credentials.api_key']),
                            )} pr-11`}
                        />

                        <button
                            type="button"
                            onClick={() => setShowApiKey((current) => !current)}
                            aria-label={showApiKey ? 'Hide API key' : 'Show API key'}
                            title={showApiKey ? 'Hide API key' : 'Show API key'}
                            className="absolute right-2.5 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        >
                            {showApiKey ? (
                                <EyeOff className="h-4 w-4" />
                            ) : (
                                <Eye className="h-4 w-4" />
                            )}
                        </button>
                    </div>

                    {apiKeyConfigured ? (
                        <div className="mt-2 flex items-start gap-2 rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2.5">
                            <LockKeyhole className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />

                            <p className="text-xs leading-5 text-emerald-700">
                                An API key is already stored encrypted. Leave this
                                field empty to keep it, or enter a new value to
                                rotate the credential.
                            </p>
                        </div>
                    ) : null}
                </Field>

                <div className="flex items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                    <KeyRound className="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />

                    <div>
                        <p className="text-sm font-semibold text-sky-900">
                            Server-side credential
                        </p>

                        <p className="mt-1 text-sm leading-6 text-sky-800">
                            The API key is encrypted by SimpleDesk and is never
                            returned to the browser after it has been saved.
                        </p>
                    </div>
                </div>
            </div>
        </section>
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

                {required ? (
                    <span className="ml-1 text-red-500">*</span>
                ) : null}
            </label>

            {children}

            {hint && !error ? (
                <p className="mt-1.5 text-xs leading-5 text-gray-400">
                    {hint}
                </p>
            ) : null}

            <InputError message={error} className="mt-1.5" />
        </div>
    )
}

function stringValue(
    value: InfrastructureConfigurationValue | undefined,
): string {
    return typeof value === 'string' ? value : ''
}

function inputClass(error = false): string {
    return `h-11 w-full rounded-xl border bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 ${
        error
            ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
            : 'border-gray-200 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'
    }`
}

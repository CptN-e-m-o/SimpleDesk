import InputError from '@/Components/InputError'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'
import {
    Eye,
    EyeOff,
    KeyRound,
    LockKeyhole,
    Plus,
    Server,
    Trash2,
} from 'lucide-react'
import { useState } from 'react'
import type { ReactNode } from 'react'

import type {
    InfrastructureConfigurationValue,
} from './ManagedPusherProtocolConfiguration'

type TypesenseNode = {
    host: string
    port: number | string
    protocol: string
    path: string
}

type Props = {
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

export default function ManagedTypesenseConfiguration({
                                                          configuration,
                                                          apiKey,
                                                          apiKeyConfigured,
                                                          errors,
                                                          onConfigurationChange,
                                                          onApiKeyChange,
                                                      }: Props) {
    const [showApiKey, setShowApiKey] = useState(false)
    const nodes = typesenseNodes(configuration.nodes)

    const updateNode = (
        index: number,
        key: keyof TypesenseNode,
        value: string | number,
    ) => {
        onConfigurationChange(
            'nodes',
            nodes.map((node, nodeIndex) =>
                nodeIndex === index
                    ? {
                        ...node,
                        [key]: value,
                    }
                    : node,
            ),
        )
    }

    const addNode = () => {
        onConfigurationChange('nodes', [
            ...nodes,
            {
                host: '',
                port: 8108,
                protocol: 'http',
                path: '',
            },
        ])
    }

    const removeNode = (index: number) => {
        if (nodes.length <= 1) {
            return
        }

        onConfigurationChange(
            'nodes',
            nodes.filter((_, nodeIndex) => nodeIndex !== index),
        )
    }

    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                <div className="flex items-start gap-3">
                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100">
                        <Server className="h-5 w-5 text-indigo-600" />
                    </div>

                    <div>
                        <h2 className="font-semibold text-gray-900">
                            Typesense cluster
                        </h2>

                        <p className="mt-1 text-sm leading-6 text-gray-500">
                            Configure one or more Typesense nodes, runtime
                            connection behavior, and the server-side API key.
                        </p>
                    </div>
                </div>
            </div>

            <div className="space-y-7 p-5 sm:p-6">
                <div>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 className="text-sm font-semibold text-gray-900">
                                Nodes
                            </h3>

                            <p className="mt-1 text-sm leading-6 text-gray-500">
                                Configure every Typesense node that may serve this
                                SimpleDesk connection.
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={addNode}
                            disabled={nodes.length >= 20}
                            className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <Plus className="h-4 w-4" />
                            Add node
                        </button>
                    </div>

                    <InputError
                        message={errors['configuration.nodes']}
                        className="mt-2"
                    />

                    <div className="mt-4 space-y-4">
                        {nodes.map((node, index) => (
                            <div
                                key={index}
                                className="rounded-2xl border border-gray-200 bg-gray-50/50 p-4"
                            >
                                <div className="mb-4 flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900">
                                            Node {index + 1}
                                        </p>

                                        <p className="mt-0.5 text-xs text-gray-400">
                                            Typesense server endpoint
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={() => removeNode(index)}
                                        disabled={nodes.length <= 1}
                                        aria-label={`Remove node ${index + 1}`}
                                        className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>

                                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                                    <Field
                                        label="Host"
                                        required
                                        error={
                                            errors[
                                                `configuration.nodes.${index}.host`
                                                ]
                                        }
                                    >
                                        <input
                                            type="text"
                                            value={node.host}
                                            onChange={(event) =>
                                                updateNode(
                                                    index,
                                                    'host',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="typesense"
                                            autoComplete="off"
                                            className={inputClass(
                                                Boolean(
                                                    errors[
                                                        `configuration.nodes.${index}.host`
                                                        ],
                                                ),
                                            )}
                                        />
                                    </Field>

                                    <Field
                                        label="Port"
                                        required
                                        error={
                                            errors[
                                                `configuration.nodes.${index}.port`
                                                ]
                                        }
                                    >
                                        <input
                                            type="number"
                                            min={1}
                                            max={65535}
                                            value={node.port}
                                            onChange={(event) =>
                                                updateNode(
                                                    index,
                                                    'port',
                                                    numberValue(
                                                        event.target.value,
                                                    ),
                                                )
                                            }
                                            className={inputClass(
                                                Boolean(
                                                    errors[
                                                        `configuration.nodes.${index}.port`
                                                        ],
                                                ),
                                            )}
                                        />
                                    </Field>

                                    <Field
                                        label="Protocol"
                                        required
                                        error={
                                            errors[
                                                `configuration.nodes.${index}.protocol`
                                                ]
                                        }
                                    >
                                        <Select
                                            value={node.protocol}
                                            onValueChange={(value) =>
                                                updateNode(
                                                    index,
                                                    'protocol',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-11 w-full rounded-xl border-gray-200 bg-white">
                                                <SelectValue />
                                            </SelectTrigger>

                                            <SelectContent>
                                                <SelectItem value="http">
                                                    HTTP
                                                </SelectItem>

                                                <SelectItem value="https">
                                                    HTTPS
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </Field>

                                    <Field
                                        label="Path"
                                        error={
                                            errors[
                                                `configuration.nodes.${index}.path`
                                                ]
                                        }
                                        hint="Optional path prefix."
                                    >
                                        <input
                                            type="text"
                                            value={node.path}
                                            onChange={(event) =>
                                                updateNode(
                                                    index,
                                                    'path',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Optional"
                                            autoComplete="off"
                                            className={inputClass(
                                                Boolean(
                                                    errors[
                                                        `configuration.nodes.${index}.path`
                                                        ],
                                                ),
                                            )}
                                        />
                                    </Field>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="border-t border-gray-100 pt-6">
                    <h3 className="text-sm font-semibold text-gray-900">
                        Runtime connection behavior
                    </h3>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        These values control the normal Typesense client used by
                        the Search runtime. Administrative health probes use
                        separate bounded settings.
                    </p>

                    <div className="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                        <Field
                            label="Connection timeout"
                            required
                            suffix="sec"
                            error={
                                errors[
                                    'configuration.connection_timeout_seconds'
                                    ]
                            }
                        >
                            <input
                                type="number"
                                min={0.1}
                                max={60}
                                step={0.1}
                                value={inputValue(
                                    configuration.connection_timeout_seconds,
                                )}
                                onChange={(event) =>
                                    onConfigurationChange(
                                        'connection_timeout_seconds',
                                        numberValue(event.target.value),
                                    )
                                }
                                className={inputClass(
                                    Boolean(
                                        errors[
                                            'configuration.connection_timeout_seconds'
                                            ],
                                    ),
                                    true,
                                )}
                            />
                        </Field>

                        <Field
                            label="Healthcheck interval"
                            required
                            suffix="sec"
                            error={
                                errors[
                                    'configuration.healthcheck_interval_seconds'
                                    ]
                            }
                        >
                            <input
                                type="number"
                                min={1}
                                max={3600}
                                step={1}
                                value={inputValue(
                                    configuration.healthcheck_interval_seconds,
                                )}
                                onChange={(event) =>
                                    onConfigurationChange(
                                        'healthcheck_interval_seconds',
                                        numberValue(event.target.value),
                                    )
                                }
                                className={inputClass(
                                    Boolean(
                                        errors[
                                            'configuration.healthcheck_interval_seconds'
                                            ],
                                    ),
                                    true,
                                )}
                            />
                        </Field>

                        <Field
                            label="Retries"
                            required
                            error={errors['configuration.num_retries']}
                        >
                            <input
                                type="number"
                                min={0}
                                max={10}
                                step={1}
                                value={inputValue(configuration.num_retries)}
                                onChange={(event) =>
                                    onConfigurationChange(
                                        'num_retries',
                                        numberValue(event.target.value),
                                    )
                                }
                                className={inputClass(
                                    Boolean(
                                        errors['configuration.num_retries'],
                                    ),
                                )}
                            />
                        </Field>

                        <Field
                            label="Retry interval"
                            required
                            suffix="sec"
                            error={
                                errors[
                                    'configuration.retry_interval_seconds'
                                    ]
                            }
                        >
                            <input
                                type="number"
                                min={0}
                                max={60}
                                step={0.1}
                                value={inputValue(
                                    configuration.retry_interval_seconds,
                                )}
                                onChange={(event) =>
                                    onConfigurationChange(
                                        'retry_interval_seconds',
                                        numberValue(event.target.value),
                                    )
                                }
                                className={inputClass(
                                    Boolean(
                                        errors[
                                            'configuration.retry_interval_seconds'
                                            ],
                                    ),
                                    true,
                                )}
                            />
                        </Field>
                    </div>
                </div>

                <div className="border-t border-gray-100 pt-6">
                    <Field
                        label="API key"
                        required={!apiKeyConfigured}
                        error={errors['credentials.api_key']}
                    >
                        <div className="relative">
                            <input
                                type={showApiKey ? 'text' : 'password'}
                                value={apiKey}
                                onChange={(event) =>
                                    onApiKeyChange(event.target.value)
                                }
                                placeholder={
                                    apiKeyConfigured
                                        ? 'Leave blank to keep current API key'
                                        : 'Enter Typesense API key'
                                }
                                autoComplete="new-password"
                                className={`${inputClass(
                                    Boolean(errors['credentials.api_key']),
                                )} pr-11`}
                            />

                            <button
                                type="button"
                                onClick={() =>
                                    setShowApiKey((current) => !current)
                                }
                                aria-label={
                                    showApiKey
                                        ? 'Hide API key'
                                        : 'Show API key'
                                }
                                title={
                                    showApiKey
                                        ? 'Hide API key'
                                        : 'Show API key'
                                }
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
                                    An API key is already stored encrypted. Leave
                                    this field empty to preserve it, or enter a
                                    new value to rotate the credential.
                                </p>
                            </div>
                        ) : null}
                    </Field>
                </div>

                <div className="flex items-start gap-3 rounded-2xl border border-sky-100 bg-sky-50/60 p-4">
                    <KeyRound className="mt-0.5 h-5 w-5 shrink-0 text-sky-600" />

                    <p className="text-sm leading-6 text-sky-800">
                        Typesense credentials are encrypted in Infrastructure
                        Connections. Health checks use authenticated read-only
                        provider operations and never create or modify
                        collections.
                    </p>
                </div>
            </div>
        </section>
    )
}

function typesenseNodes(
    value: InfrastructureConfigurationValue | undefined,
): TypesenseNode[] {
    if (!Array.isArray(value)) {
        return [
            {
                host: '',
                port: 8108,
                protocol: 'http',
                path: '',
            },
        ]
    }

    const nodes = value.map((node) => ({
        host: stringValue(node.host),
        port: scalarValue(node.port, 8108),
        protocol:
            stringValue(node.protocol) === 'https'
                ? 'https'
                : 'http',
        path: stringValue(node.path),
    }))

    return nodes.length > 0
        ? nodes
        : [
            {
                host: '',
                port: 8108,
                protocol: 'http',
                path: '',
            },
        ]
}


function stringValue(
    value: InfrastructureConfigurationValue | undefined,
): string {
    return typeof value === 'string' ? value : ''
}

function scalarValue(
    value: InfrastructureConfigurationValue | undefined,
    fallback: number,
): string | number {
    return typeof value === 'string' || typeof value === 'number'
        ? value
        : fallback
}

function inputValue(
    value: InfrastructureConfigurationValue | undefined,
): string | number {
    return typeof value === 'string' || typeof value === 'number'
        ? value
        : ''
}

function numberValue(value: string): number | string {
    if (value === '') {
        return ''
    }

    const number = Number(value)

    return Number.isNaN(number) ? value : number
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

                {required ? (
                    <span className="ml-1 text-red-500">*</span>
                ) : null}
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
                <p className="mt-1.5 text-xs leading-5 text-gray-400">
                    {hint}
                </p>
            ) : null}

            <InputError message={error} className="mt-1.5" />
        </div>
    )
}

function inputClass(
    error = false,
    withSuffix = false,
): string {
    return `h-11 w-full rounded-xl border bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 ${
        withSuffix ? 'pr-16' : ''
    } ${
        error
            ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
            : 'border-gray-200 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'
    }`
}

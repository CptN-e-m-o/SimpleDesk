import type {
    FormEvent,
    ReactNode,
} from 'react'

import {
    Link,
    useForm,
} from '@inertiajs/react'

import {
    AlertTriangle,
    Check,
    Database,
    Gauge,
    Info,
    LockKeyhole,
    Save,
    Settings2,
    Workflow,
} from 'lucide-react'

import type {
    LucideIcon,
} from 'lucide-react'

import { route } from 'ziggy-js'

import InputError from '@/Components/InputError'

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'

import { usePermissions } from '@/hooks/usePermissions'

import {
    QueueDriverBadge,
} from './components/QueueBadges'

import type {
    QueueConfiguration,
    QueueConfigurationValues,
    QueueDriver,
    QueueDriverDefinition,
    RedisConnection,
} from './queueTypes'

type Props = {
    definitions:
        QueueDriverDefinition[]

    redisConnections:
        RedisConnection[]

    minimumRetryAfter:
        number

    configuration?:
        QueueConfiguration
}

type FormData = {
    name: string

    driver:
        QueueDriver

    is_enabled:
        boolean

    configuration:
        QueueConfigurationValues
}

export default function QueueConfigurationForm({
                                                   definitions,
                                                   redisConnections,
                                                   minimumRetryAfter,
                                                   configuration,
                                               }: Props) {
    const { can } =
        usePermissions()

    const editing =
        Boolean(
            configuration,
        )

    const initialDriver:
        QueueDriver =
        configuration?.driver
        ?? definitions[0]?.type
        ?? 'database'

    const form =
        useForm<FormData>({
            name:
                configuration?.name
                ?? '',

            driver:
            initialDriver,

            is_enabled:
                configuration
                    ?.is_enabled
                ?? true,

            configuration:
                initialValues(
                    initialDriver,
                    definitions,
                    redisConnections,
                    minimumRetryAfter,
                    configuration,
                ),
        })

    const definition =
        definitions.find(
            (
                item,
            ) =>
                item.type
                === form.data.driver,
        )

    const errors =
        form.errors as Record<
            string,
            string | undefined
        >

    const databaseConnections =
        form.data.driver
        === 'database'
            ? definition
                ?.options
                .database_connections
            ?? []
            : []

    const currentDatabaseConnection =
        String(
            form.data
                .configuration
                .database_connection
            ?? '',
        )

    const currentDatabaseUnavailable =
        editing
        && currentDatabaseConnection
        !== ''
        && !databaseConnections.includes(
            currentDatabaseConnection,
        )

    const currentRedisId =
        positiveInteger(
            form.data
                .configuration
                .infrastructure_connection_id,
        )

    const currentRedis =
        currentRedisId
            ? redisConnections.find(
                (
                    item,
                ) =>
                    item.id
                    === currentRedisId,
            )
            : undefined

    const currentRedisUnavailable =
        currentRedisId !== null
        && (
            !currentRedis
            || !currentRedis.is_enabled
            || Boolean(
                currentRedis.deleted_at,
            )
        )

    const eligibleRedis =
        redisConnections.filter(
            (
                item,
            ) =>
                item.is_enabled
                && !item.deleted_at,
        )

    const canViewInfrastructure =
        can(
            'admin.settings.infrastructure_connections.view',
        )

    const submit = (
        event: FormEvent,
    ) => {
        event.preventDefault()

        if (
            editing
            && configuration
        ) {
            form.put(
                route(
                    'admin.system.queues.update',
                    configuration.id,
                ),

                {
                    preserveScroll:
                        true,
                },
            )

            return
        }

        form.post(
            route(
                'admin.system.queues.store',
            ),
        )
    }

    const selectDriver = (
        driver:
        QueueDriver,
    ) => {
        if (editing) {
            return
        }

        form.setData(
            (
                data,
            ) => ({
                ...data,

                driver,

                configuration:
                    defaultsFor(
                        driver,
                        definitions,
                        redisConnections,
                        minimumRetryAfter,
                    ),
            }),
        )

        form.clearErrors()
    }

    const setConfiguration =
        <
            K extends keyof QueueConfigurationValues,
        >(
            key: K,
            value:
            QueueConfigurationValues[K],
        ) => {
            form.setData(
                'configuration',

                {
                    ...form.data
                        .configuration,

                    [key]:
                    value,
                },
            )
        }

    return (
        <form
            onSubmit={
                submit
            }
            className="space-y-6"
        >
            <Section
                icon={
                    Settings2
                }
                title="General"
                description="Name this configuration and control whether it is available for managed Queue operations."
            >
                <Field
                    label="Name"
                    required
                    error={
                        errors.name
                    }
                >
                    <input
                        value={
                            form.data.name
                        }
                        onChange={
                            (
                                event,
                            ) =>
                                form.setData(
                                    'name',
                                    event
                                        .target
                                        .value,
                                )
                        }
                        className={
                            inputClass(
                                Boolean(
                                    errors.name,
                                ),
                            )
                        }
                        placeholder="Production queue"
                        autoComplete="off"
                    />
                </Field>

                <Field
                    label="Driver"
                    required
                    error={
                        errors.driver
                    }
                >
                    {editing ? (
                        <div className="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                            <div className="flex items-center gap-3">
                                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100">
                                    <LockKeyhole className="h-5 w-5 text-gray-500" />
                                </span>

                                <div>
                                    <QueueDriverBadge
                                        driver={
                                            form.data.driver
                                        }
                                    />

                                    <p className="mt-2 text-sm leading-6 text-gray-500">
                                        The Queue driver cannot be
                                        changed after this
                                        configuration has been
                                        created.
                                    </p>
                                </div>
                            </div>
                        </div>
                    ) : definitions.length > 0 ? (
                        <div className="grid gap-3 lg:grid-cols-3">
                            {definitions.map(
                                (
                                    item,
                                ) => {
                                    const selected =
                                        item.type
                                        === form
                                            .data
                                            .driver

                                    const Icon =
                                        driverIcon(
                                            item.type,
                                        )

                                    return (
                                        <button
                                            key={
                                                item.type
                                            }
                                            type="button"
                                            onClick={
                                                () =>
                                                    selectDriver(
                                                        item.type,
                                                    )
                                            }
                                            aria-pressed={
                                                selected
                                            }
                                            className={
                                                `relative rounded-2xl border p-4 text-left transition ${
                                                    selected
                                                        ? 'border-sky-300 bg-sky-50 shadow-sm ring-4 ring-sky-50'
                                                        : 'border-gray-200 bg-white hover:border-sky-200 hover:bg-sky-50/30'
                                                }`
                                            }
                                        >
                                            <div className="flex items-start gap-3 pr-6">
                                                <span
                                                    className={
                                                        `flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                                                            selected
                                                                ? 'bg-sky-100 text-sky-700'
                                                                : 'bg-gray-100 text-gray-500'
                                                        }`
                                                    }
                                                >
                                                    <Icon className="h-5 w-5" />
                                                </span>

                                                <div className="min-w-0">
                                                    <span className="block font-semibold text-gray-900">
                                                        {
                                                            item.label
                                                        }
                                                    </span>

                                                    {item
                                                        .requires_infrastructure ? (
                                                        <span className="mt-1 block text-xs font-medium text-sky-700">
                                                            Uses an
                                                            Infrastructure
                                                            Connection
                                                        </span>
                                                    ) : null}
                                                </div>
                                            </div>

                                            <p className="mt-3 text-sm leading-6 text-gray-500">
                                                {
                                                    item.description
                                                }
                                            </p>

                                            {!item
                                                .recommended_for_production ? (
                                                <span className="mt-3 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                    Not recommended
                                                    for production
                                                </span>
                                            ) : null}

                                            {selected ? (
                                                <span className="absolute right-4 top-4 flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-white">
                                                    <Check className="h-3.5 w-3.5" />
                                                </span>
                                            ) : null}
                                        </button>
                                    )
                                },
                            )}
                        </div>
                    ) : (
                        <InlineWarning
                            title="No Queue drivers are available"
                            description="SimpleDesk did not expose any registered Queue driver definitions."
                        />
                    )}
                </Field>

                <Toggle
                    enabled={
                        form.data
                            .is_enabled
                    }
                    onChange={
                        (
                            enabled,
                        ) =>
                            form.setData(
                                'is_enabled',
                                enabled,
                            )
                    }
                    label="Enabled"
                    description="Enabled configurations can be used by managed Queue operations. Enabling this profile does not activate it."
                    error={
                        errors.is_enabled
                    }
                />
            </Section>

            {form.data.driver
            === 'database' ? (
                <Section
                    icon={
                        Database
                    }
                    title="Database configuration"
                    description="Store queued jobs through one of the application's configured database connections."
                >
                    {currentDatabaseUnavailable ? (
                        <InlineWarning
                            title={`Previously selected database connection: ${currentDatabaseConnection}`}
                            description="This connection is no longer exposed by the current application configuration. Select an available connection before saving changes."
                        />
                    ) : null}

                    {databaseConnections
                        .length === 0
                    && !currentDatabaseUnavailable ? (
                        <InlineWarning
                            title="No database connections available"
                            description="No database connection is currently exposed for Queue configuration."
                        />
                    ) : null}

                    <div className="grid gap-5 xl:grid-cols-2">
                        <Field
                            label="Database connection"
                            required
                            error={
                                errors[
                                    'configuration.database_connection'
                                    ]
                            }
                        >
                            <Select
                                value={
                                    currentDatabaseConnection
                                }
                                onValueChange={
                                    (
                                        value,
                                    ) =>
                                        setConfiguration(
                                            'database_connection',
                                            value,
                                        )
                                }
                                disabled={
                                    databaseConnections
                                        .length
                                    === 0
                                }
                            >
                                <SelectTrigger
                                    className={
                                        selectTriggerClass(
                                            Boolean(
                                                errors[
                                                    'configuration.database_connection'
                                                    ],
                                            ),
                                        )
                                    }
                                >
                                    <SelectValue placeholder="Choose a database connection" />
                                </SelectTrigger>

                                <SelectContent>
                                    {currentDatabaseUnavailable ? (
                                        <SelectItem
                                            value={
                                                currentDatabaseConnection
                                            }
                                            disabled
                                        >
                                            {
                                                currentDatabaseConnection
                                            }{' '}
                                            · Unavailable
                                        </SelectItem>
                                    ) : null}

                                    {databaseConnections.map(
                                        (
                                            name,
                                        ) => (
                                            <SelectItem
                                                value={
                                                    name
                                                }
                                                key={
                                                    name
                                                }
                                            >
                                                {
                                                    name
                                                }
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                        </Field>

                        <RetryField
                            value={
                                form.data
                                    .configuration
                                    .retry_after
                                ?? ''
                            }
                            minimum={
                                minimumRetryAfter
                            }
                            error={
                                errors[
                                    'configuration.retry_after'
                                    ]
                            }
                            onChange={
                                (
                                    value,
                                ) =>
                                    setConfiguration(
                                        'retry_after',
                                        value,
                                    )
                            }
                        />
                    </div>

                    <Toggle
                        enabled={
                            Boolean(
                                form.data
                                    .configuration
                                    .after_commit,
                            )
                        }
                        onChange={
                            (
                                enabled,
                            ) =>
                                setConfiguration(
                                    'after_commit',
                                    enabled,
                                )
                        }
                        label="After commit"
                        description="Dispatch queued jobs only after the current database transaction commits successfully."
                        error={
                            errors[
                                'configuration.after_commit'
                                ]
                        }
                    />
                </Section>
            ) : null}

            {form.data.driver
            === 'redis' ? (
                <Section
                    icon={
                        Gauge
                    }
                    title="Redis configuration"
                    description="Use a Redis Infrastructure Connection without duplicating host, credentials, TLS, or connection ownership here."
                >
                    {currentRedisUnavailable ? (
                        <RedisUnavailableWarning
                            connection={
                                currentRedis
                            }
                            connectionId={
                                currentRedisId
                            }
                        />
                    ) : null}

                    {eligibleRedis
                        .length === 0
                    && !currentRedisUnavailable ? (
                        <InlineWarning
                            title="No enabled Redis connections available"
                            description="Create or enable a Redis Infrastructure Connection before configuring this Queue driver."
                        />
                    ) : null}

                    <Field
                        label="Infrastructure connection"
                        required
                        error={
                            errors[
                                'configuration.infrastructure_connection_id'
                                ]
                        }
                        hint="Queue configuration references the Infrastructure Connection; Redis credentials remain managed separately."
                    >
                        <Select
                            value={
                                currentRedisId
                                    ? String(
                                        currentRedisId,
                                    )
                                    : ''
                            }
                            onValueChange={
                                (
                                    value,
                                ) =>
                                    setConfiguration(
                                        'infrastructure_connection_id',
                                        Number(
                                            value,
                                        ),
                                    )
                            }
                            disabled={
                                eligibleRedis
                                    .length === 0
                            }
                        >
                            <SelectTrigger
                                className={
                                    selectTriggerClass(
                                        Boolean(
                                            errors[
                                                'configuration.infrastructure_connection_id'
                                                ],
                                        ),
                                    )
                                }
                            >
                                <SelectValue placeholder="Choose an enabled Redis connection" />
                            </SelectTrigger>

                            <SelectContent>
                                {currentRedisUnavailable
                                && currentRedisId ? (
                                    <SelectItem
                                        value={
                                            String(
                                                currentRedisId,
                                            )
                                        }
                                        disabled
                                    >
                                        {currentRedis
                                            ? `${currentRedis.name} · ${redisAvailabilityLabel(currentRedis)}`
                                            : `Connection #${currentRedisId} · Missing / unavailable`}
                                    </SelectItem>
                                ) : null}

                                {eligibleRedis.map(
                                    (
                                        connection,
                                    ) => (
                                        <SelectItem
                                            key={
                                                connection.id
                                            }
                                            value={
                                                String(
                                                    connection.id,
                                                )
                                            }
                                        >
                                            {
                                                connection.name
                                            }

                                            {' · '}

                                            {humanize(
                                                connection.source,
                                            )}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>

                        {canViewInfrastructure ? (
                            <Link
                                href={route(
                                    'admin.system.connections.index',
                                )}
                                className="mt-2 inline-flex text-sm font-medium text-sky-700 transition hover:text-sky-900"
                            >
                                Manage infrastructure connections
                                →
                            </Link>
                        ) : null}
                    </Field>

                    <div className="grid gap-5 xl:grid-cols-2">
                        <RetryField
                            value={
                                form.data
                                    .configuration
                                    .retry_after
                                ?? ''
                            }
                            minimum={
                                minimumRetryAfter
                            }
                            error={
                                errors[
                                    'configuration.retry_after'
                                    ]
                            }
                            onChange={
                                (
                                    value,
                                ) =>
                                    setConfiguration(
                                        'retry_after',
                                        value,
                                    )
                            }
                        />

                        {form.data
                            .configuration
                            .block_for
                        !== null ? (
                            <Field
                                label="Block for"
                                required
                                hint="Between 1 and 60 seconds. Zero is intentionally not accepted."
                                error={
                                    errors[
                                        'configuration.block_for'
                                        ]
                                }
                            >
                                <div className="relative">
                                    <input
                                        type="number"
                                        min={
                                            1
                                        }
                                        max={
                                            60
                                        }
                                        step={
                                            1
                                        }
                                        inputMode="numeric"
                                        value={
                                            form.data
                                                .configuration
                                                .block_for
                                            ?? ''
                                        }
                                        onChange={
                                            (
                                                event,
                                            ) =>
                                                setConfiguration(
                                                    'block_for',
                                                    integerOrEmpty(
                                                        event
                                                            .target
                                                            .value,
                                                    ),
                                                )
                                        }
                                        className={
                                            `${inputClass(
                                                Boolean(
                                                    errors[
                                                        'configuration.block_for'
                                                        ],
                                                ),
                                            )} pr-20`
                                        }
                                    />

                                    <span className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400">
                                        seconds
                                    </span>
                                </div>
                            </Field>
                        ) : (
                            <div className="rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
                                <p className="text-sm font-semibold text-gray-700">
                                    Blocking wait disabled
                                </p>

                                <p className="mt-1 text-sm leading-5 text-gray-500">
                                    The worker will not use a Redis
                                    blocking wait for this Queue
                                    configuration.
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="grid gap-4 xl:grid-cols-2">
                        <Toggle
                            enabled={
                                form.data
                                    .configuration
                                    .block_for
                                !== null
                            }
                            onChange={
                                (
                                    enabled,
                                ) =>
                                    setConfiguration(
                                        'block_for',
                                        enabled
                                            ? 5
                                            : null,
                                    )
                            }
                            label="Blocking wait"
                            description="Wait briefly for a Redis job instead of continuously polling for work."
                            error={
                                form.data
                                    .configuration
                                    .block_for
                                === null
                                    ? errors[
                                        'configuration.block_for'
                                        ]
                                    : undefined
                            }
                        />

                        <Toggle
                            enabled={
                                Boolean(
                                    form.data
                                        .configuration
                                        .after_commit,
                                )
                            }
                            onChange={
                                (
                                    enabled,
                                ) =>
                                    setConfiguration(
                                        'after_commit',
                                        enabled,
                                    )
                            }
                            label="After commit"
                            description="Dispatch queued jobs only after the current database transaction commits successfully."
                            error={
                                errors[
                                    'configuration.after_commit'
                                    ]
                            }
                        />
                    </div>
                </Section>
            ) : null}

            {form.data.driver
            === 'sync' ? (
                <section className="overflow-hidden rounded-[28px] border border-amber-200 bg-white shadow-sm">
                    <div className="flex gap-4 bg-amber-50 px-5 py-5 sm:px-6">
                        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-amber-200">
                            <Info className="h-5 w-5 text-amber-700" />
                        </span>

                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <h2 className="font-semibold text-amber-950">
                                    Synchronous execution
                                </h2>

                                <span className="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-inset ring-amber-200">
                                    Not recommended for production
                                </span>
                            </div>

                            <p className="mt-2 max-w-4xl text-sm leading-6 text-amber-800">
                                Jobs execute immediately in the
                                same request or process that
                                dispatches them. No asynchronous
                                Queue worker is involved, so
                                expensive jobs increase request
                                execution time directly.
                            </p>

                            <p className="mt-2 text-sm leading-6 text-amber-800">
                                This driver has no additional
                                configuration fields.
                            </p>
                        </div>
                    </div>
                </section>
            ) : null}

            {!definition ? (
                <InlineWarning
                    title="Driver definition unavailable"
                    description="The selected Queue driver is not currently registered by the backend. Saving this configuration is expected to be rejected."
                />
            ) : null}

            <div className="flex flex-col-reverse gap-3 rounded-[28px] border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <p className="max-w-2xl text-sm leading-6 text-gray-500">
                    {editing
                        ? 'Saving updates this stored profile only. It does not switch the active Queue runtime.'
                        : 'Creating this profile does not activate it or modify the current Queue runtime.'}
                </p>

                <div className="flex flex-col-reverse gap-3 sm:flex-row">
                    <Link
                        href={route(
                            'admin.system.queues.index',
                        )}
                        className="inline-flex h-11 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        disabled={
                            form.processing
                        }
                        className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-5 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-wait disabled:opacity-60"
                    >
                        <Save className="h-4 w-4" />

                        {form.processing
                            ? 'Saving…'
                            : editing
                                ? 'Save changes'
                                : 'Create configuration'}
                    </button>
                </div>
            </div>
        </form>
    )
}

function Section({
                     icon: Icon,
                     title,
                     description,
                     children,
                 }: {
    icon:
        LucideIcon

    title:
        string

    description:
        string

    children:
        ReactNode
}) {
    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                <div className="flex gap-3">
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
            </div>

            <div className="space-y-5 p-5 sm:p-6">
                {children}
            </div>
        </section>
    )
}

function Field({
                   label,
                   required,
                   hint,
                   error,
                   children,
               }: {
    label:
        string

    required?:
        boolean

    hint?:
        string

    error?:
        string

    children:
        ReactNode
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

            {hint
            && !error ? (
                <p className="mt-1.5 text-xs leading-5 text-gray-500">
                    {hint}
                </p>
            ) : null}

            <InputError
                message={
                    error
                }
                className="mt-1.5"
            />
        </div>
    )
}

function Toggle({
                    enabled,
                    onChange,
                    label,
                    description,
                    error,
                }: {
    enabled:
        boolean

    onChange:
        (enabled: boolean) => void

    label:
        string

    description:
        string

    error?:
        string
}) {
    return (
        <div>
            <button
                type="button"
                role="switch"
                aria-checked={
                    enabled
                }
                onClick={
                    () =>
                        onChange(
                            !enabled,
                        )
                }
                className={
                    `flex w-full items-center justify-between gap-5 rounded-2xl border p-4 text-left transition ${
                        enabled
                            ? 'border-sky-200 bg-sky-50/60'
                            : 'border-gray-200 bg-gray-50/60 hover:border-gray-300'
                    }`
                }
            >
                <span className="min-w-0">
                    <span className="block text-sm font-semibold text-gray-800">
                        {label}
                    </span>

                    <span className="mt-1 block text-sm leading-5 text-gray-500">
                        {description}
                    </span>
                </span>

                <span
                    className={
                        `relative h-6 w-11 shrink-0 rounded-full transition ${
                            enabled
                                ? 'bg-sky-600'
                                : 'bg-gray-300'
                        }`
                    }
                >
                    <span
                        className={
                            `absolute top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform ${
                                enabled
                                    ? 'translate-x-[22px]'
                                    : 'translate-x-0.5'
                            }`
                        }
                    />
                </span>
            </button>

            <InputError
                message={
                    error
                }
                className="mt-1.5"
            />
        </div>
    )
}

function RetryField({
                        value,
                        minimum,
                        error,
                        onChange,
                    }: {
    value:
        number | ''

    minimum:
        number

    error?:
        string

    onChange:
        (
            value:
                number | '',
        ) => void
}) {
    return (
        <Field
            label="Retry after"
            required
            hint={
                `Must be at least ${minimum} seconds so a job cannot be retried while its worker may still be processing it.`
            }
            error={
                error
            }
        >
            <div className="relative">
                <input
                    type="number"
                    min={
                        minimum
                    }
                    step={
                        1
                    }
                    inputMode="numeric"
                    value={
                        value
                    }
                    onChange={
                        (
                            event,
                        ) =>
                            onChange(
                                integerOrEmpty(
                                    event
                                        .target
                                        .value,
                                ),
                            )
                    }
                    className={
                        `${inputClass(
                            Boolean(
                                error,
                            ),
                        )} pr-20`
                    }
                />

                <span className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400">
                    seconds
                </span>
            </div>
        </Field>
    )
}

function InlineWarning({
                           title,
                           description,
                       }: {
    title:
        string

    description:
        string
}) {
    return (
        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <div className="flex gap-3">
                <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                <div>
                    <p className="font-semibold text-amber-950">
                        {title}
                    </p>

                    <p className="mt-1 text-sm leading-6 text-amber-800">
                        {description}
                    </p>
                </div>
            </div>
        </div>
    )
}

function RedisUnavailableWarning({
                                     connection,
                                     connectionId,
                                 }: {
    connection?:
        RedisConnection

    connectionId:
        number | null
}) {
    if (
        connection
    ) {
        return (
            <InlineWarning
                title={
                    `Previously selected Redis: ${connection.name}`
                }
                description={
                    `${redisAvailabilityLabel(connection)}. This connection cannot be selected for new Queue configuration. Choose an enabled, non-archived Redis connection before saving changes.`
                }
            />
        )
    }

    return (
        <InlineWarning
            title="Referenced Redis connection is unavailable"
            description={
                connectionId
                    ? `Infrastructure Connection #${connectionId} is no longer available in the current catalog. Choose an eligible Redis connection before saving changes.`
                    : 'The previously referenced Redis Infrastructure Connection is no longer available. Choose an eligible connection before saving changes.'
            }
        />
    )
}

function defaultsFor(
    driver:
    QueueDriver,

    definitions:
    QueueDriverDefinition[],

    redis:
    RedisConnection[],

    minimum:
    number,
): QueueConfigurationValues {
    if (
        driver
        === 'database'
    ) {
        return {
            database_connection:
                definitions.find(
                    (
                        item,
                    ) =>
                        item.type
                        === 'database',
                )
                    ?.options
                    .database_connections?.[0]
                ?? '',

            retry_after:
            minimum,

            after_commit:
                false,
        }
    }

    if (
        driver
        === 'redis'
    ) {
        return {
            infrastructure_connection_id:
                redis.find(
                    (
                        item,
                    ) =>
                        item.is_enabled
                        && !item.deleted_at,
                )?.id
                ?? '',

            retry_after:
            minimum,

            block_for:
                5,

            after_commit:
                false,
        }
    }

    return {}
}

function initialValues(
    driver:
    QueueDriver,

    definitions:
    QueueDriverDefinition[],

    redis:
    RedisConnection[],

    minimum:
    number,

    configuration?:
    QueueConfiguration,
): QueueConfigurationValues {
    const defaults =
        defaultsFor(
            driver,
            definitions,
            redis,
            minimum,
        )

    if (
        !configuration
    ) {
        return defaults
    }

    return {
        ...defaults,
        ...configuration.configuration,
    }
}

function driverIcon(
    driver:
    QueueDriver,
): LucideIcon {
    if (
        driver
        === 'database'
    ) {
        return Database
    }

    if (
        driver
        === 'redis'
    ) {
        return Gauge
    }

    return Workflow
}

function positiveInteger(
    value:
    unknown,
): number | null {
    if (
        value === null
        || value === undefined
        || value === ''
    ) {
        return null
    }

    const parsed =
        Number(
            value,
        )

    if (
        !Number.isInteger(
            parsed,
        )
        || parsed <= 0
    ) {
        return null
    }

    return parsed
}

function integerOrEmpty(
    value:
    string,
): number | '' {
    if (
        value === ''
    ) {
        return ''
    }

    const parsed =
        Number(
            value,
        )

    if (
        !Number.isFinite(
            parsed,
        )
    ) {
        return ''
    }

    return Math.trunc(
        parsed,
    )
}

function redisAvailabilityLabel(
    connection:
    RedisConnection,
): string {
    if (
        connection.deleted_at
    ) {
        return 'Archived / unavailable'
    }

    if (
        !connection.is_enabled
    ) {
        return 'Disabled / unavailable'
    }

    return 'Enabled'
}

function humanize(
    value:
    string,
) {
    return value
        .replace(
            /[._-]+/g,
            ' ',
        )
        .replace(
            /\b\w/g,
            (
                letter,
            ) =>
                letter.toUpperCase(),
        )
}

function inputClass(
    error = false,
) {
    return `h-11 w-full rounded-xl border bg-white px-3.5 text-sm text-gray-900 outline-none transition ${
        error
            ? 'border-red-300 focus:border-red-400 focus:ring-4 focus:ring-red-100'
            : 'border-gray-200 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'
    }`
}

function selectTriggerClass(
    error = false,
) {
    return `h-11 w-full rounded-xl bg-white ${
        error
            ? 'border-red-300 focus:ring-red-100'
            : 'border-gray-200'
    }`
}

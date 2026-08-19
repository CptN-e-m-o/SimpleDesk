import type { FormEvent, ReactNode } from 'react'
import { Link, useForm } from '@inertiajs/react'
import {
    AlertTriangle,
    Check,
    Database,
    FileArchive,
    Info,
    LockKeyhole,
    Save,
    Server,
    Settings2,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
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

import { CacheDriverBadge } from './components/CacheBadges'
import type {
    CacheConfiguration,
    CacheDefinition,
    CacheDriverType,
    InfrastructureOption,
} from './cacheTypes'

type Props = {
    definitions: CacheDefinition[]
    redisConnections: InfrastructureOption[]
    configuration?: CacheConfiguration
}

type CacheConfigurationValues = {
    database_connection?: string
}

type FormData = {
    name: string
    driver: CacheDriverType
    infrastructure_connection_id: number | '' | null
    configuration: CacheConfigurationValues
    is_enabled: boolean
}

export default function CacheConfigurationForm({
                                                   definitions,
                                                   redisConnections,
                                                   configuration,
                                               }: Props) {
    const { can } = usePermissions()

    const editing = Boolean(configuration)

    const initialDriver =
        configuration?.driver
        ?? definitions.find((item) => item.available)?.type
        ?? definitions[0]?.type
        ?? 'database'

    const eligibleRedis = redisConnections.filter(
        (connection) => connection.is_enabled && !connection.deleted_at,
    )

    const form = useForm<FormData>({
        name: configuration?.name ?? '',
        driver: initialDriver,
        infrastructure_connection_id:
            configuration?.infrastructure_connection_id
            ?? (initialDriver === 'redis' ? eligibleRedis[0]?.id ?? '' : null),
        configuration:
            configuration?.configuration
            ?? defaultsFor(initialDriver, definitions),
        is_enabled: configuration?.is_enabled ?? true,
    })

    const definition = definitions.find(
        (item) => item.type === form.data.driver,
    )

    const errors = form.errors as Record<string, string | undefined>

    const databaseConnections =
        form.data.driver === 'database'
            ? definition?.options.database_connections ?? []
            : []

    const currentDatabaseConnection = String(
        form.data.configuration.database_connection ?? '',
    )

    const currentDatabaseUnavailable =
        currentDatabaseConnection !== ''
        && !databaseConnections.includes(currentDatabaseConnection)

    const currentRedisId = positiveInteger(
        form.data.infrastructure_connection_id,
    )

    const currentRedis = currentRedisId
        ? redisConnections.find((connection) => connection.id === currentRedisId)
        : undefined

    const currentRedisUnavailable =
        currentRedisId !== null
        && (
            !currentRedis
            || !currentRedis.is_enabled
            || Boolean(currentRedis.deleted_at)
        )

    const canViewInfrastructure = can(
        'admin.settings.infrastructure_connections.view',
    )

    const saveBlocked =
        !definition
        || !definition.available
        || (
            form.data.driver === 'database'
            && (
                currentDatabaseConnection === ''
                || currentDatabaseUnavailable
            )
        )
        || (
            form.data.driver === 'redis'
            && (
                currentRedisId === null
                || currentRedisUnavailable
            )
        )

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        if (editing && configuration) {
            form.put(
                route(
                    'admin.system.cache.update',
                    configuration.id,
                ),
                {
                    preserveScroll: true,
                },
            )

            return
        }

        form.post(
            route('admin.system.cache.store'),
        )
    }

    const selectDriver = (driver: CacheDriverType) => {
        if (editing) {
            return
        }

        const selectedDefinition = definitions.find(
            (item) => item.type === driver,
        )

        if (!selectedDefinition?.available) {
            return
        }

        form.setData((data) => ({
            ...data,
            driver,
            infrastructure_connection_id:
                driver === 'redis'
                    ? eligibleRedis[0]?.id ?? ''
                    : null,
            configuration: defaultsFor(
                driver,
                definitions,
            ),
        }))

        form.clearErrors()
    }

    const setConfiguration = <
        K extends keyof CacheConfigurationValues,
    >(
        key: K,
        value: CacheConfigurationValues[K],
    ) => {
        form.setData(
            'configuration',
            {
                ...form.data.configuration,
                [key]: value,
            },
        )
    }

    return (
        <form
            onSubmit={submit}
            className="space-y-6"
        >
            <Section
                icon={Settings2}
                title="General"
                description="Name this managed Cache profile and control whether it can be selected for activation."
            >
                <Field
                    label="Name"
                    required
                    error={errors.name}
                >
                    <input
                        type="text"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData(
                                'name',
                                event.target.value,
                            )
                        }
                        placeholder="Production cache"
                        autoComplete="off"
                        className={inputClass(
                            Boolean(errors.name),
                        )}
                    />
                </Field>

                <Field
                    label="Driver"
                    required
                    error={errors.driver}
                >
                    {editing ? (
                        <div className="rounded-2xl border border-gray-200 bg-gray-50/70 p-4">
                            <div className="flex items-start gap-3">
                                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100">
                                    <LockKeyhole className="h-5 w-5 text-gray-500" />
                                </span>

                                <div className="min-w-0">
                                    <CacheDriverBadge
                                        driver={form.data.driver}
                                    />

                                    <p className="mt-2 text-sm leading-6 text-gray-500">
                                        The Cache driver cannot be changed after this profile has been created.
                                    </p>

                                    {definition && !definition.available ? (
                                        <p className="mt-2 text-sm font-medium text-red-700">
                                            {definition.unavailable_reason
                                                ?? 'This driver is currently unavailable.'}
                                        </p>
                                    ) : null}
                                </div>
                            </div>
                        </div>
                    ) : definitions.length > 0 ? (
                        <div className="grid gap-3 lg:grid-cols-3">
                            {definitions.map((item) => {
                                const selected =
                                    item.type === form.data.driver

                                const Icon = driverIcon(
                                    item.type,
                                )

                                return (
                                    <button
                                        key={item.type}
                                        type="button"
                                        disabled={!item.available}
                                        onClick={() =>
                                            selectDriver(item.type)
                                        }
                                        aria-pressed={selected}
                                        className={`relative rounded-2xl border p-4 text-left transition ${
                                            !item.available
                                                ? 'cursor-not-allowed border-gray-200 bg-gray-50 opacity-65'
                                                : selected
                                                    ? 'border-sky-300 bg-sky-50 shadow-sm ring-4 ring-sky-50'
                                                    : 'border-gray-200 bg-white hover:border-sky-200 hover:bg-sky-50/30'
                                        }`}
                                    >
                                        <div className="flex items-start gap-3 pr-6">
                                            <span
                                                className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                                                    selected
                                                        ? 'bg-sky-100 text-sky-700'
                                                        : 'bg-gray-100 text-gray-500'
                                                }`}
                                            >
                                                <Icon className="h-5 w-5" />
                                            </span>

                                            <div className="min-w-0">
                                                <span className="block font-semibold text-gray-900">
                                                    {item.label}
                                                </span>

                                                {item.requires_infrastructure ? (
                                                    <span className="mt-1 block text-xs font-medium text-sky-700">
                                                        Infrastructure Connection
                                                    </span>
                                                ) : null}
                                            </div>
                                        </div>

                                        <p className="mt-3 text-sm leading-6 text-gray-500">
                                            {item.description}
                                        </p>

                                        <div className="mt-3 flex flex-wrap gap-2">
                                            {item.recommended_for_production ? (
                                                <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                                    Production
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                    Not recommended for production
                                                </span>
                                            )}

                                            {!item.available ? (
                                                <span className="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                                    Unavailable
                                                </span>
                                            ) : null}
                                        </div>

                                        {!item.available && item.unavailable_reason ? (
                                            <p className="mt-3 text-xs leading-5 text-gray-500">
                                                {item.unavailable_reason}
                                            </p>
                                        ) : null}

                                        {selected && item.available ? (
                                            <span className="absolute right-4 top-4 flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-white">
                                                <Check className="h-3.5 w-3.5" />
                                            </span>
                                        ) : null}
                                    </button>
                                )
                            })}
                        </div>
                    ) : (
                        <InlineWarning
                            title="No Cache drivers are available"
                            description="SimpleDesk did not expose any registered managed Cache drivers."
                        />
                    )}
                </Field>

                <Toggle
                    enabled={form.data.is_enabled}
                    onChange={(enabled) =>
                        form.setData(
                            'is_enabled',
                            enabled,
                        )
                    }
                    label="Enabled"
                    description="Enabled profiles can be activated later. Enabling a profile does not change the running Cache backend."
                    error={errors.is_enabled}
                />
            </Section>

            {form.data.driver === 'database' ? (
                <Section
                    icon={Database}
                    title="Database configuration"
                    description="Store Cache entries and atomic lock state in an allowlisted application database connection."
                >
                    {currentDatabaseUnavailable ? (
                        <InlineWarning
                            title={`Database connection unavailable: ${currentDatabaseConnection}`}
                            description="This connection is no longer exposed for managed Cache. Select another available connection before saving."
                        />
                    ) : null}

                    {databaseConnections.length === 0
                    && !currentDatabaseUnavailable ? (
                        <InlineWarning
                            title="No database connections available"
                            description="No application database connection is currently exposed for managed Cache profiles."
                        />
                    ) : null}

                    <Field
                        label="Database connection"
                        required
                        hint="Only explicitly allowed application database connections can be selected."
                        error={
                            errors[
                                'configuration.database_connection'
                                ]
                        }
                    >
                        <Select
                            value={currentDatabaseConnection}
                            onValueChange={(value) =>
                                setConfiguration(
                                    'database_connection',
                                    value,
                                )
                            }
                            disabled={
                                databaseConnections.length === 0
                            }
                        >
                            <SelectTrigger
                                className={selectTriggerClass(
                                    Boolean(
                                        errors[
                                            'configuration.database_connection'
                                            ],
                                    ),
                                )}
                            >
                                <SelectValue placeholder="Choose a database connection" />
                            </SelectTrigger>

                            <SelectContent>
                                {currentDatabaseUnavailable ? (
                                    <SelectItem
                                        value={currentDatabaseConnection}
                                        disabled
                                    >
                                        {currentDatabaseConnection}
                                        {' · '}
                                        Unavailable
                                    </SelectItem>
                                ) : null}

                                {databaseConnections.map(
                                    (name) => (
                                        <SelectItem
                                            key={name}
                                            value={name}
                                        >
                                            {name}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                    </Field>

                    <InlineInfo
                        title="Cache and lock tables are verified before activation"
                        description="SimpleDesk requires both the cache and cache_locks tables on the selected database connection. Health testing verifies actual Cache and atomic lock operations."
                    />
                </Section>
            ) : null}

            {form.data.driver === 'file' ? (
                <Section
                    icon={FileArchive}
                    title="File configuration"
                    description="SimpleDesk manages isolated filesystem locations automatically for this Cache profile."
                >
                    <InlineInfo
                        title="Filesystem paths are managed by SimpleDesk"
                        description="Administrators cannot enter arbitrary paths. Each profile receives separate data and lock directories below storage/framework/cache/simpledesk."
                    />

                    <div className="grid gap-4 lg:grid-cols-2">
                        <ManagedValue
                            label="Cache data"
                            value="Profile-isolated data directory"
                        />

                        <ManagedValue
                            label="Atomic locks"
                            value="Separate profile-isolated lock directory"
                        />
                    </div>

                    <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <div className="flex gap-3">
                            <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                            <div>
                                <p className="text-sm font-semibold text-amber-900">
                                    Local filesystem storage is process-host local
                                </p>

                                <p className="mt-1 text-sm leading-6 text-amber-800">
                                    File Cache is useful for simple single-host deployments, but it is not recommended when multiple application nodes must share Cache and lock state.
                                </p>
                            </div>
                        </div>
                    </div>
                </Section>
            ) : null}

            {form.data.driver === 'redis' ? (
                <Section
                    icon={Server}
                    title="Redis configuration"
                    description="Reference an existing Redis Infrastructure Connection without duplicating host, TLS, authentication, or credential configuration."
                >
                    {currentRedisUnavailable ? (
                        <RedisUnavailableWarning
                            connection={currentRedis}
                            connectionId={currentRedisId}
                        />
                    ) : null}

                    {eligibleRedis.length === 0
                    && !currentRedisUnavailable ? (
                        <InlineWarning
                            title="No enabled Redis connections available"
                            description="Create or enable a Redis Infrastructure Connection before configuring managed Redis Cache."
                        />
                    ) : null}

                    <Field
                        label="Infrastructure connection"
                        required
                        hint="Redis credentials and network settings remain owned by Infrastructure Connections."
                        error={
                            errors.infrastructure_connection_id
                        }
                    >
                        <Select
                            value={
                                currentRedisId
                                    ? String(currentRedisId)
                                    : ''
                            }
                            onValueChange={(value) =>
                                form.setData(
                                    'infrastructure_connection_id',
                                    Number(value),
                                )
                            }
                            disabled={eligibleRedis.length === 0}
                        >
                            <SelectTrigger
                                className={selectTriggerClass(
                                    Boolean(
                                        errors.infrastructure_connection_id,
                                    ),
                                )}
                            >
                                <SelectValue placeholder="Choose an enabled Redis connection" />
                            </SelectTrigger>

                            <SelectContent>
                                {currentRedisUnavailable
                                && currentRedisId ? (
                                    <SelectItem
                                        value={String(currentRedisId)}
                                        disabled
                                    >
                                        {currentRedis
                                            ? `${currentRedis.name} · ${redisAvailabilityLabel(currentRedis)}`
                                            : `Connection #${currentRedisId} · Missing / unavailable`}
                                    </SelectItem>
                                ) : null}

                                {eligibleRedis.map(
                                    (connection) => (
                                        <SelectItem
                                            key={connection.id}
                                            value={String(connection.id)}
                                        >
                                            {connection.name}
                                            {' · '}
                                            {humanize(connection.source)}
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
                                Manage infrastructure connections →
                            </Link>
                        ) : null}
                    </Field>

                    <InlineInfo
                        title="Credentials are not stored in this Cache profile"
                        description="The Cache profile stores only the Infrastructure Connection reference. Redis connection details and secrets remain managed separately."
                    />
                </Section>
            ) : null}

            {errors.configuration ? (
                <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
                    <InputError
                        message={errors.configuration}
                    />
                </div>
            ) : null}

            <section className="flex flex-col gap-4 rounded-[24px] border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div>
                    <p className="font-semibold text-gray-900">
                        {editing
                            ? 'Save profile changes'
                            : 'Create managed Cache profile'}
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        Saving this form does not activate the profile or change the current Cache runtime.
                    </p>

                    {saveBlocked ? (
                        <p className="mt-2 text-sm font-medium text-amber-700">
                            Resolve the unavailable or missing driver configuration before saving.
                        </p>
                    ) : null}
                </div>

                <div className="flex flex-wrap gap-3">
                    <Link
                        href={route(
                            'admin.system.cache.index',
                        )}
                        className="inline-flex h-11 items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                    >
                        Cancel
                    </Link>

                    <button
                        type="submit"
                        disabled={
                            form.processing
                            || saveBlocked
                        }
                        className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-5 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                    >
                        <Save className="h-4 w-4" />

                        {form.processing
                            ? 'Saving…'
                            : editing
                                ? 'Save configuration'
                                : 'Create configuration'}
                    </button>
                </div>
            </section>
        </form>
    )
}

function Section({
                     icon: Icon,
                     title,
                     description,
                     children,
                 }: {
    icon: LucideIcon
    title: string
    description: string
    children: ReactNode
}) {
    return (
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
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

            <div className="space-y-5 p-5 sm:p-6">
                {children}
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
            <div className="mb-2 flex flex-wrap items-baseline justify-between gap-2">
                <label className="text-sm font-semibold text-gray-800">
                    {label}

                    {required ? (
                        <span className="ml-1 text-red-500">
                            *
                        </span>
                    ) : null}
                </label>

                {hint ? (
                    <span className="text-xs text-gray-400">
                        {hint}
                    </span>
                ) : null}
            </div>

            {children}

            <InputError
                message={error}
                className="mt-2"
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
    enabled: boolean
    onChange: (enabled: boolean) => void
    label: string
    description: string
    error?: string
}) {
    return (
        <div>
            <button
                type="button"
                role="switch"
                aria-checked={enabled}
                onClick={() =>
                    onChange(!enabled)
                }
                className={`flex w-full items-start justify-between gap-5 rounded-2xl border p-4 text-left transition ${
                    enabled
                        ? 'border-emerald-200 bg-emerald-50/60'
                        : 'border-gray-200 bg-gray-50/60'
                }`}
            >
                <div>
                    <p className="text-sm font-semibold text-gray-900">
                        {label}
                    </p>

                    <p className="mt-1 text-sm leading-6 text-gray-500">
                        {description}
                    </p>
                </div>

                <span
                    className={`relative mt-0.5 h-6 w-11 shrink-0 rounded-full transition ${
                        enabled
                            ? 'bg-emerald-500'
                            : 'bg-gray-300'
                    }`}
                >
                    <span
                        className={`absolute top-1 h-4 w-4 rounded-full bg-white shadow-sm transition ${
                            enabled
                                ? 'left-6'
                                : 'left-1'
                        }`}
                    />
                </span>
            </button>

            <InputError
                message={error}
                className="mt-2"
            />
        </div>
    )
}

function InlineWarning({
                           title,
                           description,
                       }: {
    title: string
    description: string
}) {
    return (
        <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4">
            <div className="flex gap-3">
                <AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                <div>
                    <p className="text-sm font-semibold text-amber-900">
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

function InlineInfo({
                        title,
                        description,
                    }: {
    title: string
    description: string
}) {
    return (
        <div className="rounded-2xl border border-sky-200 bg-sky-50 p-4">
            <div className="flex gap-3">
                <Info className="mt-0.5 h-5 w-5 shrink-0 text-sky-700" />

                <div>
                    <p className="text-sm font-semibold text-sky-900">
                        {title}
                    </p>

                    <p className="mt-1 text-sm leading-6 text-sky-800">
                        {description}
                    </p>
                </div>
            </div>
        </div>
    )
}

function ManagedValue({
                          label,
                          value,
                      }: {
    label: string
    value: string
}) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                {label}
            </p>

            <p className="mt-1 text-sm font-semibold text-gray-800">
                {value}
            </p>
        </div>
    )
}

function RedisUnavailableWarning({
                                     connection,
                                     connectionId,
                                 }: {
    connection?: InfrastructureOption
    connectionId: number | null
}) {
    if (!connection) {
        return (
            <InlineWarning
                title={`Redis connection #${connectionId ?? 'unknown'} is unavailable`}
                description="The Infrastructure Connection referenced by this profile can no longer be resolved. Select another enabled Redis connection before saving."
            />
        )
    }

    return (
        <InlineWarning
            title={`${connection.name} is unavailable`}
            description={
                connection.deleted_at
                    ? 'The referenced Redis Infrastructure Connection is archived. Select another enabled connection before saving.'
                    : 'The referenced Redis Infrastructure Connection is disabled. Select another enabled connection before saving.'
            }
        />
    )
}

function defaultsFor(
    driver: CacheDriverType,
    definitions: CacheDefinition[],
): CacheConfigurationValues {
    if (driver !== 'database') {
        return {}
    }

    const definition = definitions.find(
        (item) => item.type === 'database',
    )

    return {
        database_connection:
            definition?.options.database_connections?.[0]
            ?? '',
    }
}

function positiveInteger(
    value: number | '' | null,
): number | null {
    if (
        typeof value === 'number'
        && Number.isInteger(value)
        && value > 0
    ) {
        return value
    }

    return null
}

function driverIcon(
    driver: CacheDriverType,
): LucideIcon {
    if (driver === 'database') {
        return Database
    }

    if (driver === 'file') {
        return FileArchive
    }

    return Server
}

function redisAvailabilityLabel(
    connection: InfrastructureOption,
): string {
    if (connection.deleted_at) {
        return 'Archived / unavailable'
    }

    if (!connection.is_enabled) {
        return 'Disabled / unavailable'
    }

    return 'Available'
}

function humanize(
    value: string,
): string {
    return value
        .replace(/[._-]+/g, ' ')
        .replace(
            /\b\w/g,
            (letter) => letter.toUpperCase(),
        )
}

function inputClass(
    hasError: boolean,
): string {
    return `h-11 w-full rounded-xl border bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:ring-4 ${
        hasError
            ? 'border-red-300 focus:border-red-400 focus:ring-red-100'
            : 'border-gray-200 focus:border-sky-400 focus:ring-sky-100'
    }`
}

function selectTriggerClass(
    hasError: boolean,
): string {
    return `h-11 w-full rounded-xl bg-white ${
        hasError
            ? 'border-red-300 focus:ring-red-100'
            : 'border-gray-200 focus:ring-sky-100'
    }`
}

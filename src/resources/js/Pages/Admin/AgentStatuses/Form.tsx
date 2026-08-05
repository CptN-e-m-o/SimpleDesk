import { useEffect, useMemo, useRef, useState } from 'react'
import type { ReactNode } from 'react'
import type { LucideIcon } from 'lucide-react'
import {
    Activity,
    ArrowLeft,
    Ban,
    Check,
    CheckCircle2,
    ChevronDown,
    CircleDot,
    CircleSlash,
    Clock3,
    Info,
    Palette,
    Route,
    Save,
    Search,
    ShieldCheck,
    Star,
    Tag,
    Timer,
    X,
} from 'lucide-react'
import {
    Form as InertiaForm,
    Head,
    Link,
} from '@inertiajs/react'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout'
import {
    formatAgentStatusIconName,
    resolveAgentStatusIcon,
} from './statusIcons'

type Status = {
    id: number
    name: string
    description?: string | null
    availability: string
    routing_eligibility: string
    icon: string
    color: string
    default_duration_minutes?: number | null
    is_system: boolean
    is_default: boolean
    is_active: boolean
    is_selectable: boolean
    sort_order: number
}

type Props = {
    status?: Status
    availabilityOptions: string[]
    routingOptions: string[]
    icons: string[]
}

type DurationMode =
    | 'none'
    | '15'
    | '30'
    | '60'
    | 'custom'

type ChoiceMeta = {
    label: string
    description: string
    icon: LucideIcon
    iconClassName: string
    selectedClassName: string
}

const fieldClassName =
    'h-11 w-full rounded-xl border border-gray-200 bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'

const colorPalette = [
    '#0284C7',
    '#2563EB',
    '#4F46E5',
    '#7C3AED',
    '#9333EA',
    '#DB2777',
    '#E11D48',
    '#DC2626',
    '#EA580C',
    '#D97706',
    '#65A30D',
    '#16A34A',
    '#059669',
    '#0D9488',
    '#0891B2',
    '#475569',
]

export default function FormPage({
                                     status,
                                     availabilityOptions,
                                     routingOptions,
                                     icons,
                                 }: Props) {
    const editing = status !== undefined

    const formTitle = editing
        ? 'Edit Agent Status'
        : 'Create Agent Status'

    const formAction = status
        ? route(
            'admin.agent-statuses.update',
            status.id,
        )
        : route('admin.agent-statuses.store')

    const formMethod = status ? 'put' : 'post'

    const [name, setName] = useState(
        status?.name ?? '',
    )

    const [description, setDescription] = useState(
        status?.description ?? '',
    )

    const [icon, setIcon] = useState(
        status?.icon ?? 'circle-dot',
    )

    const [color, setColor] = useState(
        status?.color ?? '#2563EB',
    )

    const [availability, setAvailability] = useState(
        status?.availability ?? 'available',
    )

    const [
        routingEligibility,
        setRoutingEligibility,
    ] = useState(
        status?.routing_eligibility ?? 'eligible',
    )

    const [durationMode, setDurationMode] =
        useState<DurationMode>(() =>
            resolveDurationMode(
                status?.default_duration_minutes,
            ),
        )

    const [customDuration, setCustomDuration] =
        useState(
            resolveCustomDuration(
                status?.default_duration_minutes,
            ),
        )

    const [sortOrder, setSortOrder] = useState(
        String(status?.sort_order ?? 0),
    )

    const [isActive, setIsActive] = useState(
        status?.is_active ?? true,
    )

    const [isSelectable, setIsSelectable] =
        useState(
            status?.is_selectable ?? true,
        )

    const [isDefault, setIsDefault] = useState(
        status?.is_default ?? false,
    )

    const durationValue = useMemo(() => {
        if (durationMode === 'none') {
            return ''
        }

        if (durationMode === 'custom') {
            return customDuration
        }

        return durationMode
    }, [customDuration, durationMode])

    const HeaderIcon = resolveAgentStatusIcon(icon)

    const previewColor = isValidHex(color)
        ? color
        : '#64748B'

    return (
        <AdminLayout title={formTitle}>
            <Head title={formTitle} />

            <InertiaForm
                action={formAction}
                method={formMethod}
            >
                {({ errors, processing }) => (
                    <div className="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
                        <input
                            type="hidden"
                            name="icon"
                            value={icon}
                        />

                        <input
                            type="hidden"
                            name="availability"
                            value={availability}
                        />

                        <input
                            type="hidden"
                            name="routing_eligibility"
                            value={routingEligibility}
                        />

                        <input
                            type="hidden"
                            name="default_duration_minutes"
                            value={durationValue}
                        />

                        <input
                            type="hidden"
                            name="is_active"
                            value={isActive ? '1' : '0'}
                        />

                        <input
                            type="hidden"
                            name="is_selectable"
                            value={
                                isSelectable ? '1' : '0'
                            }
                        />

                        <input
                            type="hidden"
                            name="is_default"
                            value={isDefault ? '1' : '0'}
                        />

                        <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="flex flex-col justify-between gap-5 bg-gradient-to-r from-sky-50/80 via-white to-white p-6 sm:flex-row sm:items-center">
                                <div className="flex items-start gap-4">
                                    <div
                                        className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1 ring-inset"
                                        style={{
                                            color: previewColor,
                                            backgroundColor:
                                                withAlpha(
                                                    previewColor,
                                                    '14',
                                                ),
                                            boxShadow: `inset 0 0 0 1px ${withAlpha(
                                                previewColor,
                                                '2E',
                                            )}`,
                                        }}
                                    >
                                        <HeaderIcon className="h-6 w-6" />
                                    </div>

                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                                {formTitle}
                                            </h1>

                                            {status?.is_system ? (
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200">
                                                    <ShieldCheck className="h-3.5 w-3.5" />
                                                    System
                                                </span>
                                            ) : null}
                                        </div>

                                        <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                            Configure how this
                                            status affects agent
                                            availability and work
                                            routing.
                                        </p>
                                    </div>
                                </div>

                                <Link
                                    href={route(
                                        'admin.agent-statuses.index',
                                    )}
                                    className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                >
                                    <ArrowLeft className="h-4 w-4" />
                                    Back to statuses
                                </Link>
                            </div>
                        </header>

                        {status?.is_system ? (
                            <div className="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                                <div>
                                    <h2 className="text-sm font-semibold text-amber-900">
                                        Protected system status
                                    </h2>

                                    <p className="mt-1 text-sm leading-6 text-amber-800">
                                        Key semantics of system
                                        statuses are protected by
                                        the backend. Invalid
                                        changes will be rejected
                                        even if they are submitted
                                        manually.
                                    </p>
                                </div>
                            </div>
                        ) : null}

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <SectionHeader
                                title="Status identity"
                                description="Choose the name, visual appearance, and description shown to administrators and agents."
                                icon={Tag}
                            />

                            <div className="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field
                                        label="Name"
                                        required
                                        error={errors.name}
                                        className="sm:col-span-2"
                                    >
                                        <input
                                            type="text"
                                            name="name"
                                            value={name}
                                            onChange={(
                                                event,
                                            ) =>
                                                setName(
                                                    event
                                                        .target
                                                        .value,
                                                )
                                            }
                                            placeholder="Lunch"
                                            autoFocus
                                            required
                                            className={
                                                fieldClassName
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Icon"
                                        required
                                        error={errors.icon}
                                    >
                                        <IconCombobox
                                            value={icon}
                                            options={icons}
                                            onChange={setIcon}
                                        />
                                    </Field>

                                    <Field
                                        label="Color"
                                        required
                                        error={errors.color}
                                    >
                                        <ColorField
                                            value={color}
                                            onChange={setColor}
                                        />
                                    </Field>

                                    <Field
                                        label="Description"
                                        error={
                                            errors.description
                                        }
                                        className="sm:col-span-2"
                                    >
                                        <textarea
                                            name="description"
                                            value={description}
                                            onChange={(
                                                event,
                                            ) =>
                                                setDescription(
                                                    event
                                                        .target
                                                        .value,
                                                )
                                            }
                                            rows={4}
                                            placeholder="Explain when agents should use this status."
                                            className="w-full resize-y rounded-xl border border-gray-200 bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                        />
                                    </Field>
                                </div>

                                <StatusPreview
                                    name={name}
                                    description={description}
                                    icon={icon}
                                    color={previewColor}
                                    availability={availability}
                                    routingEligibility={
                                        routingEligibility
                                    }
                                    duration={durationValue}
                                    isActive={isActive}
                                    isSelectable={
                                        isSelectable
                                    }
                                    isDefault={isDefault}
                                />
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <SectionHeader
                                title="Availability"
                                description="Describe the agent's immediate ability to work while this status is active."
                                icon={Activity}
                            />

                            <div className="p-6">
                                <div className="grid gap-4 md:grid-cols-3">
                                    {availabilityOptions.map(
                                        (option) => (
                                            <ChoiceCard
                                                key={option}
                                                selected={
                                                    availability ===
                                                    option
                                                }
                                                meta={availabilityMeta(
                                                    option,
                                                )}
                                                onClick={() =>
                                                    setAvailability(
                                                        option,
                                                    )
                                                }
                                            />
                                        ),
                                    )}
                                </div>

                                <ErrorMessage
                                    value={
                                        errors.availability
                                    }
                                />
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <SectionHeader
                                title="Routing eligibility"
                                description="Determine whether the agent can receive new work while this status is active."
                                icon={Route}
                            />

                            <div className="p-6">
                                <div className="grid gap-4 md:grid-cols-3">
                                    {routingOptions.map(
                                        (option) => (
                                            <ChoiceCard
                                                key={option}
                                                selected={
                                                    routingEligibility ===
                                                    option
                                                }
                                                meta={routingMeta(
                                                    option,
                                                )}
                                                onClick={() =>
                                                    setRoutingEligibility(
                                                        option,
                                                    )
                                                }
                                            />
                                        ),
                                    )}
                                </div>

                                <ErrorMessage
                                    value={
                                        errors.routing_eligibility
                                    }
                                />
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <SectionHeader
                                title="Default behavior"
                                description="Configure automatic reset duration, ordering, and where this status can be selected."
                                icon={Timer}
                            />

                            <div className="space-y-7 p-6">
                                <div>
                                    <div className="mb-3">
                                        <h3 className="text-sm font-semibold text-gray-700">
                                            Default duration
                                        </h3>

                                        <p className="mt-1 text-xs leading-5 text-gray-400">
                                            Determines when this
                                            status automatically
                                            returns to the previous
                                            or default status.
                                        </p>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                                        <DurationOption
                                            label="No reset"
                                            description="Until manually changed"
                                            selected={
                                                durationMode ===
                                                'none'
                                            }
                                            onClick={() =>
                                                setDurationMode(
                                                    'none',
                                                )
                                            }
                                        />

                                        <DurationOption
                                            label="15 minutes"
                                            description="Short break"
                                            selected={
                                                durationMode ===
                                                '15'
                                            }
                                            onClick={() =>
                                                setDurationMode(
                                                    '15',
                                                )
                                            }
                                        />

                                        <DurationOption
                                            label="30 minutes"
                                            description="Standard interval"
                                            selected={
                                                durationMode ===
                                                '30'
                                            }
                                            onClick={() =>
                                                setDurationMode(
                                                    '30',
                                                )
                                            }
                                        />

                                        <DurationOption
                                            label="1 hour"
                                            description="Longer temporary status"
                                            selected={
                                                durationMode ===
                                                '60'
                                            }
                                            onClick={() =>
                                                setDurationMode(
                                                    '60',
                                                )
                                            }
                                        />

                                        <DurationOption
                                            label="Custom"
                                            description="Enter minutes"
                                            selected={
                                                durationMode ===
                                                'custom'
                                            }
                                            onClick={() =>
                                                setDurationMode(
                                                    'custom',
                                                )
                                            }
                                        />
                                    </div>

                                    {durationMode ===
                                    'custom' ? (
                                        <div className="mt-4 max-w-sm">
                                            <Field
                                                label="Custom duration in minutes"
                                                required
                                                error={
                                                    errors.default_duration_minutes
                                                }
                                            >
                                                <div className="relative">
                                                    <Clock3 className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                                    <input
                                                        type="number"
                                                        min="1"
                                                        max="43200"
                                                        value={
                                                            customDuration
                                                        }
                                                        onChange={(
                                                            event,
                                                        ) =>
                                                            setCustomDuration(
                                                                event
                                                                    .target
                                                                    .value,
                                                            )
                                                        }
                                                        placeholder="90"
                                                        className={`${fieldClassName} pl-10 pr-20`}
                                                    />

                                                    <span className="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">
                                                        minutes
                                                    </span>
                                                </div>
                                            </Field>
                                        </div>
                                    ) : (
                                        <ErrorMessage
                                            value={
                                                errors.default_duration_minutes
                                            }
                                        />
                                    )}
                                </div>

                                <div className="grid gap-5 border-t border-gray-100 pt-6 lg:grid-cols-[260px_minmax(0,1fr)]">
                                    <Field
                                        label="Sort order"
                                        error={
                                            errors.sort_order
                                        }
                                        hint="Lower values are displayed first."
                                    >
                                        <input
                                            type="number"
                                            min="0"
                                            name="sort_order"
                                            value={sortOrder}
                                            onChange={(
                                                event,
                                            ) =>
                                                setSortOrder(
                                                    event
                                                        .target
                                                        .value,
                                                )
                                            }
                                            className={
                                                fieldClassName
                                            }
                                        />
                                    </Field>

                                    <div className="grid gap-3 sm:grid-cols-3">
                                        <ToggleCard
                                            title="Active"
                                            description="Available for new status changes."
                                            enabled={isActive}
                                            onChange={
                                                setIsActive
                                            }
                                        />

                                        <ToggleCard
                                            title="Selectable by agents"
                                            description="Agents can choose this status themselves."
                                            enabled={
                                                isSelectable
                                            }
                                            onChange={
                                                setIsSelectable
                                            }
                                        />

                                        <ToggleCard
                                            title="Default status"
                                            description="Used when no current status exists."
                                            enabled={isDefault}
                                            onChange={
                                                setIsDefault
                                            }
                                            icon={Star}
                                        />
                                    </div>
                                </div>

                                <ErrorMessage
                                    value={errors.is_active}
                                />

                                <ErrorMessage
                                    value={
                                        errors.is_selectable
                                    }
                                />

                                <ErrorMessage
                                    value={errors.is_default}
                                />

                                {isDefault &&
                                (!isActive ||
                                    !isSelectable) ? (
                                    <div className="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                        <Info className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" />

                                        <p className="text-sm leading-6 text-amber-800">
                                            A default status
                                            should normally remain
                                            active and selectable.
                                            The backend may reject
                                            an invalid default
                                            configuration.
                                        </p>
                                    </div>
                                ) : null}
                            </div>
                        </section>

                        <div className="sticky bottom-4 z-20 flex flex-col-reverse justify-end gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-xl backdrop-blur sm:flex-row">
                            <Link
                                href={route(
                                    'admin.agent-statuses.index',
                                )}
                                className="inline-flex h-11 items-center justify-center rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                            >
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? (
                                    <>
                                        <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                                        Saving...
                                    </>
                                ) : (
                                    <>
                                        <Save className="h-4 w-4" />
                                        {editing
                                            ? 'Save changes'
                                            : 'Create status'}
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                )}
            </InertiaForm>
        </AdminLayout>
    )
}

function IconCombobox({
                          value,
                          options,
                          onChange,
                      }: {
    value: string
    options: string[]
    onChange: (value: string) => void
}) {
    const [open, setOpen] = useState(false)
    const [query, setQuery] = useState('')
    const rootRef = useRef<HTMLDivElement>(null)
    const searchRef =
        useRef<HTMLInputElement>(null)

    const normalizedOptions = useMemo(() => {
        return Array.from(
            new Set(
                value
                    ? [value, ...options]
                    : options,
            ),
        ).sort((first, second) =>
            first.localeCompare(second),
        )
    }, [options, value])

    const filteredOptions = useMemo(() => {
        const normalizedQuery = query
            .trim()
            .toLowerCase()

        if (normalizedQuery === '') {
            return normalizedOptions
        }

        return normalizedOptions.filter((option) =>
            option
                .toLowerCase()
                .includes(normalizedQuery),
        )
    }, [normalizedOptions, query])

    useEffect(() => {
        const handleOutsideClick = (
            event: MouseEvent,
        ) => {
            if (
                rootRef.current &&
                !rootRef.current.contains(
                    event.target as Node,
                )
            ) {
                setOpen(false)
                setQuery('')
            }
        }

        const handleEscape = (
            event: KeyboardEvent,
        ) => {
            if (event.key === 'Escape') {
                setOpen(false)
                setQuery('')
            }
        }

        document.addEventListener(
            'mousedown',
            handleOutsideClick,
        )

        document.addEventListener(
            'keydown',
            handleEscape,
        )

        return () => {
            document.removeEventListener(
                'mousedown',
                handleOutsideClick,
            )

            document.removeEventListener(
                'keydown',
                handleEscape,
            )
        }
    }, [])

    useEffect(() => {
        if (open) {
            searchRef.current?.focus()
        }
    }, [open])

    const SelectedIcon =
        resolveAgentStatusIcon(value)

    return (
        <div ref={rootRef} className="relative">
            <button
                type="button"
                role="combobox"
                aria-expanded={open}
                onClick={() => {
                    setOpen((current) => !current)
                    setQuery('')
                }}
                className={`flex h-11 w-full items-center rounded-xl border bg-white px-3.5 text-left text-sm outline-none transition ${
                    open
                        ? 'border-sky-400 ring-4 ring-sky-100'
                        : 'border-gray-200 hover:border-gray-300'
                }`}
            >
                <SelectedIcon className="mr-3 h-4 w-4 shrink-0 text-gray-500" />

                <span className="min-w-0 flex-1 truncate text-gray-900">
                    {formatAgentStatusIconName(
                        value,
                    )}
                </span>

                <ChevronDown
                    className={`ml-3 h-4 w-4 shrink-0 text-gray-400 transition ${
                        open ? 'rotate-180' : ''
                    }`}
                />
            </button>

            {open ? (
                <div className="absolute left-0 right-0 z-50 mt-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
                    <div className="border-b border-gray-100 p-3">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                            <input
                                ref={searchRef}
                                type="search"
                                value={query}
                                onChange={(event) =>
                                    setQuery(
                                        event.target
                                            .value,
                                    )
                                }
                                placeholder="Search icons..."
                                className={`${fieldClassName} pl-10 pr-9`}
                            />

                            {query !== '' ? (
                                <button
                                    type="button"
                                    onClick={() =>
                                        setQuery('')
                                    }
                                    aria-label="Clear icon search"
                                    className="absolute right-3 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                >
                                    <X className="h-3.5 w-3.5" />
                                </button>
                            ) : null}
                        </div>
                    </div>

                    <div className="max-h-72 overflow-y-auto p-2">
                        {filteredOptions.length >
                        0 ? (
                            filteredOptions.map(
                                (option) => {
                                    const OptionIcon =
                                        resolveAgentStatusIcon(
                                            option,
                                        )

                                    const selected =
                                        option === value

                                    return (
                                        <button
                                            key={option}
                                            type="button"
                                            onClick={() => {
                                                onChange(
                                                    option,
                                                )
                                                setOpen(
                                                    false,
                                                )
                                                setQuery('')
                                            }}
                                            className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition ${
                                                selected
                                                    ? 'bg-sky-50 font-semibold text-sky-700'
                                                    : 'text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100">
                                                <OptionIcon className="h-4 w-4" />
                                            </span>

                                            <span className="min-w-0 flex-1 truncate">
                                                {formatAgentStatusIconName(
                                                    option,
                                                )}
                                            </span>

                                            {selected ? (
                                                <Check className="h-4 w-4 shrink-0 text-sky-600" />
                                            ) : null}
                                        </button>
                                    )
                                },
                            )
                        ) : (
                            <div className="px-4 py-10 text-center">
                                <Search className="mx-auto h-8 w-8 text-gray-300" />

                                <p className="mt-3 text-sm text-gray-500">
                                    No icons found.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            ) : null}
        </div>
    )
}

function ColorField({
                        value,
                        onChange,
                    }: {
    value: string
    onChange: (value: string) => void
}) {
    const pickerValue = isValidHex(value)
        ? value
        : '#2563EB'

    return (
        <div>
            <div className="flex gap-2">
                <label className="relative flex h-11 w-12 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:border-gray-300">
                    <input
                        type="color"
                        value={pickerValue}
                        onChange={(event) =>
                            onChange(
                                event.target.value.toUpperCase(),
                            )
                        }
                        className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                    />

                    <Palette
                        className="h-5 w-5"
                        style={{
                            color: pickerValue,
                        }}
                    />
                </label>

                <div className="relative flex-1">
                    <span
                        className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 rounded-full ring-1 ring-inset ring-black/10"
                        style={{
                            backgroundColor:
                            pickerValue,
                        }}
                    />

                    <input
                        type="text"
                        name="color"
                        value={value}
                        onChange={(event) =>
                            onChange(
                                event.target.value.toUpperCase(),
                            )
                        }
                        placeholder="#2563EB"
                        maxLength={7}
                        className={`${fieldClassName} pl-10 font-mono uppercase`}
                    />
                </div>
            </div>

            <div className="mt-3 flex flex-wrap gap-2">
                {colorPalette.map(
                    (paletteColor) => (
                        <button
                            key={paletteColor}
                            type="button"
                            onClick={() =>
                                onChange(
                                    paletteColor,
                                )
                            }
                            title={paletteColor}
                            aria-label={`Select ${paletteColor}`}
                            className={`h-7 w-7 rounded-full ring-offset-2 transition hover:scale-110 ${
                                paletteColor ===
                                pickerValue.toUpperCase()
                                    ? 'ring-2 ring-gray-900'
                                    : 'ring-1 ring-black/10'
                            }`}
                            style={{
                                backgroundColor:
                                paletteColor,
                            }}
                        />
                    ),
                )}
            </div>
        </div>
    )
}

function StatusPreview({
                           name,
                           description,
                           icon,
                           color,
                           availability,
                           routingEligibility,
                           duration,
                           isActive,
                           isSelectable,
                           isDefault,
                       }: {
    name: string
    description: string
    icon: string
    color: string
    availability: string
    routingEligibility: string
    duration: string
    isActive: boolean
    isSelectable: boolean
    isDefault: boolean
}) {
    const Icon = resolveAgentStatusIcon(icon)

    return (
        <aside className="rounded-3xl border border-gray-200 bg-gray-50/70 p-5">
            <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                Preview
            </div>

            <div className="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div className="flex items-start gap-3">
                    <span
                        className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                        style={{
                            color,
                            backgroundColor:
                                withAlpha(color, '14'),
                            boxShadow: `inset 0 0 0 1px ${withAlpha(
                                color,
                                '30',
                            )}`,
                        }}
                    >
                        <Icon className="h-6 w-6" />
                    </span>

                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h3 className="font-semibold text-gray-900">
                                {name.trim() ||
                                    'Untitled status'}
                            </h3>

                            {isDefault ? (
                                <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                    <Star className="h-3 w-3 fill-current" />
                                    Default
                                </span>
                            ) : null}
                        </div>

                        <p className="mt-1 text-sm leading-6 text-gray-500">
                            {description.trim() ||
                                'No description provided.'}
                        </p>
                    </div>
                </div>

                <div className="mt-5 flex flex-wrap gap-2">
                    <SmallBadge
                        label={formatKey(
                            availability,
                        )}
                        colorClass={
                            availability ===
                            'available'
                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                : availability ===
                                'limited'
                                    ? 'bg-amber-50 text-amber-700 ring-amber-200'
                                    : 'bg-rose-50 text-rose-700 ring-rose-200'
                        }
                    />

                    <SmallBadge
                        label={formatKey(
                            routingEligibility,
                        )}
                        colorClass={
                            routingEligibility ===
                            'eligible'
                                ? 'bg-sky-50 text-sky-700 ring-sky-200'
                                : routingEligibility ===
                                'fallback'
                                    ? 'bg-violet-50 text-violet-700 ring-violet-200'
                                    : 'bg-gray-100 text-gray-600 ring-gray-200'
                        }
                    />
                </div>

                <div className="mt-5 grid grid-cols-2 gap-3 border-t border-gray-100 pt-4 text-xs">
                    <PreviewProperty
                        label="Duration"
                        value={
                            duration
                                ? `${duration} min`
                                : 'No reset'
                        }
                    />

                    <PreviewProperty
                        label="State"
                        value={
                            isActive
                                ? 'Active'
                                : 'Inactive'
                        }
                    />

                    <PreviewProperty
                        label="Agent selectable"
                        value={
                            isSelectable
                                ? 'Yes'
                                : 'No'
                        }
                    />

                    <PreviewProperty
                        label="Icon key"
                        value={icon}
                    />
                </div>
            </div>
        </aside>
    )
}

function ChoiceCard({
                        selected,
                        meta,
                        onClick,
                    }: {
    selected: boolean
    meta: ChoiceMeta
    onClick: () => void
}) {
    const Icon = meta.icon

    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={selected}
            className={`relative rounded-2xl border p-5 text-left transition ${
                selected
                    ? meta.selectedClassName
                    : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
            }`}
        >
            <div className="flex items-start gap-3">
                <span
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${meta.iconClassName}`}
                >
                    <Icon className="h-5 w-5" />
                </span>

                <span className="min-w-0 flex-1">
                    <span className="block font-semibold text-gray-900">
                        {meta.label}
                    </span>

                    <span className="mt-1 block text-xs leading-5 text-gray-500">
                        {meta.description}
                    </span>
                </span>

                <span
                    className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border ${
                        selected
                            ? 'border-sky-600 bg-sky-600 text-white'
                            : 'border-gray-300 bg-white'
                    }`}
                >
                    {selected ? (
                        <Check className="h-3.5 w-3.5" />
                    ) : null}
                </span>
            </div>
        </button>
    )
}

function DurationOption({
                            label,
                            description,
                            selected,
                            onClick,
                        }: {
    label: string
    description: string
    selected: boolean
    onClick: () => void
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={selected}
            className={`rounded-2xl border p-4 text-left transition ${
                selected
                    ? 'border-sky-300 bg-sky-50 ring-1 ring-inset ring-sky-200'
                    : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
            }`}
        >
            <div className="flex items-start justify-between gap-3">
                <div>
                    <div className="text-sm font-semibold text-gray-900">
                        {label}
                    </div>

                    <div className="mt-1 text-xs leading-5 text-gray-500">
                        {description}
                    </div>
                </div>

                <span
                    className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border ${
                        selected
                            ? 'border-sky-600 bg-sky-600 text-white'
                            : 'border-gray-300 bg-white'
                    }`}
                >
                    {selected ? (
                        <Check className="h-3.5 w-3.5" />
                    ) : null}
                </span>
            </div>
        </button>
    )
}

function ToggleCard({
                        title,
                        description,
                        enabled,
                        onChange,
                        icon: Icon,
                    }: {
    title: string
    description: string
    enabled: boolean
    onChange: (enabled: boolean) => void
    icon?: LucideIcon
}) {
    return (
        <button
            type="button"
            onClick={() => onChange(!enabled)}
            aria-pressed={enabled}
            className={`flex items-center justify-between gap-4 rounded-2xl border p-4 text-left transition ${
                enabled
                    ? 'border-sky-200 bg-sky-50/70'
                    : 'border-gray-200 bg-gray-50/70'
            }`}
        >
            <div className="flex min-w-0 items-start gap-3">
                {Icon ? (
                    <Icon
                        className={`mt-0.5 h-5 w-5 shrink-0 ${
                            enabled
                                ? 'text-amber-600'
                                : 'text-gray-400'
                        }`}
                    />
                ) : null}

                <div>
                    <div className="text-sm font-semibold text-gray-900">
                        {title}
                    </div>

                    <div className="mt-1 text-xs leading-5 text-gray-500">
                        {description}
                    </div>
                </div>
            </div>

            <Toggle enabled={enabled} />
        </button>
    )
}

function Toggle({
                    enabled,
                }: {
    enabled: boolean
}) {
    return (
        <span
            aria-hidden="true"
            className={`relative inline-flex h-6 w-11 shrink-0 rounded-full transition ${
                enabled
                    ? 'bg-sky-600'
                    : 'bg-gray-300'
            }`}
        >
            <span
                className={`absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition ${
                    enabled
                        ? 'translate-x-5'
                        : 'translate-x-0'
                }`}
            />
        </span>
    )
}

function SectionHeader({
                           title,
                           description,
                           icon: Icon,
                       }: {
    title: string
    description: string
    icon: LucideIcon
}) {
    return (
        <div className="border-b border-gray-200 bg-gray-50/70 px-6 py-5">
            <div className="flex items-start gap-3">
                <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100">
                    <Icon className="h-5 w-5 text-sky-600" />
                </div>

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

function Field({
                   label,
                   required = false,
                   hint,
                   error,
                   className = '',
                   children,
               }: {
    label: string
    required?: boolean
    hint?: string
    error?: string
    className?: string
    children: ReactNode
}) {
    return (
        <label className={`block ${className}`}>
            <span className="mb-2 block text-sm font-semibold text-gray-700">
                {label}

                {required ? (
                    <span className="ml-1 text-rose-500">
                        *
                    </span>
                ) : null}
            </span>

            {children}

            {hint && !error ? (
                <span className="mt-1.5 block text-xs leading-5 text-gray-400">
                    {hint}
                </span>
            ) : null}

            <ErrorMessage value={error} />
        </label>
    )
}

function ErrorMessage({
                          value,
                      }: {
    value?: string
}) {
    if (!value) {
        return null
    }

    return (
        <p className="mt-1.5 text-sm font-medium text-rose-600">
            {value}
        </p>
    )
}

function SmallBadge({
                        label,
                        colorClass,
                    }: {
    label: string
    colorClass: string
}) {
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 ring-inset ${colorClass}`}
        >
            {label}
        </span>
    )
}

function PreviewProperty({
                             label,
                             value,
                         }: {
    label: string
    value: string
}) {
    return (
        <div>
            <div className="font-medium text-gray-400">
                {label}
            </div>

            <div className="mt-1 truncate font-semibold text-gray-700">
                {value}
            </div>
        </div>
    )
}

function availabilityMeta(
    value: string,
): ChoiceMeta {
    if (value === 'available') {
        return {
            label: 'Available',
            description:
                'The agent is ready to work normally.',
            icon: CheckCircle2,
            iconClassName:
                'bg-emerald-100 text-emerald-700',
            selectedClassName:
                'border-emerald-300 bg-emerald-50 ring-1 ring-inset ring-emerald-200',
        }
    }

    if (value === 'limited') {
        return {
            label: 'Limited',
            description:
                'The agent can work, but with reduced availability.',
            icon: CircleDot,
            iconClassName:
                'bg-amber-100 text-amber-700',
            selectedClassName:
                'border-amber-300 bg-amber-50 ring-1 ring-inset ring-amber-200',
        }
    }

    if (value === 'unavailable') {
        return {
            label: 'Unavailable',
            description:
                'The agent should not receive normal work.',
            icon: Ban,
            iconClassName:
                'bg-rose-100 text-rose-700',
            selectedClassName:
                'border-rose-300 bg-rose-50 ring-1 ring-inset ring-rose-200',
        }
    }

    return {
        label: formatKey(value),
        description:
            'Custom availability classification.',
        icon: Activity,
        iconClassName:
            'bg-gray-100 text-gray-600',
        selectedClassName:
            'border-sky-300 bg-sky-50 ring-1 ring-inset ring-sky-200',
    }
}

function routingMeta(
    value: string,
): ChoiceMeta {
    if (value === 'eligible') {
        return {
            label: 'Eligible',
            description:
                'The agent can receive new work normally.',
            icon: CheckCircle2,
            iconClassName:
                'bg-sky-100 text-sky-700',
            selectedClassName:
                'border-sky-300 bg-sky-50 ring-1 ring-inset ring-sky-200',
        }
    }

    if (value === 'fallback') {
        return {
            label: 'Fallback',
            description:
                'Use the agent only when preferred candidates are unavailable.',
            icon: CircleDot,
            iconClassName:
                'bg-violet-100 text-violet-700',
            selectedClassName:
                'border-violet-300 bg-violet-50 ring-1 ring-inset ring-violet-200',
        }
    }

    if (value === 'blocked') {
        return {
            label: 'Blocked',
            description:
                'The agent cannot receive newly routed work.',
            icon: CircleSlash,
            iconClassName:
                'bg-gray-200 text-gray-600',
            selectedClassName:
                'border-gray-400 bg-gray-100 ring-1 ring-inset ring-gray-300',
        }
    }

    return {
        label: formatKey(value),
        description:
            'Custom routing eligibility policy.',
        icon: Route,
        iconClassName:
            'bg-gray-100 text-gray-600',
        selectedClassName:
            'border-sky-300 bg-sky-50 ring-1 ring-inset ring-sky-200',
    }
}

function resolveDurationMode(
    duration?: number | null,
): DurationMode {
    if (!duration) {
        return 'none'
    }

    if (
        duration === 15 ||
        duration === 30 ||
        duration === 60
    ) {
        return String(
            duration,
        ) as DurationMode
    }

    return 'custom'
}

function resolveCustomDuration(
    duration?: number | null,
): string {
    if (
        duration &&
        duration !== 15 &&
        duration !== 30 &&
        duration !== 60
    ) {
        return String(duration)
    }

    return ''
}

function formatKey(value: string): string {
    return value
        .replace(/[-_]+/g, ' ')
        .trim()
        .split(/\s+/)
        .map(
            (part) =>
                part.charAt(0).toUpperCase() +
                part.slice(1),
        )
        .join(' ')
}

function isValidHex(value: string): boolean {
    return /^#[0-9A-Fa-f]{6}$/.test(value)
}

function withAlpha(
    color: string,
    alpha: string,
): string {
    if (isValidHex(color)) {
        return `${color}${alpha}`
    }

    return color
}

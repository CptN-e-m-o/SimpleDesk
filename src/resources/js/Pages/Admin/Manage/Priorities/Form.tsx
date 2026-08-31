import {
    Form,
    Head,
    Link,
} from '@inertiajs/react'
import {
    ArrowLeft,
    Check,
    Info,
    Save,
    Star,
} from 'lucide-react'
import {
    type ReactNode,
    useState,
} from 'react'
import { route } from 'ziggy-js'

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'
import AdminLayout from '@/Layouts/AdminLayout'

type Priority = {
    id: number
    name: string
    slug: string
    description: string | null
    color: string
    visibility: string
    is_active: boolean
    is_default: boolean
}

type Props = {
    priority?: Priority
    visibilityOptions: string[]
}

export default function PriorityForm({
                                         priority,
                                         visibilityOptions,
                                     }: Props) {
    const editing = Boolean(priority)
    const lockedDefault =
        Boolean(priority?.is_default)

    const [color, setColor] = useState(
        priority?.color ?? '#2563EB',
    )

    const [visibility, setVisibility] =
        useState(
            priority?.visibility ?? 'public',
        )

    const [active, setActive] = useState(
        priority?.is_active ?? true,
    )

    const [
        makeDefault,
        setMakeDefault,
    ] = useState(
        priority?.is_default ?? false,
    )

    const validColor =
        /^#[0-9A-Fa-f]{6}$/.test(color)

    const canBecomeDefault =
        active &&
        visibility === 'public'

    function updateVisibility(
        value: string,
    ) {
        if (lockedDefault) {
            return
        }

        setVisibility(value)

        if (value !== 'public') {
            setMakeDefault(false)
        }
    }

    function updateActive(
        value: boolean,
    ) {
        if (lockedDefault) {
            return
        }

        setActive(value)

        if (!value) {
            setMakeDefault(false)
        }
    }

    return (
        <AdminLayout
            title={
                editing
                    ? 'Edit Priority'
                    : 'Create Priority'
            }
        >
            <Head
                title={
                    editing
                        ? 'Edit Priority'
                        : 'Create Priority'
                }
            />

            <Form
                action={
                    editing
                        ? route(
                            'admin.manage.priorities.update',
                            priority!.id,
                        )
                        : route(
                            'admin.manage.priorities.store',
                        )
                }
                method={
                    editing
                        ? 'put'
                        : 'post'
                }
            >
                {({
                      errors,
                      processing,
                  }) => (
                    <div className="mx-auto max-w-4xl space-y-6">
                        <section className="flex flex-col gap-4 rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    {editing
                                        ? 'Edit priority'
                                        : 'Create priority'}
                                </h1>

                                <p className="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                                    Configure how this
                                    urgency level appears
                                    and where it can be
                                    selected.
                                </p>
                            </div>

                            <Link
                                href={route(
                                    'admin.manage.priorities.index',
                                )}
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Back
                            </Link>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-100 px-6 py-5">
                                <h2 className="font-semibold text-gray-900">
                                    General
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Basic identity and
                                    presentation settings.
                                </p>
                            </div>

                            <div className="space-y-6 p-6">
                                <Field
                                    label="Name"
                                    required
                                    error={
                                        errors.name
                                    }
                                >
                                    <input
                                        name="name"
                                        defaultValue={
                                            priority?.name ??
                                            ''
                                        }
                                        required
                                        maxLength={
                                            120
                                        }
                                        placeholder="For example: Urgent"
                                        className={
                                            inputClass
                                        }
                                    />
                                </Field>

                                {editing &&
                                    priority?.slug && (
                                        <div>
                                            <div className="text-sm font-medium text-gray-800">
                                                Identifier
                                            </div>

                                            <div className="mt-2 flex flex-col gap-1 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 sm:flex-row sm:items-center sm:gap-3">
                                                <code className="text-sm font-semibold text-gray-700">
                                                    {
                                                        priority.slug
                                                    }
                                                </code>

                                                <span className="text-xs text-gray-400">
                                                    Stable
                                                    machine
                                                    identifier
                                                    used by
                                                    APIs and
                                                    configuration.
                                                </span>
                                            </div>
                                        </div>
                                    )}

                                <Field
                                    label="Description"
                                    error={
                                        errors.description
                                    }
                                >
                                    <textarea
                                        name="description"
                                        defaultValue={
                                            priority?.description ??
                                            ''
                                        }
                                        rows={4}
                                        maxLength={
                                            1000
                                        }
                                        placeholder="Explain when this priority should be used."
                                        className={
                                            inputClass
                                        }
                                    />
                                </Field>

                                <div className="grid gap-6 md:grid-cols-2">
                                    <Field
                                        label="Color"
                                        required
                                        error={
                                            errors.color
                                        }
                                    >
                                        <div className="mt-2 flex gap-3">
                                            <input
                                                type="color"
                                                value={
                                                    validColor
                                                        ? color
                                                        : '#2563EB'
                                                }
                                                onChange={(
                                                    event,
                                                ) =>
                                                    setColor(
                                                        event.target.value.toUpperCase(),
                                                    )
                                                }
                                                aria-label="Choose priority color"
                                                className="h-11 w-14 shrink-0 cursor-pointer rounded-xl border border-gray-200 bg-white p-1"
                                            />

                                            <div className="relative flex-1">
                                                <span
                                                    className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 rounded-md ring-1 ring-inset ring-black/10"
                                                    style={{
                                                        backgroundColor:
                                                            validColor
                                                                ? color
                                                                : '#FFFFFF',
                                                    }}
                                                />

                                                <input
                                                    name="color"
                                                    value={
                                                        color
                                                    }
                                                    onChange={(
                                                        event,
                                                    ) =>
                                                        setColor(
                                                            event.target.value,
                                                        )
                                                    }
                                                    onBlur={() => {
                                                        if (
                                                            validColor
                                                        ) {
                                                            setColor(
                                                                color.toUpperCase(),
                                                            )
                                                        }
                                                    }}
                                                    placeholder="#2563EB"
                                                    className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-3.5 text-sm font-medium uppercase text-gray-900 outline-none transition placeholder:font-normal placeholder:text-gray-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                                />
                                            </div>
                                        </div>
                                    </Field>

                                    <Field
                                        label="Visibility"
                                        required
                                        error={
                                            errors.visibility
                                        }
                                    >
                                        <input
                                            type="hidden"
                                            name="visibility"
                                            value={
                                                visibility
                                            }
                                        />

                                        <div className="mt-2">
                                            <Select
                                                value={
                                                    visibility
                                                }
                                                disabled={
                                                    lockedDefault
                                                }
                                                onValueChange={
                                                    updateVisibility
                                                }
                                            >
                                                <SelectTrigger className="!h-11 w-full rounded-xl border-gray-200 bg-white px-3.5 text-sm font-medium text-gray-700 shadow-none transition hover:border-gray-300 hover:bg-gray-50 focus-visible:border-sky-400 focus-visible:ring-4 focus-visible:ring-sky-100 disabled:bg-gray-100 disabled:text-gray-500">
                                                    <SelectValue />
                                                </SelectTrigger>

                                                <SelectContent
                                                    position="popper"
                                                    align="start"
                                                    className="min-w-[220px] rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl shadow-gray-900/10"
                                                >
                                                    {visibilityOptions.map(
                                                        (
                                                            value,
                                                        ) => (
                                                            <SelectItem
                                                                key={
                                                                    value
                                                                }
                                                                value={
                                                                    value
                                                                }
                                                                className="cursor-pointer rounded-lg px-3 py-2.5 text-sm text-gray-700 focus:bg-gray-100 focus:text-gray-900"
                                                            >
                                                                <span className="flex items-center gap-2.5">
                                                                    <span
                                                                        className={`h-2 w-2 rounded-full ${
                                                                            value ===
                                                                            'public'
                                                                                ? 'bg-sky-500'
                                                                                : 'bg-violet-500'
                                                                        }`}
                                                                    />

                                                                    {value ===
                                                                    'public'
                                                                        ? 'Public'
                                                                        : 'Internal'}
                                                                </span>
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <p className="mt-2 text-xs leading-5 text-gray-500">
                                            Public
                                            priorities may
                                            be offered to
                                            requesters.
                                            Internal
                                            priorities are
                                            available only
                                            to staff and
                                            internal
                                            workflows.
                                        </p>
                                    </Field>
                                </div>
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-100 px-6 py-5">
                                <h2 className="font-semibold text-gray-900">
                                    Availability
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Control whether the
                                    priority can be used
                                    for new ticket
                                    assignments.
                                </p>
                            </div>

                            <div className="space-y-4 p-6">
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value={
                                        active
                                            ? '1'
                                            : '0'
                                    }
                                />

                                <ToggleCard
                                    title="Active"
                                    description={
                                        lockedDefault
                                            ? 'The current default priority must remain active.'
                                            : 'Available for new priority assignments.'
                                    }
                                    checked={
                                        active
                                    }
                                    disabled={
                                        lockedDefault
                                    }
                                    onChange={() =>
                                        updateActive(
                                            !active,
                                        )
                                    }
                                />

                                <input
                                    type="hidden"
                                    name="is_default"
                                    value={
                                        makeDefault
                                            ? '1'
                                            : '0'
                                    }
                                />

                                {lockedDefault ? (
                                    <div className="flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4">
                                        <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                                            <Star className="h-4 w-4 fill-current" />
                                        </div>

                                        <div>
                                            <div className="text-sm font-semibold text-sky-900">
                                                Current
                                                default
                                                priority
                                            </div>

                                            <p className="mt-1 text-xs leading-5 text-sky-700">
                                                This
                                                priority
                                                cannot be
                                                disabled or
                                                made
                                                internal
                                                while it is
                                                the system
                                                default.
                                                Select
                                                another
                                                active,
                                                public
                                                priority as
                                                default
                                                first.
                                            </p>
                                        </div>
                                    </div>
                                ) : (
                                    <ToggleCard
                                        title="Make default"
                                        description="Use this priority when a ticket source does not provide an explicit priority."
                                        checked={
                                            makeDefault
                                        }
                                        disabled={
                                            !canBecomeDefault
                                        }
                                        accent
                                        onChange={() =>
                                            setMakeDefault(
                                                !makeDefault,
                                            )
                                        }
                                    />
                                )}

                                {!lockedDefault &&
                                    !canBecomeDefault && (
                                        <div className="flex items-start gap-2 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                            <Info className="mt-0.5 h-4 w-4 shrink-0" />

                                            <span>
                                                A default
                                                priority
                                                must be
                                                both
                                                active and
                                                public.
                                            </span>
                                        </div>
                                    )}
                            </div>
                        </section>

                        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <Link
                                href={route(
                                    'admin.manage.priorities.index',
                                )}
                                className="inline-flex h-11 items-center justify-center rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                            >
                                Cancel
                            </Link>

                            <button
                                type="submit"
                                disabled={
                                    processing
                                }
                                className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <Save className="h-4 w-4" />

                                {processing
                                    ? 'Saving...'
                                    : editing
                                        ? 'Save changes'
                                        : 'Create priority'}
                            </button>
                        </div>
                    </div>
                )}
            </Form>
        </AdminLayout>
    )
}

const inputClass =
    'mt-2 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100'

function Field({
                   label,
                   required = false,
                   error,
                   children,
               }: {
    label: string
    required?: boolean
    error?: string
    children: ReactNode
}) {
    return (
        <label className="block">
            <span className="text-sm font-medium text-gray-800">
                {label}

                {required && (
                    <span className="ml-1 text-rose-500">
                        *
                    </span>
                )}
            </span>

            {children}

            {error && (
                <span className="mt-1.5 block text-xs font-medium text-rose-600">
                    {error}
                </span>
            )}
        </label>
    )
}

function ToggleCard({
                        title,
                        description,
                        checked,
                        disabled = false,
                        accent = false,
                        onChange,
                    }: {
    title: string
    description: string
    checked: boolean
    disabled?: boolean
    accent?: boolean
    onChange: () => void
}) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onChange}
            aria-pressed={checked}
            className={`flex w-full items-center justify-between gap-4 rounded-2xl border p-4 text-left transition ${
                accent
                    ? 'border-sky-200 bg-sky-50/60'
                    : 'border-gray-200 bg-white'
            } ${
                disabled
                    ? 'cursor-not-allowed opacity-70'
                    : 'hover:border-gray-300 hover:bg-gray-50/50'
            }`}
        >
            <div>
                <div className="text-sm font-semibold text-gray-900">
                    {title}
                </div>

                <p className="mt-1 text-xs leading-5 text-gray-500">
                    {description}
                </p>
            </div>

            <span
                className={`flex h-6 w-11 shrink-0 items-center rounded-full p-0.5 transition ${
                    checked
                        ? 'bg-sky-600'
                        : 'bg-gray-200'
                }`}
            >
                <span
                    className={`flex h-5 w-5 items-center justify-center rounded-full bg-white shadow-sm transition ${
                        checked
                            ? 'translate-x-5'
                            : 'translate-x-0'
                    }`}
                >
                    {checked && (
                        <Check className="h-3 w-3 text-sky-600" />
                    )}
                </span>
            </span>
        </button>
    )
}

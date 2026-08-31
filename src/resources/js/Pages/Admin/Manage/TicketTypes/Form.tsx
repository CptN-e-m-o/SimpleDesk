import {
    Form,
    Head,
    Link,
} from '@inertiajs/react'
import {
    ArrowLeft,
    Check,
    Save,
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

type TicketType = {
    id: number
    name: string
    slug: string
    description: string | null
    visibility: string
    is_active: boolean
}

type Props = {
    ticketType?: TicketType
    visibilityOptions: string[]
}

export default function TicketTypeForm({
                                           ticketType,
                                           visibilityOptions,
                                       }: Props) {
    const editing = Boolean(ticketType)

    const [visibility, setVisibility] =
        useState(
            ticketType?.visibility ??
            'public',
        )

    const [active, setActive] =
        useState(
            ticketType?.is_active ??
            true,
        )

    return (
        <AdminLayout
            title={
                editing
                    ? 'Edit Ticket Type'
                    : 'Create Ticket Type'
            }
        >
            <Head
                title={
                    editing
                        ? 'Edit Ticket Type'
                        : 'Create Ticket Type'
                }
            />

            <Form
                action={
                    editing
                        ? route(
                            'admin.manage.ticket-types.update',
                            ticketType!.id,
                        )
                        : route(
                            'admin.manage.ticket-types.store',
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
                                        ? 'Edit ticket type'
                                        : 'Create ticket type'}
                                </h1>

                                <p className="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                                    Ticket types
                                    classify
                                    requests.
                                    Routing, forms,
                                    SLA and
                                    automations are
                                    configured
                                    separately.
                                </p>
                            </div>

                            <Link
                                href={route(
                                    'admin.manage.ticket-types.index',
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
                                    Basic
                                    classification
                                    identity and
                                    visibility
                                    settings.
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
                                            ticketType?.name ??
                                            ''
                                        }
                                        required
                                        maxLength={
                                            120
                                        }
                                        placeholder="For example: Incident"
                                        className={
                                            inputClass
                                        }
                                    />
                                </Field>

                                {editing &&
                                    ticketType?.slug && (
                                        <div>
                                            <div className="text-sm font-medium text-gray-800">
                                                Identifier
                                            </div>

                                            <div className="mt-2 flex flex-col gap-1 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3 sm:flex-row sm:items-center sm:gap-3">
                                                <code className="text-sm font-semibold text-gray-700">
                                                    {
                                                        ticketType.slug
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
                                            ticketType?.description ??
                                            ''
                                        }
                                        rows={4}
                                        maxLength={
                                            1000
                                        }
                                        placeholder="Explain what kind of requests belong to this type."
                                        className={
                                            inputClass
                                        }
                                    />
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
                                            onValueChange={
                                                setVisibility
                                            }
                                        >
                                            <SelectTrigger className="!h-11 w-full rounded-xl border-gray-200 bg-white px-3.5 text-sm font-medium text-gray-700 shadow-none transition hover:border-gray-300 hover:bg-gray-50 focus-visible:border-sky-400 focus-visible:ring-4 focus-visible:ring-sky-100">
                                                <SelectValue />
                                            </SelectTrigger>

                                            <SelectContent
                                                position="popper"
                                                align="start"
                                                className="min-w-[240px] rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl shadow-gray-900/10"
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
                                        ticket types
                                        may be
                                        exposed to
                                        requester-facing
                                        forms.
                                        Internal
                                        types remain
                                        available to
                                        staff,
                                        automation
                                        and
                                        authorized
                                        integrations.
                                    </p>
                                </Field>
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-100 px-6 py-5">
                                <h2 className="font-semibold text-gray-900">
                                    Availability
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Control
                                    whether this
                                    type can be
                                    selected for
                                    new ticket
                                    classifications.
                                </p>
                            </div>

                            <div className="p-6">
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value={
                                        active
                                            ? '1'
                                            : '0'
                                    }
                                />

                                <button
                                    type="button"
                                    onClick={() =>
                                        setActive(
                                            !active,
                                        )
                                    }
                                    aria-pressed={
                                        active
                                    }
                                    className="flex w-full items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white p-4 text-left transition hover:border-gray-300 hover:bg-gray-50/50"
                                >
                                    <div>
                                        <div className="text-sm font-semibold text-gray-900">
                                            Active
                                        </div>

                                        <p className="mt-1 text-xs leading-5 text-gray-500">
                                            Available
                                            for new
                                            ticket
                                            classifications.
                                            Existing
                                            tickets
                                            keep the
                                            type even
                                            when it is
                                            inactive.
                                        </p>
                                    </div>

                                    <span
                                        className={`flex h-6 w-11 shrink-0 items-center rounded-full p-0.5 transition ${
                                            active
                                                ? 'bg-sky-600'
                                                : 'bg-gray-200'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-5 w-5 items-center justify-center rounded-full bg-white shadow-sm transition ${
                                                active
                                                    ? 'translate-x-5'
                                                    : 'translate-x-0'
                                            }`}
                                        >
                                            {active && (
                                                <Check className="h-3 w-3 text-sky-600" />
                                            )}
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </section>

                        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <Link
                                href={route(
                                    'admin.manage.ticket-types.index',
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
                                        : 'Create ticket type'}
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

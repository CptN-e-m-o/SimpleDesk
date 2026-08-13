import type { ChangeEvent } from 'react'
import type { LucideIcon } from 'lucide-react'

import {
    ArrowLeft,
    Check,
    CheckCircle2,
    CircleDot,
    Hash,
    Info,
    ListChecks,
    Save,
    Sparkles,
} from 'lucide-react'
import {
    Head,
    Link,
    useForm,
} from '@inertiajs/react'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout'
import SkillRuleBuilder from './SkillRuleBuilder'
import type {
    RuleField,
    Skill,
    SkillRule,
} from './skillTypes'

type Props = {
    skill?: Skill
    matchTypeOptions: string[]
    ruleSchema: RuleField[]
}

type FormData = {
    name: string
    description: string
    match_type: 'any' | 'all'
    is_active: boolean
    sort_order: number
    rules: SkillRule[]
}

export default function SkillForm({
                                      skill,
                                      ruleSchema,
                                  }: Props) {
    const editing = Boolean(skill)

    const firstField = ruleSchema[0]

    const initialRules: SkillRule[] =
        skill?.rules?.length
            ? skill.rules
            : firstField
                ? [
                    {
                        field_key: firstField.key,
                        operator:
                            firstField.operators[0],
                        value: null,
                    },
                ]
                : []

    const form = useForm<FormData>({
        name: skill?.name ?? '',
        description:
            skill?.description ?? '',
        match_type:
            skill?.match_type ?? 'any',
        is_active:
            skill?.is_active ?? true,
        sort_order:
            skill?.sort_order ?? 0,
        rules: initialRules,
    })

    const submit = () => {
        if (editing && skill) {
            form.put(
                route(
                    'admin.skills.update',
                    skill.id,
                ),
                {
                    preserveScroll: true,
                },
            )

            return
        }

        form.post(
            route('admin.skills.store'),
            {
                preserveScroll: true,
            },
        )
    }

    const fieldLabel = (
        key: string,
    ): string =>
        ruleSchema.find(
            (field) => field.key === key,
        )?.label ?? formatLabel(key)

    const activeRuleCount =
        form.data.rules.length

    return (
        <AdminLayout
            title={
                editing
                    ? 'Edit Skill'
                    : 'Create Skill'
            }
        >
            <Head
                title={
                    editing
                        ? 'Edit Skill'
                        : 'Create Skill'
                }
            />

            <div className="pb-28">
                <div className="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
                    <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="flex flex-col justify-between gap-5 bg-gradient-to-r from-violet-50/80 via-white to-white p-6 sm:flex-row sm:items-center">
                            <div className="flex items-start gap-4">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-100 ring-1 ring-inset ring-violet-200">
                                    <Sparkles className="h-6 w-6 text-violet-700" />
                                </div>

                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                            {editing
                                                ? 'Edit Skill'
                                                : 'Create Skill'}
                                        </h1>

                                        {editing &&
                                        skill ? (
                                            <span className="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 ring-1 ring-inset ring-gray-200">
                                                Version{' '}
                                                {
                                                    skill.version
                                                }
                                            </span>
                                        ) : null}
                                    </div>

                                    <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                        Define a reusable set
                                        of ticket conditions
                                        for classification and
                                        future skill-based
                                        routing.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route(
                                    'admin.skills.index',
                                )}
                                className="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Back to skills
                            </Link>
                        </div>
                    </header>

                    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <SectionHeader
                            icon={Info}
                            title="General information"
                            description="Name and organize this ticket classification rule."
                        />

                        <div className="grid gap-6 p-5 sm:p-6 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <FieldLabel
                                    label="Name"
                                    required
                                />

                                <input
                                    type="text"
                                    value={
                                        form.data.name
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        form.setData(
                                            'name',
                                            event.target
                                                .value,
                                        )
                                    }
                                    maxLength={100}
                                    placeholder="e.g. VIP Billing"
                                    className={inputClass(
                                        Boolean(
                                            form.errors
                                                .name,
                                        ),
                                    )}
                                />

                                <div className="mt-1.5 flex items-start justify-between gap-4">
                                    <Error
                                        text={
                                            form.errors
                                                .name
                                        }
                                    />

                                    <span className="ml-auto shrink-0 text-xs text-gray-400">
                                        {
                                            form.data.name
                                                .length
                                        }
                                        /100
                                    </span>
                                </div>
                            </div>

                            <div className="md:col-span-2">
                                <FieldLabel label="Description" />

                                <textarea
                                    value={
                                        form.data
                                            .description
                                    }
                                    onChange={(
                                        event,
                                    ) =>
                                        form.setData(
                                            'description',
                                            event.target
                                                .value,
                                        )
                                    }
                                    maxLength={1000}
                                    rows={4}
                                    placeholder="Describe when and why this skill should be applied..."
                                    className={`${inputClass(
                                        Boolean(
                                            form.errors
                                                .description,
                                        ),
                                    )} min-h-[112px] resize-y`}
                                />

                                <div className="mt-1.5 flex items-start justify-between gap-4">
                                    <Error
                                        text={
                                            form.errors
                                                .description
                                        }
                                    />

                                    <span className="ml-auto shrink-0 text-xs text-gray-400">
                                        {
                                            form.data
                                                .description
                                                .length
                                        }
                                        /1000
                                    </span>
                                </div>
                            </div>

                            <div>
                                <FieldLabel label="Sort order" />

                                <div className="relative">
                                    <Hash className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                    <input
                                        type="number"
                                        min={0}
                                        max={100000}
                                        value={
                                            form.data
                                                .sort_order
                                        }
                                        onChange={(
                                            event,
                                        ) =>
                                            setSortOrder(
                                                event,
                                                form.setData,
                                            )
                                        }
                                        className={`${inputClass(
                                            Boolean(
                                                form.errors
                                                    .sort_order,
                                            ),
                                        )} pl-10`}
                                    />
                                </div>

                                <p className="mt-1.5 text-xs leading-5 text-gray-400">
                                    Lower values appear
                                    earlier in the catalog.
                                </p>

                                <Error
                                    text={
                                        form.errors
                                            .sort_order
                                    }
                                />
                            </div>

                            <div>
                                <FieldLabel label="State" />

                                <button
                                    type="button"
                                    onClick={() =>
                                        form.setData(
                                            'is_active',
                                            !form.data
                                                .is_active,
                                        )
                                    }
                                    className={`flex min-h-11 w-full items-center gap-3 rounded-xl border px-4 py-3 text-left transition ${
                                        form.data
                                            .is_active
                                            ? 'border-emerald-200 bg-emerald-50/60 hover:bg-emerald-50'
                                            : 'border-gray-200 bg-gray-50 hover:bg-gray-100'
                                    }`}
                                >
                                    <ToggleSwitch
                                        active={
                                            form.data
                                                .is_active
                                        }
                                    />

                                    <div className="min-w-0 flex-1">
                                        <div
                                            className={`text-sm font-semibold ${
                                                form.data
                                                    .is_active
                                                    ? 'text-emerald-800'
                                                    : 'text-gray-700'
                                            }`}
                                        >
                                            {form.data
                                                .is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </div>

                                        <div className="mt-0.5 text-xs leading-5 text-gray-500">
                                            {form.data
                                                .is_active
                                                ? 'Available for ticket classification.'
                                                : 'Kept in the catalog but excluded from classification.'}
                                        </div>
                                    </div>
                                </button>

                                <Error
                                    text={
                                        form.errors
                                            .is_active
                                    }
                                />
                            </div>
                        </div>
                    </section>

                    <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <SectionHeader
                            icon={ListChecks}
                            title="Conditions"
                            description="Define how ticket properties must match this skill."
                        />

                        <div className="space-y-6 p-5 sm:p-6">
                            <div>
                                <div className="mb-3">
                                    <FieldLabel
                                        label="Matching mode"
                                        required
                                    />

                                    <p className="mt-1 text-xs leading-5 text-gray-400">
                                        Choose whether one or
                                        every condition must
                                        match.
                                    </p>
                                </div>

                                <div className="grid gap-3 sm:grid-cols-2">
                                    <MatchTypeCard
                                        type="any"
                                        selected={
                                            form.data
                                                .match_type ===
                                            'any'
                                        }
                                        onClick={() =>
                                            form.setData(
                                                'match_type',
                                                'any',
                                            )
                                        }
                                    />

                                    <MatchTypeCard
                                        type="all"
                                        selected={
                                            form.data
                                                .match_type ===
                                            'all'
                                        }
                                        onClick={() =>
                                            form.setData(
                                                'match_type',
                                                'all',
                                            )
                                        }
                                    />
                                </div>

                                <Error
                                    text={
                                        form.errors
                                            .match_type
                                    }
                                />
                            </div>

                            <div className="border-t border-gray-100 pt-6">
                                <SkillRuleBuilder
                                    rules={
                                        form.data.rules
                                    }
                                    schema={
                                        ruleSchema
                                    }
                                    errors={
                                        form.errors
                                    }
                                    onChange={(
                                        rules,
                                    ) =>
                                        form.setData(
                                            'rules',
                                            rules,
                                        )
                                    }
                                />

                                <Error
                                    text={
                                        form.errors
                                            .rules
                                    }
                                />
                            </div>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <SectionHeader
                            icon={CheckCircle2}
                            title="Summary"
                            description="Review the current configuration before saving."
                        />

                        <div className="p-5 sm:p-6">
                            <div className="rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50/80 to-white p-5">
                                <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700">
                                                <Sparkles className="h-5 w-5" />
                                            </div>

                                            <div className="min-w-0">
                                                <div className="truncate font-semibold text-gray-900">
                                                    {form
                                                            .data
                                                            .name ||
                                                        'Untitled skill'}
                                                </div>

                                                <div className="mt-0.5 text-xs text-gray-500">
                                                    {
                                                        activeRuleCount
                                                    }{' '}
                                                    {activeRuleCount ===
                                                    1
                                                        ? 'condition'
                                                        : 'conditions'}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <SummaryBadge
                                            label={`Match ${form.data.match_type.toUpperCase()}`}
                                            className={
                                                form.data
                                                    .match_type ===
                                                'all'
                                                    ? 'bg-violet-100 text-violet-700 ring-violet-200'
                                                    : 'bg-sky-100 text-sky-700 ring-sky-200'
                                            }
                                        />

                                        <SummaryBadge
                                            label={
                                                form.data
                                                    .is_active
                                                    ? 'Active'
                                                    : 'Inactive'
                                            }
                                            className={
                                                form.data
                                                    .is_active
                                                    ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
                                                    : 'bg-gray-100 text-gray-600 ring-gray-200'
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="mt-5 border-t border-violet-100 pt-5">
                                    {form.data.rules
                                        .length > 0 ? (
                                        <div className="space-y-2">
                                            {form.data.rules.map(
                                                (
                                                    rule,
                                                    index,
                                                ) => (
                                                    <div
                                                        key={`${rule.field_key}-${rule.operator}-${index}`}
                                                        className="flex items-start gap-3 rounded-xl bg-white/70 px-3 py-2.5 ring-1 ring-inset ring-violet-100"
                                                    >
                                                        <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-[11px] font-bold text-violet-700">
                                                            {index +
                                                                1}
                                                        </span>

                                                        <div className="min-w-0 text-sm">
                                                            <span className="font-semibold text-gray-700">
                                                                {fieldLabel(
                                                                    rule.field_key,
                                                                )}
                                                            </span>

                                                            <span className="mx-2 text-gray-300">
                                                                ·
                                                            </span>

                                                            <span className="text-gray-500">
                                                                {formatLabel(
                                                                    rule.operator,
                                                                )}
                                                            </span>
                                                        </div>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    ) : (
                                        <div className="text-sm text-gray-400">
                                            No conditions
                                            configured.
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div className="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white/95 backdrop-blur">
                <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <div className="hidden min-w-0 sm:block">
                        <div className="text-sm font-medium text-gray-700">
                            {editing
                                ? 'Update skill'
                                : 'Create skill'}
                        </div>

                        <div className="mt-0.5 text-xs text-gray-400">
                            {form.isDirty
                                ? 'You have unsaved changes.'
                                : 'No unsaved changes.'}
                        </div>
                    </div>

                    <div className="ml-auto flex items-center gap-3">
                        <Link
                            href={route(
                                'admin.skills.index',
                            )}
                            className="inline-flex h-11 items-center justify-center rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                        >
                            Cancel
                        </Link>

                        <button
                            type="button"
                            disabled={
                                form.processing ||
                                ruleSchema.length ===
                                0
                            }
                            onClick={submit}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {form.processing ? (
                                <span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
                            ) : (
                                <Save className="h-4 w-4" />
                            )}

                            {form.processing
                                ? 'Saving...'
                                : editing
                                    ? 'Save changes'
                                    : 'Create skill'}
                        </button>
                    </div>
                </div>
            </div>
        </AdminLayout>
    )
}

function SectionHeader({
                           icon: Icon,
                           title,
                           description,
                       }: {
    icon: LucideIcon
    title: string
    description: string
}) {
    return (
        <div className="flex items-start gap-3 border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                <Icon className="h-5 w-5 text-violet-600" />
            </div>

            <div>
                <h2 className="font-semibold text-gray-900">
                    {title}
                </h2>

                <p className="mt-1 text-sm leading-5 text-gray-500">
                    {description}
                </p>
            </div>
        </div>
    )
}

function FieldLabel({
                        label,
                        required = false,
                    }: {
    label: string
    required?: boolean
}) {
    return (
        <span className="mb-2 block text-sm font-semibold text-gray-700">
            {label}

            {required ? (
                <span className="ml-1 text-rose-500">
                    *
                </span>
            ) : null}
        </span>
    )
}

function ToggleSwitch({
                          active,
                      }: {
    active: boolean
}) {
    return (
        <span
            className={`relative h-6 w-11 shrink-0 rounded-full transition ${
                active
                    ? 'bg-emerald-500'
                    : 'bg-gray-300'
            }`}
        >
            <span
                className={`absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform ${
                    active
                        ? 'translate-x-5'
                        : 'translate-x-0'
                }`}
            />
        </span>
    )
}

function MatchTypeCard({
                           type,
                           selected,
                           onClick,
                       }: {
    type: 'any' | 'all'
    selected: boolean
    onClick: () => void
}) {
    const isAll = type === 'all'

    return (
        <button
            type="button"
            onClick={onClick}
            className={`relative rounded-2xl border p-4 text-left outline-none transition ${
                selected
                    ? 'border-violet-400 bg-violet-50 ring-4 ring-violet-100'
                    : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
            }`}
        >
            <div className="flex items-start gap-3">
                <div
                    className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${
                        selected
                            ? 'bg-violet-100 text-violet-700'
                            : 'bg-gray-100 text-gray-500'
                    }`}
                >
                    {isAll ? (
                        <CheckCircle2 className="h-5 w-5" />
                    ) : (
                        <CircleDot className="h-5 w-5" />
                    )}
                </div>

                <div className="min-w-0 pr-7">
                    <div
                        className={`font-semibold ${
                            selected
                                ? 'text-violet-900'
                                : 'text-gray-800'
                        }`}
                    >
                        Match{' '}
                        {type.toUpperCase()}
                    </div>

                    <p className="mt-1 text-sm leading-5 text-gray-500">
                        {isAll
                            ? 'Every condition must match the ticket.'
                            : 'At least one condition must match the ticket.'}
                    </p>
                </div>
            </div>

            <span
                className={`absolute right-4 top-4 flex h-5 w-5 items-center justify-center rounded-full border transition ${
                    selected
                        ? 'border-violet-600 bg-violet-600 text-white'
                        : 'border-gray-300 bg-white'
                }`}
            >
                {selected ? (
                    <Check className="h-3 w-3" />
                ) : null}
            </span>
        </button>
    )
}

function SummaryBadge({
                          label,
                          className,
                      }: {
    label: string
    className: string
}) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${className}`}
        >
            {label}
        </span>
    )
}

function Error({
                   text,
               }: {
    text?: string
}) {
    if (!text) {
        return null
    }

    return (
        <p className="mt-1.5 text-sm text-rose-600">
            {text}
        </p>
    )
}

function inputClass(
    hasError: boolean,
): string {
    return `mt-2 w-full rounded-xl border bg-white px-3.5 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 ${
        hasError
            ? 'border-rose-300 focus:border-rose-400 focus:ring-4 focus:ring-rose-100'
            : 'border-gray-200 hover:border-gray-300 focus:border-violet-400 focus:ring-4 focus:ring-violet-100'
    }`
}

function setSortOrder(
    event: ChangeEvent<HTMLInputElement>,
    setData: (
        key: 'sort_order',
        value: number,
    ) => void,
) {
    const value =
        event.target.value === ''
            ? 0
            : Number(event.target.value)

    setData(
        'sort_order',
        Number.isFinite(value) ? value : 0,
    )
}

function formatLabel(
    value: string,
): string {
    return value
        .replace(/[_-]+/g, ' ')
        .trim()
        .split(/\s+/)
        .map(
            (part) =>
                part.charAt(0).toUpperCase() +
                part.slice(1),
        )
        .join(' ')
}

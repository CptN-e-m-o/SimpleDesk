import {
    useEffect,
    useRef,
    useState,
} from 'react'
import type { LucideIcon } from 'lucide-react'
import {
    Archive,
    CheckCircle2,
    CircleDot,
    Copy,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    SlidersHorizontal,
    Sparkles,
    Trash2,
    X,
} from 'lucide-react'
import {
    Head,
    Link,
    router,
} from '@inertiajs/react'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout'
import SkillSelect from './SkillSelect'
import type {
    PaginationLink,
    Skill,
} from './skillTypes'

type Filters = {
    search?: string
    match_type?: string
    state?: string
}

type SkillsPagination = {
    data: Skill[]
    links: PaginationLink[]
    total: number
    from?: number | null
    to?: number | null
}

type Props = {
    skills: SkillsPagination
    filters?: Filters
    permissions?: string[]
}

type PermissionChecker = (
    permission: string,
) => boolean

export default function SkillsIndex({
                                        skills,
                                        filters = {},
                                        permissions = [],
                                    }: Props) {
    const [search, setSearch] = useState(
        filters.search ?? '',
    )

    const searchTimeoutRef =
        useRef<number | null>(null)

    const can: PermissionChecker = (
        permission,
    ) => permissions.includes(permission)

    const matchType =
        filters.match_type ?? ''

    const state =
        filters.state ?? 'active'

    const visibleCount = skills.data.length

    const activeOnPage = skills.data.filter(
        (skill) =>
            !skill.deleted_at &&
            skill.is_active,
    ).length

    const rulesOnPage = skills.data.reduce(
        (total, skill) =>
            total + skill.rules_count,
        0,
    )

    const hasFilters =
        search.trim() !== '' ||
        matchType !== '' ||
        state !== 'active'

    useEffect(() => {
        setSearch(filters.search ?? '')
    }, [filters.search])

    const clearSearchTimeout = () => {
        if (searchTimeoutRef.current === null) {
            return
        }

        window.clearTimeout(
            searchTimeoutRef.current,
        )

        searchTimeoutRef.current = null
    }

    const navigate = (
        next: Partial<Filters> = {},
        searchValue: string = search,
    ) => {
        clearSearchTimeout()

        router.get(
            route('admin.skills.index'),
            {
                search: searchValue.trim(),
                match_type:
                    next.match_type ??
                    matchType,
                state:
                    next.state ??
                    state,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    useEffect(() => {
        const normalizedSearch =
            search.trim()

        const appliedSearch = (
            filters.search ?? ''
        ).trim()

        if (
            normalizedSearch ===
            appliedSearch
        ) {
            return
        }

        clearSearchTimeout()

        searchTimeoutRef.current =
            window.setTimeout(() => {
                searchTimeoutRef.current =
                    null

                router.get(
                    route(
                        'admin.skills.index',
                    ),
                    {
                        search:
                        normalizedSearch,
                        match_type:
                        matchType,
                        state,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    },
                )
            }, 400)

        return () => {
            clearSearchTimeout()
        }
    }, [
        search,
        filters.search,
        matchType,
        state,
    ])

    const clearSearch = () => {
        setSearch('')
    }

    const resetFilters = () => {
        clearSearchTimeout()
        setSearch('')

        router.get(
            route('admin.skills.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }

    return (
        <AdminLayout title="Skills">
            <Head title="Skills" />

            <div className="space-y-6 p-4 sm:p-6">
                <header className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-5 bg-gradient-to-r from-violet-50/80 via-white to-white p-6 sm:flex-row sm:items-center">
                        <div className="flex items-start gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-100 ring-1 ring-inset ring-violet-200">
                                <Sparkles className="h-6 w-6 text-violet-700" />
                            </div>

                            <div>
                                <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    Skills
                                </h1>

                                <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                    Create reusable ticket
                                    classification rules that
                                    can later be used for agent
                                    skill matching and automatic
                                    routing.
                                </p>
                            </div>
                        </div>

                        {can(
                            'admin.staff.skills.create',
                        ) ? (
                            <Link
                                href={route(
                                    'admin.skills.create',
                                )}
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200"
                            >
                                <Plus className="h-4 w-4" />
                                Create skill
                            </Link>
                        ) : null}
                    </div>

                    <div className="grid border-t border-gray-200 sm:grid-cols-3">
                        <Metric
                            label="Matching skills"
                            value={skills.total}
                            icon={Sparkles}
                        />

                        <Metric
                            label="Active on this page"
                            value={activeOnPage}
                            icon={CheckCircle2}
                        />

                        <Metric
                            label="Rules on this page"
                            value={rulesOnPage}
                            icon={
                                SlidersHorizontal
                            }
                        />
                    </div>
                </header>

                <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between gap-4 rounded-t-[28px] border-b border-gray-200 bg-gray-50/70 px-5 py-4 sm:px-6">
                        <div className="flex items-start gap-3">
                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100">
                                <SlidersHorizontal className="h-5 w-5 text-violet-600" />
                            </div>

                            <div>
                                <h2 className="font-semibold text-gray-900">
                                    Filters
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Search by name and narrow
                                    the catalog by matching mode
                                    or current state.
                                </p>
                            </div>
                        </div>

                        {hasFilters ? (
                            <button
                                type="button"
                                onClick={
                                    resetFilters
                                }
                                className="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                            >
                                <X className="h-4 w-4" />
                                Reset
                            </button>
                        ) : null}
                    </div>

                    <div className="grid gap-4 rounded-b-[28px] p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-[minmax(280px,1fr)_220px_220px]">
                        <div>
                            <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Search
                            </span>

                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                                <input
                                    type="search"
                                    value={search}
                                    onChange={(
                                        event,
                                    ) =>
                                        setSearch(
                                            event.target
                                                .value,
                                        )
                                    }
                                    placeholder="Search skills..."
                                    className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-10 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-violet-400 focus:ring-4 focus:ring-violet-100"
                                />

                                {search !== '' ? (
                                    <button
                                        type="button"
                                        onClick={
                                            clearSearch
                                        }
                                        title="Clear search"
                                        aria-label="Clear search"
                                        className="absolute right-3 top-1/2 inline-flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                    </button>
                                ) : null}
                            </div>
                        </div>

                        <div>
                            <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Match type
                            </span>

                            <SkillSelect
                                value={matchType}
                                options={[
                                    {
                                        value: '',
                                        label:
                                            'Any match type',
                                    },
                                    {
                                        value: 'any',
                                        label:
                                            'Match ANY',
                                    },
                                    {
                                        value: 'all',
                                        label:
                                            'Match ALL',
                                    },
                                ]}
                                onChange={(value) =>
                                    navigate({
                                        match_type:
                                            String(
                                                value,
                                            ),
                                    })
                                }
                            />
                        </div>

                        <div>
                            <span className="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                                State
                            </span>

                            <SkillSelect
                                value={state}
                                options={[
                                    {
                                        value:
                                            'active',
                                        label:
                                            'Active',
                                    },
                                    {
                                        value:
                                            'inactive',
                                        label:
                                            'Inactive',
                                    },
                                    {
                                        value:
                                            'archived',
                                        label:
                                            'Archived',
                                    },
                                ]}
                                onChange={(value) =>
                                    navigate({
                                        state: String(
                                            value,
                                        ),
                                    })
                                }
                            />
                        </div>
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-2 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <div>
                            <h2 className="font-semibold text-gray-900">
                                Skill catalog
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                {skills.total === 1
                                    ? '1 skill matches the current filters'
                                    : `${skills.total} skills match the current filters`}
                            </p>
                        </div>

                        {skills.from &&
                        skills.to ? (
                            <span className="text-sm text-gray-400">
                                Showing {skills.from}–
                                {skills.to}
                            </span>
                        ) : skills.data.length >
                        0 ? (
                            <span className="text-sm text-gray-400">
                                Showing{' '}
                                {visibleCount}
                            </span>
                        ) : null}
                    </div>

                    {skills.data.length >
                    0 ? (
                        <>
                            <div className="hidden overflow-x-auto md:block">
                                <table className="w-full min-w-[820px] table-fixed text-left text-sm">
                                    <colgroup>
                                        <col className="w-[34%]" />
                                        <col className="w-[15%]" />
                                        <col className="w-[28%]" />
                                        <col className="w-[11%]" />
                                        <col className="w-[12%]" />
                                    </colgroup>

                                    <thead>
                                    <tr className="border-b border-gray-200 bg-gray-50/80 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <th className="px-5 py-4">
                                            Skill
                                        </th>

                                        <th className="px-4 py-4">
                                            Matching
                                        </th>

                                        <th className="px-4 py-4">
                                            Rules
                                        </th>

                                        <th className="px-4 py-4">
                                            State
                                        </th>

                                        <th className="sticky right-0 z-20 border-l border-gray-200 bg-gray-50 px-4 py-4 text-right shadow-[-8px_0_18px_-16px_rgba(15,23,42,0.45)]">
                                            Actions
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-100">
                                    {skills.data.map(
                                        (
                                            skill,
                                        ) => (
                                            <SkillRow
                                                key={
                                                    skill.id
                                                }
                                                skill={
                                                    skill
                                                }
                                                can={
                                                    can
                                                }
                                            />
                                        ),
                                    )}
                                    </tbody>
                                </table>
                            </div>

                            <div className="grid gap-4 p-4 md:hidden">
                                {skills.data.map(
                                    (skill) => (
                                        <SkillCard
                                            key={
                                                skill.id
                                            }
                                            skill={
                                                skill
                                            }
                                            can={can}
                                        />
                                    ),
                                )}
                            </div>
                        </>
                    ) : (
                        <EmptyState
                            filtered={hasFilters}
                            canCreate={can(
                                'admin.staff.skills.create',
                            )}
                            onReset={
                                resetFilters
                            }
                        />
                    )}

                    {skills.links.length > 3 ? (
                        <Pagination
                            links={skills.links}
                        />
                    ) : null}
                </section>
            </div>
        </AdminLayout>
    )
}

function SkillRow({
                      skill,
                      can,
                  }: {
    skill: Skill
    can: PermissionChecker
}) {
    const archived = Boolean(
        skill.deleted_at,
    )

    return (
        <tr
            className={`group transition ${
                archived
                    ? 'bg-rose-50/30 hover:bg-rose-50/60'
                    : 'hover:bg-gray-50/80'
            }`}
        >
            <td className="px-5 py-4 align-top">
                <SkillIdentity
                    skill={skill}
                />
            </td>

            <td className="px-4 py-4 align-top">
                <MatchBadge
                    matchType={
                        skill.match_type
                    }
                />
            </td>

            <td className="px-4 py-4 align-top">
                <RulesPreview
                    skill={skill}
                />
            </td>

            <td className="px-4 py-4 align-top">
                <StateBadge skill={skill} />
            </td>

            <td
                className={`sticky right-0 z-10 border-l border-gray-100 px-4 py-4 align-top shadow-[-8px_0_18px_-16px_rgba(15,23,42,0.45)] transition ${
                    archived
                        ? 'bg-rose-50 group-hover:bg-rose-50'
                        : 'bg-white group-hover:bg-gray-50'
                }`}
            >
                <SkillActions
                    skill={skill}
                    can={can}
                />
            </td>
        </tr>
    )
}

function SkillCard({
                       skill,
                       can,
                   }: {
    skill: Skill
    can: PermissionChecker
}) {
    return (
        <article
            className={
                skill.deleted_at
                    ? 'rounded-2xl border border-rose-200 bg-rose-50/40 p-4'
                    : 'rounded-2xl border border-gray-200 bg-white p-4'
            }
        >
            <div className="flex items-start justify-between gap-3">
                <SkillIdentity
                    skill={skill}
                />

                <StateBadge skill={skill} />
            </div>

            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                <div className="rounded-2xl bg-gray-50 p-4">
                    <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Matching
                    </div>

                    <div className="mt-3">
                        <MatchBadge
                            matchType={
                                skill.match_type
                            }
                        />
                    </div>
                </div>

                <div className="rounded-2xl bg-gray-50 p-4">
                    <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Conditions
                    </div>

                    <div className="mt-3">
                        <RulesPreview
                            skill={skill}
                        />
                    </div>
                </div>
            </div>

            <div className="mt-4 flex justify-end border-t border-gray-100 pt-4">
                <SkillActions
                    skill={skill}
                    can={can}
                />
            </div>
        </article>
    )
}

function SkillIdentity({
                           skill,
                       }: {
    skill: Skill
}) {
    return (
        <div className="flex min-w-0 items-start gap-3">
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 ring-1 ring-inset ring-violet-100">
                <Sparkles className="h-5 w-5" />
            </span>

            <div className="min-w-0">
                <div className="font-semibold text-gray-900">
                    {skill.name}
                </div>

                <p className="mt-1 line-clamp-2 max-w-md text-xs leading-5 text-gray-500">
                    {skill.description ||
                        'No description provided.'}
                </p>

                <div className="mt-1.5 text-[11px] font-medium text-gray-400">
                    Version {skill.version}
                </div>
            </div>
        </div>
    )
}

function MatchBadge({
                        matchType,
                    }: {
    matchType: string
}) {
    if (matchType === 'all') {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                <CheckCircle2 className="h-3.5 w-3.5" />
                Match ALL
            </span>
        )
    }

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">
            <CircleDot className="h-3.5 w-3.5" />
            Match ANY
        </span>
    )
}

function StateBadge({
                        skill,
                    }: {
    skill: Skill
}) {
    if (skill.deleted_at) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">
                <span className="h-1.5 w-1.5 rounded-full bg-rose-500" />
                Archived
            </span>
        )
    }

    if (skill.is_active) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                Active
            </span>
        )
    }

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
            <span className="h-1.5 w-1.5 rounded-full bg-amber-500" />
            Inactive
        </span>
    )
}

function RulesPreview({
                          skill,
                      }: {
    skill: Skill
}) {
    const rules = skill.rules.slice(0, 2)

    return (
        <div>
            <div className="text-sm font-semibold text-gray-800">
                {skill.rules_count}{' '}
                {skill.rules_count === 1
                    ? 'condition'
                    : 'conditions'}
            </div>

            {rules.length > 0 ? (
                <div className="mt-1.5 space-y-1">
                    {rules.map(
                        (rule, index) => (
                            <div
                                key={`${rule.field_key}-${rule.operator}-${index}`}
                                className="truncate text-xs text-gray-500"
                            >
                                <span className="font-medium text-gray-600">
                                    {formatRulePart(
                                        rule.field_key,
                                    )}
                                </span>{' '}
                                <span>
                                    {formatRulePart(
                                        rule.operator,
                                    )}
                                </span>
                            </div>
                        ),
                    )}

                    {skill.rules_count >
                    2 ? (
                        <div className="text-[11px] font-medium text-violet-500">
                            +
                            {skill.rules_count -
                                2}{' '}
                            more
                        </div>
                    ) : null}
                </div>
            ) : (
                <div className="mt-1 text-xs text-gray-400">
                    No conditions
                </div>
            )}
        </div>
    )
}

function SkillActions({
                          skill,
                          can,
                      }: {
    skill: Skill
    can: PermissionChecker
}) {
    const archived = Boolean(
        skill.deleted_at,
    )

    const duplicate = () => {
        router.post(
            route(
                'admin.skills.duplicate',
                skill.id,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const toggle = () => {
        router.patch(
            route(
                'admin.skills.toggle',
                skill.id,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const archive = () => {
        const confirmed = window.confirm(
            `Archive "${skill.name}"?`,
        )

        if (!confirmed) {
            return
        }

        router.delete(
            route(
                'admin.skills.destroy',
                skill.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    const restore = () => {
        router.post(
            route(
                'admin.skills.restore',
                skill.id,
            ),
            {},
            {
                preserveScroll: true,
            },
        )
    }

    const forceDelete = () => {
        const confirmed = window.confirm(
            `Permanently delete "${skill.name}"?\n\nThis action is irreversible and the skill cannot be restored.`,
        )

        if (!confirmed) {
            return
        }

        router.delete(
            route(
                'admin.skills.force-delete',
                skill.id,
            ),
            {
                preserveScroll: true,
            },
        )
    }

    if (archived) {
        return (
            <div className="flex min-w-max items-center justify-end gap-1.5">
                {can(
                    'admin.staff.skills.archive',
                ) ? (
                    <ActionButton
                        title="Restore skill"
                        label={`Restore ${skill.name}`}
                        icon={RotateCcw}
                        onClick={restore}
                        hoverClassName="hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                    />
                ) : null}

                {can(
                    'admin.staff.skills.delete',
                ) ? (
                    <ActionButton
                        title="Permanently delete"
                        label={`Permanently delete ${skill.name}`}
                        icon={Trash2}
                        onClick={
                            forceDelete
                        }
                        hoverClassName="hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                    />
                ) : null}
            </div>
        )
    }

    return (
        <div className="flex min-w-max items-center justify-end gap-1.5">
            {can(
                'admin.staff.skills.update',
            ) ? (
                <>
                    <ActionButton
                        as="link"
                        href={route(
                            'admin.skills.edit',
                            skill.id,
                        )}
                        title="Edit skill"
                        label={`Edit ${skill.name}`}
                        icon={Pencil}
                        hoverClassName="hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700"
                    />

                    <ActionButton
                        title={
                            skill.is_active
                                ? 'Deactivate skill'
                                : 'Activate skill'
                        }
                        label={
                            skill.is_active
                                ? `Deactivate ${skill.name}`
                                : `Activate ${skill.name}`
                        }
                        icon={
                            skill.is_active
                                ? CheckCircle2
                                : CircleDot
                        }
                        onClick={toggle}
                        hoverClassName={
                            skill.is_active
                                ? 'hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700'
                                : 'hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700'
                        }
                    />
                </>
            ) : null}

            {can(
                'admin.staff.skills.create',
            ) ? (
                <ActionButton
                    title="Duplicate skill"
                    label={`Duplicate ${skill.name}`}
                    icon={Copy}
                    onClick={duplicate}
                    hoverClassName="hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                />
            ) : null}

            {can(
                'admin.staff.skills.archive',
            ) ? (
                <ActionButton
                    title="Archive skill"
                    label={`Archive ${skill.name}`}
                    icon={Archive}
                    onClick={archive}
                    hoverClassName="hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                />
            ) : null}
        </div>
    )
}

type ActionButtonProps = {
    title: string
    label: string
    icon: LucideIcon
    hoverClassName: string
    onClick?: () => void
    as?: 'button' | 'link'
    href?: string
}

function ActionButton({
                          title,
                          label,
                          icon: Icon,
                          hoverClassName,
                          onClick,
                          as = 'button',
                          href,
                      }: ActionButtonProps) {
    const className = `inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition ${hoverClassName}`

    if (as === 'link' && href) {
        return (
            <Link
                href={href}
                title={title}
                aria-label={label}
                className={className}
            >
                <Icon className="h-4 w-4" />
            </Link>
        )
    }

    return (
        <button
            type="button"
            onClick={onClick}
            title={title}
            aria-label={label}
            className={className}
        >
            <Icon className="h-4 w-4" />
        </button>
    )
}

function Metric({
                    label,
                    value,
                    icon: Icon,
                }: {
    label: string
    value: number
    icon: LucideIcon
}) {
    return (
        <div className="border-b border-gray-200 px-6 py-4 last:border-b-0 sm:border-b-0 sm:border-r sm:last:border-r-0">
            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                <Icon className="h-4 w-4" />
                {label}
            </div>

            <div className="mt-1 text-lg font-semibold text-gray-900">
                {value}
            </div>
        </div>
    )
}

function EmptyState({
                        filtered,
                        canCreate,
                        onReset,
                    }: {
    filtered: boolean
    canCreate: boolean
    onReset: () => void
}) {
    return (
        <div className="px-6 py-16 text-center">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-violet-50">
                <Sparkles className="h-8 w-8 text-violet-400" />
            </div>

            <h3 className="mt-5 font-semibold text-gray-900">
                {filtered
                    ? 'No matching skills'
                    : 'No skills yet'}
            </h3>

            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                {filtered
                    ? 'No skills match the current filters. Try changing or clearing them.'
                    : 'Create your first skill to define reusable ticket classification conditions.'}
            </p>

            <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                {filtered ? (
                    <button
                        type="button"
                        onClick={onReset}
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                    >
                        <X className="h-4 w-4" />
                        Clear filters
                    </button>
                ) : null}

                {canCreate ? (
                    <Link
                        href={route(
                            'admin.skills.create',
                        )}
                        className="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white transition hover:bg-violet-700"
                    >
                        <Plus className="h-4 w-4" />
                        Create skill
                    </Link>
                ) : null}
            </div>
        </div>
    )
}

function Pagination({
                        links,
                    }: {
    links: PaginationLink[]
}) {
    return (
        <nav
            aria-label="Skills pagination"
            className="flex flex-wrap items-center justify-center gap-1.5 border-t border-gray-200 px-4 py-4"
        >
            {links.map((link, index) => {
                const label =
                    formatPaginationLabel(
                        link.label,
                    )

                if (!link.url) {
                    return (
                        <span
                            key={`${link.label}-${index}`}
                            className="inline-flex h-10 min-w-10 cursor-not-allowed items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-3 text-sm text-gray-300"
                        >
                            {label}
                        </span>
                    )
                }

                return (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        preserveState
                        className={
                            link.active
                                ? 'inline-flex h-10 min-w-10 items-center justify-center rounded-xl bg-violet-600 px-3 text-sm font-semibold text-white shadow-sm'
                                : 'inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700'
                        }
                    >
                        {label}
                    </Link>
                )
            })}
        </nav>
    )
}

function formatRulePart(
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

function formatPaginationLabel(
    label: string,
): string {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace(/<[^>]*>/g, '')
}

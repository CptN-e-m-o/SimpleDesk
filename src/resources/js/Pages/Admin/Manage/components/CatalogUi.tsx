import { Link, router } from '@inertiajs/react'
import { Search, X } from 'lucide-react'
import { type FormEvent, useState } from 'react'
import { route } from 'ziggy-js'

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select'

export type CatalogFiltersValue = {
    search?: string
    status?: string
    visibility?: string
}

export type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

export type Paginator<T> = {
    data: T[]
    links: PaginationLink[]
    current_page?: number
    last_page?: number
    from?: number | null
    to?: number | null
    total?: number
}

type CatalogFiltersProps = {
    routeName: string
    filters?: CatalogFiltersValue
}

const ALL_STATUSES = '__all_statuses__'
const ALL_VISIBILITY = '__all_visibility__'

export function CatalogFilters({
                                   routeName,
                                   filters = {},
                               }: CatalogFiltersProps) {
    const [search, setSearch] = useState(
        filters.search ?? '',
    )

    const [status, setStatus] = useState(
        filters.status ?? '',
    )

    const [visibility, setVisibility] = useState(
        filters.visibility ?? '',
    )

    function submit(
        event: FormEvent<HTMLFormElement>,
    ) {
        event.preventDefault()

        const query: Record<string, string> = {}

        if (search.trim() !== '') {
            query.search = search.trim()
        }

        if (status !== '') {
            query.status = status
        }

        if (visibility !== '') {
            query.visibility = visibility
        }

        router.get(
            route(routeName),
            query,
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        )
    }

    function reset() {
        setSearch('')
        setStatus('')
        setVisibility('')

        router.get(
            route(routeName),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            },
        )
    }

    const hasFilters =
        search !== '' ||
        status !== '' ||
        visibility !== ''

    return (
        <form
            onSubmit={submit}
            className="rounded-[24px] border border-gray-200 bg-white p-4 shadow-sm"
        >
            <div className="grid gap-3 lg:grid-cols-[minmax(240px,1fr)_190px_190px_auto]">
                <div className="relative">
                    <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />

                    <input
                        type="search"
                        value={search}
                        onChange={(event) =>
                            setSearch(
                                event.target.value,
                            )
                        }
                        placeholder="Search by name or description..."
                        className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                    />
                </div>

                <Select
                    value={
                        status === ''
                            ? ALL_STATUSES
                            : status
                    }
                    onValueChange={(value) =>
                        setStatus(
                            value === ALL_STATUSES
                                ? ''
                                : value,
                        )
                    }
                >
                    <SelectTrigger className="!h-11 w-full rounded-xl border-gray-200 bg-white px-3.5 text-sm font-medium text-gray-700 shadow-none transition hover:border-gray-300 hover:bg-gray-50 focus-visible:border-sky-400 focus-visible:ring-4 focus-visible:ring-sky-100">
                        <SelectValue />
                    </SelectTrigger>

                    <SelectContent
                        position="popper"
                        align="start"
                        className="min-w-[190px] rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl shadow-gray-900/10"
                    >
                        <SelectItem
                            value={ALL_STATUSES}
                            className="cursor-pointer rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 focus:bg-gray-100 focus:text-gray-900"
                        >
                            Active & inactive
                        </SelectItem>

                        <SelectItem
                            value="active"
                            className="cursor-pointer rounded-lg px-3 py-2.5 text-sm text-gray-700 focus:bg-gray-100 focus:text-gray-900"
                        >
                            <span className="flex items-center gap-2.5">
                                <span className="h-2 w-2 rounded-full bg-emerald-500" />
                                Active
                            </span>
                        </SelectItem>

                        <SelectItem
                            value="inactive"
                            className="cursor-pointer rounded-lg px-3 py-2.5 text-sm text-gray-700 focus:bg-gray-100 focus:text-gray-900"
                        >
                            <span className="flex items-center gap-2.5">
                                <span className="h-2 w-2 rounded-full bg-amber-500" />
                                Inactive
                            </span>
                        </SelectItem>

                        <SelectItem
                            value="archived"
                            className="cursor-pointer rounded-lg px-3 py-2.5 text-sm text-gray-700 focus:bg-gray-100 focus:text-gray-900"
                        >
                            <span className="flex items-center gap-2.5">
                                <span className="h-2 w-2 rounded-full bg-gray-400" />
                                Archived
                            </span>
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    value={
                        visibility === ''
                            ? ALL_VISIBILITY
                            : visibility
                    }
                    onValueChange={(value) =>
                        setVisibility(
                            value === ALL_VISIBILITY
                                ? ''
                                : value,
                        )
                    }
                >
                    <SelectTrigger className="!h-11 w-full rounded-xl border-gray-200 bg-white px-3.5 text-sm font-medium text-gray-700 shadow-none transition hover:border-gray-300 hover:bg-gray-50 focus-visible:border-sky-400 focus-visible:ring-4 focus-visible:ring-sky-100">
                        <SelectValue />
                    </SelectTrigger>

                    <SelectContent
                        position="popper"
                        align="start"
                        className="min-w-[190px] rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl shadow-gray-900/10"
                    >
                        <SelectItem
                            value={ALL_VISIBILITY}
                            className="cursor-pointer rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 focus:bg-gray-100 focus:text-gray-900"
                        >
                            All visibility
                        </SelectItem>

                        <SelectItem
                            value="public"
                            className="cursor-pointer rounded-lg px-3 py-2.5 text-sm text-gray-700 focus:bg-gray-100 focus:text-gray-900"
                        >
                            <span className="flex items-center gap-2.5">
                                <span className="h-2 w-2 rounded-full bg-sky-500" />
                                Public
                            </span>
                        </SelectItem>

                        <SelectItem
                            value="internal"
                            className="cursor-pointer rounded-lg px-3 py-2.5 text-sm text-gray-700 focus:bg-gray-100 focus:text-gray-900"
                        >
                            <span className="flex items-center gap-2.5">
                                <span className="h-2 w-2 rounded-full bg-violet-500" />
                                Internal
                            </span>
                        </SelectItem>
                    </SelectContent>
                </Select>

                <div className="flex gap-2">
                    <button
                        type="submit"
                        className="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 lg:flex-none"
                    >
                        Apply
                    </button>

                    {hasFilters && (
                        <button
                            type="button"
                            onClick={reset}
                            title="Clear filters"
                            className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    )}
                </div>
            </div>
        </form>
    )
}

export function Pagination({
                               links,
                           }: {
    links: PaginationLink[]
}) {
    if (!links || links.length <= 3) {
        return null
    }

    return (
        <div className="flex flex-wrap items-center justify-center gap-1 border-t border-gray-100 px-5 py-4">
            {links.map((link, index) => {
                const label = link.label
                    .replace(
                        '&laquo; Previous',
                        '‹ Previous',
                    )
                    .replace(
                        'Next &raquo;',
                        'Next ›',
                    )
                    .replace('&laquo;', '‹')
                    .replace('&raquo;', '›')

                if (!link.url) {
                    return (
                        <span
                            key={`${label}-${index}`}
                            className="inline-flex h-9 min-w-9 cursor-not-allowed items-center justify-center rounded-xl px-3 text-sm text-gray-300"
                        >
                            {label}
                        </span>
                    )
                }

                return (
                    <Link
                        key={`${label}-${index}`}
                        href={link.url}
                        preserveScroll
                        className={
                            link.active
                                ? 'inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-slate-900 px-3 text-sm font-semibold text-white'
                                : 'inline-flex h-9 min-w-9 items-center justify-center rounded-xl px-3 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900'
                        }
                    >
                        {label}
                    </Link>
                )
            })}
        </div>
    )
}

export function VisibilityBadge({
                                    visibility,
                                }: {
    visibility: string
}) {
    const isPublic =
        visibility === 'public'

    return (
        <span
            className={
                isPublic
                    ? 'inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200'
                    : 'inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200'
            }
        >
            {isPublic
                ? 'Public'
                : 'Internal'}
        </span>
    )
}

export function StatusBadge({
                                active,
                                archived,
                            }: {
    active: boolean
    archived: boolean
}) {
    if (archived) {
        return (
            <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                Archived
            </span>
        )
    }

    return (
        <span
            className={
                active
                    ? 'inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200'
                    : 'inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200'
            }
        >
            {active
                ? 'Active'
                : 'Inactive'}
        </span>
    )
}

export function EmptyCatalogState({
                                      title,
                                      description,
                                  }: {
    title: string
    description: string
}) {
    return (
        <div className="px-6 py-16 text-center">
            <div className="mx-auto max-w-md">
                <h3 className="text-base font-semibold text-gray-900">
                    {title}
                </h3>

                <p className="mt-2 text-sm leading-6 text-gray-500">
                    {description}
                </p>
            </div>
        </div>
    )
}

import {
    Head,
    Link,
    router,
} from '@inertiajs/react'
import {
    Archive,
    ArrowDown,
    ArrowUp,
    CheckCircle2,
    Edit3,
    MoreHorizontal,
    Plus,
    RotateCcw,
    Star,
    XCircle,
} from 'lucide-react'
import { useState } from 'react'
import { route } from 'ziggy-js'

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/Components/ui/alert-dialog'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { usePermissions } from '@/hooks/usePermissions'
import AdminLayout from '@/Layouts/AdminLayout'
import {
    CatalogFilters,
    EmptyCatalogState,
    type CatalogFiltersValue,
    type Paginator,
    Pagination,
    StatusBadge,
    VisibilityBadge,
} from '@/Pages/Admin/Manage/components/CatalogUi'

type Priority = {
    id: number
    name: string
    slug: string
    description: string | null
    color: string
    visibility: string
    sort_order: number
    is_default: boolean
    is_active: boolean
    deleted_at: string | null
    tickets_count: number
}

type Props = {
    priorities: Paginator<Priority>
    filters?: CatalogFiltersValue
}

export default function PrioritiesIndex({
                                            priorities,
                                            filters = {},
                                        }: Props) {
    const { can } = usePermissions()

    const [archiveTarget, setArchiveTarget] =
        useState<Priority | null>(null)

    const archivedView =
        filters.status === 'archived'

    function move(
        index: number,
        delta: number,
    ) {
        const target = index + delta

        if (
            target < 0 ||
            target >= priorities.data.length
        ) {
            return
        }

        const ids = priorities.data.map(
            (priority) => priority.id,
        )

        const currentId = ids[index]
        const targetId = ids[target]

        ids[index] = targetId
        ids[target] = currentId

        router.patch(
            route(
                'admin.manage.priorities.reorder',
            ),
            { ids },
            {
                preserveScroll: true,
            },
        )
    }

    function archivePriority() {
        if (!archiveTarget) {
            return
        }

        router.delete(
            route(
                'admin.manage.priorities.destroy',
                archiveTarget.id,
            ),
            {
                preserveScroll: true,
                onFinish: () =>
                    setArchiveTarget(null),
            },
        )
    }

    return (
        <AdminLayout title="Priorities">
            <Head title="Priorities" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-5 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                    Ticket Priorities
                                </h1>

                                <span className="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                    {priorities.total ??
                                        priorities.data
                                            .length}
                                </span>
                            </div>

                            <p className="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                                Configure the urgency
                                levels used when tickets
                                are created, classified,
                                filtered and processed.
                            </p>
                        </div>

                        {can(
                            'admin.manage.priorities.create',
                        ) && (
                            <Link
                                href={route(
                                    'admin.manage.priorities.create',
                                )}
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                <Plus className="h-4 w-4" />
                                New priority
                            </Link>
                        )}
                    </div>
                </section>

                <CatalogFilters
                    routeName="admin.manage.priorities.index"
                    filters={filters}
                />

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    {priorities.data.length ===
                    0 ? (
                        <EmptyCatalogState
                            title={
                                archivedView
                                    ? 'No archived priorities'
                                    : 'No priorities found'
                            }
                            description={
                                archivedView
                                    ? 'Archived priorities will appear here and can be restored when needed.'
                                    : 'Try changing the current filters or create a new priority.'
                            }
                        />
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="min-w-full">
                                    <thead className="border-b border-gray-200 bg-gray-50/80">
                                    <tr className="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <th className="w-24 px-5 py-3.5">
                                            Order
                                        </th>

                                        <th className="min-w-[300px] px-5 py-3.5">
                                            Priority
                                        </th>

                                        <th className="px-5 py-3.5">
                                            Visibility
                                        </th>

                                        <th className="px-5 py-3.5">
                                            Status
                                        </th>

                                        <th className="px-5 py-3.5 text-right">
                                            Usage
                                        </th>

                                        <th className="w-16 px-5 py-3.5">
                                                <span className="sr-only">
                                                    Actions
                                                </span>
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-100">
                                    {priorities.data.map(
                                        (
                                            priority,
                                            index,
                                        ) => {
                                            const archived =
                                                Boolean(
                                                    priority.deleted_at,
                                                )

                                            return (
                                                <tr
                                                    key={
                                                        priority.id
                                                    }
                                                    className="transition hover:bg-gray-50/70"
                                                >
                                                    <td className="px-5 py-4">
                                                        {can(
                                                            'admin.manage.priorities.update',
                                                        ) &&
                                                        !archived ? (
                                                            <div className="flex items-center gap-1">
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        move(
                                                                            index,
                                                                            -1,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        index ===
                                                                        0
                                                                    }
                                                                    title="Move up"
                                                                    className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-30"
                                                                >
                                                                    <ArrowUp className="h-3.5 w-3.5" />
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        move(
                                                                            index,
                                                                            1,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        index ===
                                                                        priorities
                                                                            .data
                                                                            .length -
                                                                        1
                                                                    }
                                                                    title="Move down"
                                                                    className="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-900 disabled:cursor-not-allowed disabled:opacity-30"
                                                                >
                                                                    <ArrowDown className="h-3.5 w-3.5" />
                                                                </button>
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-gray-400">
                                                                    —
                                                                </span>
                                                        )}
                                                    </td>

                                                    <td className="px-5 py-4">
                                                        <div className="flex items-start gap-3">
                                                                <span
                                                                    className="mt-0.5 h-9 w-9 shrink-0 rounded-xl ring-1 ring-inset ring-black/5"
                                                                    style={{
                                                                        backgroundColor:
                                                                        priority.color,
                                                                    }}
                                                                />

                                                            <div className="min-w-0">
                                                                <div className="flex flex-wrap items-center gap-2">
                                                                        <span className="font-semibold text-gray-900">
                                                                            {
                                                                                priority.name
                                                                            }
                                                                        </span>

                                                                    {priority.is_default && (
                                                                        <span className="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">
                                                                                <Star className="h-3 w-3 fill-current" />
                                                                                Default
                                                                            </span>
                                                                    )}
                                                                </div>

                                                                {priority.description && (
                                                                    <p className="mt-1 max-w-xl text-sm leading-5 text-gray-500">
                                                                        {priority.description}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td className="px-5 py-4">
                                                        <VisibilityBadge
                                                            visibility={
                                                                priority.visibility
                                                            }
                                                        />
                                                    </td>

                                                    <td className="px-5 py-4">
                                                        <StatusBadge
                                                            active={
                                                                priority.is_active
                                                            }
                                                            archived={
                                                                archived
                                                            }
                                                        />
                                                    </td>

                                                    <td className="px-5 py-4 text-right">
                                                            <span className="text-sm font-semibold tabular-nums text-gray-700">
                                                                {
                                                                    priority.tickets_count
                                                                }
                                                            </span>

                                                        <span className="ml-1 text-xs text-gray-400">
                                                                tickets
                                                            </span>
                                                    </td>

                                                    <td className="px-5 py-4">
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger
                                                                asChild
                                                            >
                                                                <button
                                                                    type="button"
                                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-900"
                                                                >
                                                                    <MoreHorizontal className="h-4 w-4" />

                                                                    <span className="sr-only">
                                                                            Priority actions
                                                                        </span>
                                                                </button>
                                                            </DropdownMenuTrigger>

                                                            <DropdownMenuContent
                                                                align="end"
                                                                className="min-w-52"
                                                            >
                                                                {!archived &&
                                                                    can(
                                                                        'admin.manage.priorities.update',
                                                                    ) && (
                                                                        <>
                                                                            <DropdownMenuItem
                                                                                asChild
                                                                            >
                                                                                <Link
                                                                                    href={route(
                                                                                        'admin.manage.priorities.edit',
                                                                                        priority.id,
                                                                                    )}
                                                                                    className="flex cursor-pointer items-center gap-2"
                                                                                >
                                                                                    <Edit3 className="h-4 w-4" />
                                                                                    Edit
                                                                                </Link>
                                                                            </DropdownMenuItem>

                                                                            {!priority.is_default && (
                                                                                <>
                                                                                    <DropdownMenuItem
                                                                                        disabled={
                                                                                            !priority.is_active ||
                                                                                            priority.visibility !==
                                                                                            'public'
                                                                                        }
                                                                                        onClick={() =>
                                                                                            router.patch(
                                                                                                route(
                                                                                                    'admin.manage.priorities.default',
                                                                                                    priority.id,
                                                                                                ),
                                                                                                {},
                                                                                                {
                                                                                                    preserveScroll:
                                                                                                        true,
                                                                                                },
                                                                                            )
                                                                                        }
                                                                                    >
                                                                                        <Star className="h-4 w-4" />
                                                                                        Make
                                                                                        default
                                                                                    </DropdownMenuItem>

                                                                                    <DropdownMenuItem
                                                                                        onClick={() =>
                                                                                            router.patch(
                                                                                                route(
                                                                                                    'admin.manage.priorities.enabled',
                                                                                                    priority.id,
                                                                                                ),
                                                                                                {
                                                                                                    enabled:
                                                                                                        !priority.is_active,
                                                                                                },
                                                                                                {
                                                                                                    preserveScroll:
                                                                                                        true,
                                                                                                },
                                                                                            )
                                                                                        }
                                                                                    >
                                                                                        {priority.is_active ? (
                                                                                            <XCircle className="h-4 w-4" />
                                                                                        ) : (
                                                                                            <CheckCircle2 className="h-4 w-4" />
                                                                                        )}

                                                                                        {priority.is_active
                                                                                            ? 'Disable'
                                                                                            : 'Enable'}
                                                                                    </DropdownMenuItem>
                                                                                </>
                                                                            )}
                                                                        </>
                                                                    )}

                                                                {archived &&
                                                                    can(
                                                                        'admin.manage.priorities.archive',
                                                                    ) && (
                                                                        <DropdownMenuItem
                                                                            onClick={() =>
                                                                                router.post(
                                                                                    route(
                                                                                        'admin.manage.priorities.restore',
                                                                                        priority.id,
                                                                                    ),
                                                                                    {},
                                                                                    {
                                                                                        preserveScroll:
                                                                                            true,
                                                                                    },
                                                                                )
                                                                            }
                                                                        >
                                                                            <RotateCcw className="h-4 w-4" />
                                                                            Restore
                                                                        </DropdownMenuItem>
                                                                    )}

                                                                {!archived &&
                                                                    !priority.is_default &&
                                                                    can(
                                                                        'admin.manage.priorities.archive',
                                                                    ) && (
                                                                        <>
                                                                            <DropdownMenuSeparator />

                                                                            <DropdownMenuItem
                                                                                onClick={() =>
                                                                                    setArchiveTarget(
                                                                                        priority,
                                                                                    )
                                                                                }
                                                                                className="text-rose-600 focus:text-rose-700"
                                                                            >
                                                                                <Archive className="h-4 w-4" />
                                                                                Archive
                                                                            </DropdownMenuItem>
                                                                        </>
                                                                    )}
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </td>
                                                </tr>
                                            )
                                        },
                                    )}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination
                                links={
                                    priorities.links ??
                                    []
                                }
                            />
                        </>
                    )}
                </section>
            </div>

            <AlertDialog
                open={archiveTarget !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setArchiveTarget(null)
                    }
                }}
            >
                <AlertDialogContent className="p-6">
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Archive priority?
                        </AlertDialogTitle>

                        <AlertDialogDescription>
                            {archiveTarget
                                ? `"${archiveTarget.name}" will no longer be available for new assignments. Existing tickets will keep their current priority.`
                                : ''}
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <AlertDialogFooter className="mt-6">
                        <AlertDialogCancel>
                            Cancel
                        </AlertDialogCancel>

                        <AlertDialogAction
                            onClick={
                                archivePriority
                            }
                        >
                            Archive priority
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    )
}

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

type TicketType = {
    id: number
    name: string
    slug: string
    description: string | null
    visibility: string
    sort_order: number
    is_active: boolean
    deleted_at: string | null
    tickets_count: number
}

type Props = {
    ticketTypes: Paginator<TicketType>
    filters?: CatalogFiltersValue
}

export default function TicketTypesIndex({
                                             ticketTypes,
                                             filters = {},
                                         }: Props) {
    const { can } = usePermissions()

    const [archiveTarget, setArchiveTarget] =
        useState<TicketType | null>(null)

    const archivedView =
        filters.status === 'archived'

    function move(
        index: number,
        delta: number,
    ) {
        const targetIndex = index + delta

        if (
            targetIndex < 0 ||
            targetIndex >= ticketTypes.data.length
        ) {
            return
        }

        const ids = ticketTypes.data.map(
            (ticketType) => ticketType.id,
        )

        const currentId = ids[index]
        const targetId = ids[targetIndex]

        ids[index] = targetId
        ids[targetIndex] = currentId

        router.patch(
            route(
                'admin.manage.ticket-types.reorder',
            ),
            { ids },
            {
                preserveScroll: true,
            },
        )
    }

    function archiveTicketType() {
        if (!archiveTarget) {
            return
        }

        router.delete(
            route(
                'admin.manage.ticket-types.destroy',
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
        <AdminLayout title="Ticket Types">
            <Head title="Ticket Types" />

            <div className="space-y-6">
                <section className="flex flex-col gap-5 rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-semibold tracking-tight text-gray-900">
                                Ticket Types
                            </h1>

                            <span className="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                {ticketTypes.total ??
                                    ticketTypes.data.length}
                            </span>
                        </div>

                        <p className="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                            Classify tickets by the kind
                            of request, issue or work
                            they represent. Routing and
                            SLA behavior remain
                            separate.
                        </p>
                    </div>

                    {can(
                        'admin.manage.ticket_types.create',
                    ) && (
                        <Link
                            href={route(
                                'admin.manage.ticket-types.create',
                            )}
                            className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            <Plus className="h-4 w-4" />
                            New ticket type
                        </Link>
                    )}
                </section>

                <CatalogFilters
                    routeName="admin.manage.ticket-types.index"
                    filters={filters}
                />

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    {ticketTypes.data.length === 0 ? (
                        <EmptyCatalogState
                            title={
                                archivedView
                                    ? 'No archived ticket types'
                                    : 'No ticket types found'
                            }
                            description={
                                archivedView
                                    ? 'Archived ticket types will appear here and can be restored when needed.'
                                    : 'Try changing the current filters or create a new ticket type.'
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

                                        <th className="min-w-[320px] px-5 py-3.5">
                                            Ticket type
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
                                    {ticketTypes.data.map(
                                        (
                                            ticketType,
                                            index,
                                        ) => {
                                            const archived =
                                                Boolean(
                                                    ticketType.deleted_at,
                                                )

                                            return (
                                                <tr
                                                    key={
                                                        ticketType.id
                                                    }
                                                    className="transition hover:bg-gray-50/70"
                                                >
                                                    <td className="px-5 py-4">
                                                        {can(
                                                            'admin.manage.ticket_types.update',
                                                        ) &&
                                                        !archived ? (
                                                            <div className="flex gap-1">
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
                                                                        ticketTypes
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
                                                        <div className="font-semibold text-gray-900">
                                                            {
                                                                ticketType.name
                                                            }
                                                        </div>

                                                        {ticketType.description && (
                                                            <p className="mt-1 max-w-xl text-sm leading-5 text-gray-500">
                                                                {
                                                                    ticketType.description
                                                                }
                                                            </p>
                                                        )}
                                                    </td>

                                                    <td className="px-5 py-4">
                                                        <VisibilityBadge
                                                            visibility={
                                                                ticketType.visibility
                                                            }
                                                        />
                                                    </td>

                                                    <td className="px-5 py-4">
                                                        <StatusBadge
                                                            active={
                                                                ticketType.is_active
                                                            }
                                                            archived={
                                                                archived
                                                            }
                                                        />
                                                    </td>

                                                    <td className="px-5 py-4 text-right">
                                                            <span className="text-sm font-semibold tabular-nums text-gray-700">
                                                                {
                                                                    ticketType.tickets_count
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
                                                                            Ticket
                                                                            type
                                                                            actions
                                                                        </span>
                                                                </button>
                                                            </DropdownMenuTrigger>

                                                            <DropdownMenuContent
                                                                align="end"
                                                                className="min-w-48"
                                                            >
                                                                {!archived &&
                                                                    can(
                                                                        'admin.manage.ticket_types.update',
                                                                    ) && (
                                                                        <>
                                                                            <DropdownMenuItem
                                                                                asChild
                                                                            >
                                                                                <Link
                                                                                    href={route(
                                                                                        'admin.manage.ticket-types.edit',
                                                                                        ticketType.id,
                                                                                    )}
                                                                                    className="flex cursor-pointer items-center gap-2"
                                                                                >
                                                                                    <Edit3 className="h-4 w-4" />
                                                                                    Edit
                                                                                </Link>
                                                                            </DropdownMenuItem>

                                                                            <DropdownMenuItem
                                                                                onClick={() =>
                                                                                    router.patch(
                                                                                        route(
                                                                                            'admin.manage.ticket-types.enabled',
                                                                                            ticketType.id,
                                                                                        ),
                                                                                        {
                                                                                            enabled:
                                                                                                !ticketType.is_active,
                                                                                        },
                                                                                        {
                                                                                            preserveScroll:
                                                                                                true,
                                                                                        },
                                                                                    )
                                                                                }
                                                                            >
                                                                                {ticketType.is_active ? (
                                                                                    <XCircle className="h-4 w-4" />
                                                                                ) : (
                                                                                    <CheckCircle2 className="h-4 w-4" />
                                                                                )}

                                                                                {ticketType.is_active
                                                                                    ? 'Disable'
                                                                                    : 'Enable'}
                                                                            </DropdownMenuItem>
                                                                        </>
                                                                    )}

                                                                {archived &&
                                                                    can(
                                                                        'admin.manage.ticket_types.archive',
                                                                    ) && (
                                                                        <DropdownMenuItem
                                                                            onClick={() =>
                                                                                router.post(
                                                                                    route(
                                                                                        'admin.manage.ticket-types.restore',
                                                                                        ticketType.id,
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
                                                                    can(
                                                                        'admin.manage.ticket_types.archive',
                                                                    ) && (
                                                                        <>
                                                                            <DropdownMenuSeparator />

                                                                            <DropdownMenuItem
                                                                                onClick={() =>
                                                                                    setArchiveTarget(
                                                                                        ticketType,
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
                                    ticketTypes.links ??
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
                            Archive ticket type?
                        </AlertDialogTitle>

                        <AlertDialogDescription>
                            {archiveTarget
                                ? `"${archiveTarget.name}" will no longer be available for new classifications. Existing tickets will keep this ticket type.`
                                : ''}
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <AlertDialogFooter className="mt-6">
                        <AlertDialogCancel>
                            Cancel
                        </AlertDialogCancel>

                        <AlertDialogAction
                            onClick={
                                archiveTicketType
                            }
                        >
                            Archive ticket type
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    )
}

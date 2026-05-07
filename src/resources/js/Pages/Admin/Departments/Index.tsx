import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Building2,
    Eye,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    ShieldAlert,
    Trash2,
} from 'lucide-react'
import { useMemo, useState } from 'react'
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
    Department,
    formatDepartmentType,
    getDepartmentSearchStatus,
    getDepartmentStatusClasses,
    getDepartmentStatusLabel,
} from './shared'

type Props = {
    readonly departments?: Department[]
}

type DepartmentAction =
    | {
    type: 'delete' | 'restore' | 'force-delete'
    department: Department
}
    | null

type SortField = 'name' | 'type'
type SortDirection = 'asc' | 'desc'

const dialogTitles: Record<string, string> = {
    restore: 'Restore department?',
    'force-delete': 'Delete department permanently?',
    delete: 'Delete department?',
}

function getDialogDescription(action?: DepartmentAction) {
    if (!action) return null

    const name = action.department.name

    if (action.type === 'restore') {
        return (
            <>
                Department{' '}
                <span className="font-semibold text-gray-900">{name}</span>{' '}
                will be restored and returned to the list.
            </>
        )
    }

    if (action.type === 'force-delete') {
        return (
            <>
                Department{' '}
                <span className="font-semibold text-gray-900">{name}</span>{' '}
                will be permanently deleted. This action cannot be undone.
            </>
        )
    }

    return (
        <>
            Department <span className="font-semibold text-gray-900">{name}</span>{' '}
            will be soft deleted and can be restored later.
        </>
    )
}

export default function Index({ departments = [] }: Props) {
    const [search, setSearch] = useState('')
    const [departmentAction, setDepartmentAction] =
        useState<DepartmentAction>(null)
    const [processing, setProcessing] = useState(false)
    const [sortField, setSortField] = useState<SortField>('name')
    const [sortDirection, setSortDirection] = useState<SortDirection>('asc')

    const isRestore = departmentAction?.type === 'restore'

    const actionLabels: Record<string, string> = {
        restore: 'Restore Department',
        'force-delete': 'Delete Permanently',
        default: 'Delete Department',
    }

    const buttonText = processing
        ? 'Processing...'
        : actionLabels[departmentAction?.type ?? ''] ?? actionLabels.default

    const buttonClass = isRestore
        ? 'bg-emerald-600 hover:bg-emerald-700'
        : 'bg-rose-600 hover:bg-rose-700'

    const filteredDepartments = useMemo(() => {
        const query = search.trim().toLowerCase()

        if (!query) return departments

        return departments.filter((department) => {
            const managers = department.managers
                .map((manager) => manager.name.toLowerCase())
                .join(' ')
            const status = getDepartmentSearchStatus(department)
            const type = formatDepartmentType(department.type).toLowerCase()
            const rawType = department.type.toLowerCase()
            const statusName = department.status?.name?.toLowerCase() ?? ''

            return (
                department.name.toLowerCase().includes(query) ||
                managers.includes(query) ||
                status.includes(query) ||
                type.includes(query) ||
                rawType.includes(query) ||
                statusName.includes(query)
            )
        })
    }, [departments, search])

    const sortedDepartments = useMemo(() => {
        const result = [...filteredDepartments]

        result.sort((a, b) => {
            if (sortField === 'name') {
                const compare = a.name.localeCompare(b.name)
                return sortDirection === 'asc' ? compare : -compare
            }

            if (sortField === 'type') {
                const compare = formatDepartmentType(a.type).localeCompare(
                    formatDepartmentType(b.type),
                )

                return sortDirection === 'asc' ? compare : -compare
            }

            return 0
        })

        return result
    }, [filteredDepartments, sortField, sortDirection])

    const activeDepartmentsCount = useMemo(() => {
        return departments.filter((department) => !department.is_deleted).length
    }, [departments])

    const deletedDepartmentsCount = useMemo(() => {
        return departments.filter((department) => department.is_deleted).length
    }, [departments])

    const defaultDepartmentsCount = useMemo(() => {
        return departments.filter(
            (department) => !department.is_deleted && department.is_default,
        ).length
    }, [departments])

    function openActionDialog(
        type: 'delete' | 'restore' | 'force-delete',
        department: Department,
    ) {
        setDepartmentAction({ type, department })
    }

    function closeActionDialog() {
        if (processing) return
        setDepartmentAction(null)
    }

    function handleSort(field: SortField) {
        if (sortField === field) {
            setSortDirection((prev) => (prev === 'asc' ? 'desc' : 'asc'))
            return
        }

        setSortField(field)
        setSortDirection('asc')
    }

    function renderSortIcon(field: SortField) {
        if (sortField !== field) {
            return <ArrowUpDown className="h-4 w-4 text-gray-400" />
        }

        return sortDirection === 'asc' ? (
            <ArrowUp className="h-4 w-4 text-sky-600" />
        ) : (
            <ArrowDown className="h-4 w-4 text-sky-600" />
        )
    }

    function handleAction() {
        if (!departmentAction) return

        setProcessing(true)

        if (departmentAction.type === 'delete') {
            router.delete(
                route('admin.departments.destroy', departmentAction.department.id),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setDepartmentAction(null)
                    },
                    onFinish: () => {
                        setProcessing(false)
                    },
                },
            )

            return
        }

        if (departmentAction.type === 'restore') {
            router.patch(
                route(
                    'admin.departments.restore',
                    departmentAction.department.id,
                ),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setDepartmentAction(null)
                    },
                    onFinish: () => {
                        setProcessing(false)
                    },
                },
            )

            return
        }

        router.delete(
            route(
                'admin.departments.force-delete',
                departmentAction.department.id,
            ),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDepartmentAction(null)
                },
                onFinish: () => {
                    setProcessing(false)
                },
            },
        )
    }

    return (
        <AdminLayout title="Departments">
            <Head title="Departments" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="min-w-0">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                        <Building2 className="h-6 w-6 text-sky-600" />
                                    </div>

                                    <div>
                                        <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Departments
                                        </h1>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Organize support structure by
                                            department, assignment rules, and
                                            ownership.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div className="relative w-full sm:w-80">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={(e) =>
                                            setSearch(e.target.value)
                                        }
                                        placeholder="Search departments, managers, type, or status..."
                                        className="h-11 w-full rounded-2xl border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                    />
                                </div>

                                <Link
                                    href={route('admin.departments.create')}
                                    className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                >
                                    <Plus className="h-4 w-4" />
                                    New Department
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                        <div className="grid gap-3 sm:grid-cols-3">
                            <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                    Total departments
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900">
                                    {departments.length}
                                </p>
                            </div>

                            <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                    Active departments
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900">
                                    {activeDepartmentsCount}
                                </p>
                            </div>

                            <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                    Deleted departments
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900">
                                    {deletedDepartmentsCount}
                                </p>
                            </div>
                        </div>

                        <div className="mt-3">
                            <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                    Default departments
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900">
                                    {defaultDepartmentsCount}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        {sortedDepartments.length > 0 ? (
                            <>
                                <div className="hidden overflow-hidden rounded-[24px] border border-gray-200 lg:block">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleSort('name')
                                                    }
                                                    className="inline-flex cursor-pointer items-center gap-2 transition hover:text-sky-700"
                                                >
                                                    Name
                                                    {renderSortIcon('name')}
                                                </button>
                                            </th>

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleSort('type')
                                                    }
                                                    className="inline-flex cursor-pointer items-center gap-2 transition hover:text-sky-700"
                                                >
                                                    Type
                                                    {renderSortIcon('type')}
                                                </button>
                                            </th>

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Managers
                                            </th>

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Status
                                            </th>

                                            <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Actions
                                            </th>
                                        </tr>
                                        </thead>

                                        <tbody className="divide-y divide-gray-100 bg-white">
                                        {sortedDepartments.map(
                                            (department) => {
                                                const managersLabel =
                                                    department.managers
                                                        .map(
                                                            (manager) =>
                                                                manager.name,
                                                        )
                                                        .join(', ')

                                                return (
                                                    <tr
                                                        key={department.id}
                                                        className={`transition ${
                                                            department.is_deleted
                                                                ? 'bg-rose-50/30 hover:bg-rose-50/50'
                                                                : 'hover:bg-sky-50/40'
                                                        }`}
                                                    >
                                                        <td className="px-6 py-5">
                                                            <div>
                                                                <p
                                                                    className={`font-semibold ${
                                                                        department.is_deleted
                                                                            ? 'text-gray-500 line-through'
                                                                            : 'text-gray-900'
                                                                    }`}
                                                                >
                                                                    {
                                                                        department.name
                                                                    }
                                                                </p>
                                                                <p className="mt-1 text-sm text-gray-500">
                                                                    Department #
                                                                    {
                                                                        department.id
                                                                    }
                                                                </p>
                                                            </div>
                                                        </td>

                                                        <td className="px-6 py-5">
                                                            <div className="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700">
                                                                {formatDepartmentType(
                                                                    department.type,
                                                                )}
                                                            </div>
                                                        </td>

                                                        <td className="px-6 py-5 text-sm text-gray-600">
                                                            {managersLabel ||
                                                                '—'}
                                                        </td>

                                                        <td className="px-6 py-5">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                {department.is_deleted && (
                                                                    <span className="inline-flex rounded-full px-3 py-1 text-xs font-semibold bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200">
            Deleted
        </span>
                                                                )}

                                                                {!department.is_deleted && department.is_default && (
                                                                    <span className="inline-flex rounded-full px-3 py-1 text-xs font-semibold bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">
                                                                        Default
                                                                    </span>
                                                                    )}

                                                                    {department.status?.name && (
                                                                        <span className="inline-flex rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200">
                                                                            {department.status.name}
                                                                        </span>
                                                                )}
                                                            </div>
                                                        </td>

                                                        <td className="px-6 py-5">
                                                            {department.is_deleted ? (
                                                                <div className="flex items-center justify-end gap-2">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            openActionDialog(
                                                                                'restore',
                                                                                department,
                                                                            )
                                                                        }
                                                                        className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
                                                                    >
                                                                        <RotateCcw className="h-4 w-4" />
                                                                        Restore
                                                                    </button>

                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            openActionDialog(
                                                                                'force-delete',
                                                                                department,
                                                                            )
                                                                        }
                                                                        className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                                                                    >
                                                                        <ShieldAlert className="h-4 w-4" />
                                                                        Delete
                                                                        permanently
                                                                    </button>
                                                                </div>
                                                            ) : (
                                                                <div className="flex items-center justify-end gap-2">
                                                                    <Link
                                                                        href={route(
                                                                            'admin.departments.show',
                                                                            department.id,
                                                                        )}
                                                                        className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                                    >
                                                                        <Eye className="h-4 w-4" />
                                                                    </Link>

                                                                    <Link
                                                                        href={route(
                                                                            'admin.departments.edit',
                                                                            department.id,
                                                                        )}
                                                                        className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </Link>

                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            openActionDialog(
                                                                                'delete',
                                                                                department,
                                                                            )
                                                                        }
                                                                        className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </button>
                                                                </div>
                                                            )}
                                                        </td>
                                                    </tr>
                                                )
                                            },
                                        )}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="grid gap-4 lg:hidden">
                                    {sortedDepartments.map((department) => {
                                        const managersLabel =
                                            department.managers
                                                .map((manager) => manager.name)
                                                .join(', ')

                                        return (
                                            <div
                                                key={department.id}
                                                className={`rounded-[24px] border p-5 ${
                                                    department.is_deleted
                                                        ? 'border-rose-200 bg-rose-50/50'
                                                        : 'border-gray-200 bg-gray-50'
                                                }`}
                                            >
                                                <div className="flex items-start justify-between gap-4">
                                                    <div className="min-w-0">
                                                        <h3
                                                            className={`text-base font-semibold ${
                                                                department.is_deleted
                                                                    ? 'text-gray-500 line-through'
                                                                    : 'text-gray-900'
                                                            }`}
                                                        >
                                                            {department.name}
                                                        </h3>
                                                        <p className="mt-1 text-sm text-gray-500">
                                                            Department #
                                                            {department.id}
                                                        </p>
                                                    </div>

                                                    <span
                                                        className={`inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getDepartmentStatusClasses(department)}`}
                                                    >
                                                        {getDepartmentStatusLabel(
                                                            department,
                                                        )}
                                                    </span>
                                                </div>

                                                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                                    <div className="rounded-2xl bg-white px-4 py-3 ring-1 ring-inset ring-gray-200">
                                                        <p className="text-xs uppercase tracking-wide text-gray-400">
                                                            Type
                                                        </p>
                                                        <p className="mt-1 text-sm font-medium text-gray-900">
                                                            {formatDepartmentType(
                                                                department.type,
                                                            )}
                                                        </p>
                                                    </div>

                                                    <div className="rounded-2xl bg-white px-4 py-3 ring-1 ring-inset ring-gray-200">
                                                        <p className="text-xs uppercase tracking-wide text-gray-400">
                                                            Managers
                                                        </p>
                                                        <p className="mt-1 text-sm font-medium text-gray-900">
                                                            {managersLabel ||
                                                                '—'}
                                                        </p>
                                                    </div>
                                                </div>

                                                {department.status?.name ? (
                                                    <div className="mt-3 rounded-2xl bg-white px-4 py-3 ring-1 ring-inset ring-gray-200">
                                                        <p className="text-xs uppercase tracking-wide text-gray-400">
                                                            Department Status
                                                        </p>
                                                        <p className="mt-1 text-sm font-medium text-gray-900">
                                                            {
                                                                department.status
                                                                    .name
                                                            }
                                                        </p>
                                                    </div>
                                                ) : null}

                                                {department.is_deleted ? (
                                                    <div className="mt-4 grid gap-2 sm:grid-cols-2">
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                openActionDialog(
                                                                    'restore',
                                                                    department,
                                                                )
                                                            }
                                                            className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
                                                        >
                                                            <RotateCcw className="h-4 w-4" />
                                                            Restore
                                                        </button>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                openActionDialog(
                                                                    'force-delete',
                                                                    department,
                                                                )
                                                            }
                                                            className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                                                        >
                                                            <ShieldAlert className="h-4 w-4" />
                                                            Delete permanently
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <div className="mt-4 flex items-center gap-2">
                                                        <Link
                                                            href={route(
                                                                'admin.departments.show',
                                                                department.id,
                                                            )}
                                                            className="inline-flex h-10 flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                            View
                                                        </Link>

                                                        <Link
                                                            href={route(
                                                                'admin.departments.edit',
                                                                department.id,
                                                            )}
                                                            className="inline-flex h-10 flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                            Edit
                                                        </Link>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                openActionDialog(
                                                                    'delete',
                                                                    department,
                                                                )
                                                            }
                                                            className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        )
                                    })}
                                </div>

                                <div className="mt-4 flex items-center justify-between px-1 text-sm text-gray-500">
                                    <span>
                                        Showing {sortedDepartments.length} of{' '}
                                        {departments.length} departments
                                    </span>
                                </div>
                            </>
                        ) : (
                            <div className="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-sm ring-1 ring-inset ring-gray-200">
                                    <Building2 className="h-8 w-8 text-gray-400" />
                                </div>

                                <h3 className="mt-5 text-lg font-semibold text-gray-900">
                                    No departments found
                                </h3>

                                <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
                                    {search
                                        ? 'Try changing your search query or clear the search field.'
                                        : 'Create your first department to organize support ownership and routing.'}
                                </p>

                                <div className="mt-6">
                                    <Link
                                        href={route('admin.departments.create')}
                                        className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Create Department
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </section>
            </div>

            <AlertDialog
                open={departmentAction !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        closeActionDialog()
                    }
                }}
            >
                <AlertDialogContent className="rounded-[28px] border border-gray-200 bg-white p-0 shadow-xl">
                    <div
                        className={`border-b border-gray-100 px-6 py-5 ${
                            departmentAction?.type === 'restore'
                                ? 'bg-gradient-to-r from-emerald-50 to-white'
                                : 'bg-gradient-to-r from-rose-50 to-white'
                        }`}
                    >
                        <AlertDialogHeader>
                            <AlertDialogTitle className="text-xl font-semibold tracking-tight text-gray-900">
                                {
                                    dialogTitles[
                                    departmentAction?.type ?? 'delete'
                                        ]
                                }
                            </AlertDialogTitle>

                            <AlertDialogDescription className="mt-2 text-sm leading-6 text-gray-500">
                                {getDialogDescription(departmentAction)}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                    </div>

                    <div className="px-6 py-5">
                        <AlertDialogFooter className="gap-3">
                            <AlertDialogCancel
                                disabled={processing}
                                className="mt-0 cursor-pointer rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            >
                                Cancel
                            </AlertDialogCancel>

                            <AlertDialogAction
                                onClick={(e) => {
                                    e.preventDefault()
                                    handleAction()
                                }}
                                disabled={processing}
                                className={`cursor-pointer rounded-2xl px-4 text-sm font-medium text-white transition disabled:cursor-not-allowed disabled:opacity-70 ${buttonClass}`}
                            >
                                {buttonText}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </div>
                </AlertDialogContent>
            </AlertDialog>
        </AdminLayout>
    )
}

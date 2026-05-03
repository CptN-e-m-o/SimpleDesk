import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link, router } from '@inertiajs/react'
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    ShieldCheck,
    Trash2,
    UsersRound,
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

type Role = {
    id: number
    name: string
    description?: string | null
    is_system: boolean
    is_deleted: boolean
    deleted_at?: string | null
    updated_at?: string | null
}

type Props = {
    readonly roles?: Role[]
}

type RoleAction =
    | {
    type: 'delete' | 'restore' | 'force-delete'
    role: Role
}
    | null

type SortField = 'id' | 'name' | 'updated_at'
type SortDirection = 'asc' | 'desc'

const dialogTitles: Record<string, string> = {
    restore: 'Restore role?',
    'force-delete': 'Delete role permanently?',
    delete: 'Delete role?',
}

function getRoleStatusLabel(role: Role) {
    if (role.is_deleted) return 'Deleted'
    if (role.is_system) return 'System'
    return 'Custom'
}

function getRoleStatusClasses(role: Role) {
    if (role.is_deleted) {
        return 'bg-rose-50 text-rose-700 ring-rose-200'
    }

    if (role.is_system) {
        return 'bg-sky-50 text-sky-700 ring-sky-200'
    }

    return 'bg-emerald-50 text-emerald-700 ring-emerald-200'
}

function getDialogDescription(action?: RoleAction) {
    if (!action) return null

    const name = action.role.name

    if (action.type === 'restore') {
        return (
            <>
                Role <span className="font-semibold text-gray-900">{name}</span>{' '}
                will be restored and returned to the list.
            </>
        )
    }

    if (action.type === 'force-delete') {
        return (
            <>
                Role <span className="font-semibold text-gray-900">{name}</span>{' '}
                will be permanently deleted. This action cannot be undone.
            </>
        )
    }

    return (
        <>
            Role <span className="font-semibold text-gray-900">{name}</span>{' '}
            will be soft deleted and can be restored later.
        </>
    )
}

export default function Index({ roles = [] }: Props) {
    const [search, setSearch] = useState('')
    const [roleAction, setRoleAction] = useState<RoleAction>(null)
    const [processing, setProcessing] = useState(false)
    const [sortField, setSortField] = useState<SortField>('id')
    const [sortDirection, setSortDirection] = useState<SortDirection>('asc')

    const filteredRoles = useMemo(() => {
        const query = search.trim().toLowerCase()

        if (!query) return roles

        return roles.filter((role) => {
            return (
                role.name.toLowerCase().includes(query) ||
                (role.description ?? '').toLowerCase().includes(query) ||
                getRoleStatusLabel(role).toLowerCase().includes(query)
            )
        })
    }, [roles, search])

    const sortedRoles = useMemo(() => {
        const result = [...filteredRoles]

        result.sort((a, b) => {
            const first = String(a[sortField] ?? '')
            const second = String(b[sortField] ?? '')
            const compare = first.localeCompare(second)

            return sortDirection === 'asc' ? compare : -compare
        })

        return result
    }, [filteredRoles, sortField, sortDirection])

    const systemRolesCount = useMemo(() => {
        return roles.filter((role) => !role.is_deleted && role.is_system).length
    }, [roles])

    const customRolesCount = useMemo(() => {
        return roles.filter((role) => !role.is_deleted && !role.is_system).length
    }, [roles])

    const deletedRolesCount = useMemo(() => {
        return roles.filter((role) => role.is_deleted).length
    }, [roles])

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

    function openActionDialog(
        type: 'delete' | 'restore' | 'force-delete',
        role: Role,
    ) {
        setRoleAction({ type, role })
    }

    function closeActionDialog() {
        if (processing) return
        setRoleAction(null)
    }

    function handleAction() {
        if (!roleAction) return

        setProcessing(true)

        if (roleAction.type === 'delete') {
            router.delete(route('admin.roles.destroy', roleAction.role.id), {
                preserveScroll: true,
                onSuccess: () => setRoleAction(null),
                onFinish: () => setProcessing(false),
            })

            return
        }

        if (roleAction.type === 'restore') {
            router.patch(
                route('admin.roles.restore', roleAction.role.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => setRoleAction(null),
                    onFinish: () => setProcessing(false),
                },
            )

            return
        }

        router.delete(route('admin.roles.force-delete', roleAction.role.id), {
            preserveScroll: true,
            onSuccess: () => setRoleAction(null),
            onFinish: () => setProcessing(false),
        })
    }

    const isRestore = roleAction?.type === 'restore'

    const buttonText = processing
        ? 'Processing...'
        : roleAction?.type === 'restore'
            ? 'Restore Role'
            : roleAction?.type === 'force-delete'
                ? 'Delete Permanently'
                : 'Delete Role'

    const buttonClass = isRestore
        ? 'bg-emerald-600 hover:bg-emerald-700'
        : 'bg-rose-600 hover:bg-rose-700'

    return (
        <AdminLayout title="Roles">
            <Head title="Roles" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <ShieldCheck className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Roles
                                    </h1>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Manage system and custom roles for users
                                        and agents.
                                    </p>
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
                                        placeholder="Search roles, description, or status..."
                                        className="h-11 w-full rounded-2xl border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                    />
                                </div>

                                <div className="relative group">
                                    <button
                                        type="button"
                                        className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                    >
                                        <Plus className="h-4 w-4" />
                                        New Role
                                    </button>

                                    <div className="invisible absolute right-0 top-full z-20 w-56 pt-2 opacity-0 transition group-hover:visible group-hover:opacity-100">
                                        <div className="rounded-2xl border border-gray-200 bg-white p-2 shadow-xl">
                                            <Link
                                                href={route('admin.roles.create.typed', { type: 'user' })}
                                                className="block rounded-xl px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-sky-50 hover:text-sky-700"
                                            >
                                                Create User Role
                                            </Link>

                                            <Link
                                                href={route('admin.roles.create.typed', { type: 'agent' })}
                                                className="block rounded-xl px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-sky-50 hover:text-sky-700"
                                            >
                                                Create Agent Role
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                        <div className="grid gap-3 sm:grid-cols-4">
                            <Stat label="Total roles" value={roles.length} />
                            <Stat label="System roles" value={systemRolesCount} />
                            <Stat label="Custom roles" value={customRolesCount} />
                            <Stat
                                label="Deleted roles"
                                value={deletedRolesCount}
                            />
                        </div>
                    </div>

                    <div className="p-6">
                        {sortedRoles.length > 0 ? (
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
                                                Description
                                            </th>

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Status
                                            </th>

                                            <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleSort(
                                                            'updated_at',
                                                        )
                                                    }
                                                    className="inline-flex cursor-pointer items-center gap-2 transition hover:text-sky-700"
                                                >
                                                    Updated At
                                                    {renderSortIcon(
                                                        'updated_at',
                                                    )}
                                                </button>
                                            </th>

                                            <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Actions
                                            </th>
                                        </tr>
                                        </thead>

                                        <tbody className="divide-y divide-gray-100 bg-white">
                                        {sortedRoles.map((role) => (
                                            <tr
                                                key={role.id}
                                                className={`transition ${
                                                    role.is_deleted
                                                        ? 'bg-rose-50/30 hover:bg-rose-50/50'
                                                        : 'hover:bg-sky-50/40'
                                                }`}
                                            >
                                                <td className="px-6 py-5">
                                                    <p
                                                        className={`font-semibold ${
                                                            role.is_deleted
                                                                ? 'text-gray-500 line-through'
                                                                : 'text-gray-900'
                                                        }`}
                                                    >
                                                        {role.name}
                                                    </p>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        Role #{role.id}
                                                    </p>
                                                </td>

                                                <td className="max-w-xl px-6 py-5 text-sm leading-6 text-gray-600">
                                                    {role.description ||
                                                        '—'}
                                                </td>

                                                <td className="px-6 py-5">
                                                        <span
                                                            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getRoleStatusClasses(role)}`}
                                                        >
                                                            {getRoleStatusLabel(
                                                                role,
                                                            )}
                                                        </span>
                                                </td>

                                                <td className="px-6 py-5 text-sm text-gray-600">
                                                    {role.updated_at || '—'}
                                                </td>

                                                <td className="px-6 py-5">
                                                    <RoleActions
                                                        role={role}
                                                        openActionDialog={
                                                            openActionDialog
                                                        }
                                                    />
                                                </td>
                                            </tr>
                                        ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="grid gap-4 lg:hidden">
                                    {sortedRoles.map((role) => (
                                        <div
                                            key={role.id}
                                            className={`rounded-[24px] border p-5 ${
                                                role.is_deleted
                                                    ? 'border-rose-200 bg-rose-50/50'
                                                    : 'border-gray-200 bg-gray-50'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <h3
                                                        className={`text-base font-semibold ${
                                                            role.is_deleted
                                                                ? 'text-gray-500 line-through'
                                                                : 'text-gray-900'
                                                        }`}
                                                    >
                                                        {role.name}
                                                    </h3>
                                                    <p className="mt-1 text-sm text-gray-500">
                                                        Role #{role.id}
                                                    </p>
                                                </div>

                                                <span
                                                    className={`inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getRoleStatusClasses(role)}`}
                                                >
                                                    {getRoleStatusLabel(role)}
                                                </span>
                                            </div>

                                            <div className="mt-4 rounded-2xl bg-white px-4 py-3 ring-1 ring-inset ring-gray-200">
                                                <p className="text-xs uppercase tracking-wide text-gray-400">
                                                    Description
                                                </p>
                                                <p className="mt-1 text-sm font-medium text-gray-900">
                                                    {role.description || '—'}
                                                </p>
                                            </div>

                                            <div className="mt-3 rounded-2xl bg-white px-4 py-3 ring-1 ring-inset ring-gray-200">
                                                <p className="text-xs uppercase tracking-wide text-gray-400">
                                                    Updated At
                                                </p>
                                                <p className="mt-1 text-sm font-medium text-gray-900">
                                                    {role.updated_at || '—'}
                                                </p>
                                            </div>

                                            <div className="mt-4">
                                                <RoleActions
                                                    role={role}
                                                    mobile
                                                    openActionDialog={
                                                        openActionDialog
                                                    }
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-4 px-1 text-sm text-gray-500">
                                    Showing {sortedRoles.length} of{' '}
                                    {roles.length} roles
                                </div>
                            </>
                        ) : (
                            <div className="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-sm ring-1 ring-inset ring-gray-200">
                                    <UsersRound className="h-8 w-8 text-gray-400" />
                                </div>

                                <h3 className="mt-5 text-lg font-semibold text-gray-900">
                                    No roles found
                                </h3>

                                <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
                                    {search
                                        ? 'Try changing your search query or clear the search field.'
                                        : 'Create your first custom role to manage access rules.'}
                                </p>

                                <div className="mt-6">
                                    <Link
                                        href={route('admin.roles.create')}
                                        className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Create Role
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                </section>
            </div>

            <AlertDialog
                open={roleAction !== null}
                onOpenChange={(open) => {
                    if (!open) closeActionDialog()
                }}
            >
                <AlertDialogContent className="rounded-[28px] border border-gray-200 bg-white p-0 shadow-xl">
                    <div
                        className={`border-b border-gray-100 px-6 py-5 ${
                            roleAction?.type === 'restore'
                                ? 'bg-gradient-to-r from-emerald-50 to-white'
                                : 'bg-gradient-to-r from-rose-50 to-white'
                        }`}
                    >
                        <AlertDialogHeader>
                            <AlertDialogTitle className="text-xl font-semibold tracking-tight text-gray-900">
                                {dialogTitles[roleAction?.type ?? 'delete']}
                            </AlertDialogTitle>

                            <AlertDialogDescription className="mt-2 text-sm leading-6 text-gray-500">
                                {getDialogDescription(roleAction)}
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

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
            <p className="text-xs font-medium uppercase tracking-wide text-gray-400">
                {label}
            </p>
            <p className="mt-2 text-2xl font-semibold text-gray-900">{value}</p>
        </div>
    )
}

function RoleActions({
                         role,
                         mobile = false,
                         openActionDialog,
                     }: {
    role: Role
    mobile?: boolean
    openActionDialog: (
        type: 'delete' | 'restore' | 'force-delete',
        role: Role,
    ) => void
}) {
    if (role.is_deleted) {
        return (
            <div
                className={
                    mobile
                        ? 'grid gap-2 sm:grid-cols-2'
                        : 'flex items-center justify-end gap-2'
                }
            >
                <button
                    type="button"
                    onClick={() => openActionDialog('restore', role)}
                    className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100"
                >
                    <RotateCcw className="h-4 w-4" />
                    Restore
                </button>

                <button
                    type="button"
                    onClick={() => openActionDialog('force-delete', role)}
                    className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                >
                    <Trash2 className="h-4 w-4" />
                    Delete permanently
                </button>
            </div>
        )
    }

    return (
        <div
            className={
                mobile
                    ? 'flex items-center gap-2'
                    : 'flex items-center justify-end gap-2'
            }
        >
            <Link
                href={route('admin.roles.edit', role.id)}
                className={
                    mobile
                        ? 'inline-flex h-10 flex-1 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700'
                        : 'inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700'
                }
            >
                <Pencil className="h-4 w-4" />
                {mobile ? 'Edit' : null}
            </Link>

            {!role.is_system ? (
                <button
                    type="button"
                    onClick={() => openActionDialog('delete', role)}
                    className="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                >
                    <Trash2 className="h-4 w-4" />
                </button>
            ) : null}
        </div>
    )
}

import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link } from '@inertiajs/react'
import {
    ArrowLeft,
    BarChart3,
    Building2,
    ClipboardList,
    Eye,
    FileText,
    Mail,
    Pencil,
    Shield,
    TrendingUp,
    UserCheck,
    UsersRound,
} from 'lucide-react'
import { route } from 'ziggy-js'
import { useMemo, useState } from 'react'

type DepartmentStatus = {
    id: number
    name: string
    code: string
    color?: string | null
} | null

type DepartmentUser = {
    id: number
    name: string
    email: string
    is_manager?: boolean
}

type DepartmentTeam = {
    id: number
    name: string
}

type DepartmentData = {
    id: number
    name: string
    slug: string
    type: string
    business_hours: string | null
    outgoing_email: string | null
    signature: string | null
    is_default: boolean
    status: DepartmentStatus
    managers: DepartmentUser[]
    teams: DepartmentTeam[]
    users: DepartmentUser[]
    members_count: number
    teams_count: number
    managers_count: number
}

type Props = {
    readonly department: DepartmentData
}

function formatDepartmentType(type: string) {
    if (!type) return '—'

    return type
        .split(/[_\s-]+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ')
}

function getTypeClasses(type: string) {
    return type === 'private'
        ? 'bg-violet-50 text-violet-700 ring-violet-200'
        : 'bg-sky-50 text-sky-700 ring-sky-200'
}

export default function Show({ department }: Props) {
    const [search, setSearch] = useState('')

    const filteredUsers = useMemo(() => {
        const query = search.trim().toLowerCase()

        if (!query) return department.users

        return department.users.filter((user) => {
            return (
                user.name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query)
            )
        })
    }, [search, department.users])

    return (
        <AdminLayout title="Department Details">
            <Head title={`${department.name} - Department`} />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-sm font-medium text-sky-600">
                            <Shield className="h-4 w-4" />
                            Department Management
                        </div>

                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                            {department.name}
                        </h1>

                        <div className="mt-3 flex flex-wrap items-center gap-2">
                            <span
                                className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getTypeClasses(department.type)}`}
                            >
                                {formatDepartmentType(department.type)}
                            </span>

                            {department.is_default && (
                                <span className="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                    Default Department
                                </span>
                            )}

                            {department.status?.name && (
                                <span className="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                    {department.status.name}
                                </span>
                            )}

                            <span className="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
                                {department.members_count} member
                                {department.members_count === 1 ? '' : 's'}
                            </span>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href={route('admin.departments.index')}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back
                        </Link>

                        <Link
                            href={route('admin.departments.edit', department.id)}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                        >
                            <Pencil className="h-4 w-4" />
                            Edit Department
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[340px_minmax(0,1fr)]">
                    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div className="flex items-center gap-3">
                                <div className="flex h-14 w-14 items-center justify-center rounded-3xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <Building2 className="h-7 w-7 text-sky-600" />
                                </div>

                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900">
                                        Department Summary
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Overview and key metadata.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="p-6">
                            <div className="rounded-[24px] bg-gray-50 p-5 ring-1 ring-inset ring-gray-200">
                                <div className="text-center">
                                    <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-[28px] bg-white shadow-sm ring-1 ring-inset ring-gray-200">
                                        <Building2 className="h-10 w-10 text-sky-600" />
                                    </div>

                                    <h3 className="mt-4 text-2xl font-semibold tracking-tight text-gray-900">
                                        {department.name}
                                    </h3>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Department
                                    </p>
                                </div>

                                <div className="mt-6 space-y-4">
                                    <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Type
                                        </span>
                                        <span
                                            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getTypeClasses(department.type)}`}
                                        >
                                            {formatDepartmentType(department.type)}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Status
                                        </span>
                                        <span className="text-right text-sm font-semibold text-gray-900">
                                            {department.status?.name ?? '—'}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Managers
                                        </span>
                                        <span className="text-right text-sm font-semibold text-gray-900">
                                            {department.managers_count}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Members
                                        </span>
                                        <span className="text-right text-sm font-semibold text-gray-900">
                                            {department.members_count}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Teams
                                        </span>
                                        <span className="text-right text-sm font-semibold text-gray-900">
                                            {department.teams_count}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Business Hours
                                        </span>
                                        <span className="text-right text-sm font-semibold text-gray-900">
                                            {department.business_hours ?? '—'}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Outgoing Email
                                        </span>
                                        <span className="text-right text-sm font-semibold text-gray-900">
                                            {department.outgoing_email ?? '—'}
                                        </span>
                                    </div>
                                </div>

                                <Link
                                    href={route('admin.departments.edit', department.id)}
                                    className="mt-6 inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                >
                                    <Pencil className="h-4 w-4" />
                                    Edit Department
                                </Link>
                            </div>
                        </div>
                    </section>

                    <div className="space-y-6">
                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Department Members
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Users assigned to this department.
                                        </p>
                                    </div>

                                    <div className="relative w-full lg:w-80">
                                        <input
                                            type="text"
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            placeholder="Search members..."
                                            className="h-11 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                                <div className="grid gap-3 md:grid-cols-3">
                                    <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                        <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                            <UsersRound className="h-4 w-4" />
                                            Total Members
                                        </div>
                                        <p className="mt-2 text-2xl font-semibold text-gray-900">
                                            {department.members_count}
                                        </p>
                                    </div>

                                    <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                        <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                            <UserCheck className="h-4 w-4" />
                                            Managers
                                        </div>
                                        <p className="mt-2 text-2xl font-semibold text-gray-900">
                                            {department.managers_count}
                                        </p>
                                    </div>

                                    <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                        <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                            <Building2 className="h-4 w-4" />
                                            Teams
                                        </div>
                                        <p className="mt-2 text-2xl font-semibold text-gray-900">
                                            {department.teams_count}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-6">
                                {filteredUsers.length > 0 ? (
                                    <div className="hidden overflow-hidden rounded-[24px] border border-gray-200 lg:block">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Name
                                                </th>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Email
                                                </th>
                                                <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Role
                                                </th>
                                                <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Actions
                                                </th>
                                            </tr>
                                            </thead>

                                            <tbody className="divide-y divide-gray-100 bg-white">
                                            {filteredUsers.map((user) => (
                                                <tr
                                                    key={user.id}
                                                    className="transition hover:bg-sky-50/40"
                                                >
                                                    <td className="px-6 py-5">
                                                        <p className="font-semibold text-gray-900">
                                                            {user.name}
                                                        </p>
                                                        <p className="mt-1 text-sm text-gray-500">
                                                            User #{user.id}
                                                        </p>
                                                    </td>

                                                    <td className="px-6 py-5 text-sm text-gray-600">
                                                        <div className="inline-flex items-center gap-2">
                                                            <Mail className="h-4 w-4 text-gray-400" />
                                                            {user.email}
                                                        </div>
                                                    </td>

                                                    <td className="px-6 py-5">
                                                        {user.is_manager ? (
                                                            <span className="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                                                    Manager
                                                                </span>
                                                        ) : (
                                                            <span className="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
                                                                    Member
                                                                </span>
                                                        )}
                                                    </td>

                                                    <td className="px-6 py-5">
                                                        <div className="flex justify-end">
                                                            <Link
                                                                href="#"
                                                                className="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
                                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-sm ring-1 ring-inset ring-gray-200">
                                            <UsersRound className="h-8 w-8 text-gray-400" />
                                        </div>

                                        <h3 className="mt-5 text-lg font-semibold text-gray-900">
                                            No members found
                                        </h3>

                                        <p className="mt-2 text-sm leading-6 text-gray-500">
                                            Try changing the search query or update department managers.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 ring-1 ring-inset ring-violet-100">
                                        <Building2 className="h-5 w-5 text-violet-600" />
                                    </div>

                                    <div>
                                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Connected Teams
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Teams linked to this department.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-6">
                                {department.teams.length > 0 ? (
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {department.teams.map((team) => (
                                            <div
                                                key={team.id}
                                                className="rounded-[24px] border border-gray-200 bg-gray-50 p-5"
                                            >
                                                <p className="font-semibold text-gray-900">
                                                    {team.name}
                                                </p>
                                                <p className="mt-1 text-sm text-gray-500">
                                                    Team #{team.id}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center">
                                        <p className="text-sm font-medium text-gray-700">
                                            No teams connected
                                        </p>
                                    </div>
                                )}
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 ring-1 ring-inset ring-violet-100">
                                        <FileText className="h-5 w-5 text-violet-600" />
                                    </div>

                                    <div>
                                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Department Signature
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Signature used for department replies.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-6">
                                {department.signature ? (
                                    <div
                                        className="prose max-w-none rounded-[24px] border border-gray-200 bg-gray-50 px-5 py-4 prose-p:text-gray-700 prose-li:text-gray-700 prose-strong:text-gray-900"
                                        dangerouslySetInnerHTML={{
                                            __html: department.signature,
                                        }}
                                    />
                                ) : (
                                    <div className="rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center">
                                        <p className="text-sm font-medium text-gray-700">
                                            No signature yet
                                        </p>
                                        <p className="mt-1 text-sm text-gray-500">
                                            You can add a signature when editing this department.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </section>

                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 ring-1 ring-inset ring-emerald-100">
                                        <BarChart3 className="h-5 w-5 text-emerald-600" />
                                    </div>

                                    <div>
                                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Department Report
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Placeholder analytics block for future reporting.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-6">
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="rounded-[24px] border border-gray-200 bg-gray-50 p-5">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-gray-200">
                                                <ClipboardList className="h-5 w-5 text-sky-600" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">
                                                    Open Tickets
                                                </p>
                                                <p className="mt-1 text-2xl font-semibold text-gray-900">
                                                    0
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="rounded-[24px] border border-gray-200 bg-gray-50 p-5">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-gray-200">
                                                <TrendingUp className="h-5 w-5 text-amber-600" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">
                                                    Closed Tickets
                                                </p>
                                                <p className="mt-1 text-2xl font-semibold text-gray-900">
                                                    0
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="rounded-[24px] border border-gray-200 bg-gray-50 p-5">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white ring-1 ring-inset ring-gray-200">
                                                <BarChart3 className="h-5 w-5 text-emerald-600" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">
                                                    SLA Performance
                                                </p>
                                                <p className="mt-1 text-2xl font-semibold text-gray-900">
                                                    —
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </AdminLayout>
    )
}

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

type TeamMember = {
    id: number
    name: string
    email: string
    is_lead: boolean
}

type TeamDepartment = {
    id: number
    name: string
    slug: string
}

type TeamData = {
    id: number
    name: string
    departments: TeamDepartment[]
    is_active: boolean
    admin_notes: string | null
    members_count: number
    lead: {
        id: number
        name: string
        email: string
    } | null
    members: TeamMember[]
}

type Props = {
    readonly team: TeamData
}


function getStatusClasses(isActive: boolean) {
    return isActive
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
        : 'bg-rose-50 text-rose-700 ring-rose-200'
}


export default function Show({ team }: Props) {
    const [search, setSearch] = useState('')

    const filteredMembers = useMemo(() => {
        const query = search.trim().toLowerCase()

        if (!query) return team.members

        return team.members.filter((member) => {
            return (
                member.name.toLowerCase().includes(query) ||
                member.email.toLowerCase().includes(query)
            )
        })
    }, [search, team.members])

    return (
        <AdminLayout title="Team Details">
            <Head title={`${team.name} - Team`} />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-sm font-medium text-sky-600">
                            <Shield className="h-4 w-4" />
                            Team Management
                        </div>

                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                            {team.name}
                        </h1>

                        <div className="mt-3 flex flex-wrap items-center gap-2">
                            <span
                                className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getStatusClasses(team.is_active)}`}
                            >
                                {team.is_active ? 'Active' : 'Inactive'}
                            </span>

                            <span className="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">
                                {team.members_count} member{team.members_count === 1 ? '' : 's'}
                            </span>

                            {team.lead && (
                                <span className="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                    Lead: {team.lead.name}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href={route('admin.teams.index')}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back
                        </Link>

                        <Link
                            href={route('admin.teams.edit', team.id)}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                        >
                            <Pencil className="h-4 w-4" />
                            Edit Team
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[340px_minmax(0,1fr)]">
                    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div className="flex items-center gap-3">
                                <div className="flex h-14 w-14 items-center justify-center rounded-3xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <UsersRound className="h-7 w-7 text-sky-600" />
                                </div>

                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900">
                                        Team Summary
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
                                        <UsersRound className="h-10 w-10 text-sky-600" />
                                    </div>

                                    <h3 className="mt-4 text-2xl font-semibold tracking-tight text-gray-900">
                                        {team.name}
                                    </h3>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Support team
                                    </p>
                                </div>

                                <div className="mt-6 space-y-4">
                                    <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Team Lead
                                        </span>
                                        <span className="text-right text-sm font-semibold text-gray-900">
                                            {team.lead?.name ?? '—'}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Team Size
                                        </span>
                                        <span className="text-right text-sm font-semibold text-gray-900">
                                            {team.members_count}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-4 border-b border-gray-200 pb-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Status
                                        </span>
                                        <span
                                            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${getStatusClasses(team.is_active)}`}
                                        >
                                            {team.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>

                                    <div className="flex items-start justify-between gap-4">
                                        <span className="text-sm font-medium text-gray-500">
                                            Departments
                                        </span>

                                        <div className="flex max-w-[180px] flex-wrap justify-end gap-2">
                                            {team.departments.length > 0 ? (
                                                team.departments.map((department) => (
                                                    <span
                                                        key={department.id}
                                                        className="inline-flex rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200"
                                                    >
                                                        {department.name}
                                                    </span>
                                                                                            ))
                                                                                        ) : (
                                                                                            <span className="text-sm font-semibold text-gray-900">
                                                    —
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <Link
                                    href={route('admin.teams.edit', team.id)}
                                    className="mt-6 inline-flex h-11 w-full items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                                >
                                    <Pencil className="h-4 w-4" />
                                    Edit Team
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
                                            Team Members
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Members assigned to this team.
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
                                            {team.members_count}
                                        </p>
                                    </div>

                                    <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                        <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                            <UserCheck className="h-4 w-4" />
                                            Team Lead
                                        </div>
                                        <p className="mt-2 text-base font-semibold text-gray-900">
                                            {team.lead?.name ?? 'Not assigned'}
                                        </p>
                                    </div>

                                    <div className="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                        <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                            <Building2 className="h-4 w-4" />
                                            Departments
                                        </div>
                                        <p className="mt-2 text-base font-semibold text-gray-900">
                                            {team.departments.length}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-6">
                                {filteredMembers.length > 0 ? (
                                    <>
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
                                                        Role in Team
                                                    </th>
                                                    <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        Actions
                                                    </th>
                                                </tr>
                                                </thead>

                                                <tbody className="divide-y divide-gray-100 bg-white">
                                                {filteredMembers.map((member) => (
                                                    <tr
                                                        key={member.id}
                                                        className="transition hover:bg-sky-50/40"
                                                    >
                                                        <td className="px-6 py-5">
                                                            <div>
                                                                <p className="font-semibold text-gray-900">
                                                                    {member.name}
                                                                </p>
                                                                <p className="mt-1 text-sm text-gray-500">
                                                                    User #{member.id}
                                                                </p>
                                                            </div>
                                                        </td>

                                                        <td className="px-6 py-5 text-sm text-gray-600">
                                                            <div className="inline-flex items-center gap-2">
                                                                <Mail className="h-4 w-4 text-gray-400" />
                                                                {member.email}
                                                            </div>
                                                        </td>

                                                        <td className="px-6 py-5">
                                                            {member.is_lead ? (
                                                                <span className="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                                                        Team Lead
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

                                        <div className="grid gap-4 lg:hidden">
                                            {filteredMembers.map((member) => (
                                                <div
                                                    key={member.id}
                                                    className="rounded-[24px] border border-gray-200 bg-gray-50 p-5"
                                                >
                                                    <div className="flex items-start justify-between gap-4">
                                                        <div className="min-w-0">
                                                            <h3 className="text-base font-semibold text-gray-900">
                                                                {member.name}
                                                            </h3>
                                                            <p className="mt-1 text-sm text-gray-500">
                                                                {member.email}
                                                            </p>
                                                        </div>

                                                        {member.is_lead ? (
                                                            <span className="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                                                Lead
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
                                                                Member
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </>
                                ) : (
                                    <div className="rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-14 text-center">
                                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-sm ring-1 ring-inset ring-gray-200">
                                            <UsersRound className="h-8 w-8 text-gray-400" />
                                        </div>

                                        <h3 className="mt-5 text-lg font-semibold text-gray-900">
                                            No members found
                                        </h3>

                                        <p className="mt-2 text-sm leading-6 text-gray-500">
                                            Try changing the search query or update the team members.
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
                                            Internal Notes
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            Private notes for administrators.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-6">
                                {team.admin_notes ? (
                                    <div
                                        className="prose max-w-none rounded-[24px] border border-gray-200 bg-gray-50 px-5 py-4 prose-p:text-gray-700 prose-li:text-gray-700 prose-strong:text-gray-900"
                                        dangerouslySetInnerHTML={{ __html: team.admin_notes }}
                                    />
                                ) : (
                                    <div className="rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center">
                                        <p className="text-sm font-medium text-gray-700">
                                            No internal notes yet
                                        </p>
                                        <p className="mt-1 text-sm text-gray-500">
                                            You can add notes when editing this team.
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
                                            Team Report
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

                                <div className="mt-6 rounded-[24px] border border-dashed border-gray-300 bg-gray-50 px-6 py-16 text-center">
                                    <p className="text-sm font-medium text-gray-700">
                                        Reporting widgets will go here later
                                    </p>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Once team ticket analytics are ready, this section can display charts and trends.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </AdminLayout>
    )
}

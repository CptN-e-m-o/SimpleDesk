import AdminLayout from '@/Layouts/AdminLayout'
import InputError from '@/Components/InputError'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    Check,
    ChevronDown,
    ChevronsUpDown,
    Save,
    Shield,
    UsersRound,
    X,
} from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import {route} from "ziggy-js";
import type {
    DepartmentOption,
    UserOption,
    TeamFormData,
} from '@/types/team'

type Props = {
    readonly mode: 'create' | 'edit'
    readonly teamId?: number
    readonly departments: DepartmentOption[]
    readonly users: UserOption[]
    readonly initialData: TeamFormData
    readonly submitUrl: string
}

export default function TeamForm({
                                     mode,
                                     teamId,
                                     departments,
                                     users,
                                     initialData,
                                     submitUrl,
                                 }: Props) {
    const [memberSearch, setMemberSearch] = useState('')
    const [isDepartmentsOpen, setIsDepartmentsOpen] = useState(false)
    const departmentsRef = useRef<HTMLDivElement | null>(null)

    const { data, setData, post, put, processing, errors } = useForm(initialData)

    const isEdit = mode === 'edit'

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (
                departmentsRef.current &&
                !departmentsRef.current.contains(event.target as Node)
            ) {
                setIsDepartmentsOpen(false)
            }
        }

        document.addEventListener('mousedown', handleClickOutside)

        return () => {
            document.removeEventListener('mousedown', handleClickOutside)
        }
    }, [])

    const filteredUsers = useMemo(() => {
        const query = memberSearch.trim().toLowerCase()

        if (!query) return users

        return users.filter((user) => {
            return (
                user.name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query)
            )
        })
    }, [memberSearch, users])

    const selectedMembers = useMemo(() => {
        return users.filter((user) => data.member_ids.includes(user.id))
    }, [data.member_ids, users])

    function toggleDepartment(departmentId: string) {
        if (data.departments.includes(departmentId)) {
            setData(
                'departments',
                data.departments.filter((id) => id !== departmentId),
            )
            return
        }

        setData('departments', [...data.departments, departmentId])
    }

    function removeDepartment(departmentId: string) {
        setData(
            'departments',
            data.departments.filter((id) => id !== departmentId),
        )
    }

    function toggleMember(userId: number) {
        const isSelected = data.member_ids.includes(userId)

        if (isSelected) {
            const nextMemberIds = data.member_ids.filter((id) => id !== userId)

            setData({
                ...data,
                member_ids: nextMemberIds,
                lead_user_id:
                    data.lead_user_id === userId ? '' : data.lead_user_id,
            })

            return
        }

        setData('member_ids', [...data.member_ids, userId])
    }

    function removeMember(userId: number) {
        const nextMemberIds = data.member_ids.filter((id) => id !== userId)

        setData({
            ...data,
            member_ids: nextMemberIds,
            lead_user_id: data.lead_user_id === userId ? '' : data.lead_user_id,
        })
    }

    function submit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault()

        if (isEdit) {
            put(submitUrl)
        } else {
            post(submitUrl)
        }
    }

    const pageTitle = isEdit ? 'Edit Team' : 'Create Team'
    const pageDescription = isEdit
        ? 'Update team details, change members, and assign a different team lead if needed.'
        : 'Create a new team, assign members, choose a team lead, and configure routing-related departments.'
    let submitText = ''

    if (processing) {
        submitText = isEdit ? 'Saving...' : 'Creating...'
    } else {
        submitText = isEdit ? 'Save Changes' : 'Create Team'
    }

    return (
        <AdminLayout title={pageTitle}>
            <Head title={isEdit ? `${pageTitle}` : pageTitle} />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-sm font-medium text-sky-600">
                            <Shield className="h-4 w-4" />
                            Team Management
                        </div>

                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                            {pageTitle}
                        </h1>

                        <p className="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                            {pageDescription}
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href={route('admin.teams.index')}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Teams
                        </Link>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {/* Team Details */}
                    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <UsersRound className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Team Details
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Basic information and status for the team.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 p-6 lg:grid-cols-2">
                            <div className="lg:col-span-2">
                                <label
                                    htmlFor="team-name"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Team name <span className="text-rose-500">*</span>
                                </label>

                                <input
                                    id="team-name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Example: Customer Support"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div>
                                <fieldset>
                                    <legend className="mb-2 block text-sm font-medium text-gray-700">
                                        Status <span className="text-rose-500">*</span>
                                    </legend>

                                    <div className="grid grid-cols-2 gap-3">
                                        <label
                                            htmlFor="status-active"
                                            className={`cursor-pointer rounded-2xl border px-4 py-3 text-left text-sm font-medium transition ${
                                                data.is_active
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 ring-4 ring-emerald-100'
                                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            <input
                                                id="status-active"
                                                type="radio"
                                                name="is_active"
                                                checked={data.is_active}
                                                onChange={() => setData('is_active', true)}
                                                className="sr-only"
                                            />
                                            Active
                                        </label>

                                        <label
                                            htmlFor="status-inactive"
                                            className={`cursor-pointer rounded-2xl border px-4 py-3 text-left text-sm font-medium transition ${
                                                data.is_active
                                                    ? 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                                    : 'border-rose-200 bg-rose-50 text-rose-700 ring-4 ring-rose-100'
                                            }`}
                                        >
                                            <input
                                                id="status-inactive"
                                                type="radio"
                                                name="is_active"
                                                checked={!data.is_active}
                                                onChange={() => setData('is_active', false)}
                                                className="sr-only"
                                            />
                                            Inactive
                                        </label>
                                    </div>
                                </fieldset>

                                <InputError message={errors.is_active} className="mt-2" />
                            </div>

                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Departments
                                </label>

                                <div ref={departmentsRef} className="relative">
                                    <button
                                        type="button"
                                        onClick={() => setIsDepartmentsOpen((prev) => !prev)}
                                        className={`flex min-h-[52px] w-full items-center justify-between gap-3 rounded-[24px] border bg-white px-4 py-3 text-left transition ${
                                            isDepartmentsOpen
                                                ? 'border-sky-300 ring-4 ring-sky-100'
                                                : 'border-gray-200 hover:border-gray-300'
                                        }`}
                                    >
                                        <div className="flex min-w-0 flex-1 flex-wrap gap-2">
                                            {data.departments.length > 0 ? (
                                                data.departments.map((departmentId) => {
                                                    const department = departments.find(
                                                        (item) => item.id === departmentId,
                                                    )

                                                    return (
                                                        <span
                                                            key={departmentId}
                                                            className="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200"
                                                        >
                                                            <span>
                                                                {department?.name ?? departmentId}
                                                            </span>

                                                            <span
                                                                role="button"
                                                                tabIndex={0}
                                                                onClick={(e) => {
                                                                    e.stopPropagation()
                                                                    removeDepartment(departmentId)
                                                                }}
                                                                onKeyDown={(e) => {
                                                                    if (
                                                                        e.key === 'Enter' ||
                                                                        e.key === ' '
                                                                    ) {
                                                                        e.preventDefault()
                                                                        e.stopPropagation()
                                                                        removeDepartment(departmentId)
                                                                    }
                                                                }}
                                                                className="inline-flex h-4 w-4 items-center justify-center rounded-full text-sky-500 transition hover:bg-sky-100 hover:text-sky-700"
                                                            >
                                                                <X className="h-3 w-3" />
                                                            </span>
                                                        </span>
                                                    )
                                                })
                                            ) : (
                                                <span className="text-sm text-gray-400">
                                                    Select one or more departments
                                                </span>
                                            )}
                                        </div>

                                        <ChevronDown
                                            className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                                                isDepartmentsOpen ? 'rotate-180' : ''
                                            }`}
                                        />
                                    </button>

                                    {isDepartmentsOpen && (
                                        <div className="absolute z-20 mt-2 w-full rounded-[24px] border border-gray-200 bg-white p-2 shadow-xl shadow-gray-900/10">
                                            <div className="max-h-64 overflow-y-auto">
                                                {departments.map((department) => {
                                                    const selected = data.departments.includes(
                                                        department.id,
                                                    )

                                                    return (
                                                        <button
                                                            key={department.id}
                                                            type="button"
                                                            onClick={() =>
                                                                toggleDepartment(department.id)
                                                            }
                                                            className={`flex w-full items-center justify-between rounded-2xl px-3 py-3 text-left text-sm transition ${
                                                                selected
                                                                    ? 'bg-sky-50 text-sky-700'
                                                                    : 'text-gray-700 hover:bg-gray-50'
                                                            }`}
                                                        >
                                                            <div className="flex items-center gap-3">
                                                                <span
                                                                    className={`flex h-5 w-5 items-center justify-center rounded-md border transition ${
                                                                        selected
                                                                            ? 'border-sky-600 bg-sky-600 text-white'
                                                                            : 'border-gray-300 bg-white'
                                                                    }`}
                                                                >
                                                                    {selected && (
                                                                        <Check className="h-3.5 w-3.5" />
                                                                    )}
                                                                </span>

                                                                <span className="font-medium">
                                                                    {department.name}
                                                                </span>
                                                            </div>
                                                        </button>
                                                    )
                                                })}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <p className="mt-2 text-xs text-gray-500">
                                    You can select multiple departments.
                                </p>

                                <InputError message={errors.departments} className="mt-2" />
                            </div>

                            <div className="lg:col-span-2">
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Internal notes
                                </label>

                                <textarea
                                    value={data.admin_notes}
                                    onChange={(e) => setData('admin_notes', e.target.value)}
                                    rows={8}
                                    placeholder="Write internal notes for this team..."
                                    className="w-full rounded-[24px] border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError message={errors.admin_notes} className="mt-2" />
                            </div>
                        </div>
                    </section>

                    {/* Team Members */}
                    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div>
                                <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Team Members
                                </h2>
                                <p className="mt-1 text-sm text-gray-500">
                                    Select members first, then choose one of them as the team lead.
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-6 p-6 xl:grid-cols-[1.2fr_0.8fr]">
                            <div>
                                <label className="mb-2 block text-sm font-medium text-gray-700">
                                    Search users
                                </label>

                                <div className="relative">
                                    <input
                                        type="text"
                                        value={memberSearch}
                                        onChange={(e) => setMemberSearch(e.target.value)}
                                        placeholder="Search by name or email..."
                                        className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 pr-12 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                    />
                                    <ChevronsUpDown className="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                </div>

                                <div className="mt-4 max-h-[420px] space-y-3 overflow-y-auto pr-1">
                                    {filteredUsers.map((user) => {
                                        const selected = data.member_ids.includes(user.id)

                                        return (
                                            <button
                                                key={user.id}
                                                type="button"
                                                onClick={() => toggleMember(user.id)}
                                                className={`flex w-full items-start justify-between gap-4 rounded-[24px] border p-4 text-left transition ${
                                                    selected
                                                        ? 'border-sky-200 bg-sky-50 ring-4 ring-sky-100'
                                                        : 'border-gray-200 bg-white hover:bg-gray-50'
                                                }`}
                                            >
                                                <div className="min-w-0">
                                                    <div className="font-medium text-gray-900">
                                                        {user.name}
                                                    </div>
                                                    <div className="mt-1 text-sm text-gray-500">
                                                        {user.email}
                                                    </div>
                                                </div>

                                                <div
                                                    className={`mt-0.5 inline-flex h-6 min-w-6 items-center justify-center rounded-full px-2 text-xs font-semibold ${
                                                        selected
                                                            ? 'bg-sky-600 text-white'
                                                            : 'bg-gray-100 text-gray-500'
                                                    }`}
                                                >
                                                    {selected ? '✓' : '+'}
                                                </div>
                                            </button>
                                        )
                                    })}
                                </div>

                                <InputError message={errors.member_ids} className="mt-3" />
                            </div>

                            <div className="space-y-6">
                                <div className="rounded-[24px] border border-gray-200 bg-gray-50 p-4">
                                    <div className="flex items-center justify-between gap-3">
                                        <h3 className="text-sm font-semibold text-gray-900">
                                            Selected members
                                        </h3>
                                        <span className="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200">
                                            {selectedMembers.length}
                                        </span>
                                    </div>

                                    <div className="mt-4 space-y-3">
                                        {selectedMembers.length > 0 ? (
                                            selectedMembers.map((user) => {
                                                const isLead = data.lead_user_id === user.id

                                                return (
                                                    <div
                                                        key={user.id}
                                                        className="rounded-[20px] border border-gray-200 bg-white p-4"
                                                    >
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div className="min-w-0">
                                                                <div className="font-medium text-gray-900">
                                                                    {user.name}
                                                                </div>
                                                                <div className="mt-1 text-sm text-gray-500">
                                                                    {user.email}
                                                                </div>
                                                            </div>

                                                            <button
                                                                type="button"
                                                                onClick={() => removeMember(user.id)}
                                                                className="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                                                            >
                                                                <X className="h-4 w-4" />
                                                            </button>
                                                        </div>

                                                        <div className="mt-4">
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    setData('lead_user_id', user.id)
                                                                }
                                                                className={`inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold transition ${
                                                                    isLead
                                                                        ? 'bg-amber-100 text-amber-800 ring-1 ring-inset ring-amber-200'
                                                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                                }`}
                                                            >
                                                                {isLead
                                                                    ? 'Team Lead'
                                                                    : 'Set as team lead'}
                                                            </button>
                                                        </div>
                                                    </div>
                                                )
                                            })
                                        ) : (
                                            <div className="rounded-[20px] border border-dashed border-gray-300 bg-white px-4 py-8 text-center">
                                                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100">
                                                    <UsersRound className="h-6 w-6 text-gray-400" />
                                                </div>
                                                <p className="mt-3 text-sm font-medium text-gray-700">
                                                    No members selected yet
                                                </p>
                                                <p className="mt-1 text-xs text-gray-500">
                                                    Choose at least one admin or agent from the list.
                                                </p>
                                            </div>
                                        )}
                                    </div>

                                    <InputError message={errors.lead_user_id} className="mt-3" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <Link
                            href={route('admin.teams.index')}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                        >
                            Cancel
                        </Link>

                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-5 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <Save className="h-4 w-4" />
                            {submitText}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    )
}

import AdminLayout from '@/Layouts/AdminLayout'
import InputError from '@/Components/InputError'
import FieldHint from '@/Components/FieldHint'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    Building2,
    Check,
    ChevronDown,
    Save,
    Shield,
    X,
} from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import type { FormEvent } from 'react'
import { route } from 'ziggy-js'
import type {
    DepartmentFormData,
    DepartmentStatusOption,
    TeamOption,
} from '@/types/department'
import type { UserOption } from '@/types/team'

type Props = {
    readonly mode: 'create' | 'edit'
    readonly users: UserOption[]
    readonly teams: TeamOption[]
    readonly statuses: DepartmentStatusOption[]
    readonly initialData: DepartmentFormData
    readonly submitUrl: string
}

export default function DepartmentForm({
                                           mode,
                                           users,
                                           teams,
                                           statuses,
                                           initialData,
                                           submitUrl,
                                       }: Props) {
    const [isManagersOpen, setIsManagersOpen] = useState(false)
    const [isTeamsOpen, setIsTeamsOpen] = useState(false)
    const [isStatusOpen, setIsStatusOpen] = useState(false)
    const [managerSearch, setManagerSearch] = useState('')
    const [teamSearch, setTeamSearch] = useState('')

    const managersRef = useRef<HTMLDivElement | null>(null)
    const teamsRef = useRef<HTMLDivElement | null>(null)
    const statusRef = useRef<HTMLDivElement | null>(null)

    const { data, setData, post, put, processing, errors } =
        useForm(initialData)

    const isEdit = mode === 'edit'

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            const target = event.target as Node

            if (managersRef.current && !managersRef.current.contains(target)) {
                setIsManagersOpen(false)
            }

            if (teamsRef.current && !teamsRef.current.contains(target)) {
                setIsTeamsOpen(false)
            }

            if (statusRef.current && !statusRef.current.contains(target)) {
                setIsStatusOpen(false)
            }
        }

        document.addEventListener('mousedown', handleClickOutside)

        return () => {
            document.removeEventListener('mousedown', handleClickOutside)
        }
    }, [])

    const filteredUsers = useMemo(() => {
        const query = managerSearch.trim().toLowerCase()

        if (!query) return users

        return users.filter((user) => {
            return (
                user.name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query)
            )
        })
    }, [managerSearch, users])

    const filteredTeams = useMemo(() => {
        const query = teamSearch.trim().toLowerCase()

        if (!query) return teams

        return teams.filter((team) => {
            return team.name.toLowerCase().includes(query)
        })
    }, [teamSearch, teams])

    const selectedManagers = useMemo(() => {
        return users.filter((user) => data.manager_ids.includes(user.id))
    }, [data.manager_ids, users])

    const selectedTeams = useMemo(() => {
        return teams.filter((team) => data.team_ids.includes(team.id))
    }, [data.team_ids, teams])

    const selectedStatus = useMemo(() => {
        return statuses.find((status) => status.id === data.department_status_id)
    }, [data.department_status_id, statuses])

    function toggleManager(userId: number) {
        if (data.manager_ids.includes(userId)) {
            setData(
                'manager_ids',
                data.manager_ids.filter((id) => id !== userId),
            )
            return
        }

        setData('manager_ids', [...data.manager_ids, userId])
    }

    function removeManager(userId: number) {
        setData(
            'manager_ids',
            data.manager_ids.filter((id) => id !== userId),
        )
    }

    function toggleTeam(teamId: number) {
        if (data.team_ids.includes(teamId)) {
            setData(
                'team_ids',
                data.team_ids.filter((id) => id !== teamId),
            )
            return
        }

        setData('team_ids', [...data.team_ids, teamId])
    }

    function removeTeam(teamId: number) {
        setData(
            'team_ids',
            data.team_ids.filter((id) => id !== teamId),
        )
    }

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault()

        if (isEdit) {
            put(submitUrl)
            return
        }

        post(submitUrl)
    }

    const pageTitle = isEdit ? 'Edit Department' : 'Create Department'
    const pageDescription = isEdit
        ? 'Update department details, managers, teams, and routing-related settings.'
        : 'Create a department, assign managers, connect teams, and configure support settings.'

    const actionText = isEdit ? 'Save Changes' : 'Create Department'
    const processingText = isEdit ? 'Saving...' : 'Creating...'

    const submitText = processing ? processingText : actionText

    return (
        <AdminLayout title={pageTitle}>
            <Head title={pageTitle} />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-sm font-medium text-sky-600">
                            <Shield className="h-4 w-4" />
                            Department Management
                        </div>

                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                            {pageTitle}
                        </h1>

                        <p className="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                            {pageDescription}
                        </p>
                    </div>

                    <Link
                        href={route('admin.departments.index')}
                        className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Departments
                    </Link>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <Building2 className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Department Details
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Basic department information and public visibility.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 p-6 lg:grid-cols-2">
                            <div className="lg:col-span-2">
                                <label
                                    htmlFor="department-name"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Department name{' '}
                                    <span className="text-rose-500">*</span>
                                </label>

                                <input
                                    id="department-name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="Example: Support"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <fieldset>
                                    <div className="mb-2 flex items-center gap-2">
                                        <legend className="block text-sm font-medium text-gray-700">
                                            Type{' '}
                                            <span className="text-rose-500">
                                                *
                                            </span>
                                        </legend>

                                        <FieldHint text="Public departments are visible for regular routing. Private departments can be used for internal workflows." />
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <label
                                            htmlFor="type-public"
                                            className={`cursor-pointer rounded-2xl border px-4 py-3 text-left text-sm font-medium transition ${
                                                data.type === 'public'
                                                    ? 'border-sky-200 bg-sky-50 text-sky-700 ring-4 ring-sky-100'
                                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            <input
                                                id="type-public"
                                                type="radio"
                                                name="type"
                                                checked={
                                                    data.type === 'public'
                                                }
                                                onChange={() =>
                                                    setData('type', 'public')
                                                }
                                                className="sr-only"
                                            />
                                            <span>Public</span>
                                        </label>

                                        <label
                                            htmlFor="type-private"
                                            className={`cursor-pointer rounded-2xl border px-4 py-3 text-left text-sm font-medium transition ${
                                                data.type === 'private'
                                                    ? 'border-violet-200 bg-violet-50 text-violet-700 ring-4 ring-violet-100'
                                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            <input
                                                id="type-private"
                                                type="radio"
                                                name="type"
                                                checked={
                                                    data.type === 'private'
                                                }
                                                onChange={() =>
                                                    setData('type', 'private')
                                                }
                                                className="sr-only"
                                            />
                                            <span>Private</span>
                                        </label>
                                    </div>
                                </fieldset>

                                <InputError
                                    message={errors.type}
                                    className="mt-2"
                                />
                            </div>

                            <div ref={statusRef} className="relative">
                                <div className="mb-2 flex items-center gap-2">
                                    <p className="block text-sm font-medium text-gray-700">
                                        Department status
                                    </p>

                                    <FieldHint text="Optional business status for this department. Deleted and default states are handled separately." />
                                </div>

                                <button
                                    type="button"
                                    onClick={() =>
                                        setIsStatusOpen((prev) => !prev)
                                    }
                                    className={`flex h-12 w-full items-center justify-between gap-3 rounded-2xl border bg-white px-4 text-left text-sm transition ${
                                        isStatusOpen
                                            ? 'border-sky-300 ring-4 ring-sky-100'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <span
                                        className={
                                            selectedStatus
                                                ? 'text-gray-900'
                                                : 'text-gray-400'
                                        }
                                    >
                                        {selectedStatus?.name ??
                                            'Select status'}
                                    </span>

                                    <ChevronDown
                                        className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                                            isStatusOpen ? 'rotate-180' : ''
                                        }`}
                                    />
                                </button>

                                {isStatusOpen && (
                                    <div className="absolute z-20 mt-2 w-full rounded-[24px] border border-gray-200 bg-white p-2 shadow-xl shadow-gray-900/10">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setData(
                                                    'department_status_id',
                                                    '',
                                                )
                                                setIsStatusOpen(false)
                                            }}
                                            className="flex w-full items-center justify-between rounded-2xl px-3 py-3 text-left text-sm text-gray-700 transition hover:bg-gray-50"
                                        >
                                            No status
                                        </button>

                                        {statuses.map((status) => {
                                            const selected =
                                                data.department_status_id ===
                                                status.id

                                            return (
                                                <button
                                                    key={status.id}
                                                    type="button"
                                                    onClick={() => {
                                                        setData(
                                                            'department_status_id',
                                                            status.id,
                                                        )
                                                        setIsStatusOpen(false)
                                                    }}
                                                    className={`flex w-full items-center justify-between rounded-2xl px-3 py-3 text-left text-sm transition ${
                                                        selected
                                                            ? 'bg-sky-50 text-sky-700'
                                                            : 'text-gray-700 hover:bg-gray-50'
                                                    }`}
                                                >
                                                    <span className="font-medium">
                                                        {status.name}
                                                    </span>

                                                    {selected && (
                                                        <Check className="h-4 w-4" />
                                                    )}
                                                </button>
                                            )
                                        })}
                                    </div>
                                )}

                                <InputError
                                    message={errors.department_status_id}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <label
                                        htmlFor="business-hours"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        Business hours
                                    </label>

                                    <FieldHint text="Optional business hours label or code. Later this can be replaced by a dedicated business-hours table." />
                                </div>

                                <input
                                    id="business-hours"
                                    type="text"
                                    value={data.business_hours}
                                    onChange={(e) =>
                                        setData(
                                            'business_hours',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Example: 9:00-18:00, Mon-Fri"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError
                                    message={errors.business_hours}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <label
                                        htmlFor="outgoing-email"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        Outgoing email
                                    </label>

                                    <FieldHint text="Optional sender email for department-related replies." />
                                </div>

                                <input
                                    id="outgoing-email"
                                    type="email"
                                    value={data.outgoing_email}
                                    onChange={(e) =>
                                        setData(
                                            'outgoing_email',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="support@example.com"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError
                                    message={errors.outgoing_email}
                                    className="mt-2"
                                />
                            </div>
                        </div>
                    </section>

                    <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div>
                                <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Managers and Teams
                                </h2>
                                <p className="mt-1 text-sm text-gray-500">
                                    Choose department managers and connect related teams.
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-6 p-6 lg:grid-cols-2">
                            <div ref={managersRef} className="relative">
                                <div className="mb-2 flex items-center gap-2">
                                    <p className="block text-sm font-medium text-gray-700">
                                        Managers
                                    </p>

                                    <FieldHint text="Managers can supervise this department and can be shown in department lists." />
                                </div>

                                <button
                                    type="button"
                                    onClick={() => setIsManagersOpen((prev) => !prev)}
                                    className={`flex min-h-[52px] w-full items-center justify-between gap-3 rounded-[24px] border bg-white px-4 py-3 text-left transition ${
                                        isManagersOpen
                                            ? 'border-sky-300 ring-4 ring-sky-100'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="flex min-w-0 flex-1 flex-wrap gap-2">
                                        {selectedManagers.length > 0 ? (
                                            selectedManagers.map((user) => (
                                                <span
                                                    key={user.id}
                                                    className="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200"
                                                >
                                                    {user.name}

                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation()
                                                            removeManager(
                                                                user.id,
                                                            )
                                                        }}
                                                        className="inline-flex h-4 w-4 items-center justify-center rounded-full text-sky-500 transition hover:bg-sky-100 hover:text-sky-700"
                                                    >
                                                        <X className="h-3 w-3" />
                                                    </button>
                                                </span>
                                            ))
                                        ) : (
                                            <span className="text-sm text-gray-400">
                                                Select managers
                                            </span>
                                        )}
                                    </div>

                                    <ChevronDown
                                        className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                                            isManagersOpen ? 'rotate-180' : ''
                                        }`}
                                    />
                                </button>

                                {isManagersOpen && (
                                    <div className="absolute z-20 mt-2 w-full rounded-[24px] border border-gray-200 bg-white p-2 shadow-xl shadow-gray-900/10">
                                        <input
                                            type="text"
                                            value={managerSearch}
                                            onChange={(e) =>
                                                setManagerSearch(
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Search users..."
                                            className="mb-2 h-10 w-full rounded-2xl border border-gray-200 px-3 text-sm outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                        />

                                        <div className="max-h-64 overflow-y-auto">
                                            {filteredUsers.map((user) => {
                                                const selected =
                                                    data.manager_ids.includes(
                                                        user.id,
                                                    )

                                                return (
                                                    <button
                                                        key={user.id}
                                                        type="button"
                                                        onClick={() =>
                                                            toggleManager(
                                                                user.id,
                                                            )
                                                        }
                                                        className={`flex w-full items-start justify-between gap-3 rounded-2xl px-3 py-3 text-left text-sm transition ${
                                                            selected
                                                                ? 'bg-sky-50 text-sky-700'
                                                                : 'text-gray-700 hover:bg-gray-50'
                                                        }`}
                                                    >
                                                        <span>
                                                            <span className="block font-medium">
                                                                {user.name}
                                                            </span>
                                                            <span className="mt-0.5 block text-xs text-gray-500">
                                                                {user.email}
                                                            </span>
                                                        </span>

                                                        {selected && (
                                                            <Check className="mt-1 h-4 w-4 shrink-0" />
                                                        )}
                                                    </button>
                                                )
                                            })}
                                        </div>
                                    </div>
                                )}

                                <InputError
                                    message={errors.manager_ids}
                                    className="mt-2"
                                />
                            </div>

                            <div ref={teamsRef} className="relative">
                                <div className="mb-2 flex items-center gap-2">
                                    <p className="block text-sm font-medium text-gray-700">
                                        Teams
                                    </p>

                                    <FieldHint text="Connect teams that belong to this department." />
                                </div>

                                <div
                                    role="button"
                                    tabIndex={0}
                                    onClick={() =>
                                        setIsTeamsOpen((prev) => !prev)
                                    }
                                    onKeyDown={(e) => {
                                        if (
                                            e.key === 'Enter' ||
                                            e.key === ' '
                                        ) {
                                            e.preventDefault()
                                            setIsTeamsOpen((prev) => !prev)
                                        }
                                    }}
                                    className={`flex min-h-[52px] w-full items-center justify-between gap-3 rounded-[24px] border bg-white px-4 py-3 text-left transition ${
                                        isTeamsOpen
                                            ? 'border-sky-300 ring-4 ring-sky-100'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="flex min-w-0 flex-1 flex-wrap gap-2">
                                        {selectedTeams.length > 0 ? (
                                            selectedTeams.map((team) => (
                                                <span
                                                    key={team.id}
                                                    className="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200"
                                                >
                                                    {team.name}

                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation()
                                                            removeTeam(team.id)
                                                        }}
                                                        className="inline-flex h-4 w-4 items-center justify-center rounded-full text-sky-500 transition hover:bg-sky-100 hover:text-sky-700"
                                                    >
                                                        <X className="h-3 w-3" />
                                                    </button>
                                                </span>
                                            ))
                                        ) : (
                                            <span className="text-sm text-gray-400">
                                                Select teams
                                            </span>
                                        )}
                                    </div>

                                    <ChevronDown
                                        className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                                            isTeamsOpen ? 'rotate-180' : ''
                                        }`}
                                    />
                                </div>

                                {isTeamsOpen && (
                                    <div className="absolute z-20 mt-2 w-full rounded-[24px] border border-gray-200 bg-white p-2 shadow-xl shadow-gray-900/10">
                                        <input
                                            type="text"
                                            value={teamSearch}
                                            onChange={(e) =>
                                                setTeamSearch(e.target.value)
                                            }
                                            placeholder="Search teams..."
                                            className="mb-2 h-10 w-full rounded-2xl border border-gray-200 px-3 text-sm outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                        />

                                        <div className="max-h-64 overflow-y-auto">
                                            {filteredTeams.map((team) => {
                                                const selected =
                                                    data.team_ids.includes(
                                                        team.id,
                                                    )

                                                return (
                                                    <button
                                                        key={team.id}
                                                        type="button"
                                                        onClick={() =>
                                                            toggleTeam(team.id)
                                                        }
                                                        className={`flex w-full items-center justify-between rounded-2xl px-3 py-3 text-left text-sm transition ${
                                                            selected
                                                                ? 'bg-sky-50 text-sky-700'
                                                                : 'text-gray-700 hover:bg-gray-50'
                                                        }`}
                                                    >
                                                        <span className="font-medium">
                                                            {team.name}
                                                        </span>

                                                        {selected && (
                                                            <Check className="h-4 w-4" />
                                                        )}
                                                    </button>
                                                )
                                            })}
                                        </div>
                                    </div>
                                )}

                                <InputError
                                    message={errors.team_ids}
                                    className="mt-2"
                                />
                            </div>
                        </div>
                    </section>

                    <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div>
                                <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Department Signature
                                </h2>
                                <p className="mt-1 text-sm text-gray-500">
                                    Optional signature used for department replies.
                                </p>
                            </div>
                        </div>

                        <div className="space-y-6 p-6">
                            <div>
                                <label
                                    htmlFor="signature"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Signature
                                </label>

                                <textarea
                                    id="signature"
                                    value={data.signature}
                                    onChange={(e) =>
                                        setData('signature', e.target.value)
                                    }
                                    rows={10}
                                    placeholder="Write department signature..."
                                    className="w-full rounded-[24px] border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError
                                    message={errors.signature}
                                    className="mt-2"
                                />
                            </div>

                            <label
                                htmlFor="is-default"
                                className={`flex cursor-pointer items-start gap-3 rounded-[24px] border p-4 transition ${
                                    data.is_default
                                        ? 'border-sky-200 bg-sky-50 ring-4 ring-sky-100'
                                        : 'border-gray-200 bg-white hover:bg-gray-50'
                                }`}
                            >
                                <input
                                    id="is-default"
                                    type="checkbox"
                                    checked={data.is_default}
                                    onChange={(e) =>
                                        setData(
                                            'is_default',
                                            e.target.checked,
                                        )
                                    }
                                    className="mt-1 h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500"
                                />

                                <span>
                                    <span className="block text-sm font-medium text-gray-900">
                                        Make system&apos;s default department
                                    </span>

                                    <span className="mt-1 block text-sm leading-6 text-gray-500">
                                        Use this department as the default target
                                        when no other department is selected.
                                    </span>
                                </span>
                            </label>

                            <InputError
                                message={errors.is_default}
                                className="mt-2"
                            />
                        </div>
                    </section>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <Link
                            href={route('admin.departments.index')}
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

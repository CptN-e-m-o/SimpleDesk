import AdminLayout from '@/Layouts/AdminLayout'
import InputError from '@/Components/InputError'
import FieldHint from '@/Components/FieldHint'
import PhoneCountryInput from './PhoneCountryInput'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    Check,
    ChevronDown,
    Lock,
    Save,
    Shield,
    UserCog,
    X,
} from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import type { FormEvent } from 'react'
import { route } from 'ziggy-js'
import type {
    AgentDepartmentOption,
    AgentFormData,
    AgentRoleOption,
    AgentTeamOption,
    AgentTimezoneOption,
} from '@/types/agent'

type Props = {
    readonly mode: 'create' | 'edit'
    readonly departments: AgentDepartmentOption[]
    readonly roles: AgentRoleOption[]
    readonly teams: AgentTeamOption[]
    readonly timezones: AgentTimezoneOption[]
    readonly initialData: AgentFormData
    readonly submitUrl: string
}

function toggleId(ids: number[], id: number) {
    if (ids.includes(id)) {
        return ids.filter((currentId) => currentId !== id)
    }

    return [...ids, id]
}

function closeDropdownOnOutside(
    element: HTMLElement | null,
    target: Node,
    close: () => void,
) {
    if (!element?.contains(target)) {
        close()
    }
}

function getPageTitle(isEdit: boolean) {
    return isEdit ? 'Edit Agent' : 'Create Agent'
}

function getPageDescription(isEdit: boolean) {
    return isEdit
        ? 'Update agent profile, access roles, contact details, and account settings.'
        : 'Create a support agent, assign roles, and configure contact details.'
}

function getSubmitText(isEdit: boolean, processing: boolean) {
    if (processing) {
        return isEdit ? 'Saving...' : 'Creating...'
    }

    return isEdit ? 'Save Changes' : 'Create Agent'
}

export default function AgentForm({
                                      mode,
                                      departments,
                                      roles,
                                      teams,
                                      timezones,
                                      initialData,
                                      submitUrl,
                                  }: Props) {
    const [isRolesOpen, setIsRolesOpen] = useState(false)
    const [isDepartmentsOpen, setIsDepartmentsOpen] = useState(false)
    const [isTeamsOpen, setIsTeamsOpen] = useState(false)
    const [isTimezoneOpen, setIsTimezoneOpen] = useState(false)

    const [roleSearch, setRoleSearch] = useState('')
    const [departmentSearch, setDepartmentSearch] = useState('')
    const [teamSearch, setTeamSearch] = useState('')
    const [timezoneSearch, setTimezoneSearch] = useState('')

    const rolesRef = useRef<HTMLDivElement | null>(null)
    const departmentsRef = useRef<HTMLDivElement | null>(null)
    const teamsRef = useRef<HTMLDivElement | null>(null)
    const timezoneRef = useRef<HTMLDivElement | null>(null)

    const { data, setData, post, put, processing, errors } =
        useForm(initialData)

    const isEdit = mode === 'edit'

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            const target = event.target as Node

            closeDropdownOnOutside(rolesRef.current, target, () =>
                setIsRolesOpen(false),
            )

            closeDropdownOnOutside(departmentsRef.current, target, () =>
                setIsDepartmentsOpen(false),
            )

            closeDropdownOnOutside(teamsRef.current, target, () =>
                setIsTeamsOpen(false),
            )

            closeDropdownOnOutside(timezoneRef.current, target, () =>
                setIsTimezoneOpen(false),
            )
        }

        document.addEventListener('mousedown', handleClickOutside)

        return () => {
            document.removeEventListener('mousedown', handleClickOutside)
        }
    }, [])

    const filteredRoles = useMemo(() => {
        const query = roleSearch.trim().toLowerCase()

        if (!query) return roles

        return roles.filter((role) => {
            return (
                role.label.toLowerCase().includes(query) ||
                role.name.toLowerCase().includes(query)
            )
        })
    }, [roleSearch, roles])

    const filteredDepartments = useMemo(() => {
        const query = departmentSearch.trim().toLowerCase()

        if (!query) return departments

        return departments.filter((department) =>
            department.name.toLowerCase().includes(query),
        )
    }, [departmentSearch, departments])

    const filteredTeams = useMemo(() => {
        const query = teamSearch.trim().toLowerCase()

        if (!query) return teams

        return teams.filter((team) => team.name.toLowerCase().includes(query))
    }, [teamSearch, teams])

    const filteredTimezones = useMemo(() => {
        const query = timezoneSearch.trim().toLowerCase()

        if (!query) return timezones

        return timezones.filter((timezone) => {
            return (
                timezone.name.toLowerCase().includes(query) ||
                timezone.id.toLowerCase().includes(query)
            )
        })
    }, [timezoneSearch, timezones])

    const selectedRoles = useMemo(() => {
        return roles.filter((role) => data.role_ids.includes(role.id))
    }, [data.role_ids, roles])

    const selectedDepartments = useMemo(() => {
        return departments.filter((department) =>
            data.department_ids.includes(department.id),
        )
    }, [data.department_ids, departments])

    const selectedTeams = useMemo(() => {
        return teams.filter((team) => data.team_ids.includes(team.id))
    }, [data.team_ids, teams])

    const selectedTimezone = useMemo(() => {
        return timezones.find((timezone) => timezone.id === data.timezone)
    }, [data.timezone, timezones])

    function toggleRole(roleId: number) {
        setData('role_ids', toggleId(data.role_ids, roleId))
    }

    function removeRole(roleId: number) {
        setData(
            'role_ids',
            data.role_ids.filter((id) => id !== roleId),
        )
    }

    function toggleDepartment(departmentId: number) {
        setData('department_ids', toggleId(data.department_ids, departmentId))
    }

    function removeDepartment(departmentId: number) {
        setData(
            'department_ids',
            data.department_ids.filter((id) => id !== departmentId),
        )
    }

    function toggleTeam(teamId: number) {
        setData('team_ids', toggleId(data.team_ids, teamId))
    }

    function removeTeam(teamId: number) {
        setData(
            'team_ids',
            data.team_ids.filter((id) => id !== teamId),
        )
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault()

        if (isEdit) {
            put(submitUrl)
            return
        }

        post(submitUrl)
    }

    const pageTitle = getPageTitle(isEdit)
    const pageDescription = getPageDescription(isEdit)
    const submitText = getSubmitText(isEdit, processing)

    return (
        <AdminLayout title={pageTitle}>
            <Head title={pageTitle} />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-sm font-medium text-sky-600">
                            <Shield className="h-4 w-4" />
                            Agent Management
                        </div>

                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                            {pageTitle}
                        </h1>

                        <p className="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                            {pageDescription}
                        </p>
                    </div>

                    <Link
                        href={route('admin.agents.index')}
                        className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Agents
                    </Link>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <UserCog className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Agent Info
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Basic profile, login identity, and contact information.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 p-6 lg:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="first-name"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    First name <span className="text-rose-500">*</span>
                                </label>

                                <input
                                    id="first-name"
                                    type="text"
                                    value={data.first_name}
                                    onChange={(event) =>
                                        setData('first_name', event.target.value)
                                    }
                                    placeholder="John"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError message={errors.first_name} className="mt-2" />
                            </div>

                            <div>
                                <label
                                    htmlFor="last-name"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Last name
                                </label>

                                <input
                                    id="last-name"
                                    type="text"
                                    value={data.last_name}
                                    onChange={(event) =>
                                        setData('last_name', event.target.value)
                                    }
                                    placeholder="Smith"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError message={errors.last_name} className="mt-2" />
                            </div>

                            <div>
                                <label
                                    htmlFor="email"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Email <span className="text-rose-500">*</span>
                                </label>

                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) =>
                                        setData('email', event.target.value)
                                    }
                                    placeholder="agent@example.com"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <label
                                    htmlFor="username"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Username <span className="text-rose-500">*</span>
                                </label>

                                <input
                                    id="username"
                                    type="text"
                                    value={data.username}
                                    onChange={(event) =>
                                        setData('username', event.target.value)
                                    }
                                    placeholder="john_smith"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError message={errors.username} className="mt-2" />
                            </div>

                            <div className="lg:col-span-2">
                                <label
                                    htmlFor="location"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Location
                                </label>

                                <input
                                    id="location"
                                    type="text"
                                    value={data.location}
                                    onChange={(event) =>
                                        setData('location', event.target.value)
                                    }
                                    placeholder="Berlin, Germany"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError message={errors.location} className="mt-2" />
                            </div>

                            <div className="lg:col-span-2">
                                <PhoneCountryInput
                                    id="phone-number"
                                    label="Work phone"
                                    iso2={data.phone_country_iso2}
                                    countryCode={data.phone_country_code}
                                    number={data.phone_number}
                                    ext={data.phone_ext}
                                    withExt
                                    error={errors.phone_number}
                                    extError={errors.phone_ext}
                                    onChangeIso2={(value) =>
                                        setData('phone_country_iso2', value)
                                    }
                                    onChangeCountryCode={(value) =>
                                        setData('phone_country_code', value)
                                    }
                                    onChangeNumber={(value) =>
                                        setData('phone_number', value)
                                    }
                                    onChangeExt={(value) =>
                                        setData('phone_ext', value)
                                    }
                                />

                                <InputError
                                    message={errors.phone_country_iso2}
                                    className="mt-2"
                                />
                                <InputError
                                    message={errors.phone_country_code}
                                    className="mt-2"
                                />
                            </div>

                            <div className="lg:col-span-2">
                                <PhoneCountryInput
                                    id="mobile-number"
                                    label="Mobile phone"
                                    iso2={data.mobile_country_iso2}
                                    countryCode={data.mobile_country_code}
                                    number={data.mobile_number}
                                    error={errors.mobile_number}
                                    onChangeIso2={(value) =>
                                        setData('mobile_country_iso2', value)
                                    }
                                    onChangeCountryCode={(value) =>
                                        setData('mobile_country_code', value)
                                    }
                                    onChangeNumber={(value) =>
                                        setData('mobile_number', value)
                                    }
                                />

                                <InputError
                                    message={errors.mobile_country_iso2}
                                    className="mt-2"
                                />
                                <InputError
                                    message={errors.mobile_country_code}
                                    className="mt-2"
                                />
                            </div>

                            <div ref={timezoneRef} className="relative lg:col-span-2">
                                <div className="mb-2 flex items-center gap-2">
                                    <p className="block text-sm font-medium text-gray-700">
                                        Timezone <span className="text-rose-500">*</span>
                                    </p>

                                    <FieldHint text="Used for agent-specific timestamps and future working-hour features." />
                                </div>

                                <button
                                    type="button"
                                    onClick={() => setIsTimezoneOpen((prev) => !prev)}
                                    className={`flex h-12 w-full items-center justify-between gap-3 rounded-2xl border bg-white px-4 text-left text-sm transition ${
                                        isTimezoneOpen
                                            ? 'border-sky-300 ring-4 ring-sky-100'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <span
                                        className={
                                            selectedTimezone
                                                ? 'text-gray-900'
                                                : 'text-gray-400'
                                        }
                                    >
                                        {selectedTimezone?.name ?? 'Select timezone'}
                                    </span>

                                    <ChevronDown
                                        className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                                            isTimezoneOpen ? 'rotate-180' : ''
                                        }`}
                                    />
                                </button>

                                {isTimezoneOpen && (
                                    <div className="absolute z-20 mt-2 w-full rounded-[24px] border border-gray-200 bg-white p-2 shadow-xl shadow-gray-900/10">
                                        <input
                                            type="text"
                                            value={timezoneSearch}
                                            onChange={(event) =>
                                                setTimezoneSearch(event.target.value)
                                            }
                                            placeholder="Search timezone..."
                                            className="mb-2 h-10 w-full rounded-2xl border border-gray-200 px-3 text-sm outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                        />

                                        <div className="max-h-72 overflow-y-auto">
                                            {filteredTimezones.map((timezone) => {
                                                const selected =
                                                    data.timezone === timezone.id

                                                return (
                                                    <button
                                                        key={timezone.id}
                                                        type="button"
                                                        onClick={() => {
                                                            setData(
                                                                'timezone',
                                                                timezone.id,
                                                            )
                                                            setIsTimezoneOpen(false)
                                                        }}
                                                        className={`flex w-full items-center justify-between rounded-2xl px-3 py-3 text-left text-sm transition ${
                                                            selected
                                                                ? 'bg-sky-50 text-sky-700'
                                                                : 'text-gray-700 hover:bg-gray-50'
                                                        }`}
                                                    >
                                                        <span className="font-medium">
                                                            {timezone.name}
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

                                <InputError message={errors.timezone} className="mt-2" />
                            </div>
                        </div>
                    </section>

                    <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div>
                                <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Account Status and Setting
                                </h2>
                                <p className="mt-1 text-sm text-gray-500">
                                    Configure agent access, roles, departments, and team assignments.
                                </p>
                            </div>
                        </div>

                        <div className="grid gap-6 p-6 lg:grid-cols-2">
                            <div ref={rolesRef} className="relative lg:col-span-2">
                                <div className="mb-2 flex items-center gap-2">
                                    <p className="block text-sm font-medium text-gray-700">
                                        Roles <span className="text-rose-500">*</span>
                                    </p>

                                    <FieldHint text="Only roles with agent type can be assigned here." />
                                </div>

                                <button
                                    type="button"
                                    onClick={() => setIsRolesOpen((prev) => !prev)}
                                    className={`flex min-h-[52px] w-full items-center justify-between gap-3 rounded-[24px] border bg-white px-4 py-3 text-left transition ${
                                        isRolesOpen
                                            ? 'border-sky-300 ring-4 ring-sky-100'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="flex min-w-0 flex-1 flex-wrap gap-2">
                                        {selectedRoles.length > 0 ? (
                                            selectedRoles.map((role) => (
                                                <span
                                                    key={role.id}
                                                    className="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200"
                                                >
                                                    {role.label}

                                                    <button
                                                        type="button"
                                                        onClick={(event) => {
                                                            event.stopPropagation()
                                                            removeRole(role.id)
                                                        }}
                                                        className="inline-flex h-4 w-4 items-center justify-center rounded-full text-sky-500 transition hover:bg-sky-100 hover:text-sky-700"
                                                    >
                                                        <X className="h-3 w-3" />
                                                    </button>
                                                </span>
                                            ))
                                        ) : (
                                            <span className="text-sm text-gray-400">
                                                Select roles
                                            </span>
                                        )}
                                    </div>

                                    <ChevronDown
                                        className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                                            isRolesOpen ? 'rotate-180' : ''
                                        }`}
                                    />
                                </button>

                                {isRolesOpen && (
                                    <div className="absolute z-20 mt-2 w-full rounded-[24px] border border-gray-200 bg-white p-2 shadow-xl shadow-gray-900/10">
                                        <input
                                            type="text"
                                            value={roleSearch}
                                            onChange={(event) =>
                                                setRoleSearch(event.target.value)
                                            }
                                            placeholder="Search roles..."
                                            className="mb-2 h-10 w-full rounded-2xl border border-gray-200 px-3 text-sm outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                        />

                                        <div className="max-h-64 overflow-y-auto">
                                            {filteredRoles.map((role) => {
                                                const selected =
                                                    data.role_ids.includes(role.id)

                                                return (
                                                    <button
                                                        key={role.id}
                                                        type="button"
                                                        onClick={() =>
                                                            toggleRole(role.id)
                                                        }
                                                        className={`flex w-full items-start justify-between gap-3 rounded-2xl px-3 py-3 text-left text-sm transition ${
                                                            selected
                                                                ? 'bg-sky-50 text-sky-700'
                                                                : 'text-gray-700 hover:bg-gray-50'
                                                        }`}
                                                    >
                                                        <span>
                                                            <span className="block font-medium">
                                                                {role.label}
                                                            </span>
                                                            <span className="mt-0.5 block text-xs text-gray-500">
                                                                {role.name}
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

                                <InputError message={errors.role_ids} className="mt-2" />
                            </div>

                            <div ref={departmentsRef} className="relative">
                                <div className="mb-2 flex items-center gap-2">
                                    <p className="block text-sm font-medium text-gray-700">
                                        Departments
                                    </p>

                                    <FieldHint text="Departments this agent belongs to." />
                                </div>

                                <button
                                    type="button"
                                    onClick={() =>
                                        setIsDepartmentsOpen((prev) => !prev)
                                    }
                                    className={`flex min-h-[52px] w-full items-center justify-between gap-3 rounded-[24px] border bg-white px-4 py-3 text-left transition ${
                                        isDepartmentsOpen
                                            ? 'border-sky-300 ring-4 ring-sky-100'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="flex min-w-0 flex-1 flex-wrap gap-2">
                                        {selectedDepartments.length > 0 ? (
                                            selectedDepartments.map((department) => (
                                                <span
                                                    key={department.id}
                                                    className="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200"
                                                >
                                                    {department.name}

                                                    <button
                                                        type="button"
                                                        onClick={(event) => {
                                                            event.stopPropagation()
                                                            removeDepartment(
                                                                department.id,
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
                                                Select departments
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
                                        <input
                                            type="text"
                                            value={departmentSearch}
                                            onChange={(event) =>
                                                setDepartmentSearch(
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Search departments..."
                                            className="mb-2 h-10 w-full rounded-2xl border border-gray-200 px-3 text-sm outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                        />

                                        <div className="max-h-64 overflow-y-auto">
                                            {filteredDepartments.map(
                                                (department) => {
                                                    const selected =
                                                        data.department_ids.includes(
                                                            department.id,
                                                        )

                                                    return (
                                                        <button
                                                            key={department.id}
                                                            type="button"
                                                            onClick={() =>
                                                                toggleDepartment(
                                                                    department.id,
                                                                )
                                                            }
                                                            className={`flex w-full items-center justify-between rounded-2xl px-3 py-3 text-left text-sm transition ${
                                                                selected
                                                                    ? 'bg-sky-50 text-sky-700'
                                                                    : 'text-gray-700 hover:bg-gray-50'
                                                            }`}
                                                        >
                                                            <span className="font-medium">
                                                                {department.name}
                                                            </span>

                                                            {selected && (
                                                                <Check className="h-4 w-4" />
                                                            )}
                                                        </button>
                                                    )
                                                },
                                            )}
                                        </div>
                                    </div>
                                )}

                                <InputError
                                    message={errors.department_ids}
                                    className="mt-2"
                                />
                            </div>

                            <div ref={teamsRef} className="relative">
                                <div className="mb-2 flex items-center gap-2">
                                    <p className="block text-sm font-medium text-gray-700">
                                        Teams
                                    </p>

                                    <FieldHint text="Teams connected to this agent." />
                                </div>

                                <button
                                    type="button"
                                    onClick={() => setIsTeamsOpen((prev) => !prev)}
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
                                                        onClick={(event) => {
                                                            event.stopPropagation()
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
                                </button>

                                {isTeamsOpen && (
                                    <div className="absolute z-20 mt-2 w-full rounded-[24px] border border-gray-200 bg-white p-2 shadow-xl shadow-gray-900/10">
                                        <input
                                            type="text"
                                            value={teamSearch}
                                            onChange={(event) =>
                                                setTeamSearch(event.target.value)
                                            }
                                            placeholder="Search teams..."
                                            className="mb-2 h-10 w-full rounded-2xl border border-gray-200 px-3 text-sm outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                        />

                                        <div className="max-h-64 overflow-y-auto">
                                            {filteredTeams.map((team) => {
                                                const selected =
                                                    data.team_ids.includes(team.id)

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

                                <InputError message={errors.team_ids} className="mt-2" />
                            </div>

                            <label
                                htmlFor="is-active"
                                aria-label="Agent account is active"
                                className={`lg:col-span-2 flex cursor-pointer items-start gap-3 rounded-[24px] border p-4 transition ${
                                    data.is_active
                                        ? 'border-emerald-200 bg-emerald-50 ring-4 ring-emerald-100'
                                        : 'border-gray-200 bg-white hover:bg-gray-50'
                                }`}
                            >
                                <input
                                    id="is-active"
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(event) =>
                                        setData('is_active', event.target.checked)
                                    }
                                    className="mt-1 h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500"
                                />

                                <span>
                                    <span className="block text-sm font-medium text-gray-900">
                                        Agent account is active
                                    </span>

                                    <span className="mt-1 block text-sm leading-6 text-gray-500">
                                        Inactive agents remain in the system but cannot be treated as active staff.
                                    </span>
                                </span>
                            </label>

                            <InputError message={errors.is_active} className="mt-2" />
                        </div>
                    </section>

                    <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 ring-1 ring-inset ring-violet-100">
                                    <Lock className="h-6 w-6 text-violet-600" />
                                </div>

                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Password
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {isEdit
                                            ? 'Leave password fields empty to keep the current password.'
                                            : 'Set the initial password for this agent.'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 p-6 lg:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="password"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Password {!isEdit && <span className="text-rose-500">*</span>}
                                </label>

                                <input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(event) =>
                                        setData('password', event.target.value)
                                    }
                                    placeholder={
                                        isEdit
                                            ? 'Leave empty to keep current password'
                                            : 'Password'
                                    }
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div>
                                <label
                                    htmlFor="password-confirmation"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Confirm password {!isEdit && <span className="text-rose-500">*</span>}
                                </label>

                                <input
                                    id="password-confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(event) =>
                                        setData(
                                            'password_confirmation',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Confirm password"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError
                                    message={errors.password_confirmation}
                                    className="mt-2"
                                />
                            </div>
                        </div>
                    </section>

                    <section className="overflow-visible rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div>
                                <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Agent Signature
                                </h2>
                                <p className="mt-1 text-sm text-gray-500">
                                    Optional signature used for agent replies.
                                </p>
                            </div>
                        </div>

                        <div className="p-6">
                            <label
                                htmlFor="signature"
                                className="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Signature
                            </label>

                            <textarea
                                id="signature"
                                value={data.signature}
                                onChange={(event) =>
                                    setData('signature', event.target.value)
                                }
                                rows={10}
                                placeholder="Write agent signature..."
                                className="w-full rounded-[24px] border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                            />

                            <InputError message={errors.signature} className="mt-2" />
                        </div>
                    </section>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <Link
                            href={route('admin.agents.index')}
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

import AdminLayout from '@/Layouts/AdminLayout'
import InputError from '@/Components/InputError'
import FieldHint from '@/Components/FieldHint'
import { Head, Link, useForm } from '@inertiajs/react'
import {
    ArrowLeft,
    BadgeCheck,
    Save,
    ShieldCheck,
} from 'lucide-react'
import { useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { route } from 'ziggy-js'
import type {
    RoleFormData,
    RolePermission,
    RolePermissionGroup,
    RolePermissionPanel,
    RoleType,
} from '@/types/role'

type Props = {
    readonly mode: 'create' | 'edit'
    readonly type: RoleType
    readonly permissionPanels: RolePermissionPanel[]
    readonly initialData: RoleFormData
    readonly submitUrl: string
    readonly isSystem?: boolean
}

function getPageTitle(isEdit: boolean, type: RoleType) {
    const label = type === 'agent' ? 'Agent Role' : 'User Role'
    return isEdit ? `Edit ${label}` : `Create ${label}`
}

function getSubmitText(isEdit: boolean, processing: boolean) {
    if (processing) return isEdit ? 'Saving...' : 'Creating...'
    return isEdit ? 'Save Changes' : 'Create Role'
}

function toggleId(ids: number[], id: number) {
    return ids.includes(id)
        ? ids.filter((currentId) => currentId !== id)
        : [...ids, id]
}

function collectPermissionIds(group: RolePermissionGroup) {
    return group.permissions.flatMap((permission) =>
        collectPermissionTreeIds(permission),
    )
}

function collectPermissionTreeIds(permission: RolePermission): number[] {
    return [
        permission.id,
        ...permission.children.flatMap((child) =>
            collectPermissionTreeIds(child),
        ),
    ]
}

export default function RoleForm({
                                     mode,
                                     type,
                                     permissionPanels,
                                     initialData,
                                     submitUrl,
                                     isSystem = false,
                                 }: Props) {
    const [activePanelKey, setActivePanelKey] = useState(
        permissionPanels[0]?.key ?? 'general',
    )

    const { data, setData, post, put, processing, errors } =
        useForm<RoleFormData>(initialData)

    const isEdit = mode === 'edit'
    const pageTitle = getPageTitle(isEdit, type)
    const submitText = getSubmitText(isEdit, processing)

    const activePanel = useMemo(() => {
        return (
            permissionPanels.find((panel) => panel.key === activePanelKey) ??
            permissionPanels[0]
        )
    }, [activePanelKey, permissionPanels])

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault()

        if (isEdit) {
            put(submitUrl)
            return
        }

        post(submitUrl)
    }

    function togglePermission(permission: RolePermission) {
        let ids = toggleId(data.permission_ids, permission.id)

        if (data.permission_ids.includes(permission.id)) {
            const treeIds = collectPermissionTreeIds(permission)

            ids = ids.filter((id) => !treeIds.includes(id))
        }

        setData('permission_ids', ids)
    }

    function togglePermissionNode(
        permission: RolePermission,
        parent?: RolePermission,
    ) {
        let ids = [...data.permission_ids]

        if (parent && !ids.includes(parent.id)) {
            ids.push(parent.id)
        }

        if (permission.ui_type === 'radio' && parent) {
            const siblingIds = parent.children
                .filter((child) => child.ui_type === 'radio')
                .map((child) => child.id)

            ids = ids.filter((id) => !siblingIds.includes(id))
            ids.push(permission.id)

            setData('permission_ids', Array.from(new Set(ids)))
            return
        }

        if (ids.includes(permission.id)) {
            const treeIds = collectPermissionTreeIds(permission)

            ids = ids.filter((id) => !treeIds.includes(id))
        } else {
            ids.push(permission.id)
        }

        setData('permission_ids', Array.from(new Set(ids)))
    }

    function isChecked(id: number) {
        return data.permission_ids.includes(id)
    }

    function isGroupSelected(group: RolePermissionGroup) {
        const ids = collectPermissionIds(group)
        return ids.length > 0 && ids.every((id) => data.permission_ids.includes(id))
    }

    function toggleGroup(group: RolePermissionGroup) {
        const groupIds = collectPermissionIds(group)

        if (isGroupSelected(group)) {
            setData(
                'permission_ids',
                data.permission_ids.filter((id) => !groupIds.includes(id)),
            )

            return
        }

        setData(
            'permission_ids',
            Array.from(new Set([...data.permission_ids, ...groupIds])),
        )
    }

    function scrollToGroup(groupKey: string) {
        document
            .getElementById(`permission-group-${groupKey}`)
            ?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }

    return (
        <AdminLayout title={pageTitle}>
            <Head title={pageTitle} />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0">
                        <div className="flex items-center gap-2 text-sm font-medium text-sky-600">
                            <ShieldCheck className="h-4 w-4" />
                            Role Management
                        </div>

                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                            {pageTitle}
                        </h1>

                        <p className="mt-2 max-w-2xl text-sm leading-6 text-gray-500">
                            Configure role details and choose permissions available
                            for this role.
                        </p>
                    </div>

                    <Link
                        href={route('admin.roles.index')}
                        className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to Roles
                    </Link>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-inset ring-sky-100">
                                    <BadgeCheck className="h-6 w-6 text-sky-600" />
                                </div>

                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Role Details
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Basic role information and role type.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 p-6 lg:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="role-label"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Label <span className="text-rose-500">*</span>
                                </label>

                                <input
                                    id="role-label"
                                    type="text"
                                    value={data.label}
                                    onChange={(e) => setData('label', e.target.value)}
                                    placeholder="Example: Organization User"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError message={errors.label} className="mt-2" />
                            </div>

                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <label
                                        htmlFor="role-name"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        Name
                                    </label>

                                    <FieldHint text="Optional internal role name. If empty, it will be generated from label." />
                                </div>

                                <input
                                    id="role-name"
                                    type="text"
                                    value={data.name}
                                    disabled={isSystem}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="organization_user"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100 disabled:bg-gray-50 disabled:text-gray-400"
                                />

                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div className="lg:col-span-2">
                                <label
                                    htmlFor="role-description"
                                    className="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Description
                                </label>

                                <textarea
                                    id="role-description"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData('description', e.target.value)
                                    }
                                    rows={4}
                                    placeholder="Describe what this role can do..."
                                    className="w-full rounded-[24px] border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                <InputError
                                    message={errors.description}
                                    className="mt-2"
                                />
                            </div>

                            <div>
                                <p className="mb-2 block text-sm font-medium text-gray-700">
                                    Role type
                                </p>

                                <div className="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">
                                    {type === 'agent' ? 'Agent' : 'User'}
                                </div>
                            </div>

                            <div>
                                <label
                                    htmlFor="is-default"
                                    className={`flex cursor-pointer items-start gap-3 rounded-[24px] border p-4 transition ${
                                        data.is_default
                                            ? 'border-sky-200 bg-sky-50 ring-4 ring-sky-100'
                                            : 'border-gray-200 bg-white hover:bg-gray-50'
                                    } ${isSystem ? 'opacity-70' : ''}`}
                                >
                                    <input
                                        id="is-default"
                                        type="checkbox"
                                        checked={data.is_default}
                                        disabled={isSystem}
                                        onChange={(e) =>
                                            setData('is_default', e.target.checked)
                                        }
                                        className="mt-1 h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500"
                                    />

                                    <span>
                                        <span className="block text-sm font-medium text-gray-900">
                                            Make default role
                                        </span>

                                        <span className="mt-1 block text-sm leading-6 text-gray-500">
                                            Use this role as default for new{' '}
                                            {type === 'agent' ? 'agents' : 'users'}.
                                        </span>
                                    </span>
                                </label>

                                <InputError
                                    message={errors.is_default}
                                    className="mt-2"
                                />
                            </div>
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                            <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                Permissions
                            </h2>
                            <p className="mt-1 text-sm text-gray-500">
                                Select permissions available for this role.
                            </p>
                        </div>

                        <div className="border-b border-gray-200 px-6 pt-5">
                            <div className="flex flex-wrap gap-2">
                                {permissionPanels.map((panel) => (
                                    <button
                                        key={panel.key}
                                        type="button"
                                        onClick={() => setActivePanelKey(panel.key)}
                                        className={`rounded-t-2xl border px-4 py-3 text-sm font-medium transition ${
                                            activePanel?.key === panel.key
                                                ? 'border-gray-200 border-b-white bg-white text-sky-700'
                                                : 'border-transparent text-gray-500 hover:bg-gray-50 hover:text-gray-900'
                                        }`}
                                    >
                                        {panel.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {activePanel ? (
                            <>
                                <div className="border-b border-gray-100 bg-gray-50/70 px-6 py-4">
                                    <div className="flex flex-wrap items-center gap-2 text-sm">
                                        <span className="font-semibold text-gray-700">
                                            Scroll To:
                                        </span>

                                        {activePanel.groups.map((group, index) => (
                                            <span key={group.id}>
                                                {index > 0 ? (
                                                    <span className="mx-1 text-gray-300">
                                                        |
                                                    </span>
                                                ) : null}

                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        scrollToGroup(group.key)
                                                    }
                                                    className="font-medium text-sky-600 transition hover:text-sky-700"
                                                >
                                                    {group.label}
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                </div>

                                <div className="grid gap-5 p-6 xl:grid-cols-2">
                                    {activePanel.groups.map((group) => (
                                        <div
                                            key={group.id}
                                            id={`permission-group-${group.key}`}
                                            className="scroll-mt-6 overflow-hidden rounded-[24px] border border-gray-200 bg-gray-50"
                                        >
                                            <div className="flex items-center justify-between gap-4 border-b border-gray-200 bg-white px-5 py-4">
                                                <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-900">
                                                    {group.label}
                                                </h3>

                                                <label className="flex cursor-pointer items-center gap-2 text-xs font-medium text-gray-500">
                                                    <input
                                                        type="checkbox"
                                                        checked={isGroupSelected(group)}
                                                        onChange={() =>
                                                            toggleGroup(group)
                                                        }
                                                        className="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500"
                                                    />
                                                    Select All
                                                </label>
                                            </div>

                                            <div className="space-y-4 p-5">
                                                {group.permissions.map((permission) => (
                                                    <PermissionNode
                                                        key={permission.id}
                                                        permission={permission}
                                                        isChecked={isChecked}
                                                        togglePermissionNode={togglePermissionNode}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <div className="p-6 text-sm text-gray-500">
                                No permissions available for this role type.
                            </div>
                        )}

                        <InputError
                            message={errors.permission_ids}
                            className="px-6 pb-6"
                        />
                    </section>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <Link
                            href={route('admin.roles.index')}
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

function PermissionNode({
                            permission,
                            parent,
                            depth = 0,
                            isChecked,
                            togglePermissionNode,
                        }: {
    permission: RolePermission
    parent?: RolePermission
    depth?: number
    isChecked: (id: number) => boolean
    togglePermissionNode: (
        permission: RolePermission,
        parent?: RolePermission,
    ) => void
}) {
    const disabled = parent ? !isChecked(parent.id) : false
    const hasChildren = permission.children.length > 0

    return (
        <div>
            <label
                className={`flex cursor-pointer items-start gap-3 ${
                    disabled ? 'opacity-50' : ''
                }`}
                style={{ paddingLeft: depth * 24 }}
            >
                <input
                    type={permission.ui_type}
                    name={
                        permission.ui_type === 'radio' && parent
                            ? `permission-${parent.id}`
                            : undefined
                    }
                    checked={isChecked(permission.id)}
                    disabled={disabled}
                    onChange={() => togglePermissionNode(permission, parent)}
                    className="mt-1 h-4 w-4 border-gray-300 text-sky-600 focus:ring-sky-500"
                />

                <span>
                    <span
                        className={`block text-sm font-medium ${
                            depth === 0 ? 'text-gray-900' : 'text-gray-700'
                        }`}
                    >
                        {permission.label}
                    </span>

                    {permission.description ? (
                        <span className="mt-1 block text-sm leading-6 text-gray-500">
                            {permission.description}
                        </span>
                    ) : null}
                </span>
            </label>

            {hasChildren ? (
                <div className="mt-3 space-y-3">
                    {permission.children.map((child) => (
                        <PermissionNode
                            key={child.id}
                            permission={child}
                            parent={permission}
                            depth={depth + 1}
                            isChecked={isChecked}
                            togglePermissionNode={togglePermissionNode}
                        />
                    ))}
                </div>
            ) : null}
        </div>
    )
}

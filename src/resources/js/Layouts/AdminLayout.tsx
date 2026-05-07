import { useMemo, useState } from 'react'
import type { ComponentType, ReactNode } from 'react'
import { Link, router, usePage } from '@inertiajs/react'
import {
    LayoutDashboard,
    UserCog,
    Building2,
    UsersRound,
    Mail,
    Wrench,
    Settings,
    Puzzle,
    Code2,
    Bell,
    CreditCard,
    Bug,
    LogOut,
    ChevronDown,
    ChevronUp,
    Search,
    Plus,
    RefreshCw,
    Shield,
    Server,
    Workflow,
    CheckCircle2,
    Briefcase,
    GitBranch,
    Check,
} from 'lucide-react'

import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/Components/ui/dropdown-menu'
import { route } from 'ziggy-js'
import { usePermissions } from '@/hooks/usePermissions'

import type { SharedData } from '@/types'

type Props = {
    readonly title?: string
    readonly children: ReactNode
}

type NavItem = {
    label: string
    href: string
    icon: ComponentType<{ className?: string }>
    isActive: (url: string) => boolean
    permission?: string
    permissions?: string[]
    visible?: boolean
}

type NavSection = {
    title: string
    collapsible?: boolean
    items: NavItem[]
}

function logout() {
    router.post('/logout')
}

export default function AdminLayout({ title = 'Admin Panel', children }: Props) {
    const { props, url } = usePage<SharedData>()
    const user = props.auth.user
    const [locale, setLocale] = useState<'EN' | 'RU'>('EN')

    const { can, canAny } = usePermissions()

    const canAccessAgentPanel = canAny([
        'agent.tickets.visibility.assigned',
        'agent.tickets.visibility.team',
        'agent.tickets.visibility.department',
        'agent.tickets.visibility.all',
    ])

    const sections: NavSection[] = useMemo(() => {
        const navSections: NavSection[] = [
            {
                title: 'Main',
                collapsible: false,
                items: [
                    {
                        label: 'Admin Panel',
                        href: route('admin.dashboard'),
                        icon: LayoutDashboard,
                        isActive: (currentUrl: string) =>
                            currentUrl === '/admin/dashboard',
                    },
                    {
                        label: 'Go to Agent Page',
                        href: route('dashboard'),
                        icon: Briefcase,
                        isActive: (currentUrl: string) =>
                            currentUrl === '/dashboard' ||
                            currentUrl.startsWith('/dashboard/'),
                        visible: canAccessAgentPanel,
                    },
                ],
            },
            {
                title: 'Staff',
                collapsible: true,
                items: [
                    {
                        label: 'Agents',
                        href: '#',
                        icon: UserCog,
                        isActive: () => false,
                    },
                    {
                        label: 'Roles',
                        href: route('admin.roles.index'),
                        icon: Shield,
                        permission: 'admin.staff.manage_roles',
                        isActive: (currentUrl: string) =>
                            currentUrl === '/admin/roles' ||
                            currentUrl.startsWith('/admin/roles/'),
                    },
                    {
                        label: 'Departments',
                        href: route('admin.departments.index'),
                        icon: Building2,
                        permission: 'admin.staff.manage_departments',
                        isActive: (currentUrl: string) =>
                            currentUrl === '/admin/departments' ||
                            currentUrl.startsWith('/admin/departments/'),
                    },
                    {
                        label: 'Teams',
                        href: route('admin.teams.index'),
                        icon: UsersRound,
                        permission: 'admin.staff.manage_teams',
                        isActive: (currentUrl: string) =>
                            currentUrl === '/admin/teams' ||
                            currentUrl.startsWith('/admin/teams/'),
                    },
                ],
            },
            {
                title: 'Email',
                collapsible: true,
                items: [
                    {
                        label: 'Email Settings',
                        href: '#',
                        icon: Mail,
                        isActive: () => false,
                    },
                    {
                        label: 'BreakLines',
                        href: '#',
                        icon: GitBranch,
                        isActive: () => false,
                    },
                    {
                        label: 'Diagnostics',
                        href: '#',
                        icon: Wrench,
                        isActive: () => false,
                    },
                    {
                        label: 'Email OAuth Integration',
                        href: '#',
                        icon: CheckCircle2,
                        isActive: () => false,
                    },
                ],
            },
            {
                title: 'Drivers',
                collapsible: true,
                items: [
                    {
                        label: 'Queues',
                        href: '#',
                        icon: Workflow,
                        isActive: () => false,
                    },
                    {
                        label: 'Cache Drivers',
                        href: '#',
                        icon: Server,
                        isActive: () => false,
                    },
                    {
                        label: 'WebSockets',
                        href: '#',
                        icon: RefreshCw,
                        isActive: () => false,
                    },
                    {
                        label: 'Search',
                        href: '#',
                        icon: Search,
                        isActive: () => false,
                    },
                ],
            },
            {
                title: 'System',
                collapsible: true,
                items: [
                    {
                        label: 'Manage',
                        href: '#',
                        icon: Settings,
                        isActive: () => false,
                    },
                    {
                        label: 'Add Ons',
                        href: '#',
                        icon: Puzzle,
                        isActive: () => false,
                    },
                    {
                        label: 'Developer Settings',
                        href: '#',
                        icon: Code2,
                        isActive: () => false,
                    },
                    {
                        label: 'Notify',
                        href: '#',
                        icon: Bell,
                        isActive: () => false,
                    },
                    {
                        label: 'Billing',
                        href: '#',
                        icon: CreditCard,
                        isActive: () => false,
                    },
                    {
                        label: 'Debug',
                        href: '#',
                        icon: Bug,
                        isActive: () => false,
                    },
                ],
            },
        ]

        return navSections
            .map((section) => ({
                ...section,
                items: section.items.filter((item) => {
                    if (item.visible === false) {
                        return false
                    }

                    if (item.permission) {
                        return can(item.permission)
                    }

                    if (item.permissions) {
                        return canAny(item.permissions)
                    }

                    return true
                }),
            }))
            .filter((section) => section.items.length > 0)
    }, [can, canAny, canAccessAgentPanel])

    const [openSections, setOpenSections] = useState<Record<string, boolean>>(() => {
        const state: Record<string, boolean> = {}

        sections.forEach((section) => {
            const hasActiveItem = section.items.some((item) => item.isActive(url))

            if (section.collapsible) {
                state[section.title] = hasActiveItem
            }
        })

        return state
    })

    function toggleSection(title: string) {
        setOpenSections((prev) => ({
            ...prev,
            [title]: !prev[title],
        }))
    }



    return (
        <div className="min-h-screen bg-gray-100">
            <div className="flex min-h-screen">
                <aside className="hidden w-80 shrink-0 bg-slate-900 text-white lg:flex lg:flex-col">
                    <div className="flex h-20 items-center border-b border-white/10 px-6">
                        <Link href="/admin" className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/15 ring-1 ring-inset ring-sky-400/20">
                                <Shield className="h-5 w-5 text-sky-300" />
                            </div>

                            <div>
                                <div className="text-lg font-semibold tracking-tight text-white">
                                    SimpleDesk
                                </div>
                                <div className="text-xs uppercase tracking-[0.22em] text-slate-400">
                                    Admin Panel
                                </div>
                            </div>
                        </Link>
                    </div>

                    <div className="flex-1 overflow-y-auto px-4 py-5">
                        <div className="space-y-3">
                            {sections.map((section) => {
                                const isOpen = openSections[section.title]
                                const hasActiveItem = section.items.some((item) => item.isActive(url))

                                if (!section.collapsible) {
                                    return (
                                        <div key={section.title}>
                                            <div className="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                {section.title}
                                            </div>

                                            <nav className="space-y-1.5">
                                                {section.items.map((item) => {
                                                    const Icon = item.icon
                                                    const active = item.isActive(url)

                                                    return (
                                                        <Link
                                                            key={`${section.title}-${item.label}`}
                                                            href={item.href}
                                                            className={`group flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-medium transition-all ${
                                                                active
                                                                    ? 'bg-sky-500/15 text-sky-300 ring-1 ring-inset ring-sky-500/20'
                                                                    : 'text-slate-300 hover:bg-white/5 hover:text-white'
                                                            }`}
                                                        >
                                                            <Icon
                                                                className={`h-4 w-4 shrink-0 transition ${
                                                                    active
                                                                        ? 'text-sky-300'
                                                                        : 'text-slate-500 group-hover:text-slate-300'
                                                                }`}
                                                            />
                                                            <span>{item.label}</span>
                                                        </Link>
                                                    )
                                                })}
                                            </nav>
                                        </div>
                                    )
                                }

                                return (
                                    <div key={section.title}>
                                        <button
                                            type="button"
                                            onClick={() => toggleSection(section.title)}
                                            className={`flex w-full items-center justify-between rounded-2xl px-3.5 py-3 text-left text-sm font-medium transition ${
                                                hasActiveItem || isOpen
                                                    ? 'bg-white/8 text-white'
                                                    : 'text-slate-300 hover:bg-white/5 hover:text-white'
                                            }`}
                                        >
                                            <span className="flex items-center gap-3">
                                                <span className="text-sm font-semibold">
                                                    {section.title}
                                                </span>
                                            </span>

                                            {isOpen ? (
                                                <ChevronUp className="h-4 w-4 text-slate-400" />
                                            ) : (
                                                <ChevronDown className="h-4 w-4 text-slate-400" />
                                            )}
                                        </button>

                                        {isOpen && (
                                            <nav className="mt-2 space-y-1 pl-3">
                                                {section.items.map((item) => {
                                                    const Icon = item.icon
                                                    const active = item.isActive(url)

                                                    return (
                                                        <Link
                                                            key={`${section.title}-${item.label}`}
                                                            href={item.href}
                                                            className={`group flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-medium transition-all ${
                                                                active
                                                                    ? 'bg-sky-500/15 text-sky-300 ring-1 ring-inset ring-sky-500/20'
                                                                    : 'text-slate-300 hover:bg-white/5 hover:text-white'
                                                            }`}
                                                        >
                                                            <Icon
                                                                className={`h-4 w-4 shrink-0 transition ${
                                                                    active
                                                                        ? 'text-sky-300'
                                                                        : 'text-slate-500 group-hover:text-slate-300'
                                                                }`}
                                                            />
                                                            <span>{item.label}</span>
                                                        </Link>
                                                    )
                                                })}
                                            </nav>
                                        )}
                                    </div>
                                )
                            })}
                        </div>
                    </div>

                    <div className="border-t border-white/10 p-4">
                        <div className="rounded-2xl bg-white/5 p-3">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white">
                                    {user?.name
                                        ?.split(' ')
                                        .map((part) => part[0])
                                        .slice(0, 2)
                                        .join('')
                                        .toUpperCase() || 'A'}
                                </div>

                                <div className="min-w-0">
                                    <div className="truncate text-sm font-medium text-white">
                                        {user?.name}
                                    </div>
                                    <div className="truncate text-xs text-slate-400">
                                        {user?.email}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-3 rounded-xl border border-sky-500/20 bg-sky-500/10 px-3 py-2 text-xs font-medium text-sky-300">
                                Administrator access
                            </div>

                            <button
                                type="button"
                                onClick={logout}
                                className="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white"
                            >
                                <LogOut className="h-4 w-4" />
                                Logout
                            </button>
                        </div>
                    </div>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="sticky top-0 z-20 border-b border-gray-200 bg-white/85 backdrop-blur">
                        <div className="flex h-20 items-center justify-between gap-4 px-4 sm:px-6">
                            <div className="min-w-0">
                                <div className="flex items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-slate-500">
                                    <Shield className="h-3.5 w-3.5" />
                                    Admin
                                </div>

                                <h1 className="truncate text-3xl font-semibold tracking-tight text-gray-900">
                                    {title}
                                </h1>
                            </div>

                            <div className="hidden flex-1 justify-center xl:flex">
                                <div className="relative w-full max-w-md">
                                    <Search className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Search settings, users, departments..."
                                        className="h-12 w-full rounded-2xl border border-gray-200 bg-gray-50 pl-12 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2 sm:gap-3">
                                <Link
                                    href="#"
                                    className="hidden h-12 items-center gap-2 rounded-2xl bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800 sm:inline-flex"
                                >
                                    <Plus className="h-4 w-4" />
                                    New
                                </Link>

                                <button
                                    type="button"
                                    className="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                                >
                                    <RefreshCw className="h-5 w-5" />
                                </button>

                                <button
                                    type="button"
                                    className="relative inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                                >
                                    <Bell className="h-5 w-5" />
                                    <span className="absolute right-3 top-3 h-2.5 w-2.5 rounded-full bg-sky-500 ring-2 ring-white" />
                                </button>

                                <div className="hidden md:block">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <button
                                                type="button"
                                                className="inline-flex h-12 min-w-[96px] items-center justify-between gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                            >
                                                <span>{locale}</span>
                                                <ChevronDown className="h-4 w-4 text-gray-400" />
                                            </button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent
                                            align="end"
                                            sideOffset={8}
                                            className="min-w-[96px] rounded-2xl border border-gray-200 bg-white p-1 shadow-lg shadow-gray-900/5"
                                        >
                                            <DropdownMenuItem
                                                onClick={() => setLocale('EN')}
                                                className="flex cursor-pointer items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-gray-700 focus:bg-gray-50 focus:text-gray-900"
                                            >
                                                <span>EN</span>
                                                {locale === 'EN' && <Check className="h-4 w-4" />}
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                onClick={() => setLocale('RU')}
                                                className="flex cursor-pointer items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-gray-700 focus:bg-gray-50 focus:text-gray-900"
                                            >
                                                <span>RU</span>
                                                {locale === 'RU' && <Check className="h-4 w-4" />}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>

                                <Link
                                    href="/profile"
                                    className="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white px-3 py-2 transition hover:bg-gray-50"
                                >
                                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700">
                                        {user?.name?.charAt(0).toUpperCase() || 'A'}
                                    </div>

                                    <div className="hidden text-left lg:block">
                                        <div className="max-w-[140px] truncate text-sm font-medium text-gray-900">
                                            {user?.name}
                                        </div>
                                        <div className="max-w-[140px] truncate text-xs text-gray-500">
                                            {user?.email}
                                        </div>
                                    </div>

                                    <ChevronDown className="hidden h-4 w-4 text-gray-400 lg:block" />
                                </Link>
                            </div>
                        </div>
                    </header>

                    <main className="flex-1 p-4 sm:p-6">{children}</main>

                    <footer className="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                        <div className="flex flex-col gap-2 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                Copyright © 2026. All rights reserved. Powered by{' '}
                                <a
                                    href="https://github.com/CptN-e-m-o/SimpleDesk"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-medium text-sky-600 transition hover:text-sky-700 hover:underline"
                                >
                                    SimpleDesk
                                </a>
                            </div>

                            <div className="font-medium text-gray-600">Admin version 0.0.1</div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    )
}

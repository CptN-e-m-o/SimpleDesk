import { useState } from 'react'
import type { ReactNode } from 'react'
import { Link, router, usePage } from '@inertiajs/react'
import {
    LayoutDashboard,
    Ticket,
    LogOut,
    Search,
    Bell,
    Plus,
    RefreshCw,
    ChevronDown,
} from 'lucide-react'

import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/Components/ui/dropdown-menu'
import { Check } from 'lucide-react'

import type { SharedData } from '../types'

type Props = {
    title?: string
    children: ReactNode
}

const navigation = [
    {
        label: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
        isActive: (url: string) => url === '/dashboard',
    },
    {
        label: 'Tickets',
        href: '/tickets',
        icon: Ticket,
        isActive: (url: string) => url === '/tickets' || url.startsWith('/tickets/'),
    },
]

export default function AgentLayout({ title = 'Dashboard', children }: Props) {
    const { props, url } = usePage<SharedData>()
    const user = props.auth.user
    const [locale, setLocale] = useState<'EN' | 'RU'>('EN')

    function logout() {
        router.post('/logout')
    }

    return (
        <div className="min-h-screen bg-gray-50">
            <div className="flex min-h-screen">
                <aside className="hidden w-72 shrink-0 bg-gray-900 text-white lg:flex lg:flex-col">
                    <div className="flex h-24 items-center justify-center border-b border-gray-800 px-6">
                        <Link href="/" className="text-3xl font-extrabold tracking-tight">
                            <span className="bg-gradient-to-r from-sky-400 via-blue-500 to-violet-600 bg-clip-text text-transparent">
                                SimpleDesk
                            </span>
                        </Link>
                    </div>

                    <div className="flex-1 px-4 py-6">
                        <div className="mb-3 px-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Workspace
                        </div>

                        <nav className="space-y-1.5">
                            {navigation.map((item) => {
                                const Icon = item.icon
                                const isActive = item.isActive(url)

                                return (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={`group flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-medium transition-all ${
                                            isActive
                                                ? 'bg-sky-500/15 text-sky-300 ring-1 ring-inset ring-sky-500/20'
                                                : 'text-gray-300 hover:bg-white/5 hover:text-white'
                                        }`}
                                    >
                                        <Icon
                                            className={`h-4 w-4 shrink-0 transition ${
                                                isActive
                                                    ? 'text-sky-300'
                                                    : 'text-gray-500 group-hover:text-gray-300'
                                            }`}
                                        />
                                        <span>{item.label}</span>
                                    </Link>
                                )
                            })}
                        </nav>
                    </div>

                    <div className="rounded-2xl bg-white/5 p-3">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white">
                                {user?.name
                                    ?.split(' ')
                                    .map((n) => n[0])
                                    .slice(0, 2)
                                    .join('')
                                    .toUpperCase() || 'U'}
                            </div>

                            <div className="min-w-0">
                                <div className="truncate text-sm font-medium text-white">
                                    {user?.name}
                                </div>
                                <div className="truncate text-xs text-gray-400">
                                    {user?.email}
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            onClick={logout}
                            className="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-gray-200 transition hover:bg-white/10 hover:text-white"
                        >
                            <LogOut className="h-4 w-4" />
                            Logout
                        </button>
                    </div>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="sticky top-0 z-20 border-b border-gray-200 bg-white/80 backdrop-blur">
                        <div className="flex h-24 items-center justify-between gap-4 px-4 sm:px-6">
                            <div className="min-w-0">
                                <h1 className="truncate text-3xl font-semibold tracking-tight text-gray-900">
                                    {title}
                                </h1>
                            </div>

                            <div className="hidden flex-1 justify-center xl:flex">
                                <div className="relative w-full max-w-md">
                                    <Search className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Search tickets, users, articles..."
                                        className="
                                            h-14 w-full rounded-2xl border border-gray-200 bg-gray-50
                                            pl-12 pr-4 text-sm text-gray-900 outline-none transition
                                            placeholder:text-gray-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100
                                        "
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2 sm:gap-3">
                                <Link
                                    href="#"
                                    className="
                                        hidden h-14 items-center gap-2 rounded-2xl bg-gray-900
                                        px-5 text-sm font-medium text-white
                                        transition hover:bg-gray-800
                                        sm:inline-flex
                                    "
                                >
                                    <Plus className="h-5 w-5" />
                                    New ticket
                                </Link>

                                <Link
                                    href="#"
                                    className="inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                                >
                                    <RefreshCw className="h-5 w-5" />
                                </Link>

                                <Link
                                    href="#"
                                    className="relative inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                                >
                                    <Bell className="h-5 w-5" />
                                    <span className="absolute right-3.5 top-3.5 h-2.5 w-2.5 rounded-full bg-sky-500 ring-2 ring-white" />
                                </Link>

                                <div className="hidden md:block">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <button
                                                type="button"
                                                className="
                                                    inline-flex h-14 min-w-[104px] items-center justify-between gap-2 rounded-2xl
                                                    border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700
                                                    transition hover:bg-gray-50 hover:text-gray-900
                                                    focus:outline-none focus:ring-4 focus:ring-sky-100
                                                "
                                            >
                                                <span>{locale}</span>
                                                <ChevronDown className="h-4 w-4 text-gray-400" />
                                            </button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent
                                            align="end"
                                            sideOffset={8}
                                            className="min-w-[104px] rounded-2xl border border-gray-200 bg-white p-1 shadow-lg shadow-gray-900/5"
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
                                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-700">
                                        {user?.name?.charAt(0).toUpperCase() || 'U'}
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

                            <div className="font-medium text-gray-600">
                                Version 0.0.1
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    )
}

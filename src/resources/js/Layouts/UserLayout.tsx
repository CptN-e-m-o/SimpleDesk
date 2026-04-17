import type { ReactNode } from 'react'
import { Link, usePage } from '@inertiajs/react'
import { ChevronDown } from 'lucide-react'
import type { SharedData } from '../types'
import { hasAnyRole } from '@/lib/roles'
import { route } from 'ziggy-js'

type Props = {
    title?: string
    children: ReactNode
}

export default function PublicLayout({ children }: Props) {
    const { props } = usePage<SharedData>()
    const user = props.auth?.user
    const isAdminOrAgent = hasAnyRole(user, ['admin', 'agent'])
    return (
        <div className="min-h-screen bg-gray-50 text-gray-900">
            <header className="border-b border-gray-200 bg-white">
                <div className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
                    <Link href="/" className="text-3xl font-extrabold tracking-tight">
                        <span className="bg-gradient-to-r from-sky-500 via-blue-500 to-violet-600 bg-clip-text text-transparent">
                            SimpleDesk
                        </span>
                    </Link>

                    <nav className="hidden items-center gap-1 md:flex">
                        <Link
                            href="/"
                            className="rounded-xl px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                        >
                            Home
                        </Link>

                        <Link
                            href="/tickets/create"
                            className="rounded-xl px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                        >
                            Submit Ticket
                        </Link>

                        <Link
                            href="/knowledge-base"
                            className="rounded-xl px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                        >
                            Knowledge Base
                        </Link>

                        {user && (
                            <>
                                <Link
                                    href="/tickets"
                                    className="rounded-xl px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                                >
                                    My Tickets
                                </Link>

                                {hasAnyRole(user, ['admin', 'agent']) && (
                                    <Link
                                        href={route('dashboard')}
                                        className="rounded-xl px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                                    >
                                        Agent Dashboard
                                    </Link>
                                )}
                            </>
                        )}
                    </nav>

                    <div className="flex items-center gap-3">
                        {user ? (
                            <Link
                                href="/profile"
                                className="inline-flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                            >
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-700">
                                    {user?.name?.charAt(0).toUpperCase() || 'U'}
                                </div>
                                <span className="hidden sm:inline">{user.name}</span>
                                <ChevronDown className="hidden h-4 w-4 text-gray-400 sm:block" />
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="rounded-xl px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                                >
                                    Sign in
                                </Link>

                                <Link
                                    href="/tickets/create"
                                    className="inline-flex h-11 items-center justify-center rounded-2xl bg-gray-900 px-5 text-sm font-medium text-white transition hover:bg-gray-800"
                                >
                                    Submit ticket
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <div className="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {children}
            </div>

            <footer className="mt-12 border-t border-gray-200 bg-white">
                <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-4 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
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

                    <div className="font-medium text-gray-600">Version 0.0.1</div>
                </div>
            </footer>
        </div>
    )
}

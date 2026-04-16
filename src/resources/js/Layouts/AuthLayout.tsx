import type { ReactNode } from 'react'
import { Link } from '@inertiajs/react'

type Props = {
    title: string
    description?: string
    children: ReactNode
}

export default function AuthLayout({ title, description, children }: Props) {
    return (
        <main className="min-h-screen bg-gray-100">
            <div className="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-4 py-10">
                <div className="grid w-full overflow-hidden rounded-3xl bg-white shadow-xl lg:grid-cols-2">
                    <div className="hidden bg-gray-900 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                        <div>
                            <div className="text-2xl font-bold">SimpleDesk</div>
                            <p className="mt-4 max-w-md text-sm text-gray-300">
                                Modern helpdesk platform built with Laravel, Inertia and React.
                            </p>
                        </div>

                        <div>
                            <div className="text-sm text-gray-400">
                                Ticketing, teams, workflows and customer support in one place.
                            </div>
                        </div>
                    </div>

                    <div className="p-6 sm:p-10">
                        <div className="mb-8 lg:hidden">
                            <Link href="/" className="text-2xl font-bold text-gray-900">
                                SimpleDesk
                            </Link>
                        </div>

                        <div className="mb-6">
                            <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
                            {description && (
                                <p className="mt-2 text-sm text-gray-600">{description}</p>
                            )}
                        </div>

                        {children}
                    </div>
                </div>
            </div>
        </main>
    )
}

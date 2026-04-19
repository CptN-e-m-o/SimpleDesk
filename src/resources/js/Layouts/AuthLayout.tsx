import type { ReactNode } from 'react'
import { Link } from '@inertiajs/react'
import { ShieldCheck, Ticket, LifeBuoy } from 'lucide-react'

type Props = {
    readonly title: string
    readonly description?: string
    readonly children: ReactNode
}

const features = [
    {
        icon: Ticket,
        title: 'Track every request',
        text: 'Create, manage and resolve support tickets in one clear workflow.',
    },
    {
        icon: ShieldCheck,
        title: 'Role-based access',
        text: 'Separate access for clients, agents and administrators.',
    },
    {
        icon: LifeBuoy,
        title: 'Built for support teams',
        text: 'A clean helpdesk space focused on speed, clarity and teamwork.',
    },
]

export default function AuthLayout({ title, description, children }: Props) {
    return (
        <main className="min-h-screen bg-gray-50">
            <div className="relative overflow-hidden">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.10),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(99,102,241,0.10),_transparent_30%)]" />

                <div className="relative mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
                    <div className="grid w-full max-w-6xl overflow-hidden rounded-[32px] border border-gray-200 bg-white shadow-[0_20px_80px_-20px_rgba(15,23,42,0.18)] lg:grid-cols-[1.05fr_0.95fr]">
                        <div className="hidden bg-gray-900 p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-12">
                            <div>
                                <Link href="/" className="inline-block text-3xl font-extrabold tracking-tight">
                                    <span className="bg-gradient-to-r from-sky-400 via-blue-500 to-violet-500 bg-clip-text text-transparent">
                                        SimpleDesk
                                    </span>
                                </Link>

                                <div className="mt-8 max-w-lg">
                                    <h2 className="text-3xl font-semibold leading-tight">
                                        Helpdesk that feels simple for users and powerful for teams
                                    </h2>
                                    <p className="mt-4 text-sm leading-6 text-gray-300">
                                        Sign in to access your workspace, manage requests, and stay on top of support conversations.
                                    </p>
                                </div>

                                <div className="mt-10 space-y-4">
                                    {features.map((item) => {
                                        const Icon = item.icon

                                        return (
                                            <div
                                                key={item.title}
                                                className="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur-sm"
                                            >
                                                <div className="flex items-start gap-3">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-sky-300">
                                                        <Icon className="h-5 w-5" />
                                                    </div>

                                                    <div>
                                                        <div className="text-sm font-semibold text-white">
                                                            {item.title}
                                                        </div>
                                                        <p className="mt-1 text-sm leading-6 text-gray-300">
                                                            {item.text}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        )
                                    })}
                                </div>
                            </div>

                            <div className="flex items-center justify-between border-t border-white/10 pt-6 text-xs text-gray-400">
                                <span>Copyright © 2026. All rights reserved.</span>
                                <span>Version 0.0.1</span>
                            </div>
                        </div>

                        <div className="flex flex-col justify-center p-6 sm:p-8 md:p-10 lg:p-12">
                            <div className="mb-8 lg:hidden">
                                <Link href="/" className="text-3xl font-extrabold tracking-tight">
                                    <span className="bg-gradient-to-r from-sky-500 via-blue-500 to-violet-600 bg-clip-text text-transparent">
                                        SimpleDesk
                                    </span>
                                </Link>
                                <p className="mt-3 max-w-md text-sm leading-6 text-gray-500">
                                    Access your helpdesk workspace and manage support requests in one place.
                                </p>
                            </div>

                            <div className="mb-8">
                                <h1 className="text-3xl font-semibold tracking-tight text-gray-900">
                                    {title}
                                </h1>

                                {description && (
                                    <p className="mt-3 max-w-md text-sm leading-6 text-gray-600">
                                        {description}
                                    </p>
                                )}
                            </div>

                            <div className="rounded-3xl border border-gray-200 bg-white">
                                <div className="p-1 sm:p-2">{children}</div>
                            </div>

                            <div className="mt-8 flex flex-col gap-2 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between lg:hidden">
                                <span>Copyright © 2026. All rights reserved.</span>
                                <span className="font-medium text-gray-600">Version 0.0.1</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    )
}

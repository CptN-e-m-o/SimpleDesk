import { Head, Link } from '@inertiajs/react'
import {
    Search,
    TicketPlus,
    FolderOpen,
    Clock3,
    BookOpen,
    CreditCard,
    Shield,
    Settings,
    ChevronRight,
    LifeBuoy,
} from 'lucide-react'

import UserLayout from '@/Layouts/UserLayout'

const quickActions = [
    {
        title: 'Submit a ticket',
        description: 'Tell us about your issue and our support team will help you.',
        href: '#',
        icon: TicketPlus,
    },
    {
        title: 'My requests',
        description: 'Track statuses, replies, and updates for your submitted tickets.',
        href: '#',
        icon: Clock3,
    },
    {
        title: 'Knowledge base',
        description: 'Browse articles, guides, and answers to common questions.',
        href: '#',
        icon: FolderOpen,
    },
]

const categories = [
    {
        title: 'Getting started',
        description: 'Basic setup, first steps, and onboarding questions.',
        href: '#',
        icon: BookOpen,
    },
    {
        title: 'Account & access',
        description: 'Login issues, password reset, and profile settings.',
        href: '#',
        icon: Shield,
    },
    {
        title: 'Billing',
        description: 'Invoices, payments, subscriptions, and pricing questions.',
        href: '#',
        icon: CreditCard,
    },
    {
        title: 'Technical issues',
        description: 'Troubleshooting bugs, errors, and unexpected behavior.',
        href: '#',
        icon: Settings,
    },
]

const popularArticles = [
    {
        title: 'How to create a new support request',
        href: '#',
    },
    {
        title: 'How to reset your password',
        href: '#',
    },
    {
        title: 'How to track your ticket status',
        href: '#',
    },
    {
        title: 'How to update your account information',
        href: '#',
    },
]

export default function Home() {
    return (
        <UserLayout>
            <Head title="Home" />

            <div className="space-y-8">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="grid gap-8 px-6 py-8 sm:px-8 sm:py-10 lg:grid-cols-[1.2fr_0.8fr] lg:px-10 lg:py-12">
                        <div className="flex flex-col justify-center">
                            <div className="mb-4 inline-flex w-fit items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                                <LifeBuoy className="h-3.5 w-3.5" />
                                Support center
                            </div>

                            <h1 className="max-w-2xl text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
                                How can we help you today?
                            </h1>

                            <p className="mt-4 max-w-2xl text-base leading-7 text-gray-600 sm:text-lg">
                                Search the knowledge base, create a support request, or check
                                updates on your existing tickets.
                            </p>

                            <form
                                action="#"
                                method="get"
                                className="mt-8 flex flex-col gap-3 sm:flex-row"
                            >
                                <div className="relative flex-1">
                                    <Search className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="text"
                                        name="search"
                                        placeholder="Search articles, topics, or issues..."
                                        className="h-14 w-full rounded-2xl border border-gray-200 bg-gray-50 pl-12 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    className="inline-flex h-14 items-center justify-center rounded-2xl bg-gray-900 px-6 text-sm font-medium text-white transition hover:bg-gray-800"
                                >
                                    Search
                                </button>

                                <Link
                                    href="#"
                                    className="inline-flex h-14 items-center justify-center rounded-2xl border border-gray-200 bg-white px-6 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                                >
                                    Submit ticket
                                </Link>
                            </form>

                            <div className="mt-5 flex flex-wrap gap-2">
                                {[
                                    'Password reset',
                                    'Billing',
                                    'Account access',
                                    'Email issues',
                                    'Integrations',
                                ].map((item) => (
                                    <Link
                                        key={item}
                                        href={`/knowledge-base?search=${encodeURIComponent(item)}`}
                                        className="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                                    >
                                        {item}
                                    </Link>
                                ))}
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div className="rounded-3xl border border-gray-200 bg-gray-50 p-6">
                                <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
                                    <TicketPlus className="h-6 w-6" />
                                </div>
                                <h2 className="text-lg font-semibold text-gray-900">
                                    Need direct help?
                                </h2>
                                <p className="mt-2 text-sm leading-6 text-gray-600">
                                    Create a ticket and describe your issue in detail.
                                </p>
                                <Link
                                    href="#"
                                    className="mt-5 inline-flex items-center gap-2 text-sm font-medium text-sky-700 transition hover:text-sky-800"
                                >
                                    Create request
                                    <ChevronRight className="h-4 w-4" />
                                </Link>
                            </div>

                            <div className="rounded-3xl border border-gray-200 bg-gray-900 p-6 text-white">
                                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10">
                                    <Clock3 className="h-6 w-6" />
                                </div>
                                <h2 className="text-lg font-semibold">Already have a ticket?</h2>
                                <p className="mt-2 text-sm leading-6 text-gray-300">
                                    Open your requests to see statuses, replies, and full history.
                                </p>
                                <Link
                                    href="#"
                                    className="mt-5 inline-flex items-center gap-2 text-sm font-medium text-sky-300 transition hover:text-sky-200"
                                >
                                    View my tickets
                                    <ChevronRight className="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-3">
                    {quickActions.map((item) => {
                        const Icon = item.icon

                        return (
                            <Link
                                key={item.title}
                                href={item.href}
                                className="group rounded-[24px] border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md"
                            >
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-700 transition group-hover:bg-sky-100 group-hover:text-sky-700">
                                    <Icon className="h-6 w-6" />
                                </div>

                                <h3 className="mt-5 text-lg font-semibold text-gray-900">
                                    {item.title}
                                </h3>

                                <p className="mt-2 text-sm leading-6 text-gray-600">
                                    {item.description}
                                </p>

                                <div className="mt-5 inline-flex items-center gap-2 text-sm font-medium text-sky-700">
                                    Open
                                    <ChevronRight className="h-4 w-4 transition group-hover:translate-x-0.5" />
                                </div>
                            </Link>
                        )
                    })}
                </section>

                <section className="grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
                    <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <div className="mb-6">
                            <h2 className="text-2xl font-semibold tracking-tight text-gray-900">
                                Popular categories
                            </h2>
                            <p className="mt-2 text-sm leading-6 text-gray-600">
                                Start with the area that best matches your issue.
                            </p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            {categories.map((item) => {
                                const Icon = item.icon

                                return (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        className="group rounded-3xl border border-gray-200 bg-gray-50 p-5 transition hover:border-sky-200 hover:bg-sky-50"
                                    >
                                        <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-gray-700 transition group-hover:text-sky-700">
                                            <Icon className="h-5 w-5" />
                                        </div>

                                        <h3 className="mt-4 text-base font-semibold text-gray-900">
                                            {item.title}
                                        </h3>

                                        <p className="mt-2 text-sm leading-6 text-gray-600">
                                            {item.description}
                                        </p>
                                    </Link>
                                )
                            })}
                        </div>
                    </div>

                    <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <div className="mb-6">
                            <h2 className="text-2xl font-semibold tracking-tight text-gray-900">
                                Popular articles
                            </h2>
                            <p className="mt-2 text-sm leading-6 text-gray-600">
                                The most common answers users look for first.
                            </p>
                        </div>

                        <div className="space-y-3">
                            {popularArticles.map((article) => (
                                <Link
                                    key={article.title}
                                    href={article.href}
                                    className="group flex items-start justify-between gap-4 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4 transition hover:border-sky-200 hover:bg-sky-50"
                                >
                                    <div className="min-w-0 text-sm font-medium text-gray-900 transition group-hover:text-sky-800">
                                        {article.title}
                                    </div>

                                    <ChevronRight className="mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-sky-700" />
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="rounded-[28px] border border-sky-200 bg-gradient-to-r from-sky-50 to-blue-50 p-6 shadow-sm sm:p-8">
                    <div className="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div className="max-w-2xl">
                            <h2 className="text-2xl font-semibold tracking-tight text-gray-900">
                                Still need help?
                            </h2>
                            <p className="mt-2 text-sm leading-6 text-gray-600 sm:text-base">
                                If you can’t find the answer in the knowledge base, send us a
                                support request and we’ll assist you directly.
                            </p>
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Link
                                href="#"
                                className="inline-flex h-12 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                            >
                                Browse articles
                            </Link>

                            <Link
                                href="#"
                                className="inline-flex h-12 items-center justify-center rounded-2xl bg-gray-900 px-5 text-sm font-medium text-white transition hover:bg-gray-800"
                            >
                                Create ticket
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </UserLayout>
    )
}

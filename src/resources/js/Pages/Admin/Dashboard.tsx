import AdminLayout from '@/Layouts/AdminLayout'
import { Head, Link } from '@inertiajs/react'
import {
    UserCog,
    ShieldCheck,
    Building2,
    UsersRound,
    ArrowRight,
} from 'lucide-react'
import { route } from 'ziggy-js'

export default function Index() {
    const staffItems = [
        {
            title: 'Agents',
            description: 'Manage support agents, permissions, and account access.',
            href: '#',
            icon: UserCog,
        },
        {
            title: 'Roles',
            description: 'Configure admin and agent roles for your help desk team.',
            href: route('admin.roles.index'),
            icon: ShieldCheck,
        },
        {
            title: 'Departments',
            description: 'Organize requests by department and assign ownership.',
            href: route('admin.departments.index'),
            icon: Building2,
        },
        {
            title: 'Teams',
            description: 'Group agents into teams for routing and collaboration.',
            href: route('admin.teams.index'),
            icon: UsersRound,
        },
    ]

    return (
        <AdminLayout title="Admin Panel">
            <Head title="Admin Panel" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Staff
                                </h2>
                                <p className="mt-1 text-sm text-gray-500">
                                    Core administration tools for managing your support team.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                            {staffItems.map((item) => {
                                const Icon = item.icon

                                return (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        className="group rounded-[26px] border border-gray-200 bg-gray-50 p-5 transition-all hover:-translate-y-0.5 hover:border-sky-200 hover:bg-white hover:shadow-md"
                                    >
                                        <div className="flex h-16 w-16 items-center justify-center rounded-3xl bg-sky-50 ring-1 ring-inset ring-sky-100 transition group-hover:bg-sky-100">
                                            <Icon className="h-8 w-8 text-sky-600" />
                                        </div>

                                        <div className="mt-5">
                                            <div className="flex items-center justify-between gap-3">
                                                <h3 className="text-lg font-semibold text-gray-900">
                                                    {item.title}
                                                </h3>

                                                <ArrowRight className="h-4 w-4 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-sky-600" />
                                            </div>

                                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                                {item.description}
                                            </p>
                                        </div>
                                    </Link>
                                )
                            })}
                        </div>
                    </div>
                </section>
            </div>
        </AdminLayout>
    )
}

import AdminLayout from '@/Layouts/AdminLayout'
import { usePermissions } from '@/hooks/usePermissions'
import { Head, Link } from '@inertiajs/react'
import {
    ArrowRight,
    Activity,
    Building2,
    CalendarClock,
    KeyRound,
    Mailbox,
    MailSearch,
    ShieldCheck,
    TextQuote,
    UserCog,
    UsersRound,
    Sparkles,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { route } from 'ziggy-js'

type DashboardItem = {
    title: string
    description: string
    icon: LucideIcon
    href?: string
    permissions?: string[]
}

type DashboardCardProps = {
    item: DashboardItem
}

function DashboardCard({ item }: DashboardCardProps) {
    const Icon = item.icon

    const content = (
        <>
            <div
                className={
                    item.href
                        ? 'flex h-16 w-16 items-center justify-center rounded-3xl bg-sky-50 ring-1 ring-inset ring-sky-100 transition group-hover:bg-sky-100'
                        : 'flex h-16 w-16 items-center justify-center rounded-3xl bg-gray-100 ring-1 ring-inset ring-gray-200'
                }
            >
                <Icon
                    className={
                        item.href
                            ? 'h-8 w-8 text-sky-600'
                            : 'h-8 w-8 text-gray-400'
                    }
                />
            </div>

            <div className="mt-5">
                <div className="flex items-center justify-between gap-3">
                    <h3
                        className={
                            item.href
                                ? 'text-lg font-semibold text-gray-900'
                                : 'text-lg font-semibold text-gray-600'
                        }
                    >
                        {item.title}
                    </h3>

                    {item.href ? (
                        <ArrowRight className="h-4 w-4 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-sky-600" />
                    ) : (
                        <span className="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-500">
                            Coming soon
                        </span>
                    )}
                </div>

                <p
                    className={
                        item.href
                            ? 'mt-2 text-sm leading-6 text-gray-500'
                            : 'mt-2 text-sm leading-6 text-gray-400'
                    }
                >
                    {item.description}
                </p>
            </div>
        </>
    )

    if (!item.href) {
        return (
            <div
                aria-disabled="true"
                className="cursor-not-allowed rounded-[26px] border border-gray-200 bg-gray-50 p-5"
            >
                {content}
            </div>
        )
    }

    return (
        <Link
            href={item.href}
            className="group rounded-[26px] border border-gray-200 bg-gray-50 p-5 transition-all hover:-translate-y-0.5 hover:border-sky-200 hover:bg-white hover:shadow-md"
        >
            {content}
        </Link>
    )
}

export default function Index() {
    const { canAny } = usePermissions()

    const staffItems: DashboardItem[] = [
        {
            title: 'Agents',
            description:
                'Manage support agents, permissions, and account access.',
            href: route('admin.agents.index'),
            icon: UserCog,
        },
        {
            title: 'Roles',
            description:
                'Configure admin and agent roles for your help desk team.',
            href: route('admin.roles.index'),
            icon: ShieldCheck,
        },
        {
            title: 'Departments',
            description:
                'Organize requests by department and assign ownership.',
            href: route('admin.departments.index'),
            icon: Building2,
        },
        {
            title: 'Teams',
            description:
                'Group agents into teams for routing and collaboration.',
            href: route('admin.teams.index'),
            icon: UsersRound,
        },
        {
            title: 'Work Schedules',
            description:
                'Configure agent working hours, assignments, and schedule exceptions.',
            href: route('admin.work-schedules.index'),
            permissions: ['admin.staff.work_schedules.view'],
            icon: CalendarClock,
        },
        {
            title: 'Agent Statuses',
            description: 'Manage agent availability, routing eligibility, and temporary statuses.',
            href: route('admin.agent-statuses.index'),
            permissions: ['admin.staff.agent_statuses.view'],
            icon: Activity,
        },
        {
            title: 'Skills',
            description: 'Build reusable ticket classification rules with ANY or ALL conditions.',
            href: route('admin.skills.index'),
            permissions: ['admin.staff.skills.view'],
            icon: Sparkles,
        },
    ]

    const emailItems: DashboardItem[] = [
        {
            title: 'Email Settings',
            description:
                'Configure support mailboxes and manage incoming and outgoing email.',
            href: route('admin.email.settings.index'),
            icon: Mailbox,
        },
        {
            title: 'Reply Parsing',
            description:
                'Define rules for removing quoted messages and unnecessary content from email replies.',
            href: route('admin.email.reply-parsing.index'),
            icon: TextQuote,
        },
        {
            title: 'Email Diagnostics',
            description:
                'Test mailbox connections and troubleshoot email delivery or retrieval issues.',
            href: route('admin.email.diagnostics.index'),
            permissions: [
                'admin.mail.view_diagnostics',
                'admin.mail.test_connections',
                'admin.mail.view',
            ],
            icon: MailSearch,
        },
        {
            title: 'OAuth Integrations',
            description:
                'Connect supported email providers securely using OAuth authentication.',
            href: route('admin.email.oauth-integrations.index'),
            permissions: [
                'admin.mail.view_oauth_integrations',
                'admin.mail.manage_oauth_integrations',
            ],
            icon: KeyRound,
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
                                    Core administration tools for managing your
                                    support team.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                            {staffItems
                                .filter(
                                    (item) =>
                                        !item.permissions ||
                                        canAny(item.permissions),
                                )
                                .map((item) => (
                                    <DashboardCard
                                        key={item.title}
                                        item={item}
                                    />
                                ))}
                        </div>
                    </div>
                </section>

                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                    Email
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Configure how SimpleDesk receives,
                                    processes, and sends support emails.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="p-6">
                        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                            {emailItems
                                .filter(
                                    (item) =>
                                        !item.permissions ||
                                        canAny(item.permissions),
                                )
                                .map((item) => (
                                    <DashboardCard
                                        key={item.title}
                                        item={item}
                                    />
                                ))}
                        </div>
                    </div>
                </section>
            </div>
        </AdminLayout>
    )
}

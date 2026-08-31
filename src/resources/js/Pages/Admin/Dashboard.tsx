import AdminLayout from '@/Layouts/AdminLayout'
import { usePermissions } from '@/hooks/usePermissions'
import { Head, Link } from '@inertiajs/react'
import {
    Activity,
    ArrowRight,
    BadgeCheck,
    BookOpen,
    Building2,
    Cable,
    CalendarClock,
    Clock3,
    FileText,
    Gauge,
    KeyRound,
    ListPlus,
    Mailbox,
    MailSearch,
    ScrollText,
    ServerCog,
    ShieldCheck,
    Sparkles,
    Tags,
    TextQuote,
    Timer,
    UserCog,
    UsersRound,
    Workflow,
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

function DashboardCard({
                           item,
                       }: DashboardCardProps) {
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
                <div className="flex items-start justify-between gap-3">
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
                        <ArrowRight className="mt-1 h-4 w-4 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-sky-600" />
                    ) : (
                        <span className="shrink-0 rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-500">
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
            href: route(
                'admin.agents.index',
            ),
            permissions: [
                'admin.staff.manage_agents',
            ],
            icon: UserCog,
        },
        {
            title: 'Roles',
            description:
                'Configure admin and agent roles for your help desk team.',
            href: route(
                'admin.roles.index',
            ),
            permissions: [
                'admin.staff.manage_roles',
            ],
            icon: ShieldCheck,
        },
        {
            title: 'Departments',
            description:
                'Organize requests by department and assign ownership.',
            href: route(
                'admin.departments.index',
            ),
            permissions: [
                'admin.staff.manage_departments',
            ],
            icon: Building2,
        },
        {
            title: 'Teams',
            description:
                'Group agents into teams for routing and collaboration.',
            href: route(
                'admin.teams.index',
            ),
            permissions: [
                'admin.staff.manage_teams',
            ],
            icon: UsersRound,
        },
        {
            title: 'Work Schedules',
            description:
                'Configure agent working hours, assignments, and schedule exceptions.',
            href: route(
                'admin.work-schedules.index',
            ),
            permissions: [
                'admin.staff.work_schedules.view',
            ],
            icon: CalendarClock,
        },
        {
            title: 'Agent Statuses',
            description:
                'Manage agent availability, routing eligibility, and temporary statuses.',
            href: route(
                'admin.agent-statuses.index',
            ),
            permissions: [
                'admin.staff.agent_statuses.view',
            ],
            icon: Activity,
        },
        {
            title: 'Skills',
            description:
                'Build reusable ticket classification rules with ANY or ALL conditions.',
            href: route(
                'admin.skills.index',
            ),
            permissions: [
                'admin.staff.skills.view',
            ],
            icon: Sparkles,
        },
    ]

    const manageItems: DashboardItem[] = [
        {
            title: 'Priorities',
            description:
                'Configure ticket urgency levels, visibility, ordering, and default behavior.',
            href: route(
                'admin.manage.priorities.index',
            ),
            permissions: [
                'admin.manage.priorities.view',
            ],
            icon: Gauge,
        },
        {
            title: 'Ticket Types',
            description:
                'Define the kinds of requests and issues handled by your help desk.',
            href: route(
                'admin.manage.ticket-types.index',
            ),
            permissions: [
                'admin.manage.ticket_types.view',
            ],
            icon: Tags,
        },
        {
            title: 'Ticket Fields',
            description:
                'Define reusable system and custom data fields for tickets.',
            icon: ListPlus,
        },
        {
            title: 'Forms',
            description:
                'Build structured ticket forms from reusable fields and sections.',
            icon: FileText,
        },
        {
            title: 'Help Topics',
            description:
                'Organize incoming requests and provide context-specific defaults.',
            icon: BookOpen,
        },
        {
            title: 'Business Hours',
            description:
                'Configure support calendars, working intervals, time zones, and exceptions.',
            icon: Clock3,
        },
        {
            title: 'SLA Plans',
            description:
                'Define response and resolution targets using business-time policies.',
            icon: Timer,
        },
        {
            title: 'Automations',
            description:
                'Run configurable actions when ticket events, conditions, or time triggers match.',
            icon: Workflow,
        },
        {
            title: 'Approval Workflows',
            description:
                'Configure multi-stage approval processes for tickets and business actions.',
            icon: BadgeCheck,
        },
    ]

    const emailItems: DashboardItem[] = [
        {
            title: 'Email Settings',
            description:
                'Configure support mailboxes and manage incoming and outgoing email.',
            href: route(
                'admin.email.settings.index',
            ),
            permissions: [
                'admin.mail.view',
            ],
            icon: Mailbox,
        },
        {
            title: 'Reply Parsing',
            description:
                'Define rules for removing quoted messages and unnecessary content from email replies.',
            href: route(
                'admin.email.reply-parsing.index',
            ),
            permissions: [
                'admin.mail.view_reply_parsing',
                'admin.mail.manage_reply_parsing',
            ],
            icon: TextQuote,
        },
        {
            title: 'Email Diagnostics',
            description:
                'Test mailbox connections and troubleshoot email delivery or retrieval issues.',
            href: route(
                'admin.email.diagnostics.index',
            ),
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
            href: route(
                'admin.email.oauth-integrations.index',
            ),
            permissions: [
                'admin.mail.view_oauth_integrations',
                'admin.mail.manage_oauth_integrations',
            ],
            icon: KeyRound,
        },
    ]

    const systemItems: DashboardItem[] = [
        {
            title: 'Drivers',
            description:
                'Configure runtime drivers used by SimpleDesk subsystems.',
            href: route(
                'admin.system.drivers.index',
            ),
            permissions: [
                'admin.settings.drivers.view',
            ],
            icon: ServerCog,
        },
        {
            title:
                'Infrastructure Connections',
            description:
                'Manage secure connections to shared infrastructure resources.',
            href: route(
                'admin.system.connections.index',
            ),
            permissions: [
                'admin.settings.infrastructure_connections.view',
            ],
            icon: Cable,
        },
        {
            title: 'System Audit',
            description:
                'Review security-sensitive and administrative system operations.',
            href: route(
                'admin.system.audit.index',
            ),
            permissions: [
                'admin.settings.system_audit.view',
            ],
            icon: ScrollText,
        },
    ]

    const visibleStaffItems =
        staffItems.filter(
            (item) =>
                !item.permissions ||
                canAny(
                    item.permissions,
                ),
        )

    const visibleManageItems =
        manageItems.filter(
            (item) =>
                !item.permissions ||
                canAny(
                    item.permissions,
                ),
        )

    const visibleEmailItems =
        emailItems.filter(
            (item) =>
                !item.permissions ||
                canAny(
                    item.permissions,
                ),
        )

    const visibleSystemItems =
        systemItems.filter(
            (item) =>
                !item.permissions ||
                canAny(
                    item.permissions,
                ),
        )

    const availableManageCount =
        visibleManageItems.filter(
            (item) =>
                Boolean(item.href),
        ).length

    const plannedManageCount =
        visibleManageItems.filter(
            (item) =>
                !item.href,
        ).length

    return (
        <AdminLayout title="Admin Panel">
            <Head title="Admin Panel" />

            <div className="space-y-6">
                {visibleStaffItems.length >
                    0 && (
                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Staff
                                    </h2>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Core
                                        administration
                                        tools for
                                        managing your
                                        support team.
                                    </p>
                                </div>
                            </div>

                            <div className="p-6">
                                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                                    {visibleStaffItems.map(
                                        (
                                            item,
                                        ) => (
                                            <DashboardCard
                                                key={
                                                    item.title
                                                }
                                                item={
                                                    item
                                                }
                                            />
                                        ),
                                    )}
                                </div>
                            </div>
                        </section>
                    )}

                {visibleManageItems.length >
                    0 && (
                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Manage
                                        </h2>

                                        <p className="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                                            Configure
                                            how tickets
                                            are
                                            collected,
                                            classified,
                                            prioritized,
                                            routed, and
                                            processed.
                                        </p>

                                        <div className="mt-3 flex flex-wrap gap-2">
                                        <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            {
                                                availableManageCount
                                            }{' '}
                                            available
                                        </span>

                                            {plannedManageCount >
                                                0 && (
                                                    <span className="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 ring-1 ring-inset ring-gray-200">
                                                {
                                                    plannedManageCount
                                                }{' '}
                                                        planned
                                            </span>
                                                )}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="p-6">
                                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                                    {visibleManageItems.map(
                                        (
                                            item,
                                        ) => (
                                            <DashboardCard
                                                key={
                                                    item.title
                                                }
                                                item={
                                                    item
                                                }
                                            />
                                        ),
                                    )}
                                </div>
                            </div>
                        </section>
                    )}

                {visibleEmailItems.length >
                    0 && (
                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                        Email
                                    </h2>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Configure how
                                        SimpleDesk
                                        receives,
                                        processes, and
                                        sends support
                                        emails.
                                    </p>
                                </div>
                            </div>

                            <div className="p-6">
                                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                                    {visibleEmailItems.map(
                                        (
                                            item,
                                        ) => (
                                            <DashboardCard
                                                key={
                                                    item.title
                                                }
                                                item={
                                                    item
                                                }
                                            />
                                        ),
                                    )}
                                </div>
                            </div>
                        </section>
                    )}

                {visibleSystemItems.length >
                    0 && (
                        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                                <div>
                                    <h2 className="text-xl font-semibold tracking-tight text-gray-900">
                                        System
                                    </h2>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Infrastructure,
                                        drivers,
                                        health, and
                                        audit controls.
                                    </p>
                                </div>
                            </div>

                            <div className="p-6">
                                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                                    {visibleSystemItems.map(
                                        (
                                            item,
                                        ) => (
                                            <DashboardCard
                                                key={
                                                    item.title
                                                }
                                                item={
                                                    item
                                                }
                                            />
                                        ),
                                    )}
                                </div>
                            </div>
                        </section>
                    )}
            </div>
        </AdminLayout>
    )
}

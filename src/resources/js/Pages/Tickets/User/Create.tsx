import { Head, Link, useForm } from '@inertiajs/react'
import {
    ChevronRight,
    CircleAlert,
    FileText,
    FolderOpen,
    Info,
    Paperclip,
    SendHorizonal,
    ShieldCheck,
    TicketPlus,
} from 'lucide-react'

import UserLayout from '@/Layouts/UserLayout'

type Category = {
    id: number
    name: string
    description?: string | null
}

type Props = {
    categories: Category[]
}

const priorityOptions = [
    {
        value: 'low',
        label: 'Low',
        description: 'General question or non-urgent issue.',
    },
    {
        value: 'medium',
        label: 'Medium',
        description: 'Issue affecting normal usage.',
    },
    {
        value: 'high',
        label: 'High',
        description: 'Major issue requiring quick attention.',
    },
    {
        value: 'urgent',
        label: 'Urgent',
        description: 'Critical problem blocking work completely.',
    },
]

const suggestions = [
    'Describe what happened and what you expected instead.',
    'Include error text, steps to reproduce, and affected page.',
    'Attach screenshots or files if they help explain the issue.',
]

export default function CreateTicket({ categories }: Props) {
    const form = useForm({
        subject: '',
        category_id: '',
        priority: 'medium',
        service: '',
        description: '',
        attachments: [] as File[],
    })

    function submit(e: React.FormEvent) {
        e.preventDefault()

        form.post('/tickets', {
            forceFormData: true,
        })
    }

    return (
        <UserLayout>
            <Head title="Create Ticket" />

            <div className="space-y-8">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="grid gap-8 px-6 py-8 sm:px-8 lg:grid-cols-[1.15fr_0.85fr] lg:px-10 lg:py-10">
                        <div>
                            <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700">
                                <TicketPlus className="h-3.5 w-3.5" />
                                Support request
                            </div>

                            <div className="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                                <Link href="/" className="transition hover:text-gray-900">
                                    Home
                                </Link>
                                <ChevronRight className="h-4 w-4" />
                                <span className="font-medium text-gray-900">Create ticket</span>
                            </div>

                            <h1 className="mt-4 text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl">
                                Create a support ticket
                            </h1>

                            <p className="mt-4 max-w-2xl text-base leading-7 text-gray-600 sm:text-lg">
                                Tell us what went wrong, and our team will review your request
                                as soon as possible.
                            </p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div className="rounded-3xl border border-sky-200 bg-sky-50 p-6">
                                <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-sky-700">
                                    <ShieldCheck className="h-6 w-6" />
                                </div>

                                <h2 className="text-lg font-semibold text-gray-900">
                                    Faster resolution
                                </h2>

                                <p className="mt-2 text-sm leading-6 text-gray-600">
                                    The more details you provide, the quicker an agent can help.
                                </p>
                            </div>

                            <div className="rounded-3xl border border-gray-200 bg-gray-900 p-6 text-white">
                                <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10">
                                    <FolderOpen className="h-6 w-6" />
                                </div>

                                <h2 className="text-lg font-semibold">Check articles first</h2>

                                <p className="mt-2 text-sm leading-6 text-gray-300">
                                    You may find a quick answer in the knowledge base before
                                    submitting a request.
                                </p>

                                <Link
                                    href="/knowledge-base"
                                    className="mt-5 inline-flex items-center gap-2 text-sm font-medium text-sky-300 transition hover:text-sky-200"
                                >
                                    Browse knowledge base
                                    <ChevronRight className="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-8 lg:grid-cols-[1fr_320px]">
                    <form
                        onSubmit={submit}
                        className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm sm:p-8"
                    >
                        <div className="mb-8">
                            <h2 className="text-2xl font-semibold tracking-tight text-gray-900">
                                Ticket details
                            </h2>
                            <p className="mt-2 text-sm leading-6 text-gray-600">
                                Fill out the form below and we’ll create a request for our
                                support team.
                            </p>
                        </div>

                        <div className="grid gap-6">
                            <div>
                                <label
                                    htmlFor="subject"
                                    className="mb-2 block text-sm font-medium text-gray-800"
                                >
                                    Subject <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    id="subject"
                                    type="text"
                                    value={form.data.subject}
                                    onChange={(e) => form.setData('subject', e.target.value)}
                                    placeholder="Briefly describe your issue"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                                />
                                {form.errors.subject && (
                                    <p className="mt-2 text-sm text-rose-600">
                                        {form.errors.subject}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <label
                                        htmlFor="category_id"
                                        className="mb-2 block text-sm font-medium text-gray-800"
                                    >
                                        Category <span className="text-rose-500">*</span>
                                    </label>
                                    <select
                                        id="category_id"
                                        value={form.data.category_id}
                                        onChange={(e) => form.setData('category_id', e.target.value)}
                                        className="h-12 w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-900 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                                    >
                                        <option value="">Select a category</option>
                                        {categories.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.category_id && (
                                        <p className="mt-2 text-sm text-rose-600">
                                            {form.errors.category_id}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="priority"
                                        className="mb-2 block text-sm font-medium text-gray-800"
                                    >
                                        Priority <span className="text-rose-500">*</span>
                                    </label>
                                    <select
                                        id="priority"
                                        value={form.data.priority}
                                        onChange={(e) => form.setData('priority', e.target.value)}
                                        className="h-12 w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-900 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                                    >
                                        {priorityOptions.map((priority) => (
                                            <option key={priority.value} value={priority.value}>
                                                {priority.label}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.priority && (
                                        <p className="mt-2 text-sm text-rose-600">
                                            {form.errors.priority}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div>
                                <label
                                    htmlFor="service"
                                    className="mb-2 block text-sm font-medium text-gray-800"
                                >
                                    Product / area
                                </label>
                                <input
                                    id="service"
                                    type="text"
                                    value={form.data.service}
                                    onChange={(e) => form.setData('service', e.target.value)}
                                    placeholder="For example: Billing, Dashboard, Login, API"
                                    className="h-12 w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                                />
                                {form.errors.service && (
                                    <p className="mt-2 text-sm text-rose-600">
                                        {form.errors.service}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="description"
                                    className="mb-2 block text-sm font-medium text-gray-800"
                                >
                                    Description <span className="text-rose-500">*</span>
                                </label>
                                <textarea
                                    id="description"
                                    rows={10}
                                    value={form.data.description}
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    placeholder="Describe the issue in detail. Include steps to reproduce, expected result, and actual result."
                                    className="w-full rounded-3xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
                                />
                                {form.errors.description && (
                                    <p className="mt-2 text-sm text-rose-600">
                                        {form.errors.description}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="attachments"
                                    className="mb-2 block text-sm font-medium text-gray-800"
                                >
                                    Attachments
                                </label>

                                <label
                                    htmlFor="attachments"
                                    className="flex cursor-pointer flex-col items-center justify-center rounded-3xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center transition hover:border-sky-300 hover:bg-sky-50"
                                >
                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-gray-700">
                                        <Paperclip className="h-6 w-6" />
                                    </div>

                                    <div className="mt-4 text-sm font-medium text-gray-900">
                                        Upload screenshots or files
                                    </div>

                                    <div className="mt-1 text-sm text-gray-500">
                                        PNG, JPG, PDF, DOC up to your configured limit
                                    </div>

                                    <input
                                        id="attachments"
                                        type="file"
                                        multiple
                                        className="hidden"
                                        onChange={(e) =>
                                            form.setData(
                                                'attachments',
                                                e.target.files ? Array.from(e.target.files) : [],
                                            )
                                        }
                                    />
                                </label>

                                {form.data.attachments.length > 0 && (
                                    <div className="mt-3 space-y-2">
                                        {form.data.attachments.map((file, index) => (
                                            <div
                                                key={`${file.name}-${index}`}
                                                className="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700"
                                            >
                                                <FileText className="h-4 w-4 text-gray-400" />
                                                <span className="truncate">{file.name}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {form.errors.attachments && (
                                    <p className="mt-2 text-sm text-rose-600">
                                        {form.errors.attachments}
                                    </p>
                                )}
                            </div>

                            <div className="rounded-3xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                                <div className="flex items-start gap-3">
                                    <Info className="mt-0.5 h-4 w-4 shrink-0" />
                                    <p>
                                        Avoid sharing passwords, payment card data, or other
                                        sensitive credentials in your ticket description.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                                <Link
                                    href="/knowledge-base"
                                    className="inline-flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                                >
                                    Search knowledge base
                                </Link>

                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="inline-flex items-center justify-center gap-2 rounded-2xl bg-gray-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <SendHorizonal className="h-4 w-4" />
                                    {form.processing ? 'Submitting...' : 'Submit ticket'}
                                </button>
                            </div>
                        </div>
                    </form>

                    <aside className="space-y-4">
                        <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Before you submit
                            </h3>

                            <div className="mt-4 space-y-3">
                                {suggestions.map((item) => (
                                    <div
                                        key={item}
                                        className="flex gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3"
                                    >
                                        <CircleAlert className="mt-0.5 h-4 w-4 shrink-0 text-sky-700" />
                                        <p className="text-sm leading-6 text-gray-600">{item}</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 className="text-lg font-semibold text-gray-900">
                                Priority guide
                            </h3>

                            <div className="mt-4 space-y-3">
                                {priorityOptions.map((item) => (
                                    <div
                                        key={item.value}
                                        className="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3"
                                    >
                                        <div className="text-sm font-medium text-gray-900">
                                            {item.label}
                                        </div>
                                        <div className="mt-1 text-sm leading-6 text-gray-600">
                                            {item.description}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </aside>
                </section>
            </div>
        </UserLayout>
    )
}

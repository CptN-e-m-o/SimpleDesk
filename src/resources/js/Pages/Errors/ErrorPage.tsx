import { Link } from '@inertiajs/react'
import { ShieldAlert, ArrowLeft, Home } from 'lucide-react'

type Props = {
    status: number
}

const messages: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Access denied',
        description: 'You do not have permission to perform this action.',
    },
    404: {
        title: 'Page not found',
        description: 'The page you are looking for does not exist or was moved.',
    },
    500: {
        title: 'Server error',
        description: 'Something went wrong on our side.',
    },
    503: {
        title: 'Service unavailable',
        description: 'SimpleDesk is temporarily unavailable.',
    },
}

export default function ErrorPage({ status }: Readonly<Props>) {
    const message = messages[status] ?? {
        title: 'Something went wrong',
        description: 'An unexpected error occurred.',
    }

    return (
        <div className="min-h-screen bg-slate-950 px-6 py-10 text-white">
            <div className="mx-auto flex min-h-[calc(100vh-5rem)] max-w-5xl items-center justify-center">
                <div className="w-full rounded-[2rem] border border-white/10 bg-white/[0.04] p-8 shadow-2xl shadow-black/30 backdrop-blur md:p-12">
                    <div className="mb-8 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-500/15 ring-1 ring-sky-400/20">
                        <ShieldAlert className="h-8 w-8 text-sky-300" />
                    </div>

                    <div className="mb-4 text-sm font-semibold uppercase tracking-[0.3em] text-sky-300">
                        Error {status}
                    </div>

                    <h1 className="max-w-2xl text-4xl font-bold tracking-tight md:text-6xl">
                        {message.title}
                    </h1>

                    <p className="mt-5 max-w-xl text-base leading-7 text-slate-300">
                        {message.description}
                    </p>

                    <div className="mt-9 flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            onClick={() => globalThis.history.back()}
                            className="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Go back
                        </button>

                        <Link
                            href="/"
                            className="inline-flex items-center justify-center gap-2 rounded-2xl bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-400"
                        >
                            <Home className="h-4 w-4" />
                            Home page
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    )
}

import {
    Check,
    CircleCheck,
    Inbox,
    Mail,
    Send,
} from 'lucide-react'

type Props = {
    readonly currentStep: 1 | 2 | 3 | 4
}

const steps = [
    {
        number: 1,
        title: 'Mailbox Information',
        description: 'Name, email address and ownership.',
        icon: Mail,
    },
    {
        number: 2,
        title: 'Incoming Email',
        description: 'IMAP connection and synchronization.',
        icon: Inbox,
    },
    {
        number: 3,
        title: 'Outgoing Email',
        description: 'SMTP connection and sender settings.',
        icon: Send,
    },
    {
        number: 4,
        title: 'Review',
        description: 'Connection tests and activation.',
        icon: CircleCheck,
    },
] as const

export default function SetupSteps({
                                       currentStep,
                                   }: Props) {
    return (
        <div className="grid gap-3 lg:grid-cols-4">
            {steps.map((step) => {
                const Icon = step.icon
                const current =
                    step.number === currentStep
                const completed =
                    step.number < currentStep

                return (
                    <div
                        key={step.number}
                        className={`relative flex min-w-0 items-start gap-3 rounded-2xl border px-4 py-4 ${
                            current
                                ? 'border-sky-200 bg-sky-50'
                                : completed
                                    ? 'border-emerald-200 bg-emerald-50/60'
                                    : 'border-gray-200 bg-white'
                        }`}
                    >
                        <div
                            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl ${
                                current
                                    ? 'bg-sky-600 text-white'
                                    : completed
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-gray-100 text-gray-500'
                            }`}
                        >
                            {completed ? (
                                <Check className="h-5 w-5" />
                            ) : (
                                <Icon className="h-5 w-5" />
                            )}
                        </div>

                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <span
                                    className={`text-xs font-semibold uppercase tracking-wide ${
                                        current
                                            ? 'text-sky-600'
                                            : completed
                                                ? 'text-emerald-600'
                                                : 'text-gray-400'
                                    }`}
                                >
                                    Step {step.number}
                                </span>

                                {current ? (
                                    <span className="inline-flex rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-sky-700 ring-1 ring-inset ring-sky-200">
                                        Current
                                    </span>
                                ) : null}
                            </div>

                            <p className="mt-1 text-sm font-semibold text-gray-900">
                                {step.title}
                            </p>

                            <p className="mt-1 text-xs leading-5 text-gray-500">
                                {step.description}
                            </p>
                        </div>
                    </div>
                )
            })}
        </div>
    )
}

import { router } from '@inertiajs/react'
import {
    ArchiveRestore,
    LoaderCircle,
    Trash2,
    TriangleAlert,
    X,
} from 'lucide-react'
import {
    useEffect,
    useState,
} from 'react'
import { route } from 'ziggy-js'
import { Mailbox } from './shared'

export type MailboxAction =
    | 'delete'
    | 'restore'
    | 'force-delete'

type Props = {
    readonly mailbox: Mailbox | null
    readonly action: MailboxAction | null
    readonly onClose: () => void
}

export default function DeleteMailboxModal({
                                               mailbox,
                                               action,
                                               onClose,
                                           }: Props) {
    const [processing, setProcessing] =
        useState(false)

    const [error, setError] =
        useState<string | null>(null)

    useEffect(() => {
        setError(null)
    }, [
        mailbox,
        action,
    ])

    if (
        mailbox === null
        || action === null
    ) {
        return null
    }

    const currentMailbox = mailbox
    const currentAction = action

    const isRestore =
        currentAction === 'restore'

    const isForceDelete =
        currentAction === 'force-delete'

    const title = isRestore
        ? 'Restore Mailbox'
        : isForceDelete
            ? 'Delete Mailbox Permanently'
            : 'Delete Mailbox'

    const description = isRestore
        ? 'The mailbox will return to the list in a disabled state.'
        : isForceDelete
            ? 'This action cannot be undone.'
            : 'The mailbox will remain visible and can be restored later.'

    const confirmLabel = isRestore
        ? 'Restore Mailbox'
        : isForceDelete
            ? 'Delete Permanently'
            : 'Delete Mailbox'

    function closeModal() {
        if (processing) {
            return
        }

        onClose()
    }

    function handleError(
        errors: Record<string, string>
    ) {
        const firstError = Object
            .values(errors)
            .find(
                (value) =>
                    typeof value === 'string'
            )

        setError(
            firstError
            ?? 'The requested action could not be completed.'
        )
    }

    function performAction() {
        setProcessing(true)
        setError(null)

        const options = {
            preserveScroll: true,

            onSuccess: () => {
                onClose()
            },

            onError: handleError,

            onFinish: () => {
                setProcessing(false)
            },
        }

        if (currentAction === 'restore') {
            router.post(
                route(
                    'admin.email.settings.mailboxes.restore',
                    currentMailbox.id,
                ),
                {},
                options,
            )

            return
        }

        if (
            currentAction === 'force-delete'
        ) {
            router.delete(
                route(
                    'admin.email.settings.mailboxes.force-destroy',
                    currentMailbox.id,
                ),
                options,
            )

            return
        }

        router.delete(
            route(
                'admin.email.settings.mailboxes.destroy',
                currentMailbox.id,
            ),
            options,
        )
    }

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="mailbox-action-title"
            className="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
        >
            <button
                type="button"
                aria-label="Close confirmation"
                disabled={processing}
                onClick={closeModal}
                className="absolute inset-0 bg-gray-950/40 backdrop-blur-[2px]"
            />

            <div className="relative z-10 w-full max-w-lg overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-2xl">
                <div className="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5">
                    <div className="flex items-start gap-4">
                        <div
                            className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ${
                                isRestore
                                    ? 'bg-emerald-50 text-emerald-600'
                                    : 'bg-rose-50 text-rose-600'
                            }`}
                        >
                            {isRestore ? (
                                <ArchiveRestore className="h-5 w-5" />
                            ) : (
                                <Trash2 className="h-5 w-5" />
                            )}
                        </div>

                        <div>
                            <h2
                                id="mailbox-action-title"
                                className="text-lg font-semibold text-gray-900"
                            >
                                {title}
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                {description}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        disabled={processing}
                        onClick={closeModal}
                        className="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-50"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <div className="space-y-5 px-6 py-6">
                    <div
                        className={`rounded-2xl border px-4 py-4 ${
                            isRestore
                                ? 'border-amber-200 bg-amber-50'
                                : 'border-rose-200 bg-rose-50'
                        }`}
                    >
                        <div className="flex items-start gap-3">
                            <TriangleAlert
                                className={`mt-0.5 h-5 w-5 shrink-0 ${
                                    isRestore
                                        ? 'text-amber-600'
                                        : 'text-rose-600'
                                }`}
                            />

                            <p
                                className={`text-sm leading-6 ${
                                    isRestore
                                        ? 'text-amber-800'
                                        : 'text-rose-800'
                                }`}
                            >
                                {isRestore
                                    ? 'The mailbox and all of its channels will remain disabled after restoration.'
                                    : isForceDelete
                                        ? 'Permanent deletion is only allowed when the mailbox contains no email history or related records.'
                                        : 'Configured channels will be disabled before the mailbox is soft-deleted.'}
                            </p>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
                        <p className="text-sm font-semibold text-gray-900">
                            {currentMailbox.name}
                        </p>

                        <p className="mt-1 break-all text-sm text-gray-500">
                            {currentMailbox.email_address}
                        </p>
                    </div>

                    {error !== null ? (
                        <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800">
                            {error}
                        </div>
                    ) : null}
                </div>

                <div className="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/70 px-6 py-5 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        disabled={processing}
                        onClick={closeModal}
                        className="inline-flex h-11 items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 disabled:opacity-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        disabled={processing}
                        onClick={performAction}
                        className={`inline-flex h-11 items-center justify-center gap-2 rounded-2xl px-5 text-sm font-medium text-white transition disabled:opacity-60 ${
                            isRestore
                                ? 'bg-emerald-600 hover:bg-emerald-700'
                                : 'bg-rose-600 hover:bg-rose-700'
                        }`}
                    >
                        {processing ? (
                            <LoaderCircle className="h-4 w-4 animate-spin" />
                        ) : isRestore ? (
                            <ArchiveRestore className="h-4 w-4" />
                        ) : (
                            <Trash2 className="h-4 w-4" />
                        )}

                        {processing
                            ? 'Processing...'
                            : confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    )
}

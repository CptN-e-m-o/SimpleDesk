import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/Components/ui/alert-dialog'
import {
    ArrowLeft,
    Beaker,
    Check,
    Code2,
    FileText,
    Info,
    Layers3,
    LoaderCircle,
    Save,
} from 'lucide-react'
import { Link, useForm } from '@inertiajs/react'
import axios, { AxiosError } from 'axios'
import { FormEvent, useState } from 'react'
import { route } from 'ziggy-js'

export type SelectOption = {
    value: string
    label: string
}

export type ReplyParsingRule = {
    id: number
    name: string
    pattern: string
    pattern_type: 'literal' | 'regex'
    content_type: 'plain_text' | 'html' | 'both'
    display_order: number
    is_active: boolean
    description: string | null
    is_deleted: boolean
    deleted_at: string | null
    created_at: string | null
    updated_at: string | null
}

type RuleFormData = {
    name: string
    pattern: string
    pattern_type: 'literal' | 'regex'
    content_type: 'plain_text' | 'html' | 'both'
    display_order: number
    is_active: boolean
    description: string
}

type PreviewResult = {
    original_content: string
    parsed_content: string
    removed_content: string
    matched: boolean
    matched_rule_id: number | null
    matched_rule_name: string | null
    match_offset: number | null
    pattern_type: string
    content_type: string
}

type ValidationPayload = {
    errors?: Record<string, string[]>
    message?: string
}

type RuleFormProps = {
    rule?: ReplyParsingRule
    patternTypes: SelectOption[]
    contentTypes: SelectOption[]
}

function FieldError({ message }: { message?: string }) {
    return message ? <p className="mt-2 text-sm text-rose-600">{message}</p> : null
}

export function RuleForm({
                             rule,
                             patternTypes,
                             contentTypes,
                         }: RuleFormProps) {
    const editing = rule !== undefined

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
    } = useForm<RuleFormData>({
        name: rule?.name ?? '',
        pattern: rule?.pattern ?? '',
        pattern_type:
            rule?.pattern_type ?? 'literal',
        content_type:
            rule?.content_type ?? 'both',
        display_order:
            rule?.display_order ?? 100,
        is_active:
            rule?.is_active ?? true,
        description:
            rule?.description ?? '',
    })

    const [
        testContent,
        setTestContent,
    ] = useState('')

    const [
        testContentType,
        setTestContentType,
    ] = useState<'plain_text' | 'html'>(
        'plain_text',
    )

    const [
        preview,
        setPreview,
    ] = useState<PreviewResult | null>(null)

    const [
        previewError,
        setPreviewError,
    ] = useState<string | null>(null)

    const [
        testing,
        setTesting,
    ] = useState(false)

    function submit(
        event: FormEvent<HTMLFormElement>,
    ) {
        event.preventDefault()

        const options = {
            preserveScroll: true,
        }

        if (editing) {
            put(
                route(
                    'admin.email.reply-parsing.update',
                    rule.id,
                ),
                options,
            )

            return
        }

        post(
            route(
                'admin.email.reply-parsing.store',
            ),
            options,
        )
    }

    async function testRule() {
        setTesting(true)
        setPreview(null)
        setPreviewError(null)

        try {
            const response = await axios.post<{
                data: PreviewResult
            }>(
                route(
                    'admin.email.reply-parsing.preview',
                ),
                {
                    ...data,
                    test_content:
                    testContent,
                    test_content_type:
                    testContentType,
                },
            )

            setPreview(
                response.data.data,
            )
        } catch (error: unknown) {
            const axiosError =
                error as AxiosError<ValidationPayload>

            const validationErrors =
                axiosError.response?.data.errors

            const firstError =
                validationErrors
                    ? Object.values(
                        validationErrors,
                    )
                        .flat()
                        .find(
                            (message) =>
                                typeof message ===
                                'string',
                        )
                    : null

            setPreviewError(
                firstError ??
                axiosError.response?.data
                    .message ??
                'The rule could not be tested.',
            )
        } finally {
            setTesting(false)
        }
    }

    const inputClass =
        'mt-2 h-11 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100'

    const textareaClass =
        'mt-2 w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100'

    return (
        <form
            onSubmit={submit}
            className="space-y-6"
        >
            <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 ring-1 ring-inset ring-sky-100">
                                    <Code2 className="h-5 w-5" />
                                </div>

                                <div>
                                    <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                        {editing
                                            ? 'Edit Parsing Rule'
                                            : 'Create Parsing Rule'}
                                    </h1>

                                    <p className="mt-1 text-sm text-gray-500">
                                        Define where quoted email
                                        history begins.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Link
                            href={route(
                                'admin.email.reply-parsing.index',
                            )}
                            className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Rules
                        </Link>
                    </div>
                </div>

                <div className="space-y-8 px-6 py-6">
                    <div>
                        <div className="mb-4">
                            <h2 className="text-base font-semibold text-gray-900">
                                General Information
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                Enter a recognizable name and
                                configure the rule priority.
                            </p>
                        </div>

                        <div className="grid gap-5 md:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="rule-name"
                                    className="text-sm font-medium text-gray-700"
                                >
                                    Rule Name
                                </label>

                                <input
                                    id="rule-name"
                                    type="text"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData(
                                            'name',
                                            event.target
                                                .value,
                                        )
                                    }
                                    placeholder="Standard Outlook quoted reply"
                                    className={inputClass}
                                />

                                <FieldError
                                    message={errors.name}
                                />
                            </div>

                            <div>
                                <label
                                    htmlFor="display-order"
                                    className="text-sm font-medium text-gray-700"
                                >
                                    Display Order
                                </label>

                                <input
                                    id="display-order"
                                    type="number"
                                    min={0}
                                    value={
                                        data.display_order
                                    }
                                    onChange={(event) =>
                                        setData(
                                            'display_order',
                                            Number(
                                                event
                                                    .target
                                                    .value,
                                            ),
                                        )
                                    }
                                    className={inputClass}
                                />

                                <p className="mt-2 text-xs leading-5 text-gray-400">
                                    Lower values are displayed
                                    first and have higher priority
                                    when matches start at the same
                                    position.
                                </p>

                                <FieldError
                                    message={
                                        errors.display_order
                                    }
                                />
                            </div>
                        </div>
                    </div>

                    <div className="border-t border-gray-100 pt-7">
                        <div className="mb-4">
                            <h2 className="text-base font-semibold text-gray-900">
                                Pattern Type
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                Choose how the pattern should be
                                interpreted.
                            </p>
                        </div>

                        <div className="grid gap-3 md:grid-cols-2">
                            {patternTypes.map(
                                (option) => {
                                    const selected =
                                        data.pattern_type ===
                                        option.value

                                    return (
                                        <button
                                            key={
                                                option.value
                                            }
                                            type="button"
                                            onClick={() =>
                                                setData(
                                                    'pattern_type',
                                                    option.value as RuleFormData['pattern_type'],
                                                )
                                            }
                                            className={`relative flex cursor-pointer items-start gap-4 rounded-[22px] border px-5 py-4 text-left transition ${
                                                selected
                                                    ? 'border-sky-300 bg-sky-50 ring-4 ring-sky-100'
                                                    : 'border-gray-200 bg-white hover:border-sky-200 hover:bg-sky-50/40'
                                            }`}
                                        >
                                            <div
                                                className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ${
                                                    selected
                                                        ? 'bg-sky-600 text-white'
                                                        : 'bg-gray-100 text-gray-500'
                                                }`}
                                            >
                                                {option.value ===
                                                'regex' ? (
                                                    <Code2 className="h-5 w-5" />
                                                ) : (
                                                    <FileText className="h-5 w-5" />
                                                )}
                                            </div>

                                            <div className="min-w-0 pr-8">
                                                <p className="text-sm font-semibold text-gray-900">
                                                    {
                                                        option.label
                                                    }
                                                </p>

                                                <p className="mt-1 text-xs leading-5 text-gray-500">
                                                    {option.value ===
                                                    'regex'
                                                        ? 'Use a PCRE regular expression for flexible matching.'
                                                        : 'Search for the exact text without interpreting special characters.'}
                                                </p>
                                            </div>

                                            <span
                                                className={`absolute right-4 top-4 flex h-5 w-5 items-center justify-center rounded-full border ${
                                                    selected
                                                        ? 'border-sky-600 bg-sky-600 text-white'
                                                        : 'border-gray-300 bg-white'
                                                }`}
                                            >
                                                {selected ? (
                                                    <Check className="h-3.5 w-3.5" />
                                                ) : null}
                                            </span>
                                        </button>
                                    )
                                },
                            )}
                        </div>

                        <FieldError
                            message={
                                errors.pattern_type
                            }
                        />
                    </div>

                    <div className="border-t border-gray-100 pt-7">
                        <div className="mb-4">
                            <h2 className="text-base font-semibold text-gray-900">
                                Content Type
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                Select which email content this
                                rule can process.
                            </p>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-3">
                            {contentTypes.map(
                                (option) => {
                                    const selected =
                                        data.content_type ===
                                        option.value

                                    return (
                                        <button
                                            key={
                                                option.value
                                            }
                                            type="button"
                                            onClick={() =>
                                                setData(
                                                    'content_type',
                                                    option.value as RuleFormData['content_type'],
                                                )
                                            }
                                            className={`relative cursor-pointer rounded-[22px] border px-5 py-4 text-left transition ${
                                                selected
                                                    ? 'border-indigo-300 bg-indigo-50 ring-4 ring-indigo-100'
                                                    : 'border-gray-200 bg-white hover:border-indigo-200 hover:bg-indigo-50/40'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div
                                                    className={`flex h-10 w-10 items-center justify-center rounded-2xl ${
                                                        selected
                                                            ? 'bg-indigo-600 text-white'
                                                            : 'bg-gray-100 text-gray-500'
                                                    }`}
                                                >
                                                    <Layers3 className="h-5 w-5" />
                                                </div>

                                                <span
                                                    className={`flex h-5 w-5 items-center justify-center rounded-full border ${
                                                        selected
                                                            ? 'border-indigo-600 bg-indigo-600 text-white'
                                                            : 'border-gray-300 bg-white'
                                                    }`}
                                                >
                                                    {selected ? (
                                                        <Check className="h-3.5 w-3.5" />
                                                    ) : null}
                                                </span>
                                            </div>

                                            <p className="mt-4 text-sm font-semibold text-gray-900">
                                                {
                                                    option.label
                                                }
                                            </p>

                                            <p className="mt-1 text-xs leading-5 text-gray-500">
                                                {option.value ===
                                                'plain_text'
                                                    ? 'Apply only to the plain-text email body.'
                                                    : option.value ===
                                                    'html'
                                                        ? 'Apply only to the HTML email body.'
                                                        : 'Apply to both plain-text and HTML content.'}
                                            </p>
                                        </button>
                                    )
                                },
                            )}
                        </div>

                        <FieldError
                            message={
                                errors.content_type
                            }
                        />
                    </div>

                    <div className="border-t border-gray-100 pt-7">
                        <label
                            htmlFor="rule-pattern"
                            className="text-sm font-medium text-gray-700"
                        >
                            Pattern
                        </label>

                        <textarea
                            id="rule-pattern"
                            value={data.pattern}
                            onChange={(event) =>
                                setData(
                                    'pattern',
                                    event.target.value,
                                )
                            }
                            rows={6}
                            spellCheck={false}
                            placeholder={
                                data.pattern_type ===
                                'regex'
                                    ? 'On\\s.+wrote:'
                                    : '-----Original Message-----'
                            }
                            className={`${textareaClass} min-h-40 resize-y font-mono`}
                        />

                        <FieldError
                            message={errors.pattern}
                        />

                        <div className="mt-4 flex gap-3 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-4 text-sm leading-6 text-sky-800">
                            <Info className="mt-0.5 h-5 w-5 shrink-0" />

                            <p>
                                {data.pattern_type ===
                                'regex'
                                    ? 'Regular expressions use PCRE syntax. The expression is stored without external delimiters.'
                                    : 'Literal mode searches for this exact text. Special regular-expression characters have no special meaning.'}{' '}
                                The beginning of a match marks
                                the start of quoted content that
                                will be removed.
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-5 border-t border-gray-100 pt-7 lg:grid-cols-[minmax(0,1fr)_340px]">
                        <div>
                            <label
                                htmlFor="rule-description"
                                className="text-sm font-medium text-gray-700"
                            >
                                Description
                            </label>

                            <textarea
                                id="rule-description"
                                value={
                                    data.description
                                }
                                onChange={(event) =>
                                    setData(
                                        'description',
                                        event.target
                                            .value,
                                    )
                                }
                                rows={5}
                                placeholder="Explain which email client or quoted reply format this rule matches."
                                className={`${textareaClass} resize-y`}
                            />

                            <FieldError
                                message={
                                    errors.description
                                }
                            />
                        </div>

                        <div>
                            <p className="text-sm font-medium text-gray-700">
                                Rule Status
                            </p>

                            <button
                                type="button"
                                onClick={() =>
                                    setData(
                                        'is_active',
                                        !data.is_active,
                                    )
                                }
                                className={`mt-2 flex w-full cursor-pointer items-center justify-between gap-4 rounded-[22px] border px-5 py-4 text-left transition ${
                                    data.is_active
                                        ? 'border-emerald-200 bg-emerald-50'
                                        : 'border-gray-200 bg-gray-50'
                                }`}
                            >
                                <div>
                                    <p
                                        className={`text-sm font-semibold ${
                                            data.is_active
                                                ? 'text-emerald-900'
                                                : 'text-gray-700'
                                        }`}
                                    >
                                        {data.is_active
                                            ? 'Active'
                                            : 'Disabled'}
                                    </p>

                                    <p
                                        className={`mt-1 text-xs leading-5 ${
                                            data.is_active
                                                ? 'text-emerald-700'
                                                : 'text-gray-500'
                                        }`}
                                    >
                                        {data.is_active
                                            ? 'The rule can be used while parsing incoming replies.'
                                            : 'The rule is saved but will not be applied.'}
                                    </p>
                                </div>

                                <span
                                    className={`relative h-7 w-12 shrink-0 rounded-full transition ${
                                        data.is_active
                                            ? 'bg-emerald-600'
                                            : 'bg-gray-300'
                                    }`}
                                >
                                    <span
                                        className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow-sm transition ${
                                            data.is_active
                                                ? 'left-6'
                                                : 'left-1'
                                        }`}
                                    />
                                </span>
                            </button>

                            <FieldError
                                message={
                                    errors.is_active
                                }
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5">
                    <div className="flex items-start gap-3">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 ring-1 ring-inset ring-violet-100">
                            <Beaker className="h-5 w-5" />
                        </div>

                        <div>
                            <h2 className="text-lg font-semibold text-gray-900">
                                Test Rule
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                Preview how the current rule
                                processes an example email reply.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="space-y-5 px-6 py-6">
                    <div>
                        <p className="text-sm font-medium text-gray-700">
                            Test Content Type
                        </p>

                        <div className="mt-2 inline-flex w-full rounded-2xl border border-gray-200 bg-gray-100 p-1 sm:w-auto">
                            {(
                                [
                                    {
                                        value: 'plain_text',
                                        label: 'Plain Text',
                                    },
                                    {
                                        value: 'html',
                                        label: 'HTML',
                                    },
                                ] as const
                            ).map((option) => {
                                const selected =
                                    testContentType ===
                                    option.value

                                return (
                                    <button
                                        key={
                                            option.value
                                        }
                                        type="button"
                                        onClick={() =>
                                            setTestContentType(
                                                option.value,
                                            )
                                        }
                                        className={`h-9 flex-1 cursor-pointer rounded-xl px-5 text-sm font-medium transition sm:flex-none ${
                                            selected
                                                ? 'bg-white text-gray-900 shadow-sm ring-1 ring-inset ring-gray-200'
                                                : 'text-gray-500 hover:text-gray-800'
                                        }`}
                                    >
                                        {option.label}
                                    </button>
                                )
                            })}
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="test-content"
                            className="text-sm font-medium text-gray-700"
                        >
                            Example Email Reply
                        </label>

                        <textarea
                            id="test-content"
                            value={testContent}
                            onChange={(event) =>
                                setTestContent(
                                    event.target.value,
                                )
                            }
                            rows={9}
                            spellCheck={false}
                            placeholder="Paste an email reply to test the current rule..."
                            className={`${textareaClass} min-h-56 resize-y font-mono`}
                        />
                    </div>

                    <div className="flex justify-end">
                        <button
                            type="button"
                            onClick={testRule}
                            disabled={
                                testing ||
                                data.pattern.trim() ===
                                '' ||
                                testContent.trim() === ''
                            }
                            className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-violet-200 bg-violet-50 px-5 text-sm font-medium text-violet-700 transition hover:bg-violet-100 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {testing ? (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            ) : (
                                <Beaker className="h-4 w-4" />
                            )}

                            {testing
                                ? 'Testing...'
                                : 'Test Rule'}
                        </button>
                    </div>

                    {previewError ? (
                        <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm leading-6 text-rose-700">
                            {previewError}
                        </div>
                    ) : null}

                    {preview ? (
                        <div className="overflow-hidden rounded-[24px] border border-gray-200">
                            <div className="border-b border-gray-200 bg-gray-50 px-5 py-4">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span
                                            className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${
                                                preview.matched
                                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                                    : 'bg-gray-100 text-gray-600 ring-gray-200'
                                            }`}
                                        >
                                            {preview.matched
                                                ? 'Match Found'
                                                : 'No Match'}
                                        </span>

                                        <span className="text-sm text-gray-500">
                                            Match offset:{' '}
                                            {preview.match_offset ??
                                                '—'}
                                        </span>
                                    </div>

                                    <span className="text-xs font-medium uppercase tracking-wide text-gray-400">
                                        {
                                            preview.pattern_type
                                        }{' '}
                                        ·{' '}
                                        {
                                            preview.content_type
                                        }
                                    </span>
                                </div>
                            </div>

                            <div className="grid gap-4 bg-white p-5 lg:grid-cols-2">
                                <ResultPanel
                                    title="Parsed Content"
                                    content={
                                        preview.parsed_content
                                    }
                                />

                                <ResultPanel
                                    title="Removed Content"
                                    content={
                                        preview.removed_content
                                    }
                                />
                            </div>
                        </div>
                    ) : null}
                </div>
            </section>

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    href={route(
                        'admin.email.reply-parsing.index',
                    )}
                    className="inline-flex h-11 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-100"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    disabled={processing}
                    className="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-5 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {processing ? (
                        <LoaderCircle className="h-4 w-4 animate-spin" />
                    ) : (
                        <Save className="h-4 w-4" />
                    )}

                    {processing
                        ? 'Saving...'
                        : editing
                            ? 'Save Changes'
                            : 'Create Rule'}
                </button>
            </div>
        </form>
    )
}

function ResultPanel({ title, content }: { title: string; content: string }) {
    return <div><h3 className="text-sm font-semibold text-gray-700">{title}</h3><pre className="mt-2 min-h-28 whitespace-pre-wrap break-words rounded-2xl border border-gray-200 bg-white p-4 text-xs text-gray-700">{content || '—'}</pre></div>
}

export { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle }

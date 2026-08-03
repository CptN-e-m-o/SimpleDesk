import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    ReplyParsingRule,
} from './shared'
import AdminLayout from '@/Layouts/AdminLayout'
import { usePermissions } from '@/hooks/usePermissions'
import { Head, Link, router } from '@inertiajs/react'
import { Activity, ArrowUpDown, Ban, FileText, LoaderCircle, Pencil, Plus, RotateCcw, Search, ShieldAlert, Trash2 } from 'lucide-react'
import { useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { route } from 'ziggy-js'

type Summary = { total: number; active: number; disabled: number; deleted: number }
type Action = { type: 'delete' | 'restore' | 'force-delete'; rule: ReplyParsingRule }
type SortField = 'name' | 'pattern_type' | 'content_type' | 'display_order' | 'status'

export default function Index({ rules, summary }: { readonly rules: ReplyParsingRule[]; readonly summary: Summary }) {
    const { can } = usePermissions()
    const canManage = can('admin.mail.manage_reply_parsing')
    const [search, setSearch] = useState('')
    const [sortField, setSortField] = useState<SortField>('display_order')
    const [descending, setDescending] = useState(false)
    const [action, setAction] = useState<Action | null>(null)
    const [processing, setProcessing] = useState(false)

    const visibleRules = useMemo(() => {
        const needle = search.trim().toLowerCase()
        const filtered = rules.filter((rule) => !needle || [rule.name, rule.pattern, rule.pattern_type, rule.content_type, rule.is_deleted ? 'deleted' : rule.is_active ? 'active' : 'disabled'].join(' ').toLowerCase().includes(needle))
        return [...filtered].sort((left, right) => {
            if (left.is_deleted !== right.is_deleted) return left.is_deleted ? 1 : -1
            const status = (rule: ReplyParsingRule) => rule.is_deleted ? 'deleted' : rule.is_active ? 'active' : 'disabled'
            const a = sortField === 'status' ? status(left) : left[sortField]
            const b = sortField === 'status' ? status(right) : right[sortField]
            const result = typeof a === 'number' && typeof b === 'number' ? a - b : String(a).localeCompare(String(b))
            return descending ? -result : result
        })
    }, [rules, search, sortField, descending])

    function sort(field: SortField) {
        if (field === sortField) setDescending((value) => !value)
        else { setSortField(field); setDescending(false) }
    }

    function performAction() {
        if (!action) return
        setProcessing(true)
        const options = { preserveScroll: true, onFinish: () => { setProcessing(false); setAction(null) } }
        if (action.type === 'restore') router.post(route('admin.email.reply-parsing.restore', action.rule.id), {}, options)
        else router.delete(route(action.type === 'delete' ? 'admin.email.reply-parsing.destroy' : 'admin.email.reply-parsing.force-destroy', action.rule.id), options)
    }

    const status = (rule: ReplyParsingRule) => rule.is_deleted ? 'Deleted' : rule.is_active ? 'Active' : 'Disabled'
    const statusClass = (rule: ReplyParsingRule) => rule.is_deleted ? 'bg-rose-50 text-rose-700 ring-rose-200' : rule.is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-gray-100 text-gray-600 ring-gray-200'
    const header = (label: string, field: SortField) => <button type="button" onClick={() => sort(field)} className="inline-flex items-center gap-1">{label}<ArrowUpDown className="h-3.5 w-3.5" /></button>

    const isRestore =
        action?.type === 'restore'

    const isForceDelete =
        action?.type === 'force-delete'

    const dialogTitle = isRestore
        ? 'Restore Parsing Rule'
        : isForceDelete
            ? 'Delete Rule Permanently'
            : 'Delete Parsing Rule'

    const dialogDescription = isRestore
        ? 'The parsing rule will be restored in a disabled state.'
        : isForceDelete
            ? 'The parsing rule will be removed permanently. This action cannot be undone.'
            : 'The parsing rule will be soft deleted and will stop applying immediately.'

    const dialogActionLabel = isRestore
        ? 'Restore Rule'
        : isForceDelete
            ? 'Delete Permanently'
            : 'Delete Rule'

    return <AdminLayout title="Reply Parsing"><Head title="Reply Parsing" /><div className="space-y-6">
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <div className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5"><div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"><div><h1 className="text-xl font-semibold text-gray-900">Reply Parsing</h1><p className="mt-1 text-sm text-gray-500">Manage rules that identify the beginning of quoted email history.</p></div><div className="flex flex-col gap-3 sm:flex-row"><div className="relative"><Search className="absolute left-3 top-3.5 h-4 w-4 text-gray-400" /><input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search rules..." className="h-11 w-full rounded-2xl border border-gray-200 pl-10 pr-4 text-sm outline-none focus:border-sky-300 focus:ring-4 focus:ring-sky-100 sm:w-80" /></div>{canManage ? <Link href={route('admin.email.reply-parsing.create')} className="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white"><Plus className="h-4 w-4" />New Rule</Link> : null}</div></div></div>
            <div className="grid gap-3 border-b border-gray-100 bg-gray-50/70 p-4 sm:grid-cols-2 xl:grid-cols-4"><Summary label="Total rules" value={summary.total} icon={<FileText className="h-5 w-5" />} /><Summary label="Active" value={summary.active} icon={<Activity className="h-5 w-5" />} /><Summary label="Disabled" value={summary.disabled} icon={<Ban className="h-5 w-5" />} /><Summary label="Deleted" value={summary.deleted} icon={<Trash2 className="h-5 w-5" />} /></div>
            <div className="p-6">{visibleRules.length ? <><div className="hidden overflow-x-auto rounded-[24px] border border-gray-200 xl:block"><table className="min-w-full divide-y divide-gray-200"><thead className="bg-gray-50"><tr>{[['Name','name'],['Pattern Type','pattern_type'],['Content Type','content_type'],['Display Order','display_order'],['Status','status']].map(([label, field]) => <th key={field} className="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">{header(label, field as SortField)}</th>)}<th className="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th></tr></thead><tbody className="divide-y divide-gray-100">{visibleRules.map((rule) => <tr key={rule.id} className={rule.is_deleted ? 'bg-rose-50/40' : 'hover:bg-sky-50/30'}><td className="px-5 py-4"><p className="font-semibold text-gray-900">{rule.name}</p><code className="mt-1 block max-w-md truncate text-xs text-gray-500" title={rule.pattern}>{rule.pattern}</code></td><td className="px-5 py-4 text-sm text-gray-600">{rule.pattern_type === 'regex' ? 'Regular expression' : 'Literal'}</td><td className="px-5 py-4 text-sm text-gray-600">{rule.content_type.replace(/_/g, ' ')}</td><td className="px-5 py-4 text-sm text-gray-600">{rule.display_order}</td><td className="px-5 py-4"><span className={`rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${statusClass(rule)}`}>{status(rule)}</span></td><td className="px-5 py-4"><div className="flex justify-end gap-2">{canManage && !rule.is_deleted ? <><Link href={route('admin.email.reply-parsing.edit', rule.id)} className="rounded-xl border border-gray-200 p-2 text-gray-600 hover:text-sky-700"><Pencil className="h-4 w-4" /></Link><button onClick={() => setAction({ type: 'delete', rule })} className="rounded-xl border border-gray-200 p-2 text-gray-600 hover:text-rose-700"><Trash2 className="h-4 w-4" /></button></> : null}{canManage && rule.is_deleted ? <><button onClick={() => setAction({ type: 'restore', rule })} className="rounded-xl border border-emerald-200 p-2 text-emerald-700"><RotateCcw className="h-4 w-4" /></button><button onClick={() => setAction({ type: 'force-delete', rule })} className="rounded-xl border border-rose-200 p-2 text-rose-700"><ShieldAlert className="h-4 w-4" /></button></> : null}</div></td></tr>)}</tbody></table></div>
            <div className="grid gap-4 xl:hidden">{visibleRules.map((rule) => <article key={rule.id} className={`rounded-[24px] border p-5 ${rule.is_deleted ? 'border-rose-200 bg-rose-50/40' : 'border-gray-200 bg-gray-50'}`}><div className="flex justify-between gap-3"><div className="min-w-0"><h2 className="font-semibold text-gray-900">{rule.name}</h2><code className="mt-2 block truncate text-xs text-gray-500">{rule.pattern}</code></div><span className={`h-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${statusClass(rule)}`}>{status(rule)}</span></div><dl className="mt-4 grid grid-cols-3 gap-2 text-sm"><div><dt className="text-xs text-gray-400">Pattern</dt><dd>{rule.pattern_type}</dd></div><div><dt className="text-xs text-gray-400">Content</dt><dd>{rule.content_type.replace(/_/g, ' ')}</dd></div><div><dt className="text-xs text-gray-400">Order</dt><dd>{rule.display_order}</dd></div></dl>{canManage ? <div className="mt-4 flex gap-2">{rule.is_deleted ? <><button onClick={() => setAction({ type: 'restore', rule })} className="flex-1 rounded-2xl border border-emerald-200 py-2 text-sm text-emerald-700">Restore</button><button onClick={() => setAction({ type: 'force-delete', rule })} className="flex-1 rounded-2xl border border-rose-200 py-2 text-sm text-rose-700">Delete Permanently</button></> : <><Link href={route('admin.email.reply-parsing.edit', rule.id)} className="flex-1 rounded-2xl border border-gray-200 bg-white py-2 text-center text-sm text-gray-700">Edit</Link><button onClick={() => setAction({ type: 'delete', rule })} className="rounded-2xl border border-rose-200 px-4 text-rose-700"><Trash2 className="h-4 w-4" /></button></>}</div> : null}</article>)}</div></> : <div className="flex flex-col items-center justify-center rounded-[24px] border border-dashed border-gray-300 px-6 py-14 text-center">
                <div className="flex h-14 w-14 items-center justify-center rounded-3xl bg-gray-100 text-gray-400">
                    <FileText className="h-6 w-6" />
                </div>

                <h2 className="mt-4 text-base font-semibold text-gray-900">
                    No parsing rules found
                </h2>

                <p className="mt-2 max-w-md text-sm leading-6 text-gray-500">
                    Create a parsing rule to identify where quoted
                    email history begins.
                </p>

                {canManage ? (
                    <Link
                        href={route(
                            'admin.email.reply-parsing.create',
                        )}
                        className="mt-5 inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700"
                    >
                        <Plus className="h-4 w-4" />
                        Create First Rule
                    </Link>
                ) : null}
            </div>}</div>
        </section>
        <AlertDialog
            open={action !== null}
            onOpenChange={(open) => {
                if (
                    !open &&
                    !processing
                ) {
                    setAction(null)
                }
            }}
        >
            <AlertDialogContent className="w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-[28px] border border-gray-200 bg-white p-0 shadow-2xl">
                <div
                    className={`border-b px-6 py-5 ${
                        isRestore
                            ? 'border-emerald-100 bg-gradient-to-r from-emerald-50 to-white'
                            : 'border-rose-100 bg-gradient-to-r from-rose-50 to-white'
                    }`}
                >
                    <AlertDialogHeader>
                        <div className="flex items-start gap-4">
                            <div
                                className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1 ring-inset ${
                                    isRestore
                                        ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
                                        : 'bg-rose-100 text-rose-700 ring-rose-200'
                                }`}
                            >
                                {isRestore ? (
                                    <RotateCcw className="h-5 w-5" />
                                ) : isForceDelete ? (
                                    <ShieldAlert className="h-5 w-5" />
                                ) : (
                                    <Trash2 className="h-5 w-5" />
                                )}
                            </div>

                            <div className="min-w-0 text-left">
                                <AlertDialogTitle className="text-lg font-semibold tracking-tight text-gray-900">
                                    {dialogTitle}
                                </AlertDialogTitle>

                                <AlertDialogDescription className="mt-1 text-sm leading-6 text-gray-500">
                                    {dialogDescription}
                                </AlertDialogDescription>
                            </div>
                        </div>
                    </AlertDialogHeader>
                </div>

                <div className="space-y-4 px-6 py-6">
                    {action !== null ? (
                        <div className="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
                            <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Parsing rule
                            </p>

                            <p className="mt-2 break-words text-sm font-semibold text-gray-900">
                                {action.rule.name}
                            </p>

                            <code className="mt-2 block max-h-24 overflow-auto whitespace-pre-wrap break-all rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs leading-5 text-gray-600">
                                {action.rule.pattern}
                            </code>
                        </div>
                    ) : null}

                    <div
                        className={`rounded-2xl border px-4 py-4 text-sm leading-6 ${
                            isRestore
                                ? 'border-amber-200 bg-amber-50 text-amber-800'
                                : 'border-rose-200 bg-rose-50 text-rose-800'
                        }`}
                    >
                        {isRestore
                            ? 'After restoration, the rule will remain disabled. Review it before activating it again.'
                            : isForceDelete
                                ? 'All information associated with this parsing rule will be permanently removed.'
                                : 'The rule will remain available in the list and can be restored later.'}
                    </div>
                </div>

                <AlertDialogFooter className="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/70 px-6 py-5 sm:flex-row sm:justify-end">
                    <AlertDialogCancel
                        disabled={processing}
                        className="mt-0 inline-flex h-11 cursor-pointer items-center justify-center rounded-2xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Cancel
                    </AlertDialogCancel>

                    <AlertDialogAction
                        onClick={(event) => {
                            event.preventDefault()
                            performAction()
                        }}
                        disabled={processing}
                        className={`inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl px-5 text-sm font-medium text-white transition disabled:cursor-not-allowed disabled:opacity-60 ${
                            isRestore
                                ? 'bg-emerald-600 hover:bg-emerald-700'
                                : 'bg-rose-600 hover:bg-rose-700'
                        }`}
                    >
                        {processing ? (
                            <LoaderCircle className="h-4 w-4 animate-spin" />
                        ) : isRestore ? (
                            <RotateCcw className="h-4 w-4" />
                        ) : isForceDelete ? (
                            <ShieldAlert className="h-4 w-4" />
                        ) : (
                            <Trash2 className="h-4 w-4" />
                        )}

                        {processing
                            ? 'Processing...'
                            : dialogActionLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div></AdminLayout>
}

function Summary({ label, value, icon }: { label: string; value: number; icon: ReactNode }) {
    return <div className="rounded-2xl border border-gray-200 bg-white p-4"><div className="flex items-center justify-between text-gray-500"><span className="text-sm">{label}</span>{icon}</div><p className="mt-2 text-2xl font-semibold text-gray-900">{value}</p></div>
}

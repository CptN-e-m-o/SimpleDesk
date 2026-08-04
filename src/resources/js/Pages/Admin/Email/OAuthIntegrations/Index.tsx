import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog'
import AdminLayout from '@/Layouts/AdminLayout'
import { usePermissions } from '@/hooks/usePermissions'
import { Head, Link, router } from '@inertiajs/react'
import axios, { AxiosError } from 'axios'
import { KeyRound, LoaderCircle, Pencil, Plus, RefreshCw, RotateCcw, Search, ShieldAlert, Trash2, Unplug } from 'lucide-react'
import { useMemo, useState } from 'react'
import { route } from 'ziggy-js'
import type { OAuthIntegration } from './shared'

type Action = 'delete' | 'restore' | 'force' | 'disconnect'
type TestResponse = { data: { successful: boolean; message: string } }

export default function Index({ integrations }: { integrations: OAuthIntegration[] }) {
    const { can, canAny } = usePermissions()
    const canManage = can('admin.mail.manage_oauth_integrations')
    const canConnect = canAny(['admin.mail.connect_oauth_accounts', 'admin.mail.manage_oauth_integrations'])
    const canTest = can('admin.mail.test_connections')
    const [search, setSearch] = useState('')
    const [pending, setPending] = useState<{ action: Action; integration: OAuthIntegration } | null>(null)
    const [busyId, setBusyId] = useState<number | null>(null)
    const [message, setMessage] = useState<Record<number, string>>({})
    const filtered = useMemo(() => { const needle = search.trim().toLowerCase(); return integrations.filter((item) => !needle || `${item.name} ${item.provider} ${item.connected_email ?? ''}`.toLowerCase().includes(needle)) }, [integrations, search])
    const summary = { total: integrations.length, connected: integrations.filter((i) => i.connected && !i.deleted_at).length, attention: integrations.filter((i) => !i.deleted_at && (i.health_status === 'failed' || i.last_error_code)).length, disabled: integrations.filter((i) => !i.deleted_at && !i.is_active).length }

    async function runTest(item: OAuthIntegration) {
        setBusyId(item.id)
        try { const response = await axios.post<TestResponse>(route('admin.email.oauth-integrations.test', item.id)); setMessage((old) => ({ ...old, [item.id]: response.data.data.message })); router.reload() }
        catch (error: unknown) { const axiosError = error as AxiosError<{ message?: string }>; setMessage((old) => ({ ...old, [item.id]: axiosError.response?.data.message ?? 'Connection test failed safely.' })) }
        finally { setBusyId(null) }
    }

    function perform() {
        if (!pending) return
        setBusyId(pending.integration.id)
        const options = { preserveScroll: true, onFinish: () => { setBusyId(null); setPending(null) } }
        if (pending.action === 'restore') router.post(route('admin.email.oauth-integrations.restore', pending.integration.id), {}, options)
        else if (pending.action === 'disconnect') router.post(route('admin.email.oauth-integrations.disconnect', pending.integration.id), {}, options)
        else router.delete(route(pending.action === 'force' ? 'admin.email.oauth-integrations.force-destroy' : 'admin.email.oauth-integrations.destroy', pending.integration.id), options)
    }

    return <AdminLayout title="OAuth Integrations"><Head title="OAuth Integrations" /><div className="space-y-6">
        <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
            <header className="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-5"><div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"><div><h1 className="text-xl font-semibold text-gray-900">OAuth Integrations</h1><p className="mt-1 text-sm text-gray-500">Connect Google Workspace and Microsoft 365 accounts to existing IMAP/SMTP channels.</p></div><div className="flex gap-3"><div className="relative"><Search className="absolute left-3 top-3.5 h-4 w-4 text-gray-400" /><input value={search} onChange={(e) => setSearch(e.target.value)} className="h-11 rounded-2xl border border-gray-200 pl-10 pr-4 text-sm outline-none focus:ring-4 focus:ring-sky-100" placeholder="Search integrations..." /></div>{canManage ? <Link href={route('admin.email.oauth-integrations.create')} className="inline-flex h-11 items-center gap-2 rounded-2xl bg-sky-600 px-4 text-sm font-medium text-white"><Plus className="h-4 w-4" />Create Integration</Link> : null}</div></div></header>
            <div className="grid gap-3 border-b border-gray-100 bg-gray-50/70 p-4 sm:grid-cols-2 xl:grid-cols-4">{Object.entries(summary).map(([key, value]) => <div key={key} className="rounded-2xl border border-gray-200 bg-white p-4"><p className="text-xs font-semibold uppercase tracking-wide text-gray-400">{key}</p><p className="mt-2 text-2xl font-semibold text-gray-900">{value}</p></div>)}</div>
            <div className="p-6">{filtered.length === 0 ? <div className="rounded-[24px] border border-dashed border-gray-300 py-14 text-center"><KeyRound className="mx-auto h-8 w-8 text-gray-400" /><h2 className="mt-4 font-semibold text-gray-900">No OAuth integrations found</h2><p className="mt-2 text-sm text-gray-500">Create an integration before connecting a mailbox account.</p></div> : <div className="grid gap-4">{filtered.map((item) => <article key={item.id} className={`rounded-[24px] border p-5 ${item.deleted_at ? 'border-rose-200 bg-rose-50/40' : 'border-gray-200 bg-white'}`}><div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"><div className="min-w-0"><div className="flex flex-wrap items-center gap-2"><h2 className="font-semibold text-gray-900">{item.name}</h2><Badge item={item} /></div><p className="mt-1 text-sm text-gray-500">{item.provider === 'google' ? 'Google Workspace / Gmail' : 'Microsoft 365 / Outlook'} · {item.connected_email ?? 'No connected account'}</p><p className="mt-1 text-xs text-gray-400">Token expiry: {formatDate(item.token_expires_at)} · Last check: {formatDate(item.last_checked_at)}</p>{message[item.id] ? <p className="mt-2 text-sm text-sky-700">{message[item.id]}</p> : null}</div><div className="flex flex-wrap gap-2">{!item.deleted_at && canManage ? <Link href={route('admin.email.oauth-integrations.edit', item.id)} className="action"><Pencil className="h-4 w-4" />Edit</Link> : null}{!item.deleted_at && canConnect && !item.connected ? <a href={route('admin.email.oauth-integrations.authorize', item.id)} className="action"><KeyRound className="h-4 w-4" />Connect Account</a> : null}{!item.deleted_at && canConnect && item.connected ? <><button onClick={() => router.post(route('admin.email.oauth-integrations.refresh', item.id), {}, { preserveScroll: true })} className="action"><RefreshCw className="h-4 w-4" />Refresh Token</button><button onClick={() => setPending({ action: 'disconnect', integration: item })} className="action"><Unplug className="h-4 w-4" />Disconnect</button></> : null}{!item.deleted_at && canTest && item.connected ? <button disabled={busyId === item.id} onClick={() => runTest(item)} className="action">{busyId === item.id ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <ShieldAlert className="h-4 w-4" />}Test Connection</button> : null}{!item.deleted_at && canManage ? <button onClick={() => setPending({ action: 'delete', integration: item })} className="action text-rose-700"><Trash2 className="h-4 w-4" />Delete</button> : null}{item.deleted_at && canManage ? <><button onClick={() => setPending({ action: 'restore', integration: item })} className="action text-emerald-700"><RotateCcw className="h-4 w-4" />Restore</button><button onClick={() => setPending({ action: 'force', integration: item })} className="action text-rose-700"><Trash2 className="h-4 w-4" />Delete Permanently</button></> : null}</div></div></article>)}</div>}</div>
        </section>
        <AlertDialog open={pending !== null} onOpenChange={(open) => { if (!open && busyId === null) setPending(null) }}><AlertDialogContent className="rounded-[28px]"><AlertDialogHeader><AlertDialogTitle>{pending?.action === 'disconnect' ? 'Disconnect OAuth Account' : pending?.action === 'restore' ? 'Restore Integration' : 'Delete OAuth Integration'}</AlertDialogTitle><AlertDialogDescription>{pending?.action === 'disconnect' ? 'Local tokens will be cleared and linked OAuth channels disabled.' : pending?.action === 'restore' ? 'The integration will be restored disabled.' : 'This action will not delete mail history.'}</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel>Cancel</AlertDialogCancel><AlertDialogAction onClick={perform}>{busyId !== null ? 'Working…' : 'Continue'}</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
    </div></AdminLayout>
}

function Badge({ item }: { item: OAuthIntegration }) { const label = item.deleted_at ? 'Deleted' : !item.is_active ? 'Disabled' : item.connected ? item.health_status === 'failed' ? 'Reauthorization Required' : 'Connected' : item.has_client_secret ? 'Ready to Connect' : 'Not Configured'; const color = item.deleted_at || item.health_status === 'failed' ? 'bg-rose-50 text-rose-700' : item.connected ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600'; return <span className={`rounded-full px-3 py-1 text-xs font-semibold ${color}`}>{label}</span> }
function formatDate(value: string | null) { return value ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Never' }

import { Head, Link, router } from '@inertiajs/react'
import axios from 'axios'
import { Activity, Plus, Radio } from 'lucide-react'
import { route } from 'ziggy-js'
import { useState } from 'react'

import { Button } from '@/Components/ui/button'
import { usePermissions } from '@/hooks/usePermissions'
import AdminLayout from '@/Layouts/AdminLayout'

type Configuration = { id: number; name: string; driver: string; is_enabled: boolean; archived_at: string | null; latest_health?: { status: string; latency_ms: number; message: string } | null }
type Props = { ownership: { mode: string }; effective_connection: string; deployment_target: { connection: string; driver: string | null; available: boolean; externally_delivering?: boolean }; configurations: { data: Configuration[] }; definitions: Array<{ type: string; name: string; available: boolean; unavailable_reason?: string | null }>; active_configuration: Configuration | null }

export default function Index({ ownership, effective_connection, deployment_target, configurations, definitions, active_configuration }: Props) {
    const { can } = usePermissions()
    const [health, setHealth] = useState<Record<number, { status: string; latency_ms: number }>>({})
    const [testing, setTesting] = useState<number | null>(null)
    const post = (name: string, id?: number) => router.post(route(name, id ? { configuration: id } : undefined), {}, { preserveScroll: true })
    const toggle = (item: Configuration) => router.patch(route('admin.system.broadcasting.enabled', { configuration: item.id }), { is_enabled: !item.is_enabled }, { preserveScroll: true })
    const archive = (item: Configuration) => { if (window.confirm(`Archive ${item.name}?`)) router.delete(route('admin.system.broadcasting.destroy', { configuration: item.id }), { preserveScroll: true }) }
    const restore = (item: Configuration) => router.post(route('admin.system.broadcasting.restore', { id: item.id }), {}, { preserveScroll: true })
    const permanentlyDelete = (item: Configuration) => { if (window.confirm(`Permanently delete ${item.name}? This cannot be undone.`)) router.delete(route('admin.system.broadcasting.force-delete', { id: item.id }), { preserveScroll: true }) }
    const test = async (id: number) => {
        setTesting(id)
        try {
            const response = await axios.post(route('admin.system.broadcasting.test', { configuration: id }))
            setHealth((current) => ({ ...current, [id]: response.data }))
        } finally {
            setTesting(null)
        }
    }
    return <AdminLayout title="Real-time"><Head title="Real-time" /><div className="space-y-6">
        <header className="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm"><div className="flex items-center justify-between gap-4"><div className="flex items-center gap-3"><Radio className="h-7 w-7 text-sky-600" /><div><h1 className="text-xl font-semibold">Real-time</h1><p className="text-sm text-gray-500">Manage Laravel broadcaster ownership and outbound event delivery.</p></div></div>{can('admin.settings.broadcasting.create') ? <Button asChild><Link href={route('admin.system.broadcasting.create')}><Plus />New profile</Link></Button> : null}</div></header>
        <section className="grid gap-4 md:grid-cols-2"><div className="rounded-2xl border bg-white p-5"><p className="text-xs uppercase text-gray-500">Runtime ownership</p><p className="mt-2 text-lg font-semibold capitalize">{ownership.mode}</p><p className="text-sm text-gray-500">Effective connection: {effective_connection}</p>{active_configuration ? <p className="mt-2 text-sm">Active: {active_configuration.name}</p> : null}</div><div className="rounded-2xl border bg-white p-5"><p className="text-xs uppercase text-gray-500">Deployment target</p><p className="mt-2 font-semibold">{deployment_target.connection}</p><p className="text-sm text-gray-500">{deployment_target.driver ?? 'Invalid'}{deployment_target.externally_delivering === false ? ' · intentionally no external delivery' : ''}</p>{ownership.mode === 'managed' && can('admin.settings.broadcasting.activate') ? <Button className="mt-3" variant="outline" onClick={() => post('admin.system.broadcasting.activate-deployment')}>Return to deployment</Button> : null}</div></section>
        <section className="rounded-[28px] border bg-white p-6"><h2 className="font-semibold">Providers</h2><div className="mt-3 flex flex-wrap gap-2">{definitions.map((definition) => <span key={definition.type} title={definition.unavailable_reason ?? undefined} className={`rounded-full px-3 py-1 text-xs ${definition.available ? 'bg-sky-50 text-sky-700' : 'bg-gray-100 text-gray-500'}`}>{definition.name}{definition.available ? '' : ' · unavailable'}</span>)}</div></section>
        <section className="rounded-[28px] border bg-white p-6"><h2 className="font-semibold">Configurations</h2><div className="mt-4 divide-y">{configurations.data.map((item) => { const result = health[item.id] ?? item.latest_health; const active = active_configuration?.id === item.id; const unhealthy = result && result.status !== 'healthy'; return <div key={item.id} className="flex flex-wrap items-center justify-between gap-3 py-4"><div><p className="font-medium">{item.name}{active ? ' · Active' : ''}</p><p className="text-sm text-gray-500">{item.driver} · {item.is_enabled ? 'Enabled' : 'Disabled'}{result ? ` · ${result.status} (${result.latency_ms} ms)` : ''}</p></div><div className="relative z-20 flex flex-wrap gap-2">{can('admin.settings.broadcasting.test') ? <Button disabled={testing === item.id} variant="outline" onClick={() => void test(item.id)}><Activity />{testing === item.id ? 'Testing…' : 'Test'}</Button> : null}{can('admin.settings.broadcasting.activate') && item.is_enabled && !item.archived_at && !active ? <Button onClick={() => post('admin.system.broadcasting.activate', item.id)}>Activate</Button> : null}{can('admin.settings.broadcasting.force_activate') && unhealthy && item.is_enabled && !item.archived_at && !active ? <Button variant="destructive" onClick={() => post('admin.system.broadcasting.force-activate', item.id)}>Force activate</Button> : null}{!active && !item.archived_at && can('admin.settings.broadcasting.update') ? <Button variant="outline" onClick={() => toggle(item)}>{item.is_enabled ? 'Disable' : 'Enable'}</Button> : null}{!active && !item.archived_at && can('admin.settings.broadcasting.update') ? <Button variant="outline" asChild><Link href={route('admin.system.broadcasting.edit', { configuration: item.id })}>Edit</Link></Button> : null}{!active && !item.archived_at && can('admin.settings.broadcasting.archive') ? <Button variant="outline" onClick={() => archive(item)}>Archive</Button> : null}{item.archived_at && can('admin.settings.broadcasting.archive') ? <Button variant="outline" onClick={() => restore(item)}>Restore</Button> : null}{item.archived_at && can('admin.settings.broadcasting.delete') ? <Button variant="destructive" onClick={() => permanentlyDelete(item)}>Delete permanently</Button> : null}</div></div> })}{configurations.data.length === 0 ? <p className="py-8 text-center text-sm text-gray-500">No Real-time profiles yet.</p> : null}</div></section>
    </div></AdminLayout>
}

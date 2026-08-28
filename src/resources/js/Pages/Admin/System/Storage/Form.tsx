import { type FormEvent } from 'react'
import { Head, Link, useForm } from '@inertiajs/react'
import { ArrowLeft, Database, HardDrive, Info, LockKeyhole } from 'lucide-react'
import { route } from 'ziggy-js'

import InputError from '@/Components/InputError'
import { Button } from '@/Components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select'
import AdminLayout from '@/Layouts/AdminLayout'

type Definition = { driver: string; label: string; available: boolean; requires_infrastructure: boolean; infrastructure_type: string | null; message: string | null }
type Connection = { id: number; name: string; type: string; is_enabled: boolean; deleted_at: string | null }
type Configuration = { id: number; name: string; driver: string; infrastructure_connection_id: number | null; configuration: { prefix?: string }; is_enabled: boolean }
type Props = { configuration: Configuration | null; definitions: Definition[]; connections: Connection[] }
type FormData = { name: string; driver: string; infrastructure_connection_id: number | null; configuration: { prefix?: string }; is_enabled: boolean }

export default function Form({ configuration, definitions, connections }: Props) {
    const editing = configuration !== null
    const form = useForm<FormData>({ name: configuration?.name ?? '', driver: configuration?.driver ?? definitions.find((item) => item.available)?.driver ?? 'local', infrastructure_connection_id: configuration?.infrastructure_connection_id ?? null, configuration: configuration?.configuration ?? {}, is_enabled: configuration?.is_enabled ?? true })
    const definition = definitions.find((item) => item.driver === form.data.driver)
    const allowed = connections.filter((item) => item.type === definition?.infrastructure_type || item.id === configuration?.infrastructure_connection_id)

    const submit = (event: FormEvent) => {
        event.preventDefault()
        form.transform((data) => ({ ...data, infrastructure_connection_id: data.driver === 'local' ? null : data.infrastructure_connection_id, configuration: data.driver === 'local' || !data.configuration.prefix ? {} : { prefix: data.configuration.prefix } }))
        if (editing) form.put(route('admin.system.storage.update', { configuration: configuration.id }))
        else form.post(route('admin.system.storage.store'))
    }

    return (
        <AdminLayout title={editing ? 'Edit Storage profile' : 'Create Storage profile'}>
            <Head title={editing ? 'Edit Storage profile' : 'Create Storage profile'} />
            <div className="space-y-6">
                <header className="rounded-[28px] border border-gray-200 bg-gradient-to-r from-violet-50 to-white p-6 shadow-sm">
                    <div className="flex items-start justify-between gap-4"><div className="flex gap-4"><span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100"><HardDrive className="h-6 w-6 text-violet-700" /></span><div><h1 className="text-xl font-semibold text-gray-900">{editing ? 'Edit Storage profile' : 'Create Storage profile'}</h1><p className="mt-1 text-sm text-gray-500">Configure private application storage without changing the active runtime.</p></div></div><Button variant="outline" asChild><Link href={route('admin.system.storage.index')}><ArrowLeft className="mr-2 h-4 w-4" />Back</Link></Button></div>
                </header>
                <form onSubmit={submit} className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div className="space-y-6 rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm">
                        <div><label className="text-sm font-semibold text-gray-800">Name</label><input value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="mt-2 h-11 w-full rounded-xl border border-gray-200 px-3 text-sm" placeholder="Production object storage" /><InputError className="mt-2" message={form.errors.name} /></div>
                        <div><label className="text-sm font-semibold text-gray-800">Storage driver</label><Select disabled={editing} value={form.data.driver} onValueChange={(driver) => form.setData({ ...form.data, driver, infrastructure_connection_id: null, configuration: { prefix: '' } })}><SelectTrigger className="mt-2 h-11"><SelectValue /></SelectTrigger><SelectContent>{definitions.map((item) => <SelectItem key={item.driver} value={item.driver} disabled={!item.available}>{item.label}{item.available ? '' : ' · unavailable'}</SelectItem>)}</SelectContent></Select>{editing ? <p className="mt-2 flex gap-2 text-xs text-gray-500"><LockKeyhole className="h-4 w-4" />Driver is immutable after creation.</p> : null}<InputError className="mt-2" message={form.errors.driver} /></div>
                        {definition?.requires_infrastructure ? <><div><label className="text-sm font-semibold text-gray-800">Infrastructure Connection</label><Select value={form.data.infrastructure_connection_id ? String(form.data.infrastructure_connection_id) : ''} onValueChange={(value) => form.setData('infrastructure_connection_id', Number(value))}><SelectTrigger className="mt-2 h-11"><SelectValue placeholder="Select connection" /></SelectTrigger><SelectContent>{allowed.map((item) => <SelectItem key={item.id} value={String(item.id)} disabled={item.type !== definition.infrastructure_type || !item.is_enabled || Boolean(item.deleted_at)}>{item.name}{item.deleted_at ? ' · archived' : !item.is_enabled ? ' · disabled' : ''}</SelectItem>)}</SelectContent></Select><InputError className="mt-2" message={form.errors.infrastructure_connection_id} /></div><div><label className="text-sm font-semibold text-gray-800">Object prefix</label><input value={form.data.configuration.prefix ?? ''} onChange={(event) => form.setData('configuration', { prefix: event.target.value })} className="mt-2 h-11 w-full rounded-xl border border-gray-200 px-3 text-sm" placeholder="simpledesk" /><p className="mt-2 text-xs text-gray-500">Optional relative namespace inside the selected bucket.</p></div></> : <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4"><p className="flex items-center gap-2 text-sm font-semibold text-emerald-900"><Database className="h-4 w-4" />Private application storage</p><p className="mt-1 text-sm text-emerald-800">Local profiles always use the application-owned storage/app/private root. Arbitrary paths are not accepted.</p></div>}
                        <button type="button" onClick={() => form.setData('is_enabled', !form.data.is_enabled)} className="flex w-full items-center justify-between rounded-2xl border border-gray-200 p-4 text-left"><span><span className="block text-sm font-semibold">Enabled</span><span className="mt-1 block text-xs text-gray-500">Only enabled profiles can be activated.</span></span><span className={`h-6 w-11 rounded-full p-0.5 ${form.data.is_enabled ? 'bg-violet-600' : 'bg-gray-300'}`}><span className={`block h-5 w-5 rounded-full bg-white transition ${form.data.is_enabled ? 'translate-x-5' : ''}`} /></span></button>
                        <div className="flex justify-end gap-3"><Button variant="outline" asChild><Link href={route('admin.system.storage.index')}>Cancel</Link></Button><Button disabled={form.processing || !definition?.available}>{form.processing ? 'Saving…' : 'Save profile'}</Button></div>
                    </div>
                    <aside className="space-y-4"><div className="rounded-[28px] border border-amber-200 bg-amber-50 p-5"><p className="flex items-center gap-2 font-semibold text-amber-950"><Info className="h-5 w-5" />Control-plane boundary</p><div className="mt-3 space-y-3 text-sm leading-6 text-amber-900"><p>Saving does not activate the profile.</p><p>Credentials remain encrypted in Infrastructure Connections.</p><p>Switching profiles does not copy, move, delete, or synchronize objects.</p><p>Existing Mail storage keeps its concrete disk identity and is not controlled by this runtime.</p></div></div></aside>
                </form>
            </div>
        </AdminLayout>
    )
}

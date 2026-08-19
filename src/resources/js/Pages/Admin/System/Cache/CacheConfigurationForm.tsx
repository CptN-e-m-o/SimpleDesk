import type { CacheConfiguration, CacheDefinition, InfrastructureOption } from './cacheTypes'
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'

export default function CacheConfigurationForm({ definitions, redisConnections, configuration }: { definitions: CacheDefinition[]; redisConnections: InfrastructureOption[]; configuration?: CacheConfiguration }) {
    const form = useForm({ name: configuration?.name ?? '', driver: configuration?.driver ?? definitions[0]?.type ?? 'database', infrastructure_connection_id: configuration?.infrastructure_connection_id ?? null as number | null, configuration: configuration?.configuration ?? {}, is_enabled: configuration?.is_enabled ?? true })
    const definition = definitions.find((item) => item.type === form.data.driver)
    const submit = (event: React.FormEvent) => { event.preventDefault(); if (configuration) form.put(route('admin.system.cache.update', configuration.id)); else form.post(route('admin.system.cache.store')) }
    return <form onSubmit={submit} className="space-y-6 rounded-xl border border-gray-200 bg-white p-6">
        <div><label className="text-sm font-medium">Name</label><input className="mt-1 w-full rounded-lg border-gray-300" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required /></div>
        <div><label className="text-sm font-medium">Driver</label><select disabled={Boolean(configuration)} className="mt-1 w-full rounded-lg border-gray-300" value={form.data.driver} onChange={(e) => form.setData('driver', e.target.value as CacheDefinition['type'])}>{definitions.map((item) => <option key={item.type} value={item.type} disabled={!item.available}>{item.label}{item.available ? '' : ' — unavailable'}</option>)}</select><p className="mt-1 text-sm text-gray-500">{definition?.description}</p></div>
        {form.data.driver === 'database' && <div><label className="text-sm font-medium">Database connection</label><select className="mt-1 w-full rounded-lg border-gray-300" value={form.data.configuration.database_connection ?? ''} onChange={(e) => form.setData('configuration', { database_connection: e.target.value })}><option value="">Select connection</option>{definition?.options.database_connections?.map((name) => <option key={name}>{name}</option>)}</select></div>}
        {form.data.driver === 'file' && <p className="rounded-lg bg-blue-50 p-3 text-sm text-blue-800">SimpleDesk assigns an isolated directory below storage/framework/cache. Filesystem paths are not administrator-configurable.</p>}
        {form.data.driver === 'redis' && <div><label className="text-sm font-medium">Redis Infrastructure Connection</label><select className="mt-1 w-full rounded-lg border-gray-300" value={form.data.infrastructure_connection_id ?? ''} onChange={(e) => form.setData('infrastructure_connection_id', e.target.value ? Number(e.target.value) : null)}><option value="">Select Redis connection</option>{redisConnections.map((item) => <option key={item.id} value={item.id}>{item.name}{!item.is_enabled || item.deleted_at ? ' — unavailable' : ''}</option>)}</select></div>}
        <label className="flex gap-2 text-sm"><input type="checkbox" checked={form.data.is_enabled} onChange={(e) => form.setData('is_enabled', e.target.checked)} /> Enabled</label>
        {Object.values(form.errors).map((error) => <p key={error} className="text-sm text-red-600">{error}</p>)}
        <button disabled={form.processing || configuration?.is_active} className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">{configuration ? 'Save configuration' : 'Create configuration'}</button>
        {configuration?.is_active && <p className="text-sm text-amber-700">Runtime-affecting fields are locked while this configuration is active.</p>}
    </form>
}

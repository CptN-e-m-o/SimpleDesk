import { Form, Link, usePage } from '@inertiajs/react'
import { Clock, RotateCcw } from 'lucide-react'
import { route } from 'ziggy-js'
import type { SharedData } from '@/types'
import { usePermissions } from '@/hooks/usePermissions'
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu'

export default function AgentStatusControl() {
    const { agentStatus } = usePage<SharedData>().props
    const { can } = usePermissions()
    if (!agentStatus) return null
    const current = agentStatus.current
    if (!can('agent.status.change_own')) return <div className="flex items-center gap-2 rounded-2xl border px-3 py-2 text-sm"><span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: current.color }} />{current.name}</div>
    return <DropdownMenu><DropdownMenuTrigger asChild><button className="flex h-14 items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium"><span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: current.color }} />{current.name}{current.expires_at && <span className="text-xs text-gray-500">until {new Date(current.expires_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>}</button></DropdownMenuTrigger><DropdownMenuContent align="end" className="w-80 rounded-2xl p-3"><div className="mb-2 text-xs font-semibold uppercase text-gray-500">Set your status</div>{agentStatus.options.map((status) => <Form key={status.id} action={route('agent.status.store')} method="post"><input type="hidden" name="status_id" value={status.id}/><button type="submit" className="flex w-full items-center gap-3 rounded-xl p-2 text-left hover:bg-gray-50"><span className="h-3 w-3 rounded-full" style={{ backgroundColor: status.color }}/><span className="flex-1"><span className="block text-sm font-medium">{status.name}</span><span className="text-xs text-gray-500">{status.availability}{status.default_duration_minutes ? ` · ${status.default_duration_minutes} min` : ''}</span></span></button></Form>)}<Form action={route('agent.status.default')} method="post"><button className="mt-2 flex w-full items-center gap-2 border-t p-2 text-sm text-sky-700"><RotateCcw className="h-4 w-4"/>Return to default</button></Form>{can('agent.status.view_own_history') && <Link href={route('agent.status.history')} className="flex items-center gap-2 p-2 text-sm text-gray-600"><Clock className="h-4 w-4"/>View history</Link>}</DropdownMenuContent></DropdownMenu>
}

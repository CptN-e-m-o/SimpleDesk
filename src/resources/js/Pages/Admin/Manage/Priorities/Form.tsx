import { Form, Head, Link } from '@inertiajs/react'
import { ArrowLeft, Info, Save } from 'lucide-react'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout'

type Priority = { id: number; name: string; description: string | null; color: string; visibility: string; is_active: boolean; is_default: boolean }
type Props = { priority?: Priority; visibilityOptions: string[] }

export default function PriorityForm({ priority, visibilityOptions }: Props) {
    const editing = Boolean(priority)

    return <AdminLayout title={editing ? 'Edit Priority' : 'Create Priority'}>
        <Head title={editing ? 'Edit Priority' : 'Create Priority'} />
        <Form action={editing ? route('admin.manage.priorities.update', priority!.id) : route('admin.manage.priorities.store')} method={editing ? 'put' : 'post'}>
            {({ errors, processing }) => <div className="mx-auto max-w-3xl space-y-6 p-4 sm:p-6">
                <header className="flex items-center justify-between rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm">
                    <div><h1 className="text-2xl font-semibold text-gray-900">{editing ? 'Edit priority' : 'Create priority'}</h1><p className="mt-1 text-sm text-gray-500">The slug is generated once and remains stable after renaming.</p></div>
                    <Link href={route('admin.manage.priorities.index')} className="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold"><ArrowLeft className="h-4 w-4" />Back</Link>
                </header>
                <section className="space-y-5 rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm">
                    <Field label="Name" error={errors.name}><input name="name" defaultValue={priority?.name} required className={inputClass} /></Field>
                    <Field label="Description" error={errors.description}><textarea name="description" defaultValue={priority?.description ?? ''} rows={4} className={inputClass} /></Field>
                    <div className="grid gap-5 sm:grid-cols-2"><Field label="Color" error={errors.color}><div className="flex gap-3"><input type="color" name="color" defaultValue={priority?.color ?? '#2563EB'} className="h-11 w-14 rounded-xl border p-1" /><input name="color_preview" value={priority?.color ?? '#2563EB'} readOnly className={inputClass} /></div></Field><Field label="Visibility" error={errors.visibility}><select name="visibility" defaultValue={priority?.visibility ?? 'public'} className={inputClass}>{visibilityOptions.map((value) => <option key={value} value={value}>{value === 'public' ? 'Public' : 'Internal'}</option>)}</select></Field></div>
                    <label className="flex items-center gap-3 rounded-2xl border p-4"><input type="hidden" name="is_active" value="0" /><input type="checkbox" name="is_active" value="1" defaultChecked={priority?.is_active ?? true} /> <span><strong className="block text-sm">Active</strong><span className="text-xs text-gray-500">Available for new assignments.</span></span></label>
                    <label className="flex items-center gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4"><input type="hidden" name="is_default" value="0" /><input type="checkbox" name="is_default" value="1" defaultChecked={priority?.is_default ?? false} /> <span><strong className="block text-sm">Make default</strong><span className="text-xs text-gray-600">Used when a source creates a ticket without an explicit priority. A default must be public and active.</span></span></label>
                    <div className="flex items-start gap-2 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900"><Info className="mt-0.5 h-4 w-4" />Sort order controls presentation only; it is not a severity rank.</div>
                    <button disabled={processing} className="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white"><Save className="h-4 w-4" />Save priority</button>
                </section>
            </div>}
        </Form>
    </AdminLayout>
}

const inputClass = 'mt-2 w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100'
function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) { return <label className="block text-sm font-medium text-gray-800">{label}{children}{error ? <span className="mt-1 block text-xs text-red-600">{error}</span> : null}</label> }

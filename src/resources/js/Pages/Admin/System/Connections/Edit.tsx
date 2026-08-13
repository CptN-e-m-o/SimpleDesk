import AdminLayout from '@/Layouts/AdminLayout'
import {
    Head,
    Link,
} from '@inertiajs/react'
import {
    ArrowLeft,
    Cable,
    Pencil,
} from 'lucide-react'
import { route } from 'ziggy-js'
import Form, {
    type ConnectionFormValue,
    type Definition,
} from './Form'

type Props = {
    definitions: Definition[]
    connection: ConnectionFormValue
}

export default function Edit({
                                 definitions,
                                 connection,
                             }: Props) {
    return (
        <AdminLayout title="Edit connection">
            <Head title="Edit connection" />

            <div className="space-y-6">
                <section className="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm">
                    <div className="bg-gradient-to-r from-sky-50/80 via-white to-white px-6 py-5">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-start gap-4">
                                <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-sky-100 ring-1 ring-inset ring-sky-200">
                                    <Cable className="h-6 w-6 text-sky-700" />
                                </div>

                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h1 className="text-xl font-semibold tracking-tight text-gray-900">
                                            Edit infrastructure connection
                                        </h1>

                                        <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-200">
                                            <Pencil className="h-3.5 w-3.5" />
                                            Editing
                                        </span>
                                    </div>

                                    <p className="mt-1 max-w-3xl text-sm leading-6 text-gray-500">
                                        Update configuration for{' '}
                                        <span className="font-medium text-gray-700">
                                            {connection.name}
                                        </span>
                                        . Existing credentials remain hidden
                                        and are only changed when explicitly
                                        replaced or removed.
                                    </p>
                                </div>
                            </div>

                            <Link
                                href={route(
                                    'admin.system.connections.index',
                                )}
                                className="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Back to connections
                            </Link>
                        </div>
                    </div>
                </section>

                <Form
                    definitions={definitions}
                    connection={connection}
                />
            </div>
        </AdminLayout>
    )
}

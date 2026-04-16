import { Head, usePage } from '@inertiajs/react'
import AppLayout from '../Layouts/AppLayout'
import type { SharedData } from '../types'

export default function Dashboard() {
    const { props } = usePage<SharedData>()
    const user = props.auth.user

    return (
        <>
            <Head title="Dashboard" />

            <AppLayout title="Dashboard">
                <div className="grid gap-6 md:grid-cols-3">
                    <div className="rounded-2xl bg-white p-6 shadow-sm">
                        <div className="text-sm text-gray-500">Welcome back</div>
                        <div className="mt-2 text-xl font-semibold text-gray-900">
                            {user?.name}
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow-sm">
                        <div className="text-sm text-gray-500">Open tickets</div>
                        <div className="mt-2 text-3xl font-bold text-gray-900">0</div>
                    </div>

                    <div className="rounded-2xl bg-white p-6 shadow-sm">
                        <div className="text-sm text-gray-500">Pending tickets</div>
                        <div className="mt-2 text-3xl font-bold text-gray-900">0</div>
                    </div>
                </div>

                <div className="mt-6 rounded-2xl bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-gray-900">Getting started</h2>
                    <p className="mt-2 text-sm text-gray-600">
                        Next step: create ticket entities, statuses, priorities and the tickets list page.
                    </p>
                </div>
            </AppLayout>
        </>
    )
}

import { Head } from '@inertiajs/react'
import AgentLayout from '../../Layouts/AgentLayout'

export default function TicketsIndex() {
    return (
        <>
            <Head title="Tickets" />

            <AgentLayout title="Tickets">
                <div className="rounded-2xl bg-white p-6 shadow-sm">
                    <h2 className="text-lg font-semibold text-gray-900">Tickets</h2>
                    <p className="mt-2 text-sm text-gray-600">
                        Tickets list will be here.
                    </p>
                </div>
            </AgentLayout>
        </>
    )
}

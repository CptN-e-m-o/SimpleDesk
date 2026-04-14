import { ReactNode } from 'react'

export default function AppLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen bg-gray-100">
            {/* Sidebar */}
            <aside className="w-64 bg-gray-900 text-white p-4">
                <h2 className="text-xl font-bold mb-6">SimpleDesk</h2>

                <nav className="space-y-2">
                    <a href="/" className="block hover:bg-gray-800 p-2 rounded">
                        Dashboard
                    </a>
                    <a href="/tickets" className="block hover:bg-gray-800 p-2 rounded">
                        Tickets
                    </a>
                </nav>
            </aside>

            {/* Content */}
            <div className="flex-1">
                {/* Topbar */}
                <header className="bg-white shadow p-4">
                    <h1 className="font-semibold">Dashboard</h1>
                </header>

                <main className="p-6">{children}</main>
            </div>
        </div>
    )
}

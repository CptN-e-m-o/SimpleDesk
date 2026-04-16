import { Head, Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthLayout from '../../Layouts/AuthLayout'

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    })

    function submit(e: FormEvent) {
        e.preventDefault()
        post('/register')
    }

    return (
        <>
            <Head title="Register" />

            <AuthLayout
                title="Create account"
                description="Set up your SimpleDesk account to get started."
            >
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">
                            Name
                        </label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-gray-500"
                            autoComplete="name"
                        />
                        {errors.name && (
                            <div className="mt-1 text-sm text-red-600">{errors.name}</div>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">
                            Email
                        </label>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-gray-500"
                            autoComplete="email"
                        />
                        {errors.email && (
                            <div className="mt-1 text-sm text-red-600">{errors.email}</div>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-gray-500"
                            autoComplete="new-password"
                        />
                        {errors.password && (
                            <div className="mt-1 text-sm text-red-600">{errors.password}</div>
                        )}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">
                            Confirm password
                        </label>
                        <input
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-gray-500"
                            autoComplete="new-password"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-xl bg-gray-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-800 disabled:opacity-50"
                    >
                        {processing ? 'Creating account...' : 'Create account'}
                    </button>
                </form>

                <p className="mt-6 text-sm text-gray-600">
                    Already have an account?{' '}
                    <Link href="/login" className="font-medium text-gray-900 hover:underline">
                        Sign in
                    </Link>
                </p>
            </AuthLayout>
        </>
    )
}

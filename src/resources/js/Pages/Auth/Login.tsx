import { Head, Link, useForm } from '@inertiajs/react'
import type { FormEvent } from 'react'
import AuthLayout from '../../Layouts/AuthLayout'
import { Checkbox } from '@/Components/ui/checkbox'

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    })

    function submit(e: FormEvent) {
        e.preventDefault()
        post('/login')
    }

    return (
        <>
            <Head title="Login" />

            <AuthLayout
                title="Sign in"
                description="Enter your email and password to access SimpleDesk."
            >
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label
                            htmlFor="email"
                            className="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Email
                        </label>

                        <input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-gray-500"
                            autoComplete="email"
                        />

                        {errors.email && (
                            <div id="email-error" className="mt-1 text-sm text-red-600">
                                {errors.email}
                            </div>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="password"
                            className="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Password
                        </label>

                        <input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-gray-500"
                            autoComplete="current-password"
                        />

                        {errors.password && (
                            <div id="password-error" className="mt-1 text-sm text-red-600">
                                {errors.password}
                            </div>
                        )}
                    </div>

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="remember"
                            checked={data.remember}
                            onCheckedChange={(checked) => setData('remember', Boolean(checked))}
                        />
                        <label
                            htmlFor="remember"
                            className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                        >
                            Remember me
                        </label>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-xl bg-gray-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-800 disabled:opacity-50"
                    >
                        {processing ? 'Signing in...' : 'Sign in'}
                    </button>
                </form>

                <p className="mt-6 text-sm text-gray-600">
                    No account yet?{' '}
                    <Link href="/register" className="font-medium text-gray-900 hover:underline">
                        Create one
                    </Link>
                </p>
            </AuthLayout>
        </>
    )
}

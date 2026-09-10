import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

function ShieldCheckIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
            <path
                d="M12 2.5 4.5 5.5v5.2c0 4.8 3.2 9 7.5 10.3 4.3-1.3 7.5-5.5 7.5-10.3V5.5L12 2.5Z"
                fill="currentColor"
                opacity="0.15"
            />
            <path
                d="M12 2.5 4.5 5.5v5.2c0 4.8 3.2 9 7.5 10.3 4.3-1.3 7.5-5.5 7.5-10.3V5.5L12 2.5Z"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinejoin="round"
            />
            <path
                d="m9 12 2 2 4-4"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

function UserIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
            <circle cx="12" cy="8" r="3.2" stroke="currentColor" strokeWidth="1.6" />
            <path
                d="M5 20c0-3.5 3.1-6.3 7-6.3s7 2.8 7 6.3"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
            />
        </svg>
    );
}

function LockIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
            <rect x="5" y="10.5" width="14" height="9.5" rx="2" stroke="currentColor" strokeWidth="1.6" />
            <path
                d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
            />
        </svg>
    );
}

function EyeIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
            <path
                d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinejoin="round"
            />
            <circle cx="12" cy="12" r="2.6" stroke="currentColor" strokeWidth="1.6" />
        </svg>
    );
}

export default function Login({ status }: { status?: string }) {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/login');
    }

    return (
        <div className="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-background p-6 font-sans">
            {/* soft background glow, matches the reference screenshot's faint blue blobs */}
            <div className="pointer-events-none absolute -right-24 -top-24 h-[500px] w-[500px] rounded-full bg-[radial-gradient(circle,hsla(220,90%,60%,0.08)_0%,transparent_70%)]" />
            <div className="pointer-events-none absolute -bottom-20 -left-20 h-[400px] w-[400px] rounded-full bg-[radial-gradient(circle,hsla(220,90%,60%,0.06)_0%,transparent_70%)]" />

            <div className="relative z-10 w-full max-w-md rounded-2xl bg-card p-10 shadow-[var(--shadow-card)]">
                <div className="mb-7 text-center">
                    <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-orange">
                        <ShieldCheckIcon className="h-7 w-7 text-white" />
                    </div>
                    <div className="text-lg font-bold tracking-tight text-ink">MB</div>
                    <div className="mt-1 text-sm text-subtle">Sign in to your account</div>
                </div>

                {status && (
                    <div className="mb-5 rounded-md border border-success/30 border-l-4 border-l-success bg-success/5 px-4 py-3 text-sm font-medium text-[#276749]">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="mb-1.5 block text-xs font-semibold text-ink">
                            Username or Email
                        </label>
                        <div className="relative">
                            <UserIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-subtle" />
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                className={`w-full rounded-lg border bg-muted py-2.5 pl-9 pr-4 text-sm outline-none transition focus:border-brand-orange focus:bg-white ${
                                    errors.email ? 'border-danger' : 'border-border'
                                }`}
                                placeholder="Enter your official username"
                            />
                        </div>
                        {errors.email && <p className="mt-1 text-xs font-medium text-danger">{errors.email}</p>}
                    </div>

                    <div>
                        <label className="mb-1.5 block text-xs font-semibold text-ink">Password</label>
                        <div className="relative">
                            <LockIcon className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-subtle" />
                            <input
                                type={showPassword ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className={`w-full rounded-lg border bg-muted py-2.5 pl-9 pr-9 text-sm outline-none transition focus:border-brand-orange focus:bg-white ${
                                    errors.password ? 'border-danger' : 'border-border'
                                }`}
                                placeholder="••••••••••"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((v) => !v)}
                                className="absolute right-3 top-1/2 -translate-y-1/2 text-subtle hover:text-ink"
                                tabIndex={-1}
                            >
                                <EyeIcon className="h-4 w-4" />
                            </button>
                        </div>
                        {errors.password && <p className="mt-1 text-xs font-medium text-danger">{errors.password}</p>}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="!mt-6 w-full rounded-lg bg-brand-orange py-3 text-sm font-bold tracking-wide text-white transition hover:bg-brand-orange-hover disabled:opacity-60"
                    >
                        Login to Account
                    </button>
                </form>

                <div className="mt-4 text-center">
                    <a href="/forgot-password" className="text-sm font-medium text-brand-orange hover:underline">
                        Forgot Password?
                    </a>
                </div>

                <div className="mt-6 flex items-center justify-center gap-1.5 text-xs text-subtle">
                    <LockIcon className="h-3.5 w-3.5" />
                    <span>Official secure administrative portal</span>
                </div>
            </div>
        </div>
    );
}
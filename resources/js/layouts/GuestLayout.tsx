import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-accent-50/60 via-neutral-50 to-neutral-50 px-6 py-12">
            <Link
                href="/"
                className="relative z-10 mb-8 flex animate-fade-in items-center gap-2 text-lg font-semibold text-neutral-900"
            >
                <svg
                    className="h-6 w-6 text-accent-600"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.75"
                    aria-hidden="true"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M12 21c-4.5 0-8-3.5-8-8 0-6 8-11 8-11s8 5 8 11c0 4.5-3.5 8-8 8Z"
                    />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 21V10" />
                </svg>
                ReciclaB2B
            </Link>

            <div className="relative z-10 w-full max-w-sm animate-fade-in-up rounded-xl border border-neutral-200 bg-white p-8 shadow-sm">
                {children}
            </div>
        </div>
    );
}

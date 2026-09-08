import { type ButtonHTMLAttributes } from 'react';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    processing?: boolean;
    variant?: 'primary' | 'secondary' | 'ghost';
}

const VARIANT_CLASSES: Record<NonNullable<ButtonProps['variant']>, string> = {
    primary: 'bg-accent-500 text-neutral-900 hover:bg-accent-400 focus-visible:outline-accent-600 shadow-sm',
    secondary:
        'bg-white text-neutral-900 border border-neutral-300 hover:border-neutral-400 hover:bg-neutral-50 focus-visible:outline-neutral-400',
    ghost: 'bg-transparent text-neutral-700 hover:bg-neutral-100 focus-visible:outline-neutral-400',
};

function Spinner() {
    return (
        <svg className="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path
                className="opacity-90"
                fill="currentColor"
                d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"
            />
        </svg>
    );
}

export default function Button({
    processing = false,
    disabled,
    variant = 'primary',
    children,
    className = '',
    ...props
}: ButtonProps) {
    return (
        <button
            {...props}
            disabled={disabled || processing}
            className={`inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition-all duration-150 ease-out hover:-translate-y-px hover:shadow-md active:translate-y-0 active:shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-60 disabled:shadow-none disabled:hover:translate-y-0 ${VARIANT_CLASSES[variant]} ${className}`}
        >
            {processing && <Spinner />}
            {children}
        </button>
    );
}

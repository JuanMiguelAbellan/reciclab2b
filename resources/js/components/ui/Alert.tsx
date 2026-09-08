import { type PropsWithChildren } from 'react';

const VARIANT_CLASSES = {
    success: 'bg-green-50 text-green-800 border-green-200',
    error: 'bg-red-50 text-red-800 border-red-200',
    info: 'bg-accent-50 text-accent-900 border-accent-200',
};

const VARIANT_ICONS = {
    success: (
        <path
            fillRule="evenodd"
            d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
            clipRule="evenodd"
        />
    ),
    error: (
        <path
            fillRule="evenodd"
            d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
            clipRule="evenodd"
        />
    ),
    info: (
        <path
            fillRule="evenodd"
            d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z"
            clipRule="evenodd"
        />
    ),
};

interface AlertProps extends PropsWithChildren {
    variant?: keyof typeof VARIANT_CLASSES;
}

export default function Alert({ variant = 'info', children }: AlertProps) {
    return (
        <div
            className={`animate-slide-down mb-4 flex items-start gap-2.5 rounded-lg border px-4 py-3 text-sm ${VARIANT_CLASSES[variant]}`}
            role="alert"
        >
            <svg className="mt-0.5 h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                {VARIANT_ICONS[variant]}
            </svg>
            <span>{children}</span>
        </div>
    );
}

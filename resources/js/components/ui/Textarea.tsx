import { type TextareaHTMLAttributes } from 'react';

interface TextareaFieldProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label: string;
    error?: string;
}

export default function TextareaField({ label, error, id, name, ...props }: TextareaFieldProps) {
    const textareaId = id ?? name;

    return (
        <div>
            {label && (
                <label htmlFor={textareaId} className="block text-sm font-medium text-neutral-700">
                    {label}
                </label>
            )}
            <textarea
                id={textareaId}
                name={name}
                {...props}
                className={`mt-1.5 block w-full rounded-lg border px-3 py-2 text-sm text-neutral-900 shadow-sm transition-colors duration-150 outline-none placeholder:text-neutral-400 ${
                    error
                        ? 'border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-100'
                        : 'border-neutral-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-100'
                }`}
            />
            {error && (
                <p className="mt-1.5 text-sm text-red-600 animate-fade-in" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}

import { type SelectHTMLAttributes } from 'react';

interface Option {
    value: string;
    label: string;
}

interface SelectFieldProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label: string;
    options: Option[];
    error?: string;
    placeholder?: string;
}

export default function SelectField({
    label,
    options,
    error,
    placeholder,
    id,
    name,
    ...props
}: SelectFieldProps) {
    const selectId = id ?? name;

    return (
        <div>
            <label htmlFor={selectId} className="block text-sm font-medium text-neutral-700">
                {label}
            </label>
            <select
                id={selectId}
                name={name}
                {...props}
                className={`mt-1.5 block w-full rounded-lg border bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm transition-colors duration-150 outline-none ${
                    error
                        ? 'border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-100'
                        : 'border-neutral-300 focus:border-accent-500 focus:ring-2 focus:ring-accent-100'
                }`}
            >
                {placeholder && <option value="">{placeholder}</option>}
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {error && (
                <p className="mt-1.5 text-sm text-red-600 animate-fade-in" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}

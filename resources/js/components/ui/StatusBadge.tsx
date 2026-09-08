import { type StatusTone } from '@/lib/statusTone';

const TONE_CLASSES: Record<StatusTone, string> = {
    neutral: 'bg-neutral-100 text-neutral-700',
    success: 'bg-green-50 text-green-700',
    warning: 'bg-amber-50 text-amber-700',
    danger: 'bg-red-50 text-red-700',
    info: 'bg-accent-50 text-accent-800',
};

interface StatusBadgeProps {
    label: string;
    tone?: StatusTone;
}

export default function StatusBadge({ label, tone = 'neutral' }: StatusBadgeProps) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${TONE_CLASSES[tone]}`}
        >
            {label}
        </span>
    );
}

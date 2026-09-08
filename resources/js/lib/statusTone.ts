export type StatusTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info';

/** Shared vocabulary of User and Company statuses. */
export function accountStatusTone(value: string): StatusTone {
    switch (value) {
        case 'approved':
            return 'success';
        case 'pending':
            return 'info';
        case 'rejected':
        case 'blocked':
            return 'danger';
        default:
            return 'neutral'; // inactive
    }
}

export function offerStatusTone(value: string): StatusTone {
    switch (value) {
        case 'published':
            return 'success';
        case 'paused':
            return 'warning';
        case 'closed':
            return 'danger';
        default:
            return 'neutral'; // draft
    }
}

export interface User {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
    status: 'pending' | 'approved' | 'rejected' | 'blocked' | 'inactive';
    email_verified_at: string | null;
    has_company: boolean;
    is_staff: boolean;
    unread_conversations_count: number;
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
    status?: string | null;
}

export interface SharedPageProps {
    auth: {
        user: User | null;
    };
    flash: FlashMessages;
    [key: string]: unknown;
}

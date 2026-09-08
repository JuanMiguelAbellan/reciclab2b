import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';

interface ConversationSummary {
    id: number;
    offer_crop: string;
    counterpart_name: string;
    messages_count: number;
    unread: boolean;
    updated_at: string;
}

interface IndexConversationsProps {
    conversations: ConversationSummary[];
}

export default function Index({ conversations }: IndexConversationsProps) {
    return (
        <AppLayout>
            <Head title="Mensajes" />

            <h1 className="animate-fade-in-up text-2xl font-bold text-neutral-900">Mensajes</h1>

            {conversations.length === 0 ? (
                <div className="mt-8 animate-fade-in-up rounded-xl border border-dashed border-neutral-300 bg-white px-6 py-12 text-center text-neutral-600">
                    Todavía no tienes conversaciones. Contacta con un productor desde el{' '}
                    <Link href="/mercado" className="font-medium text-accent-700 hover:underline">
                        mercado
                    </Link>
                    .
                </div>
            ) : (
                <div className="mt-6 animate-fade-in-up divide-y divide-neutral-200 rounded-xl border border-neutral-200 bg-white">
                    {conversations.map((conversation) => (
                        <Link
                            key={conversation.id}
                            href={`/mensajes/${conversation.id}`}
                            className="flex items-center justify-between px-4 py-3 transition-colors hover:bg-neutral-50"
                        >
                            <div className="flex items-center gap-2">
                                {conversation.unread && (
                                    <span
                                        className="h-2 w-2 animate-pulse rounded-full bg-accent-500"
                                        aria-label="No leído"
                                    />
                                )}
                                <div>
                                    <p
                                        className={`text-neutral-900 ${conversation.unread ? 'font-bold' : 'font-medium'}`}
                                    >
                                        {conversation.counterpart_name}
                                    </p>
                                    <p className="text-sm text-neutral-500">Sobre: {conversation.offer_crop}</p>
                                </div>
                            </div>
                            <span className="text-sm text-neutral-500">
                                {conversation.messages_count} mensaje{conversation.messages_count === 1 ? '' : 's'}
                            </span>
                        </Link>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}

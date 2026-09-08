import { Head, Link, router, usePage } from '@inertiajs/react';
import { type FormEventHandler, type KeyboardEvent, useEffect, useRef, useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import Button from '@/components/ui/Button';
import InputField from '@/components/ui/InputField';
import TextareaField from '@/components/ui/Textarea';
import echo from '@/echo';
import { type SharedPageProps } from '@/types';

interface MessageItem {
    id: number;
    body: string;
    sender_id: number;
    sender_name: string;
    is_mine: boolean;
    created_at: string;
}

interface ConversationDetail {
    id: number;
    offer: { id: number; material: string };
    counterpart_name: string;
    canOrder: boolean;
    availableQuantity: number;
}

interface ShowConversationProps {
    conversation: ConversationDetail;
    messages: MessageItem[];
}

interface BroadcastMessagePayload {
    id: number;
    body: string;
    sender_id: number;
    sender_name: string;
    created_at: string;
}

export default function Show({ conversation, messages: initialMessages }: ShowConversationProps) {
    const { auth } = usePage<SharedPageProps>().props;
    const [messages, setMessages] = useState<MessageItem[]>(initialMessages);
    const [body, setBody] = useState('');
    const [sending, setSending] = useState(false);
    const [quantity, setQuantity] = useState('');
    const [quantityError, setQuantityError] = useState<string | undefined>();
    const [ordering, setOrdering] = useState(false);
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const channel = echo.private(`conversation.${conversation.id}`);

        channel.listen('.message.sent', (payload: BroadcastMessagePayload) => {
            setMessages((current) => {
                if (current.some((message) => message.id === payload.id)) {
                    return current;
                }

                return [
                    ...current,
                    {
                        id: payload.id,
                        body: payload.body,
                        sender_id: payload.sender_id,
                        sender_name: payload.sender_name,
                        is_mine: payload.sender_id === auth.user?.id,
                        created_at: payload.created_at,
                    },
                ];
            });
        });

        return () => {
            echo.leave(`conversation.${conversation.id}`);
        };
    }, [conversation.id, auth.user?.id]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const sendMessage = () => {
        if (!body.trim() || sending) {
            return;
        }

        setSending(true);
        router.post(
            `/mensajes/${conversation.id}/mensajes`,
            { body },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setBody(''),
                onFinish: () => setSending(false),
            },
        );
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        sendMessage();
    };

    const handleKeyDown = (e: KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    const placeOrder: FormEventHandler = (e) => {
        e.preventDefault();
        setOrdering(true);
        router.post(
            `/mensajes/${conversation.id}/pedidos`,
            { quantity_tons: quantity },
            {
                onError: (errors) => setQuantityError(errors.quantity_tons),
                onFinish: () => setOrdering(false),
            },
        );
    };

    return (
        <AppLayout>
            <Head title={`Conversación con ${conversation.counterpart_name}`} />

            <Link href="/mensajes" className="text-sm text-neutral-600 transition-colors hover:text-accent-700 hover:underline">
                ← Volver a mensajes
            </Link>

            <div className="mt-4 flex animate-fade-in-up items-center justify-between">
                <h1 className="text-xl font-bold text-neutral-900">{conversation.counterpart_name}</h1>
                <Link
                    href={`/mercado/${conversation.offer.id}`}
                    className="text-sm text-neutral-600 transition-colors hover:text-accent-700 hover:underline"
                >
                    Ver oferta ({conversation.offer.material})
                </Link>
            </div>

            {conversation.canOrder && conversation.availableQuantity > 0 && (
                <form
                    onSubmit={placeOrder}
                    className="mt-4 flex animate-fade-in-up items-end gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4"
                >
                    <div className="flex-1">
                        <InputField
                            label={`Proponer pedido (${conversation.availableQuantity} t disponibles)`}
                            type="number"
                            step="0.01"
                            min="0.01"
                            max={conversation.availableQuantity}
                            value={quantity}
                            onChange={(e) => {
                                setQuantity(e.target.value);
                                setQuantityError(undefined);
                            }}
                            error={quantityError}
                            placeholder="Toneladas"
                        />
                    </div>
                    <Button type="submit" processing={ordering} className="w-auto px-6">
                        Pedir
                    </Button>
                </form>
            )}

            <div className="mt-6 flex h-[28rem] animate-fade-in-up flex-col rounded-xl border border-neutral-200 bg-white" style={{ animationDelay: '80ms' }}>
                <div className="flex-1 space-y-3 overflow-y-auto p-4">
                    {messages.length === 0 && (
                        <p className="text-sm text-neutral-500">
                            Todavía no hay mensajes. Escribe el primero.
                        </p>
                    )}

                    {messages.map((message) => (
                        <div
                            key={message.id}
                            className={`flex animate-fade-in-up flex-col ${message.is_mine ? 'items-end' : 'items-start'}`}
                        >
                            <div
                                className={`max-w-xs rounded-2xl px-3.5 py-2 text-sm sm:max-w-md ${
                                    message.is_mine
                                        ? 'bg-accent-500 text-neutral-900'
                                        : 'bg-neutral-100 text-neutral-900'
                                }`}
                            >
                                {message.body}
                            </div>
                            <span className="mt-1 text-xs text-neutral-400">
                                {message.is_mine ? 'Tú' : message.sender_name}
                            </span>
                        </div>
                    ))}
                    <div ref={bottomRef} />
                </div>

                <form onSubmit={submit} className="flex items-end gap-3 border-t border-neutral-200 p-4">
                    <div className="flex-1">
                        <TextareaField
                            label=""
                            aria-label="Mensaje"
                            rows={2}
                            value={body}
                            onChange={(e) => setBody(e.target.value)}
                            onKeyDown={handleKeyDown}
                            placeholder="Escribe un mensaje… (Enter para enviar, Shift+Enter para salto de línea)"
                        />
                    </div>
                    <Button type="submit" processing={sending} className="w-auto px-6">
                        Enviar
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}

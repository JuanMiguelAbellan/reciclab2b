import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import Button from '@/components/ui/Button';

interface OrderDetail {
    id: number;
    offer: { id: number; material: { value: string; label: string } };
    offer_id: number;
    conversation_id: number | null;
    counterpart_name: string;
    role: 'buyer' | 'seller';
    quantity_tons: number;
    price_per_ton: number;
    total: number;
    status: { value: string; label: string };
    created_at: string;
    created_by_name: string;
    responded_by_name: string | null;
    cancelled_by_name: string | null;
    can_respond: boolean;
    can_complete: boolean;
    can_cancel: boolean;
}

interface ShowOrderProps {
    order: OrderDetail;
}

export default function Show({ order }: ShowOrderProps) {
    const [working, setWorking] = useState(false);

    const act = (action: 'aceptar' | 'rechazar' | 'completar' | 'cancelar', confirmMessage?: string) => {
        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

        setWorking(true);
        router.post(`/pedidos/${order.id}/${action}`, {}, { onFinish: () => setWorking(false) });
    };

    return (
        <AppLayout>
            <Head title={`Pedido con ${order.counterpart_name}`} />

            <Link href="/pedidos" className="text-sm text-neutral-600 hover:underline">
                ← Volver a pedidos
            </Link>

            <div className="mt-4 flex items-center justify-between">
                <h1 className="text-2xl font-bold">{order.counterpart_name}</h1>
                <span className="rounded-full bg-neutral-100 px-3 py-1 text-sm font-medium text-neutral-700">
                    {order.status.label}
                </span>
            </div>

            <p className="mt-1 text-sm text-neutral-500">
                Tú eres el {order.role === 'buyer' ? 'comprador' : 'vendedor'} en este pedido.
            </p>

            <dl className="mt-6 grid grid-cols-2 gap-4 rounded-md border border-neutral-200 p-4 sm:grid-cols-4">
                <div>
                    <dt className="text-xs text-neutral-500">Material</dt>
                    <dd className="text-lg font-semibold">{order.offer.material.label}</dd>
                </div>
                <div>
                    <dt className="text-xs text-neutral-500">Cantidad</dt>
                    <dd className="text-lg font-semibold">{order.quantity_tons} t</dd>
                </div>
                <div>
                    <dt className="text-xs text-neutral-500">Precio</dt>
                    <dd className="text-lg font-semibold">{order.price_per_ton} €/t</dd>
                </div>
                <div>
                    <dt className="text-xs text-neutral-500">Total</dt>
                    <dd className="text-lg font-semibold">{order.total} €</dd>
                </div>
            </dl>

            <div className="mt-6 space-y-1 text-sm text-neutral-600">
                <p>Pedido por {order.created_by_name}</p>
                {order.responded_by_name && <p>Respondido por {order.responded_by_name}</p>}
                {order.cancelled_by_name && <p>Cancelado por {order.cancelled_by_name}</p>}
            </div>

            {(order.can_respond || order.can_complete || order.can_cancel) && (
                <div className="mt-6 flex flex-wrap gap-3 border-t border-neutral-200 pt-6">
                    {order.can_respond && (
                        <>
                            <Button
                                type="button"
                                processing={working}
                                onClick={() => act('aceptar')}
                                className="w-auto bg-green-700 px-6 hover:bg-green-800"
                            >
                                Aceptar
                            </Button>
                            <Button
                                type="button"
                                processing={working}
                                onClick={() => act('rechazar', '¿Rechazar este pedido?')}
                                className="w-auto bg-red-700 px-6 hover:bg-red-800"
                            >
                                Rechazar
                            </Button>
                        </>
                    )}
                    {order.can_complete && (
                        <Button type="button" processing={working} onClick={() => act('completar')} className="w-auto px-6">
                            Marcar como completado
                        </Button>
                    )}
                    {order.can_cancel && (
                        <button
                            type="button"
                            disabled={working}
                            onClick={() => act('cancelar', '¿Cancelar este pedido?')}
                            className="text-sm text-red-700 hover:underline disabled:opacity-60"
                        >
                            Cancelar pedido
                        </button>
                    )}
                </div>
            )}

            <div className="mt-6 flex gap-4 text-sm">
                <Link href={`/mercado/${order.offer_id}`} className="text-neutral-600 hover:underline">
                    Ver oferta
                </Link>
                {order.conversation_id && (
                    <Link href={`/mensajes/${order.conversation_id}`} className="text-neutral-600 hover:underline">
                        Ver conversación
                    </Link>
                )}
            </div>
        </AppLayout>
    );
}

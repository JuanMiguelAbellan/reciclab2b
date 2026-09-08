import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';

interface OrderSummary {
    id: number;
    offer: { id: number; material: { value: string; label: string } };
    counterpart_name: string;
    role: 'buyer' | 'seller';
    quantity_tons: number;
    price_per_ton: number;
    total: number;
    status: { value: string; label: string };
    created_at: string;
}

interface IndexOrdersProps {
    orders: OrderSummary[];
}

const STATUS_CLASSES: Record<string, string> = {
    pending: 'bg-amber-50 text-amber-700',
    accepted: 'bg-blue-50 text-blue-700',
    rejected: 'bg-red-50 text-red-700',
    completed: 'bg-green-50 text-green-700',
    cancelled: 'bg-neutral-100 text-neutral-700',
};

export default function Index({ orders }: IndexOrdersProps) {
    return (
        <AppLayout>
            <Head title="Pedidos" />

            <h1 className="text-2xl font-bold">Pedidos</h1>

            {orders.length === 0 ? (
                <p className="mt-8 text-neutral-600">
                    Todavía no tienes pedidos.{' '}
                    <Link href="/mercado" className="font-medium underline">
                        Explora el mercado
                    </Link>
                    .
                </p>
            ) : (
                <div className="mt-6 overflow-x-auto">
                    <table className="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead>
                            <tr className="text-left text-neutral-500">
                                <th className="py-2 pr-4">Rol</th>
                                <th className="py-2 pr-4">Empresa</th>
                                <th className="py-2 pr-4">Material</th>
                                <th className="py-2 pr-4">Cantidad</th>
                                <th className="py-2 pr-4">Total</th>
                                <th className="py-2 pr-4">Estado</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-100">
                            {orders.map((order) => (
                                <tr
                                    key={order.id}
                                    onClick={() => (window.location.href = `/pedidos/${order.id}`)}
                                    className="cursor-pointer hover:bg-neutral-50"
                                >
                                    <td className="py-3 pr-4">{order.role === 'buyer' ? 'Comprador' : 'Vendedor'}</td>
                                    <td className="py-3 pr-4">
                                        <Link href={`/pedidos/${order.id}`} className="hover:underline">
                                            {order.counterpart_name}
                                        </Link>
                                    </td>
                                    <td className="py-3 pr-4">{order.offer.material.label}</td>
                                    <td className="py-3 pr-4">{order.quantity_tons} t</td>
                                    <td className="py-3 pr-4">{order.total} €</td>
                                    <td className="py-3 pr-4">
                                        <span
                                            className={`rounded-full px-2 py-1 text-xs font-medium ${STATUS_CLASSES[order.status.value] ?? ''}`}
                                        >
                                            {order.status.label}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AppLayout>
    );
}

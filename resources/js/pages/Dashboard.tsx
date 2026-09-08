import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { type SharedPageProps } from '@/types';

interface CompanyInfo {
    trade_name: string;
    status: { value: string; label: string };
}

interface DashboardStats {
    active_offers: number;
    pending_orders: number;
    unread_conversations: number;
}

interface PendingOrder {
    id: number;
    buyer_name: string;
    material: string;
    quantity_tons: number;
    total: number;
}

interface DashboardProps {
    company: CompanyInfo | null;
    stats?: DashboardStats;
    pendingOrders?: PendingOrder[];
}

const QUICK_LINKS = [
    {
        href: '/mercado',
        title: 'Mercado',
        description: 'Explora las ofertas publicadas por otras empresas.',
    },
    {
        href: '/ofertas/crear',
        title: 'Publicar oferta',
        description: 'Publica una nueva oferta de tu producto.',
    },
    {
        href: '/empresa',
        title: 'Mi empresa',
        description: 'Datos, miembros e invitaciones de tu empresa.',
    },
    {
        href: '/mensajes',
        title: 'Mensajes',
        description: 'Conversaciones con compradores y productores.',
    },
];

export default function Dashboard({ company, stats, pendingOrders }: DashboardProps) {
    const { auth } = usePage<SharedPageProps>().props;

    return (
        <AppLayout>
            <Head title="Panel" />

            <h1 className="animate-fade-in-up text-3xl font-bold text-neutral-900">
                Hola, {auth.user?.first_name}
            </h1>

            {company === null ? (
                <p className="mt-4 text-neutral-600">
                    Tu cuenta no está vinculada a ninguna empresa. Si eres administrador, gestiona la
                    plataforma desde el{' '}
                    <a href="/admin" className="font-medium underline">
                        panel de administración
                    </a>
                    .
                </p>
            ) : (
                <>
                    <p className="mt-2 animate-fade-in-up text-neutral-600" style={{ animationDelay: '60ms' }}>
                        {company.trade_name} · {company.status.label}
                    </p>

                    {stats && (
                        <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <Link
                                href="/ofertas"
                                className="animate-fade-in-up rounded-xl border border-neutral-200 bg-white p-4 transition-all duration-150 hover:-translate-y-0.5 hover:border-accent-300 hover:shadow-md"
                                style={{ animationDelay: '120ms' }}
                            >
                                <p className="text-2xl font-bold text-neutral-900">{stats.active_offers}</p>
                                <p className="text-sm text-neutral-500">Ofertas activas</p>
                            </Link>
                            <Link
                                href="/pedidos"
                                className="animate-fade-in-up rounded-xl border border-neutral-200 bg-white p-4 transition-all duration-150 hover:-translate-y-0.5 hover:border-accent-300 hover:shadow-md"
                                style={{ animationDelay: '180ms' }}
                            >
                                <p className="text-2xl font-bold text-neutral-900">{stats.pending_orders}</p>
                                <p className="text-sm text-neutral-500">Pedidos por responder</p>
                            </Link>
                            <Link
                                href="/mensajes"
                                className="animate-fade-in-up rounded-xl border border-neutral-200 bg-white p-4 transition-all duration-150 hover:-translate-y-0.5 hover:border-accent-300 hover:shadow-md"
                                style={{ animationDelay: '240ms' }}
                            >
                                <p className="text-2xl font-bold text-neutral-900">{stats.unread_conversations}</p>
                                <p className="text-sm text-neutral-500">Mensajes sin leer</p>
                            </Link>
                        </div>
                    )}

                    {pendingOrders && pendingOrders.length > 0 && (
                        <div className="mt-8">
                            <h2 className="text-lg font-bold text-neutral-900">Pedidos que requieren tu respuesta</h2>
                            <div className="mt-3 divide-y divide-neutral-200 rounded-xl border border-neutral-200 bg-white">
                                {pendingOrders.map((order) => (
                                    <Link
                                        key={order.id}
                                        href={`/pedidos/${order.id}`}
                                        className="flex items-center justify-between px-4 py-3 hover:bg-neutral-50"
                                    >
                                        <div>
                                            <p className="font-medium text-neutral-900">{order.buyer_name}</p>
                                            <p className="text-sm text-neutral-500">
                                                {order.material} · {order.quantity_tons} t
                                            </p>
                                        </div>
                                        <span className="text-sm font-medium text-neutral-700">
                                            {order.total} €
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {QUICK_LINKS.map((link, index) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className="animate-fade-in-up rounded-xl border border-neutral-200 bg-white p-5 transition-all duration-150 hover:-translate-y-0.5 hover:border-accent-300 hover:shadow-md"
                                style={{ animationDelay: `${300 + index * 60}ms` }}
                            >
                                <h2 className="font-semibold text-neutral-900">{link.title}</h2>
                                <p className="mt-1 text-sm text-neutral-600">{link.description}</p>
                            </Link>
                        ))}
                    </div>
                </>
            )}
        </AppLayout>
    );
}

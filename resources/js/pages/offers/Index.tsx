import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import StatusBadge from '@/components/ui/StatusBadge';
import { offerStatusTone } from '@/lib/statusTone';
import { type OfferOption } from './OfferFormFields';

interface OfferRow {
    id: number;
    material: OfferOption;
    quantity_tons: number;
    price_per_ton: number;
    generation_year: number;
    province: string;
    municipality: string | null;
    status: OfferOption;
}

interface IndexOffersProps {
    offers: OfferRow[];
}

export default function Index({ offers }: IndexOffersProps) {
    const publish = (offer: OfferRow) => router.post(`/ofertas/${offer.id}/publicar`);
    const pause = (offer: OfferRow) => router.post(`/ofertas/${offer.id}/pausar`);
    const close = (offer: OfferRow) => {
        if (confirm('¿Cerrar esta oferta? No podrás volver a publicarla.')) {
            router.post(`/ofertas/${offer.id}/cerrar`);
        }
    };
    const destroy = (offer: OfferRow) => {
        if (confirm('¿Eliminar este borrador?')) {
            router.delete(`/ofertas/${offer.id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Mis ofertas" />

            <div className="flex animate-fade-in-up items-center justify-between">
                <h1 className="text-2xl font-bold text-neutral-900">Mis ofertas</h1>
                <Link
                    href="/ofertas/crear"
                    className="rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-neutral-900 shadow-sm transition-all duration-150 hover:-translate-y-px hover:bg-accent-400 hover:shadow-md"
                >
                    Publicar oferta
                </Link>
            </div>

            {offers.length === 0 ? (
                <div className="mt-8 animate-fade-in-up rounded-xl border border-dashed border-neutral-300 bg-white px-6 py-12 text-center">
                    <p className="text-neutral-600">Todavía no has publicado ninguna oferta.</p>
                    <Link
                        href="/ofertas/crear"
                        className="mt-3 inline-block font-medium text-accent-700 hover:underline"
                    >
                        Crea la primera →
                    </Link>
                </div>
            ) : (
                <div className="mt-8 animate-fade-in-up overflow-x-auto rounded-xl border border-neutral-200 bg-white">
                    <table className="min-w-full divide-y divide-neutral-200 text-sm">
                        <thead>
                            <tr className="text-left text-neutral-500">
                                <th className="py-3 pr-4 pl-4">Material</th>
                                <th className="py-3 pr-4">Cantidad</th>
                                <th className="py-3 pr-4">Precio</th>
                                <th className="py-3 pr-4">Generación</th>
                                <th className="py-3 pr-4">Ubicación</th>
                                <th className="py-3 pr-4">Estado</th>
                                <th className="py-3 pr-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-100">
                            {offers.map((offer) => (
                                <tr key={offer.id} className="transition-colors hover:bg-neutral-50">
                                    <td className="py-3 pr-4 pl-4 font-medium text-neutral-900">
                                        {offer.material.label}
                                    </td>
                                    <td className="py-3 pr-4">{offer.quantity_tons} t</td>
                                    <td className="py-3 pr-4">{offer.price_per_ton} €/t</td>
                                    <td className="py-3 pr-4">{offer.generation_year}</td>
                                    <td className="py-3 pr-4">
                                        {offer.municipality ? `${offer.municipality}, ` : ''}
                                        {offer.province}
                                    </td>
                                    <td className="py-3 pr-4">
                                        <StatusBadge
                                            label={offer.status.label}
                                            tone={offerStatusTone(offer.status.value)}
                                        />
                                    </td>
                                    <td className="py-3 pr-4">
                                        <div className="flex flex-wrap gap-3">
                                            {offer.status.value !== 'closed' && (
                                                <Link
                                                    href={`/ofertas/${offer.id}/editar`}
                                                    className="text-neutral-700 transition-colors hover:text-accent-700 hover:underline"
                                                >
                                                    Editar
                                                </Link>
                                            )}

                                            {(offer.status.value === 'draft' || offer.status.value === 'paused') && (
                                                <button
                                                    type="button"
                                                    onClick={() => publish(offer)}
                                                    className="text-green-700 transition-colors hover:underline"
                                                >
                                                    Publicar
                                                </button>
                                            )}

                                            {offer.status.value === 'published' && (
                                                <button
                                                    type="button"
                                                    onClick={() => pause(offer)}
                                                    className="text-amber-700 transition-colors hover:underline"
                                                >
                                                    Pausar
                                                </button>
                                            )}

                                            {(offer.status.value === 'published' || offer.status.value === 'paused') && (
                                                <button
                                                    type="button"
                                                    onClick={() => close(offer)}
                                                    className="text-red-700 transition-colors hover:underline"
                                                >
                                                    Cerrar
                                                </button>
                                            )}

                                            {offer.status.value === 'draft' && (
                                                <button
                                                    type="button"
                                                    onClick={() => destroy(offer)}
                                                    className="text-red-700 transition-colors hover:underline"
                                                >
                                                    Eliminar
                                                </button>
                                            )}
                                        </div>
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

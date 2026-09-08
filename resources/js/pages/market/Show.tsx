import { Head, Link, router } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import Button from '@/components/ui/Button';
import InputField from '@/components/ui/InputField';
import OffersMap from '@/components/map/OffersMap';
import { type OfferOption } from '../offers/OfferFormFields';

interface OfferCompany {
    trade_name: string;
    municipality: string | null;
    province: string;
    phone: string | null;
    email: string | null;
    contact_person: string | null;
}

interface OfferDetail {
    id: number;
    material: OfferOption;
    quantity_tons: number;
    price_per_ton: number;
    generation_year: number;
    moisture_percentage: number | null;
    impurities_percentage: number | null;
    province: string;
    municipality: string | null;
    description: string | null;
    company: OfferCompany;
    company_name: string;
    public_latitude: number | null;
    public_longitude: number | null;
}

interface ShowMarketProps {
    offer: OfferDetail;
    canContact: boolean;
    canOrder: boolean;
    availableQuantity: number;
}

export default function Show({ offer, canContact, canOrder, availableQuantity }: ShowMarketProps) {
    const [contacting, setContacting] = useState(false);
    const [quantity, setQuantity] = useState('');
    const [quantityError, setQuantityError] = useState<string | undefined>();
    const [ordering, setOrdering] = useState(false);

    const contact = () => {
        setContacting(true);
        router.post(`/mercado/${offer.id}/contactar`, {}, { onFinish: () => setContacting(false) });
    };

    const placeOrder: FormEventHandler = (e) => {
        e.preventDefault();
        setOrdering(true);
        router.post(
            `/mercado/${offer.id}/pedidos`,
            { quantity_tons: quantity },
            {
                onError: (errors) => setQuantityError(errors.quantity_tons),
                onFinish: () => setOrdering(false),
            },
        );
    };

    return (
        <AppLayout>
            <Head title={`${offer.material.label} — ${offer.company.trade_name}`} />

            <Link href="/mercado" className="text-sm text-neutral-600 transition-colors hover:text-accent-700 hover:underline">
                ← Volver al mercado
            </Link>

            <div className="mt-4 grid animate-fade-in-up grid-cols-1 gap-8 md:grid-cols-3">
                <div className="md:col-span-2">
                    <h1 className="text-2xl font-bold text-neutral-900">{offer.material.label}</h1>
                    <p className="mt-1 text-neutral-600">
                        {offer.municipality ? `${offer.municipality}, ` : ''}
                        {offer.province} · Generación {offer.generation_year}
                    </p>

                    <dl className="mt-6 grid grid-cols-2 gap-4 rounded-xl border border-neutral-200 bg-white p-4 sm:grid-cols-3">
                        <div>
                            <dt className="text-xs text-neutral-500">Cantidad</dt>
                            <dd className="text-lg font-semibold text-neutral-900">{offer.quantity_tons} t</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-neutral-500">Precio</dt>
                            <dd className="text-lg font-semibold text-accent-700">{offer.price_per_ton} €/t</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-neutral-500">Humedad</dt>
                            <dd className="text-lg font-semibold text-neutral-900">
                                {offer.moisture_percentage !== null ? `${offer.moisture_percentage} %` : '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-neutral-500">Impurezas</dt>
                            <dd className="text-lg font-semibold text-neutral-900">
                                {offer.impurities_percentage !== null ? `${offer.impurities_percentage} %` : '—'}
                            </dd>
                        </div>
                    </dl>

                    {offer.description && (
                        <div className="mt-6">
                            <h2 className="text-sm font-medium text-neutral-700">Descripción</h2>
                            <p className="mt-1 whitespace-pre-line text-neutral-600">{offer.description}</p>
                        </div>
                    )}

                    {offer.public_latitude !== null && offer.public_longitude !== null && (
                        <div className="mt-6">
                            <h2 className="text-sm font-medium text-neutral-700">Ubicación aproximada</h2>
                            <OffersMap
                                offers={[
                                    {
                                        id: offer.id,
                                        material: offer.material,
                                        quantity_tons: offer.quantity_tons,
                                        price_per_ton: offer.price_per_ton,
                                        province: offer.province,
                                        municipality: offer.municipality,
                                        company_name: offer.company_name,
                                        public_latitude: offer.public_latitude,
                                        public_longitude: offer.public_longitude,
                                    },
                                ]}
                            />
                        </div>
                    )}
                </div>

                <div className="h-fit rounded-xl border border-neutral-200 bg-white p-4">
                    <h2 className="font-semibold text-neutral-900">{offer.company.trade_name}</h2>
                    <p className="mt-1 text-sm text-neutral-600">
                        {offer.company.municipality ? `${offer.company.municipality}, ` : ''}
                        {offer.company.province}
                    </p>

                    {canContact && (
                        <Button
                            type="button"
                            processing={contacting}
                            onClick={contact}
                            className="mt-4"
                        >
                            Contactar
                        </Button>
                    )}

                    {canOrder && availableQuantity > 0 && (
                        <form onSubmit={placeOrder} className="mt-4 border-t border-neutral-200 pt-4">
                            <p className="text-sm font-medium text-neutral-700">Hacer pedido</p>
                            <p className="mt-1 text-xs text-neutral-500">{availableQuantity} t disponibles</p>
                            <div className="mt-2 flex items-end gap-3">
                                <div className="flex-1">
                                    <InputField
                                        label=""
                                        aria-label="Cantidad (toneladas)"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        max={availableQuantity}
                                        value={quantity}
                                        onChange={(e) => {
                                            setQuantity(e.target.value);
                                            setQuantityError(undefined);
                                        }}
                                        error={quantityError}
                                        placeholder="Toneladas"
                                    />
                                </div>
                                <Button type="submit" processing={ordering} className="px-6">
                                    Pedir
                                </Button>
                            </div>
                        </form>
                    )}

                    {canOrder && availableQuantity <= 0 && (
                        <p className="mt-4 border-t border-neutral-200 pt-4 text-sm text-neutral-500">
                            No queda cantidad disponible para pedir.
                        </p>
                    )}

                    {(offer.company.phone || offer.company.email) && (
                        <p className="mt-1 text-xs text-neutral-500">
                            {canContact
                                ? 'También puedes usar estos datos:'
                                : 'Contacta usando estos datos:'}
                        </p>
                    )}

                    <div className="mt-4 space-y-2 text-sm">
                        {offer.company.contact_person && (
                            <p>
                                <span className="text-neutral-500">Contacto: </span>
                                {offer.company.contact_person}
                            </p>
                        )}
                        {offer.company.phone && (
                            <p>
                                <span className="text-neutral-500">Teléfono: </span>
                                <a href={`tel:${offer.company.phone}`} className="hover:underline">
                                    {offer.company.phone}
                                </a>
                            </p>
                        )}
                        {offer.company.email && (
                            <p>
                                <span className="text-neutral-500">Email: </span>
                                <a href={`mailto:${offer.company.email}`} className="hover:underline">
                                    {offer.company.email}
                                </a>
                            </p>
                        )}
                        {!offer.company.phone && !offer.company.email && (
                            <p className="text-neutral-500">Esta empresa no ha indicado datos de contacto.</p>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

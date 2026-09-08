import { Head, Link, router } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import InputField from '@/components/ui/InputField';
import SelectField from '@/components/ui/Select';
import Button from '@/components/ui/Button';
import OffersMap from '@/components/map/OffersMap';
import { type OfferOption } from '../offers/OfferFormFields';

interface OfferSummary {
    id: number;
    material: OfferOption;
    quantity_tons: number;
    price_per_ton: number;
    generation_year: number;
    province: string;
    municipality: string | null;
    company_name: string;
    public_latitude: number | null;
    public_longitude: number | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    total: number;
}

interface MarketFilters {
    material: string;
    province: string;
    min_price: string;
    max_price: string;
    min_quantity: string;
    [key: string]: string;
}

interface IndexMarketProps {
    offers: Paginated<OfferSummary>;
    filters: MarketFilters;
    materialTypes: OfferOption[];
    provinces: string[];
}

export default function Index({ offers, filters, materialTypes, provinces }: IndexMarketProps) {
    const [data, setData] = useState<MarketFilters>(filters);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        router.get('/mercado', data, { preserveState: true, replace: true });
    };

    const clear = () => {
        const empty: MarketFilters = {
            material: '',
            province: '',
            min_price: '',
            max_price: '',
            min_quantity: '',
        };
        setData(empty);
        router.get('/mercado', empty, { preserveState: true, replace: true });
    };

    const provinceOptions = provinces.map((province) => ({ value: province, label: province }));

    return (
        <AppLayout>
            <Head title="Mercado" />

            <h1 className="animate-fade-in-up text-2xl font-bold text-neutral-900">Mercado de ofertas</h1>
            <p className="mt-2 animate-fade-in-up text-neutral-600" style={{ animationDelay: '60ms' }}>
                Ofertas publicadas por productores en la plataforma.
            </p>

            <form onSubmit={submit} className="mt-6 animate-fade-in-up" style={{ animationDelay: '120ms' }}>
                <details className="rounded-xl border border-neutral-200 bg-white" open>
                    <summary className="cursor-pointer select-none px-4 py-3 text-sm font-medium text-neutral-900">
                        Filtros
                    </summary>

                    <div className="space-y-4 border-t border-neutral-200 px-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <SelectField
                                label="Material"
                                name="material"
                                value={data.material}
                                onChange={(e) => setData({ ...data, material: e.target.value })}
                                options={materialTypes}
                                placeholder="Todos"
                            />
                            <SelectField
                                label="Provincia"
                                name="province"
                                value={data.province}
                                onChange={(e) => setData({ ...data, province: e.target.value })}
                                options={provinceOptions}
                                placeholder="Todas"
                            />
                        </div>

                        <details className="rounded-lg border border-neutral-200 bg-neutral-50">
                            <summary className="cursor-pointer select-none px-3 py-2 text-sm font-medium text-neutral-700">
                                Más filtros
                            </summary>

                            <div className="grid grid-cols-3 gap-4 px-3 py-3">
                                <InputField
                                    label="Precio mín. (€/t)"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="min_price"
                                    value={data.min_price}
                                    onChange={(e) => setData({ ...data, min_price: e.target.value })}
                                />
                                <InputField
                                    label="Precio máx. (€/t)"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="max_price"
                                    value={data.max_price}
                                    onChange={(e) => setData({ ...data, max_price: e.target.value })}
                                />
                                <InputField
                                    label="Cantidad mín. (t)"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="min_quantity"
                                    value={data.min_quantity}
                                    onChange={(e) => setData({ ...data, min_quantity: e.target.value })}
                                />
                            </div>
                        </details>

                        <div className="flex items-center gap-3">
                            <Button type="submit" className="w-auto px-6">
                                Aplicar filtros
                            </Button>
                            <button
                                type="button"
                                onClick={clear}
                                className="text-sm text-neutral-600 transition-colors hover:text-accent-700 hover:underline"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>
                </details>
            </form>

            <p className="mt-6 text-sm text-neutral-500">{offers.total} ofertas encontradas</p>

            {offers.data.length > 0 && <OffersMap offers={offers.data} />}

            {offers.data.length === 0 ? (
                <div className="mt-4 rounded-xl border border-dashed border-neutral-300 bg-white px-6 py-12 text-center text-neutral-600">
                    No hay ofertas que coincidan con estos filtros.
                </div>
            ) : (
                <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {offers.data.map((offer, index) => (
                        <Link
                            key={offer.id}
                            href={`/mercado/${offer.id}`}
                            className="block animate-fade-in-up rounded-xl border border-neutral-200 bg-white p-4 transition-all duration-150 hover:-translate-y-0.5 hover:border-accent-300 hover:shadow-md"
                            style={{ animationDelay: `${Math.min(index, 8) * 40}ms` }}
                        >
                            <div className="flex items-center justify-between">
                                <span className="font-semibold text-neutral-900">{offer.material.label}</span>
                                <span className="text-sm text-neutral-500">{offer.generation_year}</span>
                            </div>
                            <p className="mt-1 text-sm text-neutral-600">{offer.company_name}</p>
                            <p className="mt-3 text-lg font-bold text-accent-700">{offer.price_per_ton} €/t</p>
                            <p className="text-sm text-neutral-600">{offer.quantity_tons} t disponibles</p>
                            <p className="mt-2 text-sm text-neutral-500">
                                {offer.municipality ? `${offer.municipality}, ` : ''}
                                {offer.province}
                            </p>
                        </Link>
                    ))}
                </div>
            )}

            {offers.links.length > 3 && (
                <div className="mt-8 flex flex-wrap gap-2">
                    {offers.links.map((link, index) => (
                        <Link
                            key={index}
                            href={link.url ?? '#'}
                            className={`rounded-lg px-3 py-1 text-sm transition-colors ${
                                link.active
                                    ? 'bg-accent-500 font-semibold text-neutral-900'
                                    : link.url
                                      ? 'text-neutral-700 hover:bg-neutral-100'
                                      : 'cursor-not-allowed text-neutral-300'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            preserveScroll
                        />
                    ))}
                </div>
            )}
        </AppLayout>
    );
}

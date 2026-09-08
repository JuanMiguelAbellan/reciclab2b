import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import AppLayout from '@/layouts/AppLayout';
import Button from '@/components/ui/Button';
import StatusBadge from '@/components/ui/StatusBadge';
import { offerStatusTone } from '@/lib/statusTone';
import OfferFormFields, { type OfferFormData, type OfferOption } from './OfferFormFields';

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
    status: OfferOption;
    exact_latitude: number | null;
    exact_longitude: number | null;
}

interface EditOfferProps {
    offer: OfferDetail;
    materialTypes: OfferOption[];
}

export default function Edit({ offer, materialTypes }: EditOfferProps) {
    const { data, setData, put, processing, errors } = useForm<OfferFormData>({
        material: offer.material.value,
        quantity_tons: String(offer.quantity_tons),
        price_per_ton: String(offer.price_per_ton),
        generation_year: String(offer.generation_year),
        moisture_percentage: offer.moisture_percentage !== null ? String(offer.moisture_percentage) : '',
        impurities_percentage: offer.impurities_percentage !== null ? String(offer.impurities_percentage) : '',
        province: offer.province,
        municipality: offer.municipality ?? '',
        description: offer.description ?? '',
        exact_latitude: offer.exact_latitude !== null ? String(offer.exact_latitude) : '',
        exact_longitude: offer.exact_longitude !== null ? String(offer.exact_longitude) : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/ofertas/${offer.id}`);
    };

    return (
        <AppLayout>
            <Head title="Editar oferta" />

            <h1 className="animate-fade-in-up text-2xl font-bold text-neutral-900">Editar oferta</h1>
            <div className="mt-2 flex animate-fade-in-up items-center gap-2" style={{ animationDelay: '60ms' }}>
                <span className="text-sm text-neutral-500">Estado actual:</span>
                <StatusBadge label={offer.status.label} tone={offerStatusTone(offer.status.value)} />
            </div>

            <form onSubmit={submit} className="mt-8 max-w-xl animate-fade-in-up space-y-4" style={{ animationDelay: '120ms' }}>
                <OfferFormFields data={data} setData={setData} errors={errors} materialTypes={materialTypes} />

                <Button type="submit" processing={processing} className="w-auto px-6">
                    Guardar cambios
                </Button>
            </form>
        </AppLayout>
    );
}

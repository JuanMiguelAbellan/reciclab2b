import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import AppLayout from '@/layouts/AppLayout';
import Button from '@/components/ui/Button';
import OfferFormFields, { type OfferFormData, type OfferOption } from './OfferFormFields';

interface CreateOfferProps {
    materialTypes: OfferOption[];
}

export default function Create({ materialTypes }: CreateOfferProps) {
    const { data, setData, post, processing, errors } = useForm<OfferFormData>({
        material: '',
        quantity_tons: '',
        price_per_ton: '',
        generation_year: String(new Date().getFullYear()),
        moisture_percentage: '',
        impurities_percentage: '',
        province: '',
        municipality: '',
        description: '',
        exact_latitude: '',
        exact_longitude: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/ofertas');
    };

    return (
        <AppLayout>
            <Head title="Publicar oferta" />

            <h1 className="animate-fade-in-up text-2xl font-bold text-neutral-900">Publicar oferta</h1>
            <p className="mt-2 animate-fade-in-up text-neutral-600" style={{ animationDelay: '60ms' }}>
                La oferta se guardará como borrador; podrás publicarla cuando quieras desde el
                listado.
            </p>

            <form onSubmit={submit} className="mt-8 max-w-xl animate-fade-in-up space-y-4" style={{ animationDelay: '120ms' }}>
                <OfferFormFields data={data} setData={setData} errors={errors} materialTypes={materialTypes} />

                <Button type="submit" processing={processing} className="w-auto px-6">
                    Guardar borrador
                </Button>
            </form>
        </AppLayout>
    );
}

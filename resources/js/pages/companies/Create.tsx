import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import AppLayout from '@/layouts/AppLayout';
import Button from '@/components/ui/Button';
import CompanyFormFields, { type CompanyFormData, type CompanyTypeOption } from './CompanyFormFields';

interface CreateCompanyProps {
    companyTypes: CompanyTypeOption[];
}

export default function Create({ companyTypes }: CreateCompanyProps) {
    const { data, setData, post, processing, errors } = useForm<CompanyFormData>({
        trade_name: '',
        legal_name: '',
        tax_id: '',
        company_type: '',
        address: '',
        postal_code: '',
        municipality: '',
        province: '',
        autonomous_community: '',
        phone: '',
        email: '',
        website: '',
        contact_person: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/empresa');
    };

    return (
        <AppLayout>
            <Head title="Crear empresa" />

            <h1 className="animate-fade-in-up text-2xl font-bold text-neutral-900">Datos de tu empresa</h1>
            <p className="mt-2 animate-fade-in-up text-neutral-600" style={{ animationDelay: '60ms' }}>
                Necesitamos estos datos para darte acceso a la plataforma. Tu empresa quedará
                pendiente de aprobación tras enviarlos.
            </p>

            <form onSubmit={submit} className="mt-8 max-w-xl space-y-4">
                <CompanyFormFields data={data} setData={setData} errors={errors} companyTypes={companyTypes} />

                <Button type="submit" processing={processing} className="w-auto px-6">
                    Enviar para aprobación
                </Button>
            </form>
        </AppLayout>
    );
}

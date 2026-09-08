import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import GuestLayout from '@/layouts/GuestLayout';
import InputField from '@/components/ui/InputField';
import Button from '@/components/ui/Button';

interface InvitationDetail {
    email: string;
    company_name: string;
}

interface AcceptInvitationProps {
    invitation: InvitationDetail;
    acceptUrl: string;
}

export default function Accept({ invitation, acceptUrl }: AcceptInvitationProps) {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(acceptUrl);
    };

    return (
        <GuestLayout>
            <Head title="Aceptar invitación" />

            <h1 className="text-xl font-semibold text-neutral-900">Únete a {invitation.company_name}</h1>
            <p className="mt-1 text-sm text-neutral-600">
                Crea tu cuenta con <strong>{invitation.email}</strong> para unirte a la empresa.
            </p>

            <form onSubmit={submit} className="mt-6 space-y-4">
                <InputField
                    label="Nombre"
                    name="first_name"
                    value={data.first_name}
                    onChange={(e) => setData('first_name', e.target.value)}
                    error={errors.first_name}
                    autoFocus
                    required
                />

                <InputField
                    label="Apellidos"
                    name="last_name"
                    value={data.last_name}
                    onChange={(e) => setData('last_name', e.target.value)}
                    error={errors.last_name}
                    required
                />

                <InputField
                    label="Contraseña"
                    type="password"
                    name="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    required
                />

                <InputField
                    label="Confirmar contraseña"
                    type="password"
                    name="password_confirmation"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                    required
                />

                <Button type="submit" processing={processing}>
                    Crear cuenta y unirme
                </Button>
            </form>
        </GuestLayout>
    );
}

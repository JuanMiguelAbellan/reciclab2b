import { Head, useForm, usePage } from '@inertiajs/react';
import GuestLayout from '@/layouts/GuestLayout';
import Button from '@/components/ui/Button';
import Alert from '@/components/ui/Alert';
import { type SharedPageProps } from '@/types';

export default function VerifyEmail() {
    const { flash } = usePage<SharedPageProps>().props;
    const { post, processing } = useForm({});

    const resend = () => {
        post('/email/verification-notification');
    };

    const logout = () => {
        post('/logout');
    };

    return (
        <GuestLayout>
            <Head title="Verifica tu correo" />

            <h1 className="text-xl font-semibold text-neutral-900">Verifica tu correo electrónico</h1>
            <p className="mt-2 text-sm text-neutral-600">
                Antes de continuar, haz clic en el enlace que te hemos enviado por correo electrónico
                para verificar tu dirección.
            </p>

            {flash.status === 'verification-link-sent' && (
                <Alert variant="success">
                    Te hemos enviado un nuevo enlace de verificación al correo indicado en el registro.
                </Alert>
            )}

            <div className="mt-6 space-y-3">
                <Button onClick={resend} processing={processing} className="w-full">
                    Reenviar correo de verificación
                </Button>

                <button
                    type="button"
                    onClick={logout}
                    className="w-full text-center text-sm text-neutral-600 hover:underline"
                >
                    Cerrar sesión
                </button>
            </div>
        </GuestLayout>
    );
}

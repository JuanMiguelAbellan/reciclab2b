import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import GuestLayout from '@/layouts/GuestLayout';
import InputField from '@/components/ui/InputField';
import Button from '@/components/ui/Button';
import Alert from '@/components/ui/Alert';
import { type SharedPageProps } from '@/types';

export default function ForgotPassword() {
    const { flash } = usePage<SharedPageProps>().props;

    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <GuestLayout>
            <Head title="Recuperar contraseña" />

            <h1 className="text-xl font-semibold text-neutral-900">Recuperar contraseña</h1>
            <p className="mt-1 text-sm text-neutral-600">
                Te enviaremos un enlace por correo para restablecer tu contraseña.
            </p>

            {flash.status && <Alert variant="success">{flash.status}</Alert>}

            <form onSubmit={submit} className="mt-6 space-y-4">
                <InputField
                    label="Correo electrónico"
                    type="email"
                    name="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    autoFocus
                    required
                />

                <Button type="submit" processing={processing} className="w-full">
                    Enviar enlace de recuperación
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-neutral-600">
                <Link href="/login" className="font-medium text-accent-700 hover:underline">
                    Volver a iniciar sesión
                </Link>
            </p>
        </GuestLayout>
    );
}

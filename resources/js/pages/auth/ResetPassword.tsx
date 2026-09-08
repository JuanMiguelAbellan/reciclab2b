import { Head, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import GuestLayout from '@/layouts/GuestLayout';
import InputField from '@/components/ui/InputField';
import Button from '@/components/ui/Button';

interface ResetPasswordProps {
    email: string;
    token: string;
}

export default function ResetPassword({ email, token }: ResetPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/reset-password');
    };

    return (
        <GuestLayout>
            <Head title="Restablecer contraseña" />

            <h1 className="text-xl font-semibold text-neutral-900">Restablecer contraseña</h1>

            <form onSubmit={submit} className="mt-6 space-y-4">
                <InputField
                    label="Correo electrónico"
                    type="email"
                    name="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    required
                />

                <InputField
                    label="Nueva contraseña"
                    type="password"
                    name="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    autoFocus
                    required
                />

                <InputField
                    label="Confirmar nueva contraseña"
                    type="password"
                    name="password_confirmation"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                    error={errors.password_confirmation}
                    required
                />

                <Button type="submit" processing={processing} className="w-full">
                    Restablecer contraseña
                </Button>
            </form>
        </GuestLayout>
    );
}

import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import GuestLayout from '@/layouts/GuestLayout';
import InputField from '@/components/ui/InputField';
import Button from '@/components/ui/Button';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <GuestLayout>
            <Head title="Crear cuenta" />

            <h1 className="text-xl font-semibold text-neutral-900">Crear cuenta</h1>
            <p className="mt-1 text-sm text-neutral-600">
                Tu cuenta quedará pendiente de aprobación tras registrarte.
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
                    label="Correo electrónico"
                    type="email"
                    name="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
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

                <Button type="submit" processing={processing} className="w-full">
                    Crear cuenta
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-neutral-600">
                ¿Ya tienes cuenta?{' '}
                <Link href="/login" className="font-medium text-accent-700 hover:underline">
                    Inicia sesión
                </Link>
            </p>
        </GuestLayout>
    );
}

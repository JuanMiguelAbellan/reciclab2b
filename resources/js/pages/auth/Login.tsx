import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import GuestLayout from '@/layouts/GuestLayout';
import InputField from '@/components/ui/InputField';
import Button from '@/components/ui/Button';
import Alert from '@/components/ui/Alert';
import { type SharedPageProps } from '@/types';

export default function Login() {
    const { flash } = usePage<SharedPageProps>().props;

    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <GuestLayout>
            <Head title="Iniciar sesión" />

            <h1 className="text-xl font-semibold text-neutral-900">Iniciar sesión</h1>

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

                <InputField
                    label="Contraseña"
                    type="password"
                    name="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={errors.password}
                    required
                />

                <div className="flex items-center justify-between text-sm">
                    <label className="flex items-center gap-2 text-neutral-600">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="accent-accent-600"
                        />
                        Recordarme
                    </label>
                    <Link href="/forgot-password" className="text-neutral-600 transition-colors hover:text-accent-700 hover:underline">
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>

                <Button type="submit" processing={processing} className="w-full">
                    Entrar
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-neutral-600">
                ¿No tienes cuenta?{' '}
                <Link href="/register" className="font-medium text-accent-700 hover:underline">
                    Regístrate
                </Link>
            </p>
        </GuestLayout>
    );
}

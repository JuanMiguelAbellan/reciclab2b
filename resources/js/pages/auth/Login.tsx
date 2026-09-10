import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { type FormEventHandler } from 'react';
import GuestLayout from '@/layouts/GuestLayout';
import InputField from '@/components/ui/InputField';
import Button from '@/components/ui/Button';
import Alert from '@/components/ui/Alert';
import { type SharedPageProps } from '@/types';

const DEMO_ACCOUNTS = [
    { role: 'Superadmin', email: 'superadmin@reciclab2b.test' },
    { role: 'Admin', email: 'admin@reciclab2b.test' },
    { role: 'Productor', email: 'productor@reciclab2b.test' },
    { role: 'Comprador', email: 'comprador@reciclab2b.test' },
];
const DEMO_PASSWORD = 'password';

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

    const fillDemoAccount = (email: string) => {
        setData({ email, password: DEMO_PASSWORD, remember: data.remember });
    };

    return (
        <GuestLayout>
            <Head title="Iniciar sesión" />

            <h1 className="text-xl font-semibold text-neutral-900">Iniciar sesión</h1>

            <div className="mt-4 rounded-lg border border-accent-200 bg-accent-50/60 p-3 text-sm">
                <p className="font-medium text-neutral-900">Portfolio: cuentas de demostración</p>
                <p className="mt-1 text-neutral-600">
                    Contraseña para todas: <code className="rounded bg-white px-1 py-0.5">{DEMO_PASSWORD}</code>
                </p>
                <ul className="mt-2 space-y-1">
                    {DEMO_ACCOUNTS.map((account) => (
                        <li key={account.email}>
                            <button
                                type="button"
                                onClick={() => fillDemoAccount(account.email)}
                                className="text-left text-accent-700 underline-offset-2 hover:underline"
                            >
                                {account.role}: {account.email}
                            </button>
                        </li>
                    ))}
                </ul>
            </div>

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

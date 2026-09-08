import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/layouts/GuestLayout';

interface ErrorPageProps {
    status: number;
}

const MESSAGES: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Acceso no permitido',
        description: 'No tienes permiso para ver esta página.',
    },
    404: {
        title: 'Página no encontrada',
        description: 'La página que buscas no existe o se ha movido.',
    },
    419: {
        title: 'Página caducada',
        description: 'La sesión de esta página ha expirado. Vuelve a intentarlo.',
    },
    500: {
        title: 'Error del servidor',
        description: 'Algo ha fallado por nuestra parte. Ya se ha registrado el error.',
    },
    503: {
        title: 'Servicio no disponible',
        description: 'La plataforma está en mantenimiento. Vuelve a intentarlo en unos minutos.',
    },
};

export default function ErrorPage({ status }: ErrorPageProps) {
    const { title, description } = MESSAGES[status] ?? {
        title: 'Ha ocurrido un error',
        description: 'Inténtalo de nuevo en unos minutos.',
    };

    return (
        <GuestLayout>
            <Head title={title} />

            <p className="text-sm font-semibold text-accent-600">Error {status}</p>
            <h1 className="mt-1 text-xl font-semibold text-neutral-900">{title}</h1>
            <p className="mt-2 text-sm text-neutral-600">{description}</p>

            <Link
                href="/"
                className="mt-6 inline-flex items-center justify-center rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-neutral-900 shadow-sm transition-all duration-150 ease-out hover:-translate-y-px hover:bg-accent-400 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
            >
                Volver al inicio
            </Link>
        </GuestLayout>
    );
}

import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';

export default function About() {
    return (
        <AppLayout>
            <Head title="Acerca de" />

            <h1 className="text-3xl font-bold text-neutral-900">Acerca de ReciclaB2B</h1>
            <p className="mt-4 max-w-xl text-neutral-600">
                ReciclaB2B es una plataforma privada B2B para el mercado de cartón y plástico
                reciclable. Conecta a generadores de material con compradores, distribuidores,
                almacenes locales e industrias, y cubre todo el proceso en un mismo sitio:
            </p>

            <ul className="mt-6 max-w-xl list-disc space-y-2 pl-5 text-neutral-600">
                <li>Publicar ofertas de producto con cantidad, precio y ubicación.</li>
                <li>Buscar y filtrar las ofertas publicadas por otras empresas.</li>
                <li>Hablar directamente con la otra parte por mensajería en tiempo real.</li>
                <li>Formalizar y seguir el pedido hasta que se completa.</li>
            </ul>

            <p className="mt-6 max-w-xl text-neutral-600">
                El acceso es privado: toda cuenta y toda empresa pasan por una aprobación manual
                antes de poder operar en la plataforma.
            </p>

            <Link
                href="/"
                className="mt-8 inline-block rounded-lg bg-accent-500 px-4 py-2 text-sm font-semibold text-neutral-900 shadow-sm transition-all duration-150 hover:-translate-y-px hover:bg-accent-400 hover:shadow-md"
            >
                Volver al inicio
            </Link>
        </AppLayout>
    );
}

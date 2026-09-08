import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import InteractiveBackground from '@/components/ui/InteractiveBackground';
import { type SharedPageProps } from '@/types';

const FEATURES = [
    {
        title: 'Publica tu material',
        description: 'Los generadores publican sus ofertas de cartón y plástico en minutos, con precio y cantidad.',
        href: '/ofertas/crear',
        icon: (
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M12 3v18m0-18 4 4m-4-4-4 4M5 21h14"
            />
        ),
    },
    {
        title: 'Encuentra en el mercado',
        description: 'Compradores, distribuidores e industrias buscan y filtran ofertas publicadas cerca de ellos.',
        href: '/mercado',
        icon: (
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm9 2-4.35-4.35"
            />
        ),
    },
    {
        title: 'Habla directamente',
        description: 'Contacta y negocia en tiempo real con la otra empresa, sin intermediarios.',
        href: '/mensajes',
        icon: (
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M21 11.5a8.5 8.5 0 0 1-12.9 7.3L3 20l1.2-5.1A8.5 8.5 0 1 1 21 11.5Z"
            />
        ),
    },
];

export default function Welcome() {
    const { auth } = usePage<SharedPageProps>().props;

    return (
        <AppLayout>
            <Head title="Inicio" />

            <InteractiveBackground />

            <div className="relative">
                <section className="py-8 text-center sm:py-16">
                    <h1 className="animate-fade-in-up text-4xl font-bold tracking-tight text-neutral-900 sm:text-5xl">
                        El mercado privado de <span className="text-accent-600">cartón y plástico</span>
                    </h1>
                    <p
                        className="mx-auto mt-5 max-w-xl animate-fade-in-up text-lg text-neutral-600"
                        style={{ animationDelay: '80ms' }}
                    >
                        Conecta productores con compradores, distribuidores, almacenes locales e
                        industrias, en un solo sitio.
                    </p>

                    <div
                        className="mt-8 flex animate-fade-in-up items-center justify-center gap-3"
                        style={{ animationDelay: '160ms' }}
                    >
                        {auth.user ? (
                            <Link
                                href="/dashboard"
                                className="rounded-lg bg-accent-500 px-5 py-2.5 text-sm font-semibold text-neutral-900 shadow-sm transition-all duration-150 hover:-translate-y-px hover:bg-accent-400 hover:shadow-md"
                            >
                                Ir a mi panel
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/register"
                                    className="rounded-lg bg-accent-500 px-5 py-2.5 text-sm font-semibold text-neutral-900 shadow-sm transition-all duration-150 hover:-translate-y-px hover:bg-accent-400 hover:shadow-md"
                                >
                                    Crear cuenta
                                </Link>
                                <Link
                                    href="/login"
                                    className="rounded-lg border border-neutral-300 bg-white px-5 py-2.5 text-sm font-semibold text-neutral-900 transition-colors duration-150 hover:border-neutral-400 hover:bg-neutral-50"
                                >
                                    Iniciar sesión
                                </Link>
                            </>
                        )}
                    </div>
                </section>

                <section className="grid grid-cols-1 gap-6 py-8 sm:grid-cols-3">
                    {FEATURES.map((feature, index) => (
                        <Link
                            key={feature.title}
                            href={feature.href}
                            className="group animate-fade-in-up rounded-xl border border-neutral-200 bg-white/90 p-6 backdrop-blur-sm transition-all duration-150 hover:-translate-y-0.5 hover:border-accent-300 hover:bg-white hover:shadow-md"
                            style={{ animationDelay: `${240 + index * 80}ms` }}
                        >
                            <svg
                                className="h-8 w-8 text-accent-600"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="1.75"
                                aria-hidden="true"
                            >
                                {feature.icon}
                            </svg>
                            <h2 className="mt-4 font-semibold text-neutral-900 group-hover:text-accent-700">
                                {feature.title}
                            </h2>
                            <p className="mt-1.5 text-sm text-neutral-600">{feature.description}</p>
                        </Link>
                    ))}
                </section>
            </div>
        </AppLayout>
    );
}

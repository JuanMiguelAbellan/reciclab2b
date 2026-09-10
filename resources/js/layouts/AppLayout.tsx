import { Link, router, useForm, usePage } from '@inertiajs/react';
import { type PropsWithChildren, useEffect } from 'react';
import Alert from '@/components/ui/Alert';
import { type SharedPageProps } from '@/types';

function Logo() {
    return (
        <Link href="/" className="flex items-center gap-2 text-lg font-semibold text-neutral-900">
            <svg
                className="h-6 w-6 text-accent-600"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.75"
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M12 21c-4.5 0-8-3.5-8-8 0-6 8-11 8-11s8 5 8 11c0 4.5-3.5 8-8 8Z"
                />
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 21V10" />
            </svg>
            ReciclaB2B
        </Link>
    );
}

const navLinkClasses =
    'rounded-full px-3 py-1.5 text-neutral-600 transition-colors duration-150 hover:bg-neutral-100 hover:text-neutral-900';

export default function AppLayout({ children }: PropsWithChildren) {
    const page = usePage<SharedPageProps>();
    const { auth, flash } = page.props;
    const { post } = useForm({});
    const userId = auth.user?.id;

    const logout = () => {
        post('/logout');
    };

    // Keeps the unread-messages badge live even when the user isn't looking
    // at the conversation the message just landed in. The conversation
    // thread itself already updates live via its own channel; this just
    // re-fetches the shared `auth` prop (where the count lives) so the
    // header badge reflects it without a full page reload.
    //
    // `echo` (laravel-echo + pusher-js) is imported dynamically: AppLayout
    // wraps every page, and a static import would pull that dependency
    // into every page's bundle instead of just the pages that need it.
    useEffect(() => {
        if (!userId) {
            return;
        }

        let channelName: string | null = null;

        import('@/echo').then(({ default: echo }) => {
            channelName = `App.Models.User.${userId}`;
            const channel = echo.private(channelName);
            channel.listen('.message.sent', () => {
                router.reload({ only: ['auth'] });
            });
        });

        return () => {
            if (channelName) {
                import('@/echo').then(({ default: echo }) => echo.leave(channelName!));
            }
        };
    }, [userId]);

    return (
        <div className="min-h-screen bg-neutral-50 text-neutral-900">
            <header className="sticky top-0 z-10 border-b border-neutral-200 bg-white/80 backdrop-blur-sm">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                    <Logo />

                    <nav className="flex items-center gap-1 text-sm">
                        {auth.user ? (
                            auth.user.is_staff ? (
                                <>
                                    <a
                                        href="/admin"
                                        className="rounded-full bg-accent-500 px-4 py-1.5 font-semibold text-neutral-900 shadow-sm transition-all duration-150 hover:-translate-y-px hover:bg-accent-400 hover:shadow-md"
                                    >
                                        Panel de administración
                                    </a>
                                    <span className="ml-2 mr-1 hidden text-neutral-500 sm:inline">
                                        {auth.user.full_name}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={logout}
                                        className={navLinkClasses}
                                    >
                                        Cerrar sesión
                                    </button>
                                </>
                            ) : (
                                <>
                                    <Link href="/mercado" className={navLinkClasses}>
                                        Mercado
                                    </Link>
                                    <Link href="/ofertas" className={navLinkClasses}>
                                        Mis ofertas
                                    </Link>
                                    <Link href="/pedidos" className="text-neutral-600 hover:underline">
                                        Pedidos
                                    </Link>
                                    {auth.user.has_company && (
                                        <Link href="/empresa" className={navLinkClasses}>
                                            Mi empresa
                                        </Link>
                                    )}
                                    <Link href="/mensajes" className={`relative ${navLinkClasses}`}>
                                        Mensajes
                                        {auth.user.unread_conversations_count > 0 && (
                                            <span className="ml-1.5 inline-flex h-5 min-w-5 animate-pulse items-center justify-center rounded-full bg-accent-500 px-1.5 text-xs font-semibold text-neutral-900">
                                                {auth.user.unread_conversations_count}
                                            </span>
                                        )}
                                    </Link>
                                    <span className="ml-2 mr-1 hidden text-neutral-500 sm:inline">
                                        {auth.user.full_name}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={logout}
                                        className={navLinkClasses}
                                    >
                                        Cerrar sesión
                                    </button>
                                </>
                            )
                        ) : (
                            <>
                                <Link href="/acerca-de" className={navLinkClasses}>
                                    Acerca de
                                </Link>
                                <Link href="/login" className={navLinkClasses}>
                                    Iniciar sesión
                                </Link>
                                <Link
                                    href="/register"
                                    className="ml-1 rounded-full bg-accent-500 px-4 py-1.5 font-semibold text-neutral-900 shadow-sm transition-all duration-150 hover:-translate-y-px hover:bg-accent-400 hover:shadow-md"
                                >
                                    Crear cuenta
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            <main key={page.url} className="relative z-10 mx-auto max-w-5xl animate-fade-in-up px-6 py-10">
                {flash.success && <Alert variant="success">{flash.success}</Alert>}
                {flash.error && <Alert variant="error">{flash.error}</Alert>}
                {children}
            </main>
        </div>
    );
}

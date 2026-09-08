import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/layouts/GuestLayout';
import Alert from '@/components/ui/Alert';
import StatusBadge from '@/components/ui/StatusBadge';
import { accountStatusTone } from '@/lib/statusTone';

type Status = 'pending' | 'rejected' | 'blocked' | 'inactive';

interface AccountStatusProps {
    scope: 'user' | 'company';
    status: Status;
    statusLabel: string;
    companyName?: string;
}

const USER_MESSAGES: Record<Status, string> = {
    pending:
        'Tu cuenta está pendiente de aprobación. Te avisaremos por correo en cuanto un administrador la revise.',
    rejected: 'Tu solicitud de acceso ha sido rechazada. Contacta con nosotros si crees que es un error.',
    blocked: 'Tu cuenta ha sido bloqueada. Contacta con el administrador para más información.',
    inactive: 'Tu cuenta está inactiva. Contacta con el administrador para reactivarla.',
};

const COMPANY_MESSAGES: Record<Status, string> = {
    pending: 'Está pendiente de aprobación. Te avisaremos por correo en cuanto un administrador la revise.',
    rejected: 'Su solicitud de alta ha sido rechazada. Contacta con nosotros si crees que es un error.',
    blocked: 'Ha sido bloqueada. Contacta con el administrador para más información.',
    inactive: 'Está inactiva. Contacta con el administrador para reactivarla.',
};

export default function AccountStatus({ scope, status, statusLabel, companyName }: AccountStatusProps) {
    const { post, processing } = useForm({});

    const logout = () => {
        post('/logout');
    };

    const title = scope === 'user' ? `Cuenta ${statusLabel.toLowerCase()}` : `Empresa ${statusLabel.toLowerCase()}`;
    const message =
        scope === 'user' ? USER_MESSAGES[status] : `${companyName ?? 'Tu empresa'}: ${COMPANY_MESSAGES[status]}`;

    return (
        <GuestLayout>
            <Head title={title} />

            <div className="flex animate-fade-in-up items-center gap-2">
                <h1 className="text-xl font-semibold text-neutral-900">
                    {scope === 'user' ? 'Estado de la cuenta' : 'Estado de la empresa'}
                </h1>
                <StatusBadge label={statusLabel} tone={accountStatusTone(status)} />
            </div>

            <div className="mt-4 animate-fade-in-up" style={{ animationDelay: '80ms' }}>
                <Alert variant={status === 'pending' ? 'info' : 'error'}>{message}</Alert>
            </div>

            <button
                type="button"
                onClick={logout}
                disabled={processing}
                className="mt-2 w-full text-center text-sm text-neutral-600 transition-colors hover:text-accent-700 hover:underline"
            >
                Cerrar sesión
            </button>
        </GuestLayout>
    );
}

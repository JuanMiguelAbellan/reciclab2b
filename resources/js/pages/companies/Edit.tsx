import { Head, router, useForm } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import Button from '@/components/ui/Button';
import InputField from '@/components/ui/InputField';
import StatusBadge from '@/components/ui/StatusBadge';
import { accountStatusTone } from '@/lib/statusTone';
import CompanyFormFields, { type CompanyFormData, type CompanyTypeOption } from './CompanyFormFields';

interface CompanyDetail {
    id: number;
    trade_name: string;
    legal_name: string;
    tax_id: string;
    company_type: string;
    address: string;
    postal_code: string;
    municipality: string;
    province: string;
    autonomous_community: string;
    phone: string;
    email: string;
    website: string;
    contact_person: string;
    status: { value: string; label: string };
}

interface Member {
    id: number;
    full_name: string;
    email: string;
    is_primary: boolean;
}

interface PendingInvitation {
    id: number;
    email: string;
}

interface EditCompanyProps {
    company: CompanyDetail;
    members: Member[];
    pendingInvitations: PendingInvitation[];
    canEdit: boolean;
    companyTypes: CompanyTypeOption[];
}

export default function Edit({ company, members, pendingInvitations, canEdit, companyTypes }: EditCompanyProps) {
    const { data, setData, put, processing, errors } = useForm<CompanyFormData>({
        trade_name: company.trade_name,
        legal_name: company.legal_name,
        tax_id: company.tax_id,
        company_type: company.company_type,
        address: company.address,
        postal_code: company.postal_code,
        municipality: company.municipality,
        province: company.province,
        autonomous_community: company.autonomous_community,
        phone: company.phone,
        email: company.email,
        website: company.website,
        contact_person: company.contact_person,
    });

    const [memberEmail, setMemberEmail] = useState('');
    const [memberError, setMemberError] = useState<string | undefined>();
    const [addingMember, setAddingMember] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/empresa/${company.id}`);
    };

    const addMember: FormEventHandler = (e) => {
        e.preventDefault();
        setAddingMember(true);
        router.post(
            `/empresa/${company.id}/miembros`,
            { email: memberEmail },
            {
                onSuccess: () => setMemberEmail(''),
                onError: (formErrors) => setMemberError(formErrors.email),
                onFinish: () => setAddingMember(false),
            },
        );
    };

    const removeMember = (member: Member) => {
        if (confirm(`¿Quitar a ${member.full_name} de la empresa?`)) {
            router.delete(`/empresa/${company.id}/miembros/${member.id}`);
        }
    };

    const [inviteEmail, setInviteEmail] = useState('');
    const [inviteError, setInviteError] = useState<string | undefined>();
    const [inviting, setInviting] = useState(false);

    const inviteMember: FormEventHandler = (e) => {
        e.preventDefault();
        setInviting(true);
        router.post(
            `/empresa/${company.id}/invitaciones`,
            { email: inviteEmail },
            {
                onSuccess: () => setInviteEmail(''),
                onError: (formErrors) => setInviteError(formErrors.email),
                onFinish: () => setInviting(false),
            },
        );
    };

    const revokeInvitation = (invitation: PendingInvitation) => {
        if (confirm(`¿Cancelar la invitación a ${invitation.email}?`)) {
            router.delete(`/empresa/${company.id}/invitaciones/${invitation.id}`);
        }
    };

    return (
        <AppLayout>
            <Head title="Mi empresa" />

            <div className="flex animate-fade-in-up items-center gap-3">
                <h1 className="text-2xl font-bold text-neutral-900">Mi empresa</h1>
                <StatusBadge label={company.status.label} tone={accountStatusTone(company.status.value)} />
            </div>

            <form onSubmit={submit} className="mt-8 max-w-xl animate-fade-in-up space-y-4" style={{ animationDelay: '80ms' }}>
                <CompanyFormFields data={data} setData={setData} errors={errors} companyTypes={companyTypes} />

                {canEdit ? (
                    <Button type="submit" processing={processing} className="w-auto px-6">
                        Guardar cambios
                    </Button>
                ) : (
                    <p className="text-sm text-neutral-500">
                        Solo el responsable principal de la empresa puede editar estos datos.
                    </p>
                )}
            </form>

            <div className="mt-12 max-w-xl animate-fade-in-up" style={{ animationDelay: '140ms' }}>
                <h2 className="text-lg font-bold text-neutral-900">Miembros</h2>

                <div className="mt-4 divide-y divide-neutral-200 rounded-xl border border-neutral-200 bg-white">
                    {members.map((member) => (
                        <div key={member.id} className="flex items-center justify-between px-4 py-3">
                            <div>
                                <p className="font-medium text-neutral-900">
                                    {member.full_name}
                                    {member.is_primary && (
                                        <span className="ml-2 rounded-full bg-accent-50 px-2 py-0.5 text-xs text-accent-800">
                                            Responsable principal
                                        </span>
                                    )}
                                </p>
                                <p className="text-sm text-neutral-500">{member.email}</p>
                            </div>
                            {canEdit && !member.is_primary && (
                                <button
                                    type="button"
                                    onClick={() => removeMember(member)}
                                    className="text-sm text-red-700 transition-colors hover:underline"
                                >
                                    Quitar
                                </button>
                            )}
                        </div>
                    ))}
                </div>

                {canEdit && (
                    <form onSubmit={addMember} className="mt-4 flex items-end gap-3">
                        <div className="flex-1">
                            <InputField
                                label="Añadir miembro con cuenta existente"
                                type="email"
                                name="member_email"
                                value={memberEmail}
                                onChange={(e) => {
                                    setMemberEmail(e.target.value);
                                    setMemberError(undefined);
                                }}
                                error={memberError}
                                placeholder="persona@empresa.test"
                            />
                        </div>
                        <Button type="submit" processing={addingMember} className="w-auto px-6">
                            Añadir
                        </Button>
                    </form>
                )}
                {canEdit && (
                    <p className="mt-1 text-xs text-neutral-500">
                        Solo funciona si esa persona ya tiene una cuenta aprobada y sin empresa. Si todavía no tiene
                        cuenta, usa "Invitar a alguien nuevo" más abajo.
                    </p>
                )}
            </div>

            {canEdit && (
                <div className="mt-12 max-w-xl animate-fade-in-up" style={{ animationDelay: '200ms' }}>
                    <h2 className="text-lg font-bold text-neutral-900">Invitar a alguien nuevo</h2>
                    <p className="mt-1 text-sm text-neutral-600">
                        Si la persona todavía no tiene cuenta, invítala por email: recibirá un enlace para
                        registrarse y unirse directamente a la empresa.
                    </p>

                    {pendingInvitations.length > 0 && (
                        <div className="mt-4 divide-y divide-neutral-200 rounded-xl border border-neutral-200 bg-white">
                            {pendingInvitations.map((invitation) => (
                                <div key={invitation.id} className="flex items-center justify-between px-4 py-3">
                                    <div>
                                        <p className="text-sm text-neutral-900">{invitation.email}</p>
                                        <p className="text-xs text-accent-700">Invitación pendiente</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => revokeInvitation(invitation)}
                                        className="text-sm text-red-700 transition-colors hover:underline"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}

                    <form onSubmit={inviteMember} className="mt-4 flex items-end gap-3">
                        <div className="flex-1">
                            <InputField
                                label="Invitar por email"
                                type="email"
                                name="invite_email"
                                value={inviteEmail}
                                onChange={(e) => {
                                    setInviteEmail(e.target.value);
                                    setInviteError(undefined);
                                }}
                                error={inviteError}
                                placeholder="persona@empresa.test"
                            />
                        </div>
                        <Button type="submit" processing={inviting} className="w-auto px-6">
                            Invitar
                        </Button>
                    </form>
                </div>
            )}
        </AppLayout>
    );
}

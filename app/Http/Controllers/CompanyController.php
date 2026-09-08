<?php

namespace App\Http\Controllers;

use App\Actions\Companies\AddCompanyMemberAction;
use App\Actions\Companies\CreateCompanyAction;
use App\Actions\Companies\InviteCompanyMemberAction;
use App\Actions\Companies\RemoveCompanyMemberAction;
use App\Actions\Companies\RevokeCompanyInvitationAction;
use App\Actions\Companies\UpdateCompanyAction;
use App\Enums\CompanyType;
use App\Http\Requests\AddCompanyMemberRequest;
use App\Http\Requests\InviteCompanyMemberRequest;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Company::class);

        return Inertia::render('companies/Create', [
            'companyTypes' => $this->companyTypeOptions(),
        ]);
    }

    public function store(StoreCompanyRequest $request, CreateCompanyAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return redirect()->route('account.status');
    }

    public function edit(Request $request): Response
    {
        $company = $request->user()->companies()->firstOrFail();

        Gate::authorize('view', $company);

        $company->load(['users', 'invitations' => fn ($query) => $query->whereNull('accepted_at')]);

        return Inertia::render('companies/Edit', [
            'company' => [
                'id' => $company->id,
                'trade_name' => $company->trade_name,
                'legal_name' => $company->legal_name,
                'tax_id' => $company->tax_id,
                'company_type' => $company->company_type->value,
                'address' => $company->address,
                'postal_code' => $company->postal_code,
                'municipality' => $company->municipality,
                'province' => $company->province,
                'autonomous_community' => $company->autonomous_community,
                'phone' => $company->phone,
                'email' => $company->email,
                'website' => $company->website,
                'contact_person' => $company->contact_person,
                'status' => ['value' => $company->status->value, 'label' => $company->status->label()],
            ],
            'members' => $company->users->map(fn (User $member) => [
                'id' => $member->id,
                'full_name' => $member->full_name,
                'email' => $member->email,
                'is_primary' => (bool) $member->pivot->is_primary,
            ]),
            'pendingInvitations' => $company->invitations->map(fn (CompanyInvitation $invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
            ]),
            'canEdit' => $request->user()->can('update', $company),
            'companyTypes' => $this->companyTypeOptions(),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompanyAction $action): RedirectResponse
    {
        $action->handle($company, $request->validated());

        return redirect()->route('companies.edit')->with('success', 'Datos de la empresa actualizados.');
    }

    public function addMember(AddCompanyMemberRequest $request, Company $company, AddCompanyMemberAction $action): RedirectResponse
    {
        $member = User::where('email', $request->validated()['email'])->firstOrFail();

        $action->handle($company, $member);

        return redirect()->route('companies.edit')->with('success', 'Miembro añadido a la empresa.');
    }

    public function removeMember(Request $request, Company $company, User $member, RemoveCompanyMemberAction $action): RedirectResponse
    {
        Gate::authorize('update', $company);

        $action->handle($company, $member);

        return redirect()->route('companies.edit')->with('success', 'Miembro eliminado de la empresa.');
    }

    public function inviteMember(InviteCompanyMemberRequest $request, Company $company, InviteCompanyMemberAction $action): RedirectResponse
    {
        $action->handle($company, $request->user(), $request->validated()['email']);

        return redirect()->route('companies.edit')->with('success', 'Invitación enviada.');
    }

    public function revokeInvitation(Request $request, Company $company, CompanyInvitation $invitation, RevokeCompanyInvitationAction $action): RedirectResponse
    {
        Gate::authorize('update', $company);

        abort_unless($invitation->company_id === $company->id, 404);

        $action->handle($invitation);

        return redirect()->route('companies.edit')->with('success', 'Invitación cancelada.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function companyTypeOptions(): array
    {
        return collect(CompanyType::cases())
            ->map(fn (CompanyType $type) => ['value' => $type->value, 'label' => $type->label()])
            ->all();
    }
}

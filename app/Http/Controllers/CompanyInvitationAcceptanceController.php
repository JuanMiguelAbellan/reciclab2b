<?php

namespace App\Http\Controllers;

use App\Actions\Companies\AcceptCompanyInvitationAction;
use App\Http\Requests\AcceptCompanyInvitationRequest;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CompanyInvitationAcceptanceController extends Controller
{
    public function show(Request $request, CompanyInvitation $invitation): Response|RedirectResponse
    {
        if (! $this->isAcceptable($invitation)) {
            return redirect()->route('login')->with('error', 'Esta invitación ya no es válida.');
        }

        return Inertia::render('invitations/Accept', [
            'invitation' => [
                'email' => $invitation->email,
                'company_name' => $invitation->company->trade_name,
            ],
            // The signature lives in this exact URL's query string, so the
            // form must submit back to it verbatim for "signed" to pass.
            'acceptUrl' => $request->fullUrl(),
        ]);
    }

    public function store(AcceptCompanyInvitationRequest $request, CompanyInvitation $invitation, AcceptCompanyInvitationAction $action): RedirectResponse
    {
        abort_unless($this->isAcceptable($invitation), 404);

        $user = $action->handle($invitation, $request->validated());

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    private function isAcceptable(CompanyInvitation $invitation): bool
    {
        return $invitation->isPending() && ! User::where('email', $invitation->email)->exists();
    }
}

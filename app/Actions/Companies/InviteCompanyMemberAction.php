<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Notifications\CompanyInvitationNotification;
use Illuminate\Support\Facades\Notification;

class InviteCompanyMemberAction
{
    /**
     * Create (or refresh) a pending invitation for an email that doesn't
     * have an account yet, and email them a signed link to accept it.
     * Eligibility is already checked by InviteCompanyMemberRequest.
     */
    public function handle(Company $company, User $invitedBy, string $email): CompanyInvitation
    {
        $invitation = CompanyInvitation::where('company_id', $company->id)
            ->where('email', $email)
            ->first() ?? new CompanyInvitation(['email' => $email]);

        $invitation->company_id = $company->id;
        $invitation->invited_by = $invitedBy->id;
        $invitation->accepted_at = null;
        $invitation->save();

        Notification::route('mail', $email)
            ->notify(new CompanyInvitationNotification($invitation));

        return $invitation;
    }
}

<?php

namespace App\Actions\Companies;

use App\Models\CompanyInvitation;
use RuntimeException;

class RevokeCompanyInvitationAction
{
    /**
     * Delete a pending invitation. An already-accepted one can't be
     * revoked — the member is already in, use RemoveCompanyMemberAction.
     */
    public function handle(CompanyInvitation $invitation): void
    {
        if (! $invitation->isPending()) {
            throw new RuntimeException('Esta invitación ya fue aceptada.');
        }

        $invitation->delete();
    }
}

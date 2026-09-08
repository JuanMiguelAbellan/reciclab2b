<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Models\User;

class AddCompanyMemberAction
{
    /**
     * Attach an existing, approved, company-less user as a non-primary
     * member. Eligibility (approved, no company yet) is already checked by
     * AddCompanyMemberRequest.
     */
    public function handle(Company $company, User $member): void
    {
        $company->users()->attach($member, ['is_primary' => false]);
    }
}

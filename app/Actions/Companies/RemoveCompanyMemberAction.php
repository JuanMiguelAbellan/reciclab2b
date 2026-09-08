<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Models\User;
use RuntimeException;

class RemoveCompanyMemberAction
{
    /**
     * Detach a non-primary member from the company. The primary contact
     * cannot be removed this way — the company would be left without an
     * owner.
     */
    public function handle(Company $company, User $member): void
    {
        $isPrimary = $company->users()
            ->whereKey($member->id)
            ->wherePivot('is_primary', true)
            ->exists();

        if ($isPrimary) {
            throw new RuntimeException('No se puede eliminar al responsable principal de la empresa.');
        }

        $company->users()->detach($member);
    }
}

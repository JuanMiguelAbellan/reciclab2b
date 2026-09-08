<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        if ($user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value])) {
            return true;
        }

        return $user->companies()->whereKey($company->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // A user belongs to at most one company at a time — every other
        // place that resolves "the user's company" (User::company(),
        // AddCompanyMemberRequest) assumes this. Without this check, a user
        // who already belongs to a company could self-service another one
        // via /empresa/crear and end up in two at once.
        return $user->isApproved() && $user->companies()->doesntExist();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        if ($user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value])) {
            return true;
        }

        return $user->companies()
            ->whereKey($company->id)
            ->wherePivot('is_primary', true)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        return $user->hasRole(RoleName::SuperAdmin->value);
    }

    /**
     * Determine whether the user can approve or reject the model.
     */
    public function approve(User $user, Company $company): bool
    {
        return $user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value]);
    }
}

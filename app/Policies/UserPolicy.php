<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

class UserPolicy
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
    public function view(User $user, User $model): bool
    {
        return $user->is($model) || $user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->is($model) || $user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value]);
    }

    /**
     * Determine whether the user can approve, reject or block the model.
     */
    public function approve(User $user, User $model): bool
    {
        return ! $user->is($model) && $user->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return ! $user->is($model) && $user->hasRole(RoleName::SuperAdmin->value);
    }
}

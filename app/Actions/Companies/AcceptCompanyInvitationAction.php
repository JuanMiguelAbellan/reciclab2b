<?php

namespace App\Actions\Companies;

use App\Enums\UserStatus;
use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AcceptCompanyInvitationAction
{
    /**
     * Create the invited user's account, attach them to the inviting
     * company as a non-primary member, and mark the invitation accepted.
     * The account still starts pending like any registration — accepting
     * an invitation only skips the "create your own company" step.
     *
     * @param  array{first_name: string, last_name: string, password: string}  $data
     */
    public function handle(CompanyInvitation $invitation, array $data): User
    {
        $user = DB::transaction(function () use ($invitation, $data) {
            $user = new User([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $invitation->email,
                'password' => Hash::make($data['password']),
            ]);
            $user->status = UserStatus::Pending;
            $user->save();

            $invitation->company->users()->attach($user, ['is_primary' => false]);

            $invitation->accepted_at = now();
            $invitation->save();

            return $user;
        });

        event(new Registered($user));

        return $user;
    }
}

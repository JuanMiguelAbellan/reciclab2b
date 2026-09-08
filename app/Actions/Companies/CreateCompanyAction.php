<?php

namespace App\Actions\Companies;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateCompanyAction
{
    /**
     * Create a company from user-submitted data and attach the creator as its
     * primary contact. New self-registered companies always start pending.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $owner, array $data): Company
    {
        return DB::transaction(function () use ($owner, $data) {
            $company = new Company($data);
            $company->status = CompanyStatus::Pending;
            $company->save();

            $company->users()->attach($owner, ['is_primary' => true]);

            return $company;
        });
    }
}

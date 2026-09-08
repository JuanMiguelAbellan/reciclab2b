<?php

namespace App\Actions\Companies;

use App\Models\Company;

class UpdateCompanyAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Company $company, array $data): Company
    {
        $company->update($data);

        return $company;
    }
}

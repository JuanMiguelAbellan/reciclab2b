<?php

namespace Tests\Feature\Companies;

use App\Enums\RoleName;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_view_their_own_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($user, ['is_primary' => true]);

        $this->assertTrue($user->can('view', $company));
    }

    public function test_a_user_cannot_view_a_company_they_do_not_belong_to(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->assertFalse($user->can('view', $company));
    }

    public function test_only_the_primary_member_can_update_the_company(): void
    {
        $primary = User::factory()->create();
        $secondary = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($primary, ['is_primary' => true]);
        $company->users()->attach($secondary, ['is_primary' => false]);

        $this->assertTrue($primary->can('update', $company));
        $this->assertFalse($secondary->can('update', $company));
    }

    public function test_an_admin_can_view_and_update_any_company(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);
        $company = Company::factory()->create();

        $this->assertTrue($admin->can('view', $company));
        $this->assertTrue($admin->can('update', $company));
        $this->assertTrue($admin->can('approve', $company));
    }
}

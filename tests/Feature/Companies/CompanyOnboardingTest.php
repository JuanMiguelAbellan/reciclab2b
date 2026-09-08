<?php

namespace Tests\Feature\Companies;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\RoleName;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_approved_user_without_a_company_is_redirected_to_create_one(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('companies.create'));
    }

    public function test_a_user_can_create_a_company_and_it_starts_pending(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/empresa', [
            'trade_name' => 'Reciclajes Test',
            'legal_name' => 'Reciclajes Test S.L.',
            'tax_id' => 'B12345678',
            'company_type' => CompanyType::Producer->value,
        ]);

        $response->assertRedirect(route('account.status'));

        $company = Company::where('tax_id', 'B12345678')->first();
        $this->assertNotNull($company);
        $this->assertSame(CompanyStatus::Pending, $company->status);
        $this->assertTrue($user->companies()->whereKey($company->id)->wherePivot('is_primary', true)->exists());
    }

    public function test_a_user_who_already_belongs_to_a_company_cannot_create_another_one(): void
    {
        $user = User::factory()->create();
        $existingCompany = Company::factory()->create();
        $existingCompany->users()->attach($user, ['is_primary' => true]);

        $this->actingAs($user)->get('/empresa/crear')->assertForbidden();

        $response = $this->actingAs($user)->post('/empresa', [
            'trade_name' => 'Segunda empresa',
            'legal_name' => 'Segunda empresa S.L.',
            'tax_id' => 'B00000001',
            'company_type' => CompanyType::Producer->value,
        ]);

        $response->assertForbidden();
        $this->assertSame(1, $user->companies()->count());
        $this->assertNull(Company::where('tax_id', 'B00000001')->first());
    }

    public function test_a_user_with_a_pending_company_cannot_access_the_dashboard(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->pending()->create();
        $company->users()->attach($user, ['is_primary' => true]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('account.status'));
    }

    public function test_a_user_with_an_approved_company_can_access_the_dashboard(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($user, ['is_primary' => true]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_staff_roles_do_not_need_a_company_to_access_the_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
    }

    public function test_tax_id_must_be_unique(): void
    {
        Company::factory()->create(['tax_id' => 'B99999999']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/empresa', [
            'trade_name' => 'Otra empresa',
            'legal_name' => 'Otra empresa S.L.',
            'tax_id' => 'B99999999',
            'company_type' => CompanyType::Producer->value,
        ]);

        $response->assertSessionHasErrors('tax_id');
    }
}

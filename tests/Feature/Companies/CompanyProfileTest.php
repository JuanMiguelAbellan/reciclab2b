<?php

namespace Tests\Feature\Companies;

use App\Enums\CompanyType;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $primary;

    protected User $secondary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->primary = User::factory()->create();
        $this->company->users()->attach($this->primary, ['is_primary' => true]);

        $this->secondary = User::factory()->create();
        $this->company->users()->attach($this->secondary, ['is_primary' => false]);
    }

    public function test_the_primary_member_can_view_and_edit_the_company(): void
    {
        $response = $this->actingAs($this->primary)->get('/empresa');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('companies/Edit')
            ->where('canEdit', true)
            ->where('company.id', $this->company->id)
        );
    }

    public function test_a_secondary_member_can_view_but_not_edit_the_company(): void
    {
        $this->actingAs($this->secondary)
            ->get('/empresa')
            ->assertInertia(fn ($page) => $page->where('canEdit', false));

        $this->actingAs($this->secondary)
            ->put("/empresa/{$this->company->id}", [
                'trade_name' => 'Otro nombre',
                'legal_name' => $this->company->legal_name,
                'tax_id' => $this->company->tax_id,
                'company_type' => $this->company->company_type->value,
                'country' => 'España',
            ])
            ->assertForbidden();
    }

    public function test_the_primary_member_can_update_company_data(): void
    {
        $response = $this->actingAs($this->primary)->put("/empresa/{$this->company->id}", [
            'trade_name' => 'Nuevo nombre comercial',
            'legal_name' => $this->company->legal_name,
            'tax_id' => $this->company->tax_id,
            'company_type' => CompanyType::Distributor->value,
            'phone' => '600123456',
        ]);

        $response->assertRedirect('/empresa');
        $this->company->refresh();
        $this->assertSame('Nuevo nombre comercial', $this->company->trade_name);
        $this->assertSame(CompanyType::Distributor, $this->company->company_type);
        $this->assertSame('600123456', $this->company->phone);
    }

    public function test_the_primary_member_can_add_an_eligible_user_as_a_member(): void
    {
        $newUser = User::factory()->create(['status' => UserStatus::Approved]);

        $response = $this->actingAs($this->primary)->post("/empresa/{$this->company->id}/miembros", [
            'email' => $newUser->email,
        ]);

        $response->assertRedirect('/empresa');
        $this->assertTrue($this->company->users()->whereKey($newUser->id)->exists());
        $this->assertFalse(
            $this->company->users()->whereKey($newUser->id)->wherePivot('is_primary', true)->exists()
        );
    }

    public function test_cannot_add_a_member_who_already_belongs_to_a_company(): void
    {
        $otherCompany = Company::factory()->create();
        $busyUser = User::factory()->create(['status' => UserStatus::Approved]);
        $otherCompany->users()->attach($busyUser, ['is_primary' => true]);

        $response = $this->actingAs($this->primary)->post("/empresa/{$this->company->id}/miembros", [
            'email' => $busyUser->email,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse($this->company->users()->whereKey($busyUser->id)->exists());
    }

    public function test_cannot_add_a_member_with_an_unknown_email(): void
    {
        $this->actingAs($this->primary)
            ->post("/empresa/{$this->company->id}/miembros", ['email' => 'nadie@ejemplo.test'])
            ->assertSessionHasErrors('email');
    }

    public function test_the_primary_member_can_remove_a_secondary_member(): void
    {
        $response = $this->actingAs($this->primary)
            ->delete("/empresa/{$this->company->id}/miembros/{$this->secondary->id}");

        $response->assertRedirect('/empresa');
        $this->assertFalse($this->company->users()->whereKey($this->secondary->id)->exists());
    }

    public function test_a_secondary_member_cannot_remove_anyone(): void
    {
        $this->actingAs($this->secondary)
            ->delete("/empresa/{$this->company->id}/miembros/{$this->primary->id}")
            ->assertForbidden();
    }
}

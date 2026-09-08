<?php

namespace Tests\Feature\Offers;

use App\Enums\RoleName;
use App\Models\Company;
use App\Models\Offer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_primary_member_of_an_approved_company_can_create_offers(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($user, ['is_primary' => true]);

        $this->assertTrue($user->can('create', Offer::class));
    }

    public function test_a_non_primary_member_of_an_approved_company_can_create_offers(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($user, ['is_primary' => false]);

        $this->assertTrue($user->can('create', Offer::class));
    }

    public function test_a_member_of_an_unapproved_company_cannot_create_offers(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->pending()->create();
        $company->users()->attach($user, ['is_primary' => true]);

        $this->assertFalse($user->can('create', Offer::class));
    }

    public function test_a_user_without_a_company_cannot_create_offers(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('create', Offer::class));
    }

    public function test_any_member_of_the_owning_company_can_update_an_open_offer(): void
    {
        $company = Company::factory()->create();
        $secondaryMember = User::factory()->create();
        $company->users()->attach($secondaryMember, ['is_primary' => false]);
        $offer = Offer::factory()->for($company)->create();

        $this->assertTrue($secondaryMember->can('update', $offer));
    }

    public function test_a_closed_offer_cannot_be_updated_even_by_its_owner(): void
    {
        $company = Company::factory()->create();
        $member = User::factory()->create();
        $company->users()->attach($member, ['is_primary' => true]);
        $offer = Offer::factory()->for($company)->closed()->create();

        $this->assertFalse($member->can('update', $offer));
    }

    public function test_a_user_from_another_company_cannot_manage_the_offer(): void
    {
        $offer = Offer::factory()->for(Company::factory())->create();
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('update', $offer));
        $this->assertFalse($outsider->can('delete', $offer));
        $this->assertFalse($outsider->can('manage', $offer));
    }

    public function test_only_draft_offers_can_be_deleted(): void
    {
        $company = Company::factory()->create();
        $member = User::factory()->create();
        $company->users()->attach($member, ['is_primary' => true]);
        $draft = Offer::factory()->for($company)->create();
        $published = Offer::factory()->for($company)->published()->create();

        $this->assertTrue($member->can('delete', $draft));
        $this->assertFalse($member->can('delete', $published));
    }

    public function test_an_admin_can_manage_any_offer_without_belonging_to_the_company(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);
        $offer = Offer::factory()->for(Company::factory())->create();

        $this->assertTrue($admin->can('update', $offer));
        $this->assertTrue($admin->can('manage', $offer));
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pending_user_cannot_access_the_dashboard(): void
    {
        $user = User::factory()->pending()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('account.status'));
    }

    public function test_a_blocked_user_cannot_access_the_dashboard(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Blocked]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('account.status'));
    }

    public function test_an_approved_and_verified_user_with_an_approved_company_can_access_the_dashboard(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Approved]);
        $company = Company::factory()->create();
        $company->users()->attach($user, ['is_primary' => true]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_an_approved_user_without_a_company_is_sent_to_create_one(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Approved]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('companies.create'));
    }

    public function test_the_account_status_page_shows_the_users_current_status(): void
    {
        $user = User::factory()->pending()->create();

        $response = $this->actingAs($user)->get('/cuenta/estado');

        $response->assertOk();
    }

    public function test_an_approved_user_without_a_company_visiting_account_status_directly_is_redirected(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Approved]);

        $response = $this->actingAs($user)->get('/cuenta/estado');

        $response->assertRedirect(route('dashboard'));
    }
}

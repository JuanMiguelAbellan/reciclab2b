<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_companys_active_offers_and_pending_orders(): void
    {
        $company = Company::factory()->create();
        $member = User::factory()->create(['status' => UserStatus::Approved]);
        $company->users()->attach($member, ['is_primary' => true]);

        Offer::factory()->for($company)->published()->create();
        Offer::factory()->for($company)->create(); // draft, shouldn't count as active

        $buyerCompany = Company::factory()->create();
        $offer = Offer::factory()->for($company)->published()->create();
        $order = Order::factory()->for($offer)->create(['buyer_company_id' => $buyerCompany->id]);

        $response = $this->actingAs($member)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('company.trade_name', $company->trade_name)
            ->where('stats.active_offers', 2)
            ->where('stats.pending_orders', 1)
            ->where('pendingOrders.0.id', $order->id)
        );
    }

    public function test_a_non_primary_member_sees_their_companys_dashboard_too(): void
    {
        $company = Company::factory()->create();
        $nonPrimaryMember = User::factory()->create(['status' => UserStatus::Approved]);
        $company->users()->attach($nonPrimaryMember, ['is_primary' => false]);

        Offer::factory()->for($company)->published()->create();

        $response = $this->actingAs($nonPrimaryMember)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('company.trade_name', $company->trade_name)
            ->where('stats.active_offers', 1)
        );
    }

    public function test_a_staff_user_without_a_company_sees_a_minimal_dashboard(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create(['status' => UserStatus::Approved]);
        $admin->assignRole(RoleName::Admin->value);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('company', null)
        );
    }
}

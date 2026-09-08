<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_an_approved_superadmin_can_access_the_admin_panel(): void
    {
        $superadmin = User::factory()->create(['status' => UserStatus::Approved]);
        $superadmin->assignRole(RoleName::SuperAdmin->value);

        $response = $this->actingAs($superadmin)->get('/admin/users');

        $response->assertOk();
    }

    public function test_an_approved_admin_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->create(['status' => UserStatus::Approved]);
        $admin->assignRole(RoleName::Admin->value);

        $response = $this->actingAs($admin)->get('/admin/companies');

        $response->assertOk();
    }

    public function test_a_producer_cannot_access_the_admin_panel(): void
    {
        $producer = User::factory()->create(['status' => UserStatus::Approved]);
        $producer->assignRole(RoleName::Producer->value);

        $response = $this->actingAs($producer)->get('/admin/users');

        $response->assertForbidden();
    }

    public function test_a_pending_superadmin_cannot_access_the_admin_panel(): void
    {
        $superadmin = User::factory()->pending()->create();
        $superadmin->assignRole(RoleName::SuperAdmin->value);

        $response = $this->actingAs($superadmin)->get('/admin/users');

        $response->assertForbidden();
    }

    public function test_a_user_without_a_role_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Approved]);

        $response = $this->actingAs($user)->get('/admin/users');

        $response->assertForbidden();
    }
}

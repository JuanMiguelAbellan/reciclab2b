<?php

namespace Tests\Feature\Admin;

use App\Enums\CompanyStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
use App\Models\User;
use App\Notifications\CompanyStatusChangedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyApprovalActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['status' => UserStatus::Approved]);
        $this->admin->assignRole(RoleName::Admin->value);
    }

    public function test_an_admin_can_approve_a_pending_company(): void
    {
        Notification::fake();

        $pending = Company::factory()->pending()->create();
        $primary = User::factory()->create();
        $pending->users()->attach($primary, ['is_primary' => true]);

        Livewire::actingAs($this->admin)
            ->test(ListCompanies::class)
            ->callTableAction('approve', $pending);

        $pending->refresh();
        $this->assertSame(CompanyStatus::Approved, $pending->status);
        $this->assertNotNull($pending->approved_at);
        $this->assertSame($this->admin->id, $pending->approved_by);
        Notification::assertSentTo($primary, CompanyStatusChangedNotification::class);
    }

    public function test_an_admin_can_reject_a_pending_company(): void
    {
        Notification::fake();

        $pending = Company::factory()->pending()->create();
        $primary = User::factory()->create();
        $pending->users()->attach($primary, ['is_primary' => true]);

        Livewire::actingAs($this->admin)
            ->test(ListCompanies::class)
            ->callTableAction('reject', $pending);

        $pending->refresh();
        $this->assertSame(CompanyStatus::Rejected, $pending->status);
        Notification::assertSentTo($primary, CompanyStatusChangedNotification::class);
    }

    public function test_an_admin_can_block_an_approved_company(): void
    {
        Notification::fake();

        $approved = Company::factory()->create();
        $primary = User::factory()->create();
        $approved->users()->attach($primary, ['is_primary' => true]);

        Livewire::actingAs($this->admin)
            ->test(ListCompanies::class)
            ->callTableAction('block', $approved);

        $approved->refresh();
        $this->assertSame(CompanyStatus::Blocked, $approved->status);
        Notification::assertSentTo($primary, CompanyStatusChangedNotification::class);
    }

    public function test_approving_a_company_without_a_primary_member_does_not_error(): void
    {
        Notification::fake();

        $pending = Company::factory()->pending()->create();

        Livewire::actingAs($this->admin)
            ->test(ListCompanies::class)
            ->callTableAction('approve', $pending);

        $this->assertSame(CompanyStatus::Approved, $pending->refresh()->status);
        Notification::assertNothingSent();
    }

    public function test_a_producer_cannot_approve_a_company(): void
    {
        $producer = User::factory()->create(['status' => UserStatus::Approved]);
        $producer->assignRole(RoleName::Producer->value);
        $pending = Company::factory()->pending()->create();

        Livewire::actingAs($producer)
            ->test(ListCompanies::class)
            ->assertForbidden();
    }
}

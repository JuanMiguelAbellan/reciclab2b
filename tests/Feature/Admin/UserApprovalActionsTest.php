<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Notifications\UserStatusChangedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserApprovalActionsTest extends TestCase
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

    public function test_an_admin_can_approve_a_pending_user(): void
    {
        Notification::fake();

        $pending = User::factory()->pending()->create();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('approve', $pending);

        $pending->refresh();
        $this->assertSame(UserStatus::Approved, $pending->status);
        $this->assertNotNull($pending->approved_at);
        $this->assertSame($this->admin->id, $pending->approved_by);
        Notification::assertSentTo($pending, UserStatusChangedNotification::class);
    }

    public function test_an_admin_can_reject_a_pending_user(): void
    {
        Notification::fake();

        $pending = User::factory()->pending()->create();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('reject', $pending);

        $pending->refresh();
        $this->assertSame(UserStatus::Rejected, $pending->status);
        $this->assertNull($pending->approved_at);
        Notification::assertSentTo($pending, UserStatusChangedNotification::class);
    }

    public function test_an_admin_can_block_an_approved_user(): void
    {
        Notification::fake();

        $approved = User::factory()->create(['status' => UserStatus::Approved]);

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->callTableAction('block', $approved);

        $approved->refresh();
        $this->assertSame(UserStatus::Blocked, $approved->status);
        Notification::assertSentTo($approved, UserStatusChangedNotification::class);
    }

    public function test_an_admin_cannot_approve_themselves(): void
    {
        $this->admin->status = UserStatus::Pending;
        $this->admin->save();

        Livewire::actingAs($this->admin)
            ->test(ListUsers::class)
            ->assertTableActionHidden('approve', $this->admin);
    }

    public function test_status_is_not_mass_assignable_through_the_edit_form(): void
    {
        $pending = User::factory()->pending()->create();

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $pending->getRouteKey()])
            ->fillForm(['first_name' => 'Nuevo Nombre'])
            ->call('save');

        $pending->refresh();
        $this->assertSame('Nuevo Nombre', $pending->first_name);
        $this->assertSame(UserStatus::Pending, $pending->status);
    }

    public function test_a_plain_admin_cannot_self_promote_to_superadmin_via_the_roles_field(): void
    {
        $superadminRole = Role::where('name', RoleName::SuperAdmin->value)->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->fillForm(['roles' => [$superadminRole->id]])
            ->call('save');

        $this->assertFalse($this->admin->refresh()->hasRole(RoleName::SuperAdmin->value));
    }
}

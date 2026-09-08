<?php

namespace Tests\Feature\Companies;

use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Notifications\CompanyInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CompanyInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_primary_member_can_invite_a_new_email(): void
    {
        Notification::fake();

        $primary = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($primary, ['is_primary' => true]);

        $response = $this->actingAs($primary)->post("/empresa/{$company->id}/invitaciones", [
            'email' => 'nueva@example.com',
        ]);

        $response->assertRedirect(route('companies.edit'));
        $this->assertDatabaseHas('company_invitations', [
            'company_id' => $company->id,
            'email' => 'nueva@example.com',
        ]);
        Notification::assertSentOnDemand(CompanyInvitationNotification::class);
    }

    public function test_cannot_invite_an_email_that_already_has_an_account(): void
    {
        $primary = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($primary, ['is_primary' => true]);
        User::factory()->create(['email' => 'existente@example.com']);

        $response = $this->actingAs($primary)->post("/empresa/{$company->id}/invitaciones", [
            'email' => 'existente@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_a_secondary_member_cannot_invite_anyone(): void
    {
        $primary = User::factory()->create();
        $secondary = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($primary, ['is_primary' => true]);
        $company->users()->attach($secondary, ['is_primary' => false]);

        $response = $this->actingAs($secondary)->post("/empresa/{$company->id}/invitaciones", [
            'email' => 'nueva@example.com',
        ]);

        $response->assertForbidden();
    }

    public function test_the_primary_member_can_revoke_a_pending_invitation(): void
    {
        $primary = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($primary, ['is_primary' => true]);
        $invitation = CompanyInvitation::factory()->for($company)->create();

        $response = $this->actingAs($primary)->delete("/empresa/{$company->id}/invitaciones/{$invitation->id}");

        $response->assertRedirect(route('companies.edit'));
        $this->assertDatabaseMissing('company_invitations', ['id' => $invitation->id]);
    }

    public function test_visiting_a_valid_signed_invitation_link_shows_the_accept_page(): void
    {
        $company = Company::factory()->create();
        $invitation = CompanyInvitation::factory()->for($company)->create(['email' => 'invitado@example.com']);

        $url = URL::temporarySignedRoute('invitations.accept', now()->addDays(7), ['invitation' => $invitation->id]);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('invitations/Accept')
            ->where('invitation.email', 'invitado@example.com'));
    }

    public function test_a_tampered_invitation_link_is_rejected(): void
    {
        $company = Company::factory()->create();
        $invitation = CompanyInvitation::factory()->for($company)->create();

        $response = $this->get("/invitaciones/{$invitation->id}/aceptar?expires=9999999999&signature=not-valid");

        $response->assertForbidden();
    }

    public function test_accepting_an_invitation_creates_the_account_and_attaches_the_company(): void
    {
        $company = Company::factory()->create();
        $invitation = CompanyInvitation::factory()->for($company)->create(['email' => 'invitado@example.com']);

        $url = URL::temporarySignedRoute('invitations.accept', now()->addDays(7), ['invitation' => $invitation->id]);

        $response = $this->post($url, [
            'first_name' => 'Nueva',
            'last_name' => 'Persona',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'invitado@example.com')->firstOrFail();

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertTrue($company->users()->whereKey($user->id)->wherePivot('is_primary', false)->exists());
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_an_already_accepted_invitation_cannot_be_accepted_again(): void
    {
        $company = Company::factory()->create();
        $invitation = CompanyInvitation::factory()->for($company)->create([
            'email' => 'invitado@example.com',
            'accepted_at' => now(),
        ]);

        $url = URL::temporarySignedRoute('invitations.accept', now()->addDays(7), ['invitation' => $invitation->id]);

        $response = $this->post($url, [
            'first_name' => 'Nueva',
            'last_name' => 'Persona',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertNotFound();
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_is_pending_approval(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'first_name' => 'Ana',
            'last_name' => 'García',
            'email' => 'ana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'ana@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertTrue($this->app['auth']->check());

        Notification::assertSentTo($user, VerifyEmail::class);
        $response->assertRedirect();
    }

    public function test_registration_requires_first_and_last_name(): void
    {
        $response = $this->post('/register', [
            'email' => 'sin-nombre@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['first_name', 'last_name']);
        $this->assertDatabaseMissing('users', ['email' => 'sin-nombre@example.com']);
    }

    public function test_registration_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);

        $response = $this->post('/register', [
            'first_name' => 'Ana',
            'last_name' => 'García',
            'email' => 'existente@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}

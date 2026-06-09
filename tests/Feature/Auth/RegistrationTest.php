<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_register_as_customers_and_must_verify_email(): void
    {
        $this->seedRoles();
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'password' => 'Sup3r$ecret!!',
            'password_confirmation' => 'Sup3r$ecret!!',
            'terms' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('customer'));
        $this->assertTrue($user->isCustomer());
        $this->assertNull($user->email_verified_at);

        // We send a branded verification notification (extends Laravel's VerifyEmail).
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    /**
     * Regression: a failing mail transport must never turn registration into a
     * 500. The send is wrapped in a try/catch that logs and returns false.
     */
    public function test_verification_send_failure_is_swallowed_and_reported(): void
    {
        $this->seedRoles();

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('notify')->andThrow(new \RuntimeException('SMTP unavailable'));

        $this->assertFalse($user->sendEmailVerificationNotification());
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $this->seedRoles();

        $response = $this->from('/register')->post('/register', [
            'name' => 'Weak User',
            'email' => 'weak@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->seedRoles();

        $response = $this->from('/register')->post('/register', [
            'name' => 'No Terms',
            'email' => 'noterms@example.com',
            'password' => 'Sup3r$ecret!!',
            'password_confirmation' => 'Sup3r$ecret!!',
        ]);

        $response->assertSessionHasErrors('terms');
        $this->assertGuest();
    }
}

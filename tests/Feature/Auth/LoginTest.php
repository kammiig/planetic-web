<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = $this->createUser(attributes: ['password' => Hash::make('Sup3r$ecret!!')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Sup3r$ecret!!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = $this->createUser(attributes: ['password' => Hash::make('Sup3r$ecret!!')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_suspended_accounts_cannot_authenticate(): void
    {
        $user = $this->createUser(attributes: [
            'password' => Hash::make('Sup3r$ecret!!'),
            'status' => 'suspended',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Sup3r$ecret!!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->createUser(attributes: ['password' => Hash::make('Sup3r$ecret!!')]);

        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'too many',
            strtolower(session('errors')->first('email'))
        );
    }
}

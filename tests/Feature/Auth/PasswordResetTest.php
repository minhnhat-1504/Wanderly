<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Forgot your password?');
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'traveler@wanderly.test',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'traveler@wanderly.test',
        ]);

        $response->assertSessionHas('status');

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class
        );
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/reset-password/sample-token?email=traveler@wanderly.test');

        $response->assertStatus(200);
        $response->assertSee('Set new password');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'traveler@wanderly.test',
            'password' => Hash::make('OldPassword#2026'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'traveler@wanderly.test',
            'password' => 'NewPassword#2026',
            'password_confirmation' => 'NewPassword#2026',
        ]);

        $response->assertRedirect('/login');
        $this->assertTrue(Hash::check('NewPassword#2026', $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'traveler@wanderly.test',
            'password' => Hash::make('OldPassword#2026'),
        ]);

        $response = $this->from('/reset-password/bad-token')->post('/reset-password', [
            'token' => 'bad-token',
            'email' => 'traveler@wanderly.test',
            'password' => 'NewPassword#2026',
            'password_confirmation' => 'NewPassword#2026',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('OldPassword#2026', $user->fresh()->password));
    }
}

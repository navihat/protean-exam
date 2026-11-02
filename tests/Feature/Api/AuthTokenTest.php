<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_token(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $this->postJson(route('api.auth.login'), [
            'email' => 'student@example.com',
            'password' => 'password',
            'device_name' => 'postman',
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'role']]])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', 'student');
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        User::factory()->create(['email' => 'student@example.com']);

        $this->postJson(route('api.auth.login'), [
            'email' => 'student@example.com',
            'password' => 'wrong-password',
            'device_name' => 'postman',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_token_authenticates_and_logout_revokes_it(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('postman')->plainTextToken;

        $this->withToken($token)->getJson(route('api.me'))
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->withToken($token)->postJson(route('api.auth.logout'))->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_guest_gets_401_on_protected_api(): void
    {
        $this->getJson(route('api.me'))->assertUnauthorized();
    }
}

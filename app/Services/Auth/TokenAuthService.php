<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Issues and revokes Sanctum personal access tokens for API clients (Postman, scripts).
 * Browser pages use the session cookie instead.
 */
class TokenAuthService
{
    /**
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password, string $deviceName): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        // Web logins fire this from the session guard; token logins must do it themselves (T15).
        event(new Login('sanctum', $user, false));

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Revokes the token used for this request. Cookie-authenticated (SPA) requests
     * carry a TransientToken, which has nothing to revoke; they log out via the web route.
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
            event(new Logout('sanctum', $user));
        }
    }
}

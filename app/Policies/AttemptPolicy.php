<?php

namespace App\Policies;

use App\Models\Mongo\Attempt;
use App\Models\User;

/**
 * Only the student who owns an attempt may read it (C5.3).
 * Laravel finds this policy by name (App\Policies\{Model}Policy); routes use `can:view,attempt`.
 */
class AttemptPolicy
{
    public function view(User $user, Attempt $attempt): bool
    {
        return $attempt->user_id === $user->id;
    }
}

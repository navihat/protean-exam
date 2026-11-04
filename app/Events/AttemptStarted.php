<?php

namespace App\Events;

use App\Models\Mongo\Attempt;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A student started a new attempt (T11). RecordActivity logs it as `attempt.started`.
 */
class AttemptStarted
{
    use Dispatchable;

    public function __construct(public readonly Attempt $attempt) {}
}

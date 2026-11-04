<?php

namespace App\Events;

use App\Models\Mongo\Attempt;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An attempt was graded and closed (T12), by the student or because its time ran out.
 * RecordActivity logs it as `attempt.submitted`.
 */
class AttemptSubmitted
{
    use Dispatchable;

    public function __construct(public readonly Attempt $attempt) {}
}

<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * An admin created, updated or deleted an exam or a question (T15).
 * Dispatched by the model observers; RecordActivity turns it into an activity_logs entry.
 */
class ContentChanged
{
    use Dispatchable;

    /**
     * @param  string  $action  `<subject>.<verb>` in past tense, e.g. `exam.created` (C6.3)
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $action,
        public readonly string $subjectType,
        public readonly int $subjectId,
        public readonly array $meta = [],
    ) {}
}

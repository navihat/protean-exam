<?php

namespace App\Http\Resources;

use App\Enums\AttemptStatus;
use App\Models\Mongo\Attempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An attempt while it is being answered: shuffled questions without is_correct.
 *
 * @mixin Attempt
 */
class AttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam' => [
                'id' => $this->exam_id,
                'name' => $this->exam['name'],
                'duration_minutes' => $this->exam['duration_minutes'],
            ],
            'status' => $this->status->value,
            'started_at' => $this->started_at->toIso8601ZuluString(),
            'expires_at' => $this->expires_at->toIso8601ZuluString(),
            // Computed from the server clock on every read; the client only displays it (C5.5).
            'remaining_seconds' => $this->status === AttemptStatus::InProgress
                ? max(0, (int) now()->diffInSeconds($this->expires_at, false))
                : 0,
            'questions' => AttemptQuestionResource::collection($this->snapshot),
            // An object even when empty, so the client always gets {"<question_id>": [...]}.
            'answers' => (object) ($this->answers ?? []),
        ];
    }
}

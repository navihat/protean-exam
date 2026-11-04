<?php

namespace App\Http\Resources;

use App\Models\Mongo\Attempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response of a submission: the score and where to read the full result.
 *
 * @mixin Attempt
 */
class AttemptSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'is_late' => $this->is_late,
            'score' => $this->score,
            'correct_count' => $this->correct_count,
            'total_questions' => $this->total_questions,
            'submitted_at' => $this->submitted_at->toIso8601ZuluString(),
            'result_url' => route('api.attempts.result', $this->id, false),
        ];
    }
}

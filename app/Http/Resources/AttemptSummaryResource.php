<?php

namespace App\Http\Resources;

use App\Models\Mongo\Attempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response of a submission: the score.
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
            'score' => $this->score,
            'correct_count' => $this->correct_count,
            'total_questions' => $this->total_questions,
            'submitted_at' => $this->submitted_at->toIso8601ZuluString(),
        ];
    }
}

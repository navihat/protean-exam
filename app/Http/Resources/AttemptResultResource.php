<?php

namespace App\Http\Resources;

use App\Models\Mongo\Attempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Result of a submitted attempt (T13): per question what was chosen and what was right.
 * Only reachable after submission (AttemptService::result), so is_correct may appear here.
 *
 * @mixin Attempt
 */
class AttemptResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $results = collect($this->results)->keyBy('question_id');

        return [
            'score' => $this->score,
            'correct_count' => $this->correct_count,
            'total_questions' => $this->total_questions,
            'is_late' => $this->is_late,
            'questions' => array_map(fn (array $question) => [
                'question_id' => $question['question_id'],
                'content' => $question['content'],
                'is_correct' => $results[$question['question_id']]['is_correct'],
                'selected_option_ids' => $results[$question['question_id']]['selected'],
                'correct_option_ids' => $results[$question['question_id']]['correct'],
                'options' => $question['options'],
            ], $this->snapshot),
        ];
    }
}

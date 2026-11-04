<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One snapshot question as the student sees it while answering.
 * Built field by field so is_correct can never leak before submission (C5.2, D5).
 */
class AttemptQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'question_id' => $this['question_id'],
            'content' => $this['content'],
            'options' => array_map(fn (array $option) => [
                'option_id' => $option['option_id'],
                'content' => $option['content'],
            ], $this['options']),
        ];
    }
}

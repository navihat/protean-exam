<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Body: {answers?: {"<question_id>": [option_id, ...]}}. Only option ids are accepted,
     * never a score or a correct flag (C5.1). Ids outside the snapshot are ignored by grading (C5.4).
     */
    public function rules(): array
    {
        return [
            'answers' => ['sometimes', 'array'],
            'answers.*' => ['array'],
            'answers.*.*' => ['integer', 'distinct'],
        ];
    }
}

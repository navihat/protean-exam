<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    public const MIN_OPTIONS = 2;

    public const MAX_OPTIONS = 6;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'options' => ['required', 'array', 'min:'.self::MIN_OPTIONS, 'max:'.self::MAX_OPTIONS],
            'options.*.content' => ['required', 'string', 'max:500'],
            'options.*.is_correct' => ['boolean'],
        ];
    }

    /**
     * A question needs at least one correct option (C2.3).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $options = $this->input('options');

                if (is_array($options) && ! collect($options)->contains(fn ($o) => filter_var($o['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))) {
                    $validator->errors()->add('options', 'Câu hỏi phải có ít nhất một đáp án đúng.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'content' => 'nội dung câu hỏi',
            'options' => 'đáp án',
            'options.*.content' => 'nội dung đáp án',
        ];
    }
}

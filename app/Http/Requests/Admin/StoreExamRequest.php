<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Access is already limited to admins by the `role:admin` route middleware.
 */
class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:300'],
            'is_published' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // An unchecked checkbox is simply absent from the form submission.
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên đề',
            'description' => 'mô tả',
            'duration_minutes' => 'thời gian làm bài',
        ];
    }
}

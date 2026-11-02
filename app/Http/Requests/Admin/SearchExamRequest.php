<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SearchExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `string` also rejects ?q[]=x, which would otherwise reach the query as an array.
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }
}

<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FetchLicensesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

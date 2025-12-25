<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProvisionLicenseRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'product_slug' => ['required', 'string'],
            'expires_at' => ['required', 'date'],
            'max_seats' => ['required', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

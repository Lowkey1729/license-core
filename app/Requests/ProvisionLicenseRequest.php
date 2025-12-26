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
            'customer_email' => ['required', 'email'],
            'product_slug' => ['required', 'string'],
            'expires_at' => ['nullable', 'date'],
            'max_seats' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

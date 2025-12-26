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
            'products' => ['required', 'array'],
            'products.*.product_slug' => ['required', 'string'],
            'products.*.expires_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'products.*.max_seats' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

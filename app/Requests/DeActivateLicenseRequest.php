<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeActivateLicenseRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string'],
            'product_slug' => ['required', 'string'],
            'fingerprint' => ['required', 'string'],
            'platform_info' => ['nullable', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

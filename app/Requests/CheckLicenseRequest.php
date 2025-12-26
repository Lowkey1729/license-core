<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckLicenseRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'license_key' => ['required', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

<?php

namespace App\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLicenseStatusRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'email'],
            'expires_at' => ['required', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

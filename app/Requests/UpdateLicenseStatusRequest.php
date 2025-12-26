<?php

namespace App\Requests;

use App\Enums\LicenseActionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLicenseStatusRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(LicenseActionEnum::cases())],
            'expires_at' => ['required_if:action,renew', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'min:2', 'max:100'],
            'logo_url' => ['sometimes', 'nullable', 'url'],
            'settings' => ['sometimes', 'nullable', 'array'],
        ];
    }
}

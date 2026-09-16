<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTotemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $totem = $this->route('totem');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('totems')->where('user_id', $totem->user_id)->ignore($totem),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:4'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }
}

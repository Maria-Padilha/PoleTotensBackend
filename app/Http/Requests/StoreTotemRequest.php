<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTotemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('totems')->where('user_id', $this->user()->id),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:4'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}

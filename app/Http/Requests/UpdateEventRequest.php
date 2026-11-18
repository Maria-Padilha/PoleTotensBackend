<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['sometimes', 'in:draft,scheduled,active,finished,canceled'],
            'access_password' => ['nullable', 'string', 'min:4', 'max:100'],
            'clear_access_password' => ['sometimes', 'boolean'],
            'appearance' => ['nullable', 'array'],
            'appearance.background_url' => ['nullable', 'string', 'max:2048'],
            'appearance.logo_url' => ['nullable', 'string', 'max:2048'],
            'appearance.overlay_opacity' => ['nullable', 'integer', 'between:0,90'],
            'appearance.overlay_tone' => ['nullable', 'in:dark,light'],
            'appearance.title_font' => ['nullable', 'string', 'max:60', 'regex:/^[\pL\d \-]+$/u'],
            'appearance.title_weight' => ['nullable', 'integer', 'between:100,900'],
            'appearance.title_size' => ['nullable', 'integer', 'between:50,250'],
            'appearance.title_spacing' => ['nullable', 'numeric', 'between:-0.1,0.5'],
            'appearance.title_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'appearance.text_font' => ['nullable', 'string', 'max:60', 'regex:/^[\pL\d \-]+$/u'],
            'appearance.text_weight' => ['nullable', 'integer', 'between:100,900'],
            'appearance.text_spacing' => ['nullable', 'numeric', 'between:-0.1,0.5'],
            'appearance.text_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'appearance.accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'appearance.card_style' => ['nullable', 'in:auto,light,dark,glass,fun'],
            'appearance.welcome_text' => ['nullable', 'string', 'max:120'],
        ];
    }
}

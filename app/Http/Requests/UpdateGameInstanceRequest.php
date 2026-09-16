<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGameInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'config' => ['nullable', 'array'],
            'config.theme' => ['nullable', 'string', 'max:50'],
            'config.primary_color' => ['nullable', 'string', 'max:20'],
            'config.background_url' => ['nullable', 'string', 'max:2048'],
            'config.logo_url' => ['nullable', 'string', 'max:2048'],
            'config.icon' => ['nullable', 'string', Rule::in(StoreGameInstanceRequest::ICON_KEYS)],
            'config.headline' => ['nullable', 'string', 'max:255'],
            'config.subtitle' => ['nullable', 'string', 'max:255'],
            'config.start_button_text' => ['nullable', 'string', 'max:100'],
            'config.start_hint' => ['nullable', 'string', 'max:255'],
            'config.timer_seconds' => ['nullable', 'integer', 'min:0'],
            'config.points_per_question' => ['nullable', 'integer', 'min:1'],
            'config.win_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'config.win_message' => ['nullable', 'string', 'max:255'],
            'config.lose_message' => ['nullable', 'string', 'max:255'],
            'config.sound_enabled' => ['nullable', 'boolean'],
            'config.questions' => ['nullable', 'array'],
            'config.questions.*.id' => ['nullable', 'string'],
            'config.questions.*.text' => ['required', 'string', 'max:500'],
            'config.questions.*.image_url' => ['nullable', 'string', 'max:2048'],
            'config.questions.*.options' => ['required', 'array', 'min:2'],
            'config.questions.*.options.*' => ['required', 'string', 'max:255'],
            'config.questions.*.correct_index' => ['required', 'integer', 'min:0'],
        ];
    }
}

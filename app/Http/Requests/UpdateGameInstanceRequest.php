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
            'config.neutral_message' => ['nullable', 'string', 'max:255'],
            'config.sound_enabled' => ['nullable', 'boolean'],
            'config.typography' => ['nullable', 'array'],
            'config.typography.title_font' => ['nullable', 'string', 'max:60', 'regex:/^[\pL\d \-]+$/u'],
            'config.typography.title_weight' => ['nullable', 'integer', 'between:100,900'],
            'config.typography.title_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'config.typography.title_size' => ['nullable', 'integer', 'between:50,250'],
            'config.typography.title_spacing' => ['nullable', 'numeric', 'between:-0.1,0.5'],
            'config.typography.text_font' => ['nullable', 'string', 'max:60', 'regex:/^[\pL\d \-]+$/u'],
            'config.typography.text_weight' => ['nullable', 'integer', 'between:100,900'],
            'config.typography.text_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'config.typography.text_spacing' => ['nullable', 'numeric', 'between:-0.1,0.5'],
            'config.capture_leads' => ['nullable', 'boolean'],
            'config.questions' => ['nullable', 'array'],
            'config.questions.*.id' => ['nullable', 'string'],
            'config.questions.*.text' => ['required', 'string', 'max:500'],
            'config.questions.*.image_url' => ['nullable', 'string', 'max:2048'],
            'config.questions.*.options' => ['required', 'array', 'min:2'],
            'config.questions.*.options.*' => ['required', 'string', 'max:255'],
            'config.questions.*.correct_index' => ['required', 'integer', 'min:0'],
            'config.statements' => ['nullable', 'array'],
            'config.statements.*.id' => ['nullable', 'string'],
            'config.statements.*.text' => ['required', 'string', 'max:500'],
            'config.statements.*.image_url' => ['nullable', 'string', 'max:2048'],
            'config.statements.*.correct_answer' => ['required', 'boolean'],
            'config.words' => ['nullable', 'array'],
            'config.words.*.id' => ['nullable', 'string'],
            'config.words.*.word' => ['required', 'string', 'max:255'],
            'config.words.*.hint' => ['nullable', 'string', 'max:500'],
            'config.words.*.image_url' => ['nullable', 'string', 'max:2048'],
            'config.cards' => ['nullable', 'array'],
            'config.cards.*.id' => ['nullable', 'string'],
            'config.cards.*.image_url' => ['required', 'string', 'max:2048'],
            'config.songs' => ['nullable', 'array'],
            'config.songs.*.id' => ['nullable', 'string'],
            'config.songs.*.title' => ['required', 'string', 'max:255'],
            'config.songs.*.artist' => ['nullable', 'string', 'max:255'],
            'config.songs.*.audio_url' => ['required', 'string', 'max:2048'],
            'config.songs.*.lyrics' => ['nullable', 'string', 'max:50000'],
            'config.songs.*.color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'config.songs.*.offset' => ['nullable', 'numeric', 'between:-10,10'],
            'config.zone' => ['nullable', 'array'],
            'config.zone.lives' => ['nullable', 'integer', 'between:1,5'],
            'config.zone.speed' => ['nullable', 'numeric', 'between:0.5,2'],
            'config.timing' => ['nullable', 'array'],
            'config.timing.min_seconds' => ['nullable', 'numeric', 'between:0.1,5'],
            'config.timing.max_seconds' => ['nullable', 'numeric', 'between:0.1,5'],
            'config.timing.hide_clock' => ['nullable', 'boolean'],
            'config.timing.hide_after' => ['nullable', 'numeric', 'between:0,5'],
            'config.prizes' => ['nullable', 'array'],
            'config.prizes.*.id' => ['nullable', 'string'],
            'config.prizes.*.label' => ['required', 'string', 'max:60'],
            'config.prizes.*.weight' => ['nullable', 'integer', 'min:1', 'max:100'],
            'config.prizes.*.color' => ['nullable', 'string', 'max:20'],
            'config.prizes.*.outcome_type' => ['nullable', 'string', Rule::in(['positive', 'negative', 'neutral'])],
            'config.prizes.*.message' => ['nullable', 'string', 'max:255'],
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameInstance extends Model
{
    protected $fillable = [
        'event_totem_id',
        'user_game_id',
        'title',
        'config',
        'position',
        'is_active',
        'preview_token',
        'preview_opened_at',
        'approved_at',
        'public_token',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'preview_opened_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function eventTotem()
    {
        return $this->belongsTo(EventTotem::class);
    }

    public function userGame()
    {
        return $this->belongsTo(UserGame::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function plays()
    {
        return $this->hasMany(GamePlay::class);
    }

    public function isPreviewExpired(): bool
    {
        if (! $this->preview_opened_at) {
            return false;
        }

        return $this->preview_opened_at->addMinutes(5)->isPast();
    }
}

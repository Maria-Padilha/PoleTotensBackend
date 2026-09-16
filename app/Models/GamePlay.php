<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePlay extends Model
{
    protected $fillable = [
        'game_instance_id',
        'lead_id',
        'player_name',
        'score',
        'metadata',
        'client_uuid',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'played_at' => 'datetime',
        ];
    }

    public function gameInstance()
    {
        return $this->belongsTo(GameInstance::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}

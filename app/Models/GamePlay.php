<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class GamePlay extends Model
{
    protected $fillable = [
        'game_instance_id',
        'lead_id',
        'player_name',
        'score',
        'ranking_position',
        'metadata',
        'client_uuid',
        'photo_token',
        'photo_url',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'played_at' => 'datetime',
        ];
    }

    // Capitaliza sempre, não importa como a pessoa digitou ("MARIA", "maria",
    // "maria da silva") — assim ranking, histórico e recap mostram o nome
    // sempre com a mesma cara. get() também capitaliza pra cobrir jogadas
    // antigas já salvas sem essa normalização.
    protected function playerName(): Attribute
    {
        $capitalize = fn (?string $value) => $value !== null ? mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8') : $value;

        return Attribute::make(get: $capitalize, set: $capitalize);
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

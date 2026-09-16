<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'game_instance_id',
        'name',
        'email',
        'phone',
        'extra',
        'client_uuid',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'extra' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    public function gameInstance()
    {
        return $this->belongsTo(GameInstance::class);
    }

    public function plays()
    {
        return $this->hasMany(GamePlay::class);
    }
}

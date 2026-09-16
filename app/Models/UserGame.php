<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGame extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'source',
        'price_paid',
        'acquired_at',
    ];

    protected function casts(): array
    {
        return [
            'price_paid' => 'decimal:2',
            'acquired_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function gameInstances()
    {
        return $this->hasMany(GameInstance::class);
    }
}

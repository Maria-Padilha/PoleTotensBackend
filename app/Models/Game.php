<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'thumbnail_path',
        'base_price',
        'config_schema',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'config_schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_game');
    }

    public function userGames()
    {
        return $this->hasMany(UserGame::class);
    }

    public function owners()
    {
        return $this->belongsToMany(User::class, 'user_games')
            ->withPivot(['source', 'price_paid', 'acquired_at'])
            ->withTimestamps();
    }
}

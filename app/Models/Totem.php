<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Totem extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'public_token',
        'location',
        'password',
        'status',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_totem')
            ->using(EventTotem::class)
            ->withPivot(['id', 'status', 'activated_at', 'finished_at'])
            ->withTimestamps();
    }

    public function eventTotems()
    {
        return $this->hasMany(EventTotem::class);
    }
}

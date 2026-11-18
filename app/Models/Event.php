<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'client_name',
        'location',
        'starts_at',
        'ends_at',
        'status',
        'access_password',
        'appearance',
    ];

    protected $hidden = [
        'access_password',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'access_password' => 'hashed',
            'appearance' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function totems()
    {
        return $this->belongsToMany(Totem::class, 'event_totem')
            ->using(EventTotem::class)
            ->withPivot(['id', 'status', 'activated_at', 'finished_at'])
            ->withTimestamps();
    }

    public function eventTotems()
    {
        return $this->hasMany(EventTotem::class);
    }

    public function recap()
    {
        return $this->hasOne(Recap::class);
    }
}

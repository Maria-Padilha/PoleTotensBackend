<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EventTotem extends Pivot
{
    public $incrementing = true;

    protected $table = 'event_totem';

    protected $fillable = [
        'event_id',
        'totem_id',
        'status',
        'activated_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function totem()
    {
        return $this->belongsTo(Totem::class);
    }

    public function gameInstances()
    {
        return $this->hasMany(GameInstance::class, 'event_totem_id', 'id');
    }
}

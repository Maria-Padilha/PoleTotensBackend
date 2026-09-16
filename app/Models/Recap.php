<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recap extends Model
{
    protected $fillable = [
        'event_id',
        'data',
        'file_path',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'event_type',
        'event_date',
        'guests',
        'duration_hours',
        'games',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'games' => 'array',
            'event_date' => 'date',
        ];
    }
}

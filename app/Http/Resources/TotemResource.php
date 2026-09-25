<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TotemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'public_token' => $this->public_token,
            'location' => $this->location,
            'status' => $this->status,
            'last_seen_at' => $this->last_seen_at,
            'event_totem' => $this->whenPivotLoaded('event_totem', fn () => [
                'id' => $this->pivot->id,
                'status' => $this->pivot->status,
                'activated_at' => $this->pivot->activated_at,
                'finished_at' => $this->pivot->finished_at,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

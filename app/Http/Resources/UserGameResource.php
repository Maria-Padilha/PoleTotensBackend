<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserGameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'price_paid' => $this->price_paid,
            'acquired_at' => $this->acquired_at,
            'game' => new GameResource($this->whenLoaded('game')),
        ];
    }
}

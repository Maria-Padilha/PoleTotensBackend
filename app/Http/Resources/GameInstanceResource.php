<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_totem_id' => $this->event_totem_id,
            'totem' => $this->whenLoaded('eventTotem', fn () => [
                'id' => $this->eventTotem->totem->id,
                'name' => $this->eventTotem->totem->name,
            ]),
            'title' => $this->title,
            'config' => $this->config ?: (object) [],
            'position' => $this->position,
            'is_active' => $this->is_active,
            'preview_token' => $this->preview_token,
            'preview_opened_at' => $this->preview_opened_at,
            'preview_expired' => $this->isPreviewExpired(),
            'approved_at' => $this->approved_at,
            'game' => $this->whenLoaded('userGame', fn () => new GameResource($this->userGame->game)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

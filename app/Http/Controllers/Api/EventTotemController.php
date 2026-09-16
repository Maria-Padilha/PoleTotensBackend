<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TotemResource;
use App\Models\Event;
use App\Models\Totem;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventTotemController extends Controller
{
    public function index(Event $event)
    {
        $this->authorize('view', $event);

        return TotemResource::collection($event->totems()->get());
    }

    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $request->validate([
            'totem_id' => ['required', 'integer', 'exists:totems,id'],
        ]);

        $totem = Totem::findOrFail($data['totem_id']);
        $this->authorize('view', $totem);

        if ($totem->user_id !== $event->user_id) {
            throw ValidationException::withMessages([
                'totem_id' => 'O totem precisa pertencer ao mesmo usuário do evento.',
            ]);
        }

        if ($event->totems()->where('totem_id', $totem->id)->exists()) {
            throw ValidationException::withMessages([
                'totem_id' => 'Esse totem já está vinculado a este evento.',
            ]);
        }

        $event->totems()->attach($totem->id, ['status' => 'pending']);

        return TotemResource::collection($event->totems()->get());
    }

    public function destroy(Event $event, Totem $totem)
    {
        $this->authorize('update', $event);

        $event->totems()->detach($totem->id);

        return response()->json(status: 204);
    }
}

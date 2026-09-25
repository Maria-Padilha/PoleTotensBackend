<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Event::class);

        $query = Event::query()->with('totems')->latest();

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        return EventResource::collection($query->paginate(20));
    }

    public function store(StoreEventRequest $request)
    {
        $this->authorize('create', Event::class);

        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($request->user()->id, $data['name']);

        $event = $request->user()->events()->create($data);

        return new EventResource($event);
    }

    private function uniqueSlug(int $userId, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Event::where('user_id', $userId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function show(Event $event)
    {
        $this->authorize('view', $event);

        return new EventResource($event->load('totems'));
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $request->validated();

        // string vazia = "não mexe na senha atual" (igual ao totem); o flag
        // explícito é o único jeito de realmente remover a senha existente.
        if ($request->boolean('clear_access_password')) {
            $data['access_password'] = null;
        } elseif (empty($data['access_password'])) {
            unset($data['access_password']);
        }
        unset($data['clear_access_password']);

        $event->update($data);

        return new EventResource($event);
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return response()->json(status: 204);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGameInstanceRequest;
use App\Http\Requests\UpdateGameInstanceRequest;
use App\Http\Resources\GameInstanceResource;
use App\Models\Event;
use App\Models\EventTotem;
use App\Models\GameInstance;
use App\Models\KaraokeSong;
use App\Models\Totem;
use App\Models\UserGame;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class GameInstanceController extends Controller
{
    public function index(Request $request, Event $event, Totem $totem)
    {
        $eventTotem = $this->resolveEventTotem($request, $event, $totem);

        $instances = $eventTotem->gameInstances()
            ->with(['eventTotem.totem', 'userGame.game'])
            ->orderBy('position')
            ->get();

        return GameInstanceResource::collection($instances);
    }

    public function store(StoreGameInstanceRequest $request, Event $event, Totem $totem)
    {
        $eventTotem = $this->resolveEventTotem($request, $event, $totem);

        $userGame = UserGame::where('id', $request->validated('user_game_id'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $instance = $eventTotem->gameInstances()->create([
            'user_game_id' => $userGame->id,
            'title' => $request->validated('title'),
            'config' => $request->validated('config') ?? [],
            'position' => $eventTotem->gameInstances()->count(),
        ]);

        KaraokeSong::syncFromConfig($request->user()->id, $request->validated('config.songs'));

        $instance->load(['eventTotem.totem', 'userGame.game']);

        return new GameInstanceResource($instance);
    }

    public function update(UpdateGameInstanceRequest $request, GameInstance $gameInstance)
    {
        $this->authorizeInstance($request, $gameInstance);

        $gameInstance->update($request->validated());

        KaraokeSong::syncFromConfig($request->user()->id, $request->validated('config.songs'));
        $gameInstance->load(['eventTotem.totem', 'userGame.game']);

        return new GameInstanceResource($gameInstance);
    }

    public function destroy(Request $request, GameInstance $gameInstance)
    {
        $this->authorizeInstance($request, $gameInstance);

        $gameInstance->delete();

        return response()->json(status: 204);
    }

    public function generatePreview(Request $request, GameInstance $gameInstance)
    {
        $this->authorizeInstance($request, $gameInstance);

        $gameInstance->update([
            'preview_token' => Str::random(40),
            'preview_opened_at' => null,
            'approved_at' => null,
        ]);

        return response()->json([
            'preview_token' => $gameInstance->preview_token,
            'preview_path' => "/preview/{$gameInstance->preview_token}",
        ]);
    }

    // Diferente do preview: gera uma vez só e não muda mais (é o link fixo
    // que fica aberto no totem) — se já existir, só devolve o mesmo.
    public function generatePublicLink(Request $request, GameInstance $gameInstance)
    {
        $this->authorizeInstance($request, $gameInstance);

        if (! $gameInstance->public_token) {
            $gameInstance->update(['public_token' => Str::random(40)]);
        }

        return response()->json([
            'public_token' => $gameInstance->public_token,
            'public_path' => "/jogar/{$gameInstance->public_token}",
        ]);
    }

    private function resolveEventTotem(Request $request, Event $event, Totem $totem): EventTotem
    {
        $user = $request->user();

        if (! $user->isAdmin() && $event->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $eventTotem = EventTotem::where('event_id', $event->id)
            ->where('totem_id', $totem->id)
            ->first();

        if (! $eventTotem) {
            throw ValidationException::withMessages([
                'totem_id' => 'Esse totem não está vinculado a este evento.',
            ]);
        }

        return $eventTotem;
    }

    private function authorizeInstance(Request $request, GameInstance $gameInstance): void
    {
        $user = $request->user();
        $event = $gameInstance->eventTotem->event;

        if (! $user->isAdmin() && $event->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}

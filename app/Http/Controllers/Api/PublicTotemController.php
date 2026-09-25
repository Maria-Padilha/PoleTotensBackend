<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTotem;
use App\Models\GameInstance;
use App\Models\GamePlay;
use App\Models\Totem;
use App\Services\EventRankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PublicTotemController extends Controller
{
    // Abre o totem → mostra o(s) evento(s) vinculados e, pra cada um, todos
    // os jogos disponíveis (é ISSO que abre no navegador em modo kiosk).
    // Se o totem tiver senha, os eventos só vêm depois de /unlock — e se um
    // evento específico tiver senha, os jogos DELE só vêm depois de
    // /events/{id}/unlock.
    public function show(string $token)
    {
        $totem = Totem::where('public_token', $token)->firstOrFail();
        $hasPassword = filled($totem->password);

        return response()->json([
            'totem' => ['name' => $totem->name, 'has_password' => $hasPassword],
            'events' => $hasPassword ? null : $this->buildEvents($totem),
        ]);
    }

    public function unlock(Request $request, string $token)
    {
        $totem = Totem::where('public_token', $token)->firstOrFail();
        $data = $request->validate(['password' => ['required', 'string']]);

        if (! $totem->password || ! Hash::check($data['password'], $totem->password)) {
            return response()->json(['message' => 'Senha incorreta.'], 422);
        }

        return response()->json(['events' => $this->buildEvents($totem)]);
    }

    public function unlockEvent(Request $request, string $token, int $eventId)
    {
        $totem = Totem::where('public_token', $token)->firstOrFail();
        $eventTotem = $totem->eventTotems()
            ->where('event_id', $eventId)
            ->with(['event', 'gameInstances.userGame.game'])
            ->firstOrFail();

        $data = $request->validate(['password' => ['required', 'string']]);
        $event = $eventTotem->event;

        if (! $event->access_password || ! Hash::check($data['password'], $event->access_password)) {
            return response()->json(['message' => 'Senha incorreta.'], 422);
        }

        return response()->json(['games' => $this->gamesFor($eventTotem)]);
    }

    // Lista de nomes já usados nesse evento — pra tela "quem vai jogar
    // agora?" oferecer selecionar em vez de digitar de novo.
    public function names(string $token, int $eventId)
    {
        Totem::where('public_token', $token)->firstOrFail();
        $event = Event::findOrFail($eventId);

        $instanceIds = EventRankingService::instanceIdsForEvent($event);

        $seen = [];
        $names = GamePlay::whereIn('game_instance_id', $instanceIds)
            ->whereNotNull('player_name')
            ->orderByDesc('played_at')
            ->get()
            ->pluck('player_name')
            ->filter(function ($name) use (&$seen) {
                $key = mb_strtolower(trim($name));
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;

                return true;
            })
            ->values();

        return response()->json(['names' => $names]);
    }

    public function ranking(string $token, int $eventId)
    {
        Totem::where('public_token', $token)->firstOrFail();
        $event = Event::findOrFail($eventId);

        $instanceIds = EventRankingService::instanceIdsForEvent($event);

        return response()->json(['ranking' => EventRankingService::overallRanking($instanceIds)]);
    }

    // Pro modo "totem parado" (attract mode): fotos recentes + o ranking de
    // CADA jogo separado, e por último o ranking geral do evento.
    public function attract(string $token, int $eventId)
    {
        Totem::where('public_token', $token)->firstOrFail();
        $event = Event::findOrFail($eventId);

        $instanceIds = EventRankingService::instanceIdsForEvent($event);

        $photos = GamePlay::with('gameInstance.userGame.game')
            ->whereIn('game_instance_id', $instanceIds)
            ->whereNotNull('photo_url')
            ->latest('played_at')
            ->take(20)
            ->get()
            ->map(fn (GamePlay $play) => [
                'photo_url' => $play->photo_url,
                'player_name' => $play->player_name,
                'game_name' => $play->gameInstance?->userGame?->game?->name,
                'ranking_position' => $play->ranking_position,
            ]);

        $rankingsByGame = GameInstance::with('userGame.game')
            ->whereIn('id', $instanceIds)
            ->get()
            ->map(function (GameInstance $instance) {
                $ranking = EventRankingService::rankingForInstance($instance->id);

                if (! $ranking) {
                    return null;
                }

                return [
                    'game_name' => $instance->title ?: $instance->userGame->game->name,
                    'ranking' => array_slice($ranking, 0, 5),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'photos' => $photos,
            'rankings_by_game' => $rankingsByGame,
            'overall_ranking' => array_slice(EventRankingService::overallRanking($instanceIds), 0, 5),
        ]);
    }

    private function buildEvents(Totem $totem): array
    {
        $eventTotems = $totem->eventTotems()
            ->whereHas('event', fn ($q) => $q->whereIn('status', ['active', 'scheduled']))
            ->with(['event', 'gameInstances.userGame.game'])
            ->get();

        return $eventTotems->map(function (EventTotem $eventTotem) {
            $event = $eventTotem->event;
            $hasPassword = filled($event->access_password);

            return [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'has_password' => $hasPassword,
                'games' => $hasPassword ? null : $this->gamesFor($eventTotem),
            ];
        })->values()->toArray();
    }

    private function gamesFor(EventTotem $eventTotem): array
    {
        return $eventTotem->gameInstances
            ->where('is_active', true)
            ->values()
            ->map(function (GameInstance $instance) {
                if (! $instance->public_token) {
                    $instance->update(['public_token' => Str::random(40)]);
                }

                return [
                    'id' => $instance->id,
                    'title' => $instance->title,
                    'public_token' => $instance->public_token,
                    'config' => $instance->config ?: (object) [],
                    'game' => [
                        'name' => $instance->userGame->game->name,
                        'slug' => $instance->userGame->game->slug,
                    ],
                ];
            })
            ->toArray();
    }
}

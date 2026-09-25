<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\GameInstance;
use App\Models\GamePlay;
use App\Models\Recap;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RecapController extends Controller
{
    // Painel: status/link do recap já gerado (se houver), sem token na mão.
    public function show(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        $recap = $event->recap;

        if (! $recap) {
            return response()->json(['generated_at' => null]);
        }

        return response()->json([
            'token' => $recap->token,
            'recap_path' => "/recap/{$recap->token}",
            'generated_at' => $recap->generated_at,
            'music_url' => $recap->music_url,
        ]);
    }

    // Gera (ou regenera) o recap agregando as jogadas reais do evento. Sem
    // CPF/lead por enquanto — ranking por nome digitado antes de jogar.
    public function generate(Request $request, Event $event)
    {
        $this->authorizeEvent($request, $event);

        $data = $request->validate([
            'music_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $eventTotemIds = $event->eventTotems()->pluck('id');
        $instanceIds = GameInstance::whereIn('event_totem_id', $eventTotemIds)->pluck('id');

        $plays = GamePlay::with('gameInstance.userGame.game')
            ->whereIn('game_instance_id', $instanceIds)
            ->get();

        $existing = Recap::where('event_id', $event->id)->first();

        $recap = Recap::updateOrCreate(
            ['event_id' => $event->id],
            [
                'token' => $existing->token ?? Str::random(40),
                'data' => $this->buildData($plays),
                'music_url' => $data['music_url'] ?? $existing?->music_url,
                'generated_at' => now(),
            ]
        );

        return response()->json([
            'token' => $recap->token,
            'recap_path' => "/recap/{$recap->token}",
            'generated_at' => $recap->generated_at,
        ]);
    }

    // Tela do recap em si (link público, sem login — pra abrir na TV do
    // evento, no totem ou mandar pro cliente depois).
    public function showPublic(Request $request, string $token)
    {
        $recap = Recap::with('event')->where('token', $token)->firstOrFail();

        return response()->json([
            'event_name' => $recap->event->name,
            'generated_at' => $recap->generated_at,
            'music_url' => $recap->music_url,
            'data' => $recap->data ?: (object) [],
        ]);
    }

    private function buildData(Collection $plays): array
    {
        $normalize = fn (string $name) => mb_strtolower(trim($name));

        $totalPlays = $plays->count();
        $totalPlayers = $plays->pluck('player_name')
            ->filter()
            ->map($normalize)
            ->unique()
            ->count();

        $topGames = $plays
            ->groupBy(fn (GamePlay $play) => $play->gameInstance?->userGame?->game?->name ?? 'Jogo')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->map(fn ($item) => [
                'name' => $item['name'],
                'detail' => 'Jogo mais jogado',
                'value' => $item['count'].' '.($item['count'] === 1 ? 'jogada' : 'jogadas'),
            ])
            ->toArray();

        $topPlayers = $plays
            ->filter(fn (GamePlay $play) => $play->score !== null && filled($play->player_name))
            ->groupBy(fn (GamePlay $play) => $normalize($play->player_name))
            ->map(function (Collection $group) {
                return [
                    'name' => $group->first()->player_name,
                    'score' => (int) $group->sum('score'),
                    'plays' => $group->count(),
                ];
            })
            ->sortByDesc('score')
            ->take(5)
            ->values()
            ->map(fn ($item) => [
                'name' => $item['name'],
                'detail' => $item['plays'].' '.($item['plays'] === 1 ? 'partida' : 'partidas'),
                'value' => $item['score'].' pts',
            ])
            ->toArray();

        // Ranking dentro de cada jogo (não o geral) — pra mostrar "por jogo"
        // no recap, além do ranking geral acima.
        $porJogo = $plays
            ->filter(fn (GamePlay $play) => $play->score !== null && filled($play->player_name))
            ->groupBy(fn (GamePlay $play) => $play->gameInstance?->userGame?->game?->name ?? 'Jogo')
            ->map(function (Collection $group, string $gameName) use ($normalize) {
                $ranking = $group
                    ->groupBy(fn (GamePlay $play) => $normalize($play->player_name))
                    ->map(fn (Collection $g) => [
                        'name' => $g->first()->player_name,
                        'score' => (int) $g->sum('score'),
                    ])
                    ->sortByDesc('score')
                    ->take(5)
                    ->values()
                    ->map(fn ($item) => [
                        'name' => $item['name'],
                        'detail' => $gameName,
                        'value' => $item['score'].' pts',
                    ])
                    ->toArray();

                return ['jogo' => $gameName, 'ranking' => $ranking];
            })
            ->values()
            ->toArray();

        $fotos = $plays
            ->filter(fn (GamePlay $play) => filled($play->photo_url))
            ->map(fn (GamePlay $play) => [
                'player_name' => $play->player_name,
                'game_name' => $play->gameInstance?->userGame?->game?->name,
                'photo_url' => $play->photo_url,
                'ranking_position' => $play->ranking_position,
            ])
            ->values()
            ->toArray();

        return [
            'total_jogadas' => $totalPlays,
            'total_jogadores' => $totalPlayers,
            'jogo_mais_jogado' => $topGames[0]['name'] ?? null,
            'top_jogos' => $topGames,
            'top_jogadores' => $topPlayers,
            'por_jogo' => $porJogo,
            'fotos' => $fotos,
            'resumo' => $totalPlays > 0
                ? "Foram {$totalPlays} jogadas de {$totalPlayers} jogadores durante o evento."
                : 'Ainda não há jogadas registradas neste evento.',
        ];
    }

    private function authorizeEvent(Request $request, Event $event): void
    {
        $user = $request->user();

        if (! $user->isAdmin() && $event->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}

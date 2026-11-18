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
            'event_info' => [
                'starts_at' => $recap->event->starts_at,
                'ends_at' => $recap->event->ends_at,
                'location' => $recap->event->location,
            ],
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
            ->reject(fn (GamePlay $play) => in_array($play->gameInstance?->userGame?->game?->slug, ['karaoke', 'acerte-o-tempo', 'pare-no-alvo'], true))
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

        // quantos jogos diferentes rodaram
        $jogosJogados = $plays
            ->map(fn (GamePlay $play) => $play->gameInstance?->userGame?->game?->name ?? 'Jogo')
            ->unique()
            ->count();

        // a maior pontuação de UMA partida (sem somar nada)
        $melhorJogada = $plays
            ->filter(fn (GamePlay $play) => $play->score !== null && filled($play->player_name))
            ->sortByDesc('score')
            ->first();

        // quem mais jogou (nº de partidas, em qualquer jogo)
        $maisPartidas = $plays
            ->filter(fn (GamePlay $play) => filled($play->player_name))
            ->groupBy(fn (GamePlay $play) => $normalize($play->player_name))
            ->map(fn (Collection $group) => ['name' => $group->first()->player_name, 'count' => $group->count()])
            ->sortByDesc('count')
            ->first();

        return [
            'total_jogadas' => $totalPlays,
            'jogos_jogados' => $jogosJogados,
            'melhor_jogada' => $melhorJogada ? [
                'player_name' => $melhorJogada->player_name,
                'game' => $melhorJogada->gameInstance?->userGame?->game?->name ?? 'Jogo',
                'score' => (int) $melhorJogada->score,
            ] : null,
            'mais_partidas' => $maisPartidas,
            'total_jogadores' => $totalPlayers,
            'jogo_mais_jogado' => $topGames[0]['name'] ?? null,
            'top_jogos' => $topGames,
            'top_jogadores' => $topPlayers,
            'por_jogo' => $porJogo,
            'karaoke' => $this->buildKaraoke($plays),
            'acerte_o_tempo' => $this->buildTiming($plays),
            'pare_no_alvo' => $this->buildTarget($plays),
            'resumo' => $totalPlays > 0
                ? "Foram {$totalPlays} jogadas de {$totalPlayers} jogadores durante o evento."
                : 'Ainda não há jogadas registradas neste evento.',
        ];
    }

    // Destaques do "Pare no Alvo": quem foi mais longe numa tentativa só (sem somar), precisão média
    // e a média de níveis por tentativa.
    private function buildTarget(Collection $plays): ?array
    {
        $attempts = $plays
            ->filter(fn (GamePlay $play) => $play->gameInstance?->userGame?->game?->slug === 'pare-no-alvo'
                && filled($play->player_name)
                && isset($play->metadata['cleared']))
            ->sort(fn ($a, $b) => [(int) $b->metadata['cleared'], (int) $b->score, $a->played_at] <=> [(int) $a->metadata['cleared'], (int) $a->score, $b->played_at])
            ->values();

        if ($attempts->isEmpty()) {
            return null;
        }

        $levels = fn (int $n) => $n.' '.($n === 1 ? 'nível' : 'níveis');

        $best = $attempts->first();

        return [
            'mais_longe' => $attempts->take(5)->map(fn (GamePlay $play) => [
                'name' => $play->player_name,
                'detail' => 'precisão '.(int) ($play->metadata['precision'] ?? 0).'%',
                'value' => $levels((int) $play->metadata['cleared']),
            ])->toArray(),
            'melhor' => [
                'player_name' => $best->player_name,
                'value' => $levels((int) $best->metadata['cleared']),
                'cleared' => (int) $best->metadata['cleared'],
            ],
            'tentativas' => $attempts->count(),
            'media_niveis' => number_format($attempts->avg(fn (GamePlay $p) => (int) $p->metadata['cleared']), 1, ',', ''),
            'precisao_media' => (int) round($attempts->avg(fn (GamePlay $p) => (int) ($p->metadata['precision'] ?? 0))),
        ];
    }

    // Destaques do "Acerte o Tempo": as tentativas mais perto do alvo (sem somar nada), quantas
    // foram "na mosca" (diferença zero) e a precisão média.
    private function buildTiming(Collection $plays): ?array
    {
        $attempts = $plays
            ->filter(fn (GamePlay $play) => $play->gameInstance?->userGame?->game?->slug === 'acerte-o-tempo'
                && filled($play->player_name)
                && isset($play->metadata['diff'], $play->metadata['target'], $play->metadata['stopped']))
            ->sort(fn ($a, $b) => [(float) $a->metadata['diff'], $a->played_at] <=> [(float) $b->metadata['diff'], $b->played_at])
            ->values();

        if ($attempts->isEmpty()) {
            return null;
        }

        $fmt = fn ($n) => number_format((float) $n, 2, ',', '');

        $closest = $attempts->take(5)->map(fn (GamePlay $play) => [
            'name' => $play->player_name,
            'detail' => 'alvo '.$fmt($play->metadata['target']).' s → parou '.$fmt($play->metadata['stopped']).' s',
            'value' => '± '.$fmt($play->metadata['diff']).' s',
        ])->toArray();

        $best = $attempts->first();

        return [
            'mais_perto' => $closest,
            'melhor' => [
                'player_name' => $best->player_name,
                'target' => (float) $best->metadata['target'],
                'stopped' => (float) $best->metadata['stopped'],
                'diff' => (float) $best->metadata['diff'],
                'text' => 'alvo '.$fmt($best->metadata['target']).' s → parou '.$fmt($best->metadata['stopped']).' s',
                'value' => '± '.$fmt($best->metadata['diff']).' s',
            ],
            'na_mosca' => $attempts->filter(fn (GamePlay $p) => (float) $p->metadata['diff'] == 0.0)->count(),
            'tentativas' => $attempts->count(),
            'media' => $fmt($attempts->avg(fn (GamePlay $p) => (float) $p->metadata['diff'])),
        ];
    }

    // Destaques do karaokê: música mais cantada, quem mais cantou (nº de apresentações) e a maior
    // nota de UMA apresentação só (sem somar), com a gravação pra tocar um trecho no recap.
    private function buildKaraoke(Collection $plays): ?array
    {
        $normalize = fn (string $name) => mb_strtolower(trim($name));

        $performances = $plays->filter(fn (GamePlay $play) => $play->gameInstance?->userGame?->game?->slug === 'karaoke'
            && $play->score !== null
            && filled($play->player_name));

        if ($performances->isEmpty()) {
            return null;
        }

        $times = fn (int $n) => $n.' '.($n === 1 ? 'vez' : 'vezes');

        $topSongs = $performances
            ->groupBy(fn (GamePlay $play) => $play->metadata['song'] ?? 'Música')
            ->map(fn (Collection $group, string $song) => [
                'song' => $song,
                'count' => $group->count(),
                'singers' => $group->map(fn (GamePlay $p) => $normalize($p->player_name))->unique()->count(),
            ])
            ->sort(fn ($a, $b) => [$b['count'], $b['singers']] <=> [$a['count'], $a['singers']])
            ->take(5)
            ->values()
            ->map(fn ($item) => [
                'name' => $item['song'],
                'detail' => $item['singers'].' '.($item['singers'] === 1 ? 'pessoa cantou' : 'pessoas cantaram'),
                'value' => $times($item['count']),
            ])
            ->toArray();

        $topSingers = $performances
            ->groupBy(fn (GamePlay $play) => $normalize($play->player_name))
            ->map(fn (Collection $group) => [
                'name' => $group->first()->player_name,
                'count' => $group->count(),
                'best' => (int) $group->max('score'),
            ])
            ->sort(fn ($a, $b) => [$b['count'], $b['best']] <=> [$a['count'], $a['best']])
            ->take(5)
            ->values()
            ->map(fn ($item) => [
                'name' => $item['name'],
                'detail' => 'Melhor nota: '.$item['best'],
                'value' => $times($item['count']),
            ])
            ->toArray();

        // a maior nota de uma apresentação (empate: a que aconteceu primeiro)
        $best = $performances->sort(fn ($a, $b) => [$b->score, $a->played_at] <=> [$a->score, $b->played_at])->first();
        $song = collect($best->gameInstance?->config['songs'] ?? [])
            ->first(fn ($item) => ($item['id'] ?? null) === ($best->metadata['song_id'] ?? '') || ($item['title'] ?? null) === ($best->metadata['song'] ?? ''));

        return [
            'musicas_mais_cantadas' => $topSongs,
            'quem_mais_cantou' => $topSingers,
            'maior_nota' => [
                'player_name' => $best->player_name,
                'song' => $best->metadata['song'] ?? 'Música',
                'score' => (int) $best->score,
                'recording_url' => $best->recording_url,
                'audio_url' => $song['audio_url'] ?? null,
                'snippet_start' => $this->firstLyricSeconds($song['lyrics'] ?? ''),
            ],
        ];
    }

    // Segundo em que a primeira frase cantada começa (pra o trecho não cair numa introdução
    // instrumental). Lê o 1º tempo [mm:ss.xx] que tenha texto de verdade depois dele.
    private function firstLyricSeconds(string $lyrics): float
    {
        foreach (preg_split('/\r?\n/', $lyrics) as $line) {
            if (! preg_match('/^\[(\d{1,3}):(\d{2})(?:[.:](\d{1,3}))?\](.*)$/', $line, $m)) {
                continue;
            }
            $text = trim(preg_replace('/<[^>]*>/', '', $m[4]));
            if ($text === '' || preg_match('/^[\[(].*[\])]$/', $text) || preg_match('/^(instrumental|intro|solo|outro)$/i', $text)) {
                continue;
            }

            return (float) ($m[1] * 60 + $m[2] + (isset($m[3]) && $m[3] !== '' ? (float) ('0.'.$m[3]) : 0));
        }

        return 0.0;
    }

    private function authorizeEvent(Request $request, Event $event): void
    {
        $user = $request->user();

        if (! $user->isAdmin() && $event->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }
    }
}

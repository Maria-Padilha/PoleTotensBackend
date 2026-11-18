<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventTotem;
use App\Models\GameInstance;
use App\Models\GamePlay;
use Illuminate\Support\Collection;

// Ranking por nome (sem CPF/lead por enquanto) — reaproveitado pelo registro
// de jogada (posição no momento), pelo totem (tela de ranking) e pelo recap.
class EventRankingService
{
    public static function instanceIdsForEvent(Event $event): Collection
    {
        $eventTotemIds = $event->eventTotems()->pluck('id');

        return GameInstance::whereIn('event_totem_id', $eventTotemIds)->pluck('id');
    }

    public static function instanceIdsForEventTotem(EventTotem $eventTotem): Collection
    {
        return GameInstance::where('event_totem_id', $eventTotem->id)->pluck('id');
    }

    private static function normalize(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    // Soma de pontos por jogador (nomes duplicados/case diferente contam
    // como a mesma pessoa), maior pontuação primeiro.
    public static function overallRanking(Collection $instanceIds): array
    {
        return GamePlay::whereIn('game_instance_id', $instanceIds)
            ->whereNotNull('score')
            ->whereNotNull('player_name')
            ->get()
            ->groupBy(fn (GamePlay $play) => self::normalize($play->player_name))
            ->map(fn (Collection $group) => [
                'name' => $group->first()->player_name,
                'score' => (int) $group->sum('score'),
                'plays' => $group->count(),
            ])
            ->sortByDesc('score')
            ->values()
            ->toArray();
    }

    // Ranking de UM jogo só (uma instância específica) — é isso que aparece
    // depois de jogar e no modo atração por jogo; não soma com outros jogos
    // do mesmo evento.
    public static function rankingForInstance(int $instanceId): array
    {
        return self::overallRanking(collect([$instanceId]));
    }

    // Karaokê: ranking por APRESENTAÇÃO (quem cantou 2x aparece 2x), com a música.
    public static function karaokePerformances(int $instanceId): array
    {
        return GamePlay::where('game_instance_id', $instanceId)
            ->whereNotNull('score')
            ->whereNotNull('player_name')
            ->orderByDesc('score')
            ->orderBy('played_at')
            ->get()
            ->map(fn (GamePlay $play) => [
                'name' => $play->player_name,
                'score' => (int) $play->score,
                'song' => $play->metadata['song'] ?? null,
            ])
            ->values()
            ->toArray();
    }

    // "Acerte o Tempo": tentativas dos mais perto do alvo pros mais longe (empate: quem fez primeiro).
    public static function timingPerformances(int $instanceId, ?int $limit = null): array
    {
        $rows = GamePlay::where('game_instance_id', $instanceId)
            ->whereNotNull('player_name')
            ->get()
            ->filter(fn (GamePlay $play) => isset($play->metadata['diff'], $play->metadata['target'], $play->metadata['stopped']))
            ->map(fn (GamePlay $play) => [
                'id' => $play->id,
                'name' => $play->player_name,
                'target' => (float) $play->metadata['target'],
                'stopped' => (float) $play->metadata['stopped'],
                'diff' => (float) $play->metadata['diff'],
                'score' => (int) $play->score,
                'played_at' => $play->played_at,
            ])
            ->sort(fn ($a, $b) => [$a['diff'], $a['played_at']] <=> [$b['diff'], $b['played_at']])
            ->values();

        return ($limit ? $rows->take($limit) : $rows)->toArray();
    }

    // "Pare no Alvo": tentativas pelos níveis passados (empate: maior pontuação, depois quem fez primeiro).
    public static function targetPerformances(int $instanceId, ?int $limit = null): array
    {
        $rows = GamePlay::where('game_instance_id', $instanceId)
            ->whereNotNull('player_name')
            ->get()
            ->filter(fn (GamePlay $play) => isset($play->metadata['cleared']))
            ->map(fn (GamePlay $play) => [
                'id' => $play->id,
                'name' => $play->player_name,
                'cleared' => (int) $play->metadata['cleared'],
                'precision' => (int) ($play->metadata['precision'] ?? 0),
                'score' => (int) $play->score,
                'played_at' => $play->played_at,
            ])
            ->sort(fn ($a, $b) => [$b['cleared'], $b['score'], $a['played_at']] <=> [$a['cleared'], $a['score'], $b['played_at']])
            ->values();

        return ($limit ? $rows->take($limit) : $rows)->toArray();
    }

    public static function positionOf(array $ranking, string $playerName): ?int
    {
        $target = self::normalize($playerName);

        foreach ($ranking as $index => $entry) {
            if (self::normalize($entry['name']) === $target) {
                return $index + 1;
            }
        }

        return null;
    }
}

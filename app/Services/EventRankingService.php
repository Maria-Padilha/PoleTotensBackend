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

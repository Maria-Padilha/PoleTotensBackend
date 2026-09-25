<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameInstance;
use App\Models\GamePlay;
use App\Services\EventRankingService;
use Illuminate\Http\Request;

class GamePlayController extends Controller
{
    // Grava o resultado de uma partida jogada de verdade no totem. Sem
    // CPF/lead por enquanto — só o nome digitado antes de jogar (pedido
    // explícito do usuário: ranking por nome nesse primeiro momento).
    public function store(Request $request, string $token)
    {
        $instance = GameInstance::where('public_token', $token)->firstOrFail();

        $data = $request->validate([
            'player_name' => ['required', 'string', 'max:100'],
            'score' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
            'client_uuid' => ['nullable', 'uuid'],
        ]);

        // client_uuid evita duplicar a jogada se o app reenviar por retry de
        // rede — mesma partida, mesmo uuid, updateOrCreate não duplica.
        $play = isset($data['client_uuid'])
            ? GamePlay::updateOrCreate(
                ['client_uuid' => $data['client_uuid']],
                [
                    'game_instance_id' => $instance->id,
                    'player_name' => $data['player_name'],
                    'score' => $data['score'] ?? null,
                    'metadata' => $data['metadata'] ?? null,
                    'played_at' => now(),
                ]
            )
            : GamePlay::create([
                'game_instance_id' => $instance->id,
                'player_name' => $data['player_name'],
                'score' => $data['score'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'client_uuid' => null,
                'played_at' => now(),
            ]);

        $ranking = [];
        $position = null;

        if ($play->score !== null) {
            // Ranking só DESSE jogo (não soma com outros jogos do evento) —
            // pedido explícito do usuário.
            $ranking = EventRankingService::rankingForInstance($instance->id);
            $position = EventRankingService::positionOf($ranking, $play->player_name);

            if ($position !== null && $play->ranking_position !== $position) {
                $play->update(['ranking_position' => $position]);
            }
        }

        return response()->json([
            'id' => $play->id,
            'ranking_position' => $position,
            'ranking' => array_slice($ranking, 0, 10),
        ], 201);
    }
}

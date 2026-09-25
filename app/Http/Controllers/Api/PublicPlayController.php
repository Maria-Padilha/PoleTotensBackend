<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameInstance;
use App\Models\GamePlay;
use App\Services\EventRankingService;
use Illuminate\Http\Request;

class PublicPlayController extends Controller
{
    // Link permanente do totem — diferente do /preview/{token}, não expira e
    // não tem a barra de "aprovar" do organizador. É isso que fica aberto no
    // navegador em modo kiosk durante o evento.
    public function show(Request $request, string $token)
    {
        $instance = GameInstance::with('userGame.game')
            ->where('public_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'id' => $instance->id,
            'title' => $instance->title,
            'config' => $instance->config ?: (object) [],
            'game' => [
                'name' => $instance->userGame->game->name,
                'slug' => $instance->userGame->game->slug,
            ],
        ]);
    }

    // Histórico bruto de jogadas dessa instância — usado pela roleta (que
    // não tem "pontuação" pra ranquear): cada giro aparece uma vez, mesmo
    // que a mesma pessoa tenha girado várias vezes, com o que ganhou e
    // quando. Diferente do ranking por pontos, aqui não agrupa por jogador.
    public function history(string $token)
    {
        $instance = GameInstance::where('public_token', $token)->firstOrFail();

        $plays = GamePlay::where('game_instance_id', $instance->id)
            ->whereNotNull('player_name')
            ->latest('played_at')
            ->take(50)
            ->get()
            ->map(fn (GamePlay $play) => [
                'player_name' => $play->player_name,
                'prize' => $play->metadata['prize'] ?? null,
                'outcome_type' => $play->metadata['outcome_type'] ?? null,
                'played_at' => $play->played_at,
            ]);

        return response()->json(['history' => $plays]);
    }

    // Ranking só desse jogo (não soma com outros jogos do mesmo evento) —
    // é o que aparece na tela "Ver ranking" depois de jogar.
    public function ranking(string $token)
    {
        $instance = GameInstance::where('public_token', $token)->firstOrFail();

        return response()->json(['ranking' => EventRankingService::rankingForInstance($instance->id)]);
    }
}

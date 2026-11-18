<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameInstance;
use App\Services\EventRankingService;

class TimingController extends Controller
{
    // Ranking do "Acerte o Tempo": por TENTATIVA, dos mais perto do alvo pros mais longe.
    // Cada linha traz o número sorteado e onde a pessoa parou.
    public function ranking(string $token)
    {
        $instance = GameInstance::where('public_token', $token)->firstOrFail();

        return response()->json([
            'ranking' => EventRankingService::timingPerformances($instance->id, 100),
        ]);
    }
}

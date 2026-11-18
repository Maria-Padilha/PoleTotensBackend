<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameInstance;
use App\Services\EventRankingService;

class TargetController extends Controller
{
    // Ranking do "Pare no Alvo": por TENTATIVA, de quem passou mais níveis pra quem passou menos.
    public function ranking(string $token)
    {
        $instance = GameInstance::where('public_token', $token)->firstOrFail();

        return response()->json([
            'ranking' => EventRankingService::targetPerformances($instance->id, 100),
        ]);
    }
}

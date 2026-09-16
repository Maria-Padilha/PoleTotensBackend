<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameInstance;
use Illuminate\Http\Request;

class PreviewController extends Controller
{
    public function show(Request $request, string $token)
    {
        $instance = GameInstance::with('userGame.game')
            ->where('preview_token', $token)
            ->firstOrFail();

        if ($instance->preview_opened_at === null) {
            $instance->preview_opened_at = now();
            $instance->save();
        } elseif ($instance->isPreviewExpired()) {
            return response()->json([
                'message' => 'Este link de preview expirou. Peça um novo link para o organizador.',
            ], 410);
        }

        return response()->json([
            'title' => $instance->title,
            'config' => $instance->config ?: (object) [],
            'game' => [
                'name' => $instance->userGame->game->name,
                'slug' => $instance->userGame->game->slug,
            ],
            'opened_at' => $instance->preview_opened_at,
            'expires_at' => $instance->preview_opened_at->copy()->addMinutes(5),
            'approved_at' => $instance->approved_at,
        ]);
    }

    public function approve(Request $request, string $token)
    {
        $instance = GameInstance::where('preview_token', $token)->firstOrFail();

        if ($instance->isPreviewExpired()) {
            return response()->json([
                'message' => 'Este link de preview expirou. Peça um novo link para o organizador.',
            ], 410);
        }

        $instance->update(['approved_at' => now()]);

        return response()->json(['approved_at' => $instance->approved_at]);
    }
}

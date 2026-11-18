<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameInstance;
use App\Models\GamePlay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KaraokeController extends Controller
{
    // Ranking do karaokê é por APRESENTAÇÃO (não soma por jogador como os
    // outros jogos): quem cantou duas vezes aparece duas vezes, cada uma com
    // a música, a nota e a gravação própria.
    public function ranking(string $token)
    {
        $instance = GameInstance::where('public_token', $token)->firstOrFail();

        $performances = GamePlay::where('game_instance_id', $instance->id)
            ->whereNotNull('score')
            ->whereNotNull('player_name')
            ->orderByDesc('score')
            ->orderBy('played_at')
            ->take(100)
            ->get()
            ->map(fn (GamePlay $play) => [
                'id' => $play->id,
                'player_name' => $play->player_name,
                'song' => $play->metadata['song'] ?? null,
                'song_id' => $play->metadata['song_id'] ?? null,
                'score' => $play->score,
                'recording_url' => $play->recording_url,
                'played_at' => $play->played_at,
            ]);

        return response()->json(['ranking' => $performances]);
    }

    // A gravação sobe depois da jogada (que já foi salva com a nota). O
    // recording_token, devolvido só na criação da jogada, impede que outro
    // aparelho anexe áudio na jogada de alguém.
    public function uploadRecording(Request $request, string $token, GamePlay $gamePlay)
    {
        $instance = GameInstance::where('public_token', $token)->firstOrFail();

        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'recording_token' => ['required', 'string'],
        ]);

        abort_unless(
            $gamePlay->game_instance_id === $instance->id
                && $gamePlay->recording_token
                && hash_equals($gamePlay->recording_token, $data['recording_token']),
            403
        );

        if ($gamePlay->recording_url) {
            return response()->json(['message' => 'Essa apresentação já tem gravação.'], 409);
        }

        $file = $data['file'];
        $mime = (string) $file->getMimeType();

        // MediaRecorder entrega webm (Chrome/Firefox, às vezes detectado como
        // "video/webm"), ogg ou mp4 (Safari) — só áudio é aceito.
        $extension = match (true) {
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'ogg') => 'ogg',
            str_contains($mime, 'mp4'), str_contains($mime, 'm4a') => 'm4a',
            str_contains($mime, 'mpeg') => 'mp3',
            str_contains($mime, 'wav') => 'wav',
            default => null,
        };

        if ($extension === null) {
            return response()->json(['message' => 'Formato de áudio não suportado.'], 422);
        }

        $path = "karaoke/{$instance->id}/".Str::uuid().".{$extension}";
        Storage::disk('r2')->put($path, file_get_contents($file->getRealPath()), 'public');

        $gamePlay->update(['recording_url' => Storage::disk('r2')->url($path)]);

        return response()->json(['recording_url' => $gamePlay->recording_url], 201);
    }
}

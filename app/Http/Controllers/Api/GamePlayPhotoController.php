<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GamePlay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GamePlayPhotoController extends Controller
{
    // Gera (ou devolve) o token da jogada — vira o QR code que o convidado
    // escaneia pra tirar a foto no próprio celular.
    public function requestToken(Request $request, GamePlay $gamePlay)
    {
        if (! $gamePlay->photo_token) {
            $gamePlay->update(['photo_token' => Str::random(32)]);
        }

        return response()->json([
            'photo_token' => $gamePlay->photo_token,
            'photo_path' => "/foto/{$gamePlay->photo_token}",
        ]);
    }

    // Tela que abre no celular do convidado ao escanear o QR.
    public function show(string $token)
    {
        $play = GamePlay::with('gameInstance.userGame.game')->where('photo_token', $token)->firstOrFail();

        return response()->json([
            'player_name' => $play->player_name,
            'game_name' => $play->gameInstance->userGame->game->name,
            'already_uploaded' => (bool) $play->photo_url,
        ]);
    }

    public function upload(Request $request, string $token)
    {
        $play = GamePlay::where('photo_token', $token)->firstOrFail();

        if ($play->photo_url) {
            return response()->json(['message' => 'Essa foto já foi enviada, obrigado!'], 409);
        }

        $data = $request->validate([
            'file' => ['required', 'image', 'max:8192'],
        ]);

        $file = $data['file'];
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = "recap-fotos/{$play->game_instance_id}/{$filename}";

        Storage::disk('r2')->put($path, file_get_contents($file->getRealPath()), 'public');
        $url = Storage::disk('r2')->url($path);

        $play->update(['photo_url' => $url]);

        return response()->json(['photo_url' => $url], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KaraokeSong;
use Illuminate\Http\Request;

class KaraokeSongController extends Controller
{
    public function index(Request $request)
    {
        $songs = KaraokeSong::where('user_id', $request->user()->id)
            ->orderBy('title')
            ->get(['id', 'title', 'artist', 'audio_url', 'lyrics', 'offset', 'color', 'updated_at']);

        return response()->json(['data' => $songs]);
    }

    public function store(Request $request)
    {
        $song = KaraokeSong::create($this->validated($request) + ['user_id' => $request->user()->id]);

        return response()->json(['data' => $song], 201);
    }

    public function update(Request $request, KaraokeSong $karaokeSong)
    {
        abort_unless($karaokeSong->user_id === $request->user()->id, 403);

        $karaokeSong->update($this->validated($request));

        return response()->json(['data' => $karaokeSong]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'audio_url' => ['required', 'string', 'max:2048'],
            'lyrics' => ['nullable', 'string', 'max:50000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'offset' => ['nullable', 'numeric', 'between:-10,10'],
        ]);

        $data['offset'] ??= 0;

        return $data;
    }

    // Remove só da biblioteca. O arquivo de áudio fica no R2 porque eventos já criados
    // podem estar usando o mesmo link.
    public function destroy(Request $request, KaraokeSong $karaokeSong)
    {
        abort_unless($karaokeSong->user_id === $request->user()->id, 403);

        $karaokeSong->delete();

        return response()->json(status: 204);
    }
}

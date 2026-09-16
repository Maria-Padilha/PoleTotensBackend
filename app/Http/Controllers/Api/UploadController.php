<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'image', 'max:8192'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'game_slug' => ['nullable', 'string', 'max:100'],
            'folder' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $eventSegment = 'geral';

        if (! empty($data['event_id'])) {
            $event = Event::findOrFail($data['event_id']);

            if (! $user->isAdmin() && $event->user_id !== $user->id) {
                abort(403);
            }

            $eventSegment = "{$event->slug}-{$event->id}";
        }

        $gameSegment = Str::slug($data['game_slug'] ?? 'geral');
        $folderSegment = Str::slug($data['folder'] ?? 'arquivos');

        $file = $request->file('file');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = "{$user->id}/{$eventSegment}/{$gameSegment}/{$folderSegment}/{$filename}";

        Storage::disk('r2')->put($path, file_get_contents($file->getRealPath()), 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('r2')->url($path),
        ], 201);
    }
}

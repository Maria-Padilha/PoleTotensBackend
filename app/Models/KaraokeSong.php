<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaraokeSong extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'artist',
        'audio_url',
        'lyrics',
        'offset',
        'color',
    ];

    protected function casts(): array
    {
        return ['offset' => 'float'];
    }

    // Guarda/atualiza no catálogo as músicas de um evento. A chave é o áudio: a mesma
    // música em outro evento atualiza a entrada (com a última sincronização) em vez de duplicar.
    public static function syncFromConfig(int $userId, ?array $songs): void
    {
        foreach ($songs ?? [] as $song) {
            if (empty($song['audio_url']) || empty($song['title'])) {
                continue;
            }

            static::updateOrCreate(
                ['user_id' => $userId, 'audio_url' => $song['audio_url']],
                [
                    'title' => $song['title'],
                    'artist' => $song['artist'] ?? null,
                    'lyrics' => $song['lyrics'] ?? null,
                    'offset' => $song['offset'] ?? 0,
                    'color' => $song['color'] ?? null,
                ],
            );
        }
    }
}

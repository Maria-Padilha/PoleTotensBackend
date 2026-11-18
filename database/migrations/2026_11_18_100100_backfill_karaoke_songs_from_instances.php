<?php

use App\Models\KaraokeSong;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Importa pra biblioteca as músicas que já estão em jogos de karaokê existentes.
    // Em caso de repetição do mesmo áudio, vale a edição mais recente.
    public function up(): void
    {
        $rows = DB::table('game_instances')
            ->join('event_totem', 'event_totem.id', '=', 'game_instances.event_totem_id')
            ->join('events', 'events.id', '=', 'event_totem.event_id')
            ->orderBy('game_instances.updated_at')
            ->get(['game_instances.config', 'events.user_id']);

        foreach ($rows as $row) {
            $config = json_decode($row->config ?? '', true);

            if (is_array($config) && ! empty($config['songs']) && is_array($config['songs'])) {
                KaraokeSong::syncFromConfig((int) $row->user_id, $config['songs']);
            }
        }
    }

    public function down(): void
    {
        //
    }
};

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameInstance;
use App\Models\GamePlay;
use App\Models\Lead;
use App\Services\EventRankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GamePlayController extends Controller
{
    // Grava o resultado de uma partida jogada de verdade no totem. Sem
    // CPF/lead por enquanto — só o nome digitado antes de jogar (pedido
    // explícito do usuário: ranking por nome nesse primeiro momento).
    public function store(Request $request, string $token)
    {
        $instance = GameInstance::where('public_token', $token)->firstOrFail();

        $data = $request->validate([
            'player_name' => ['required', 'string', 'max:100'],
            'score' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
            'client_uuid' => ['nullable', 'uuid'],
            'lead' => ['nullable', 'array'],
            'lead.email' => ['nullable', 'email', 'max:255'],
            'lead.phone' => ['nullable', 'string', 'max:30'],
            'lead.instagram' => ['nullable', 'string', 'max:100'],
        ]);

        // Só exige/grava lead se o organizador ligou "captar leads" no jogo.
        $lead = null;
        if (! empty($instance->config['capture_leads'])) {
            $phone = preg_replace('/\D+/', '', $data['lead']['phone'] ?? '');

            if (strlen($phone) < 10) {
                return response()->json(['message' => 'Informe um telefone válido.'], 422);
            }

            // Mesma pessoa jogando de novo (mesmo telefone nesse jogo) atualiza
            // o cadastro em vez de duplicar o lead.
            $lead = Lead::updateOrCreate(
                ['game_instance_id' => $instance->id, 'phone' => $phone],
                [
                    'name' => mb_convert_case(trim($data['player_name']), MB_CASE_TITLE, 'UTF-8'),
                    'email' => filled($data['lead']['email'] ?? null) ? $data['lead']['email'] : null,
                    'instagram' => $this->normalizeInstagram($data['lead']['instagram'] ?? null),
                    'captured_at' => now(),
                ]
            );
        }

        // client_uuid evita duplicar a jogada se o app reenviar por retry de
        // rede — mesma partida, mesmo uuid, updateOrCreate não duplica.
        $play = isset($data['client_uuid'])
            ? GamePlay::updateOrCreate(
                ['client_uuid' => $data['client_uuid']],
                [
                    'game_instance_id' => $instance->id,
                    'player_name' => $data['player_name'],
                    'score' => $data['score'] ?? null,
                    'metadata' => $data['metadata'] ?? null,
                    'lead_id' => $lead?->id,
                    'played_at' => now(),
                ]
            )
            : GamePlay::create([
                'game_instance_id' => $instance->id,
                'player_name' => $data['player_name'],
                'score' => $data['score'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'client_uuid' => null,
                'lead_id' => $lead?->id,
                'played_at' => now(),
            ]);

        // Karaokê: devolve o token que autoriza subir a gravação dessa jogada.
        $recordingToken = null;
        if ($instance->userGame?->game?->slug === 'karaoke') {
            if (! $play->recording_token) {
                $play->update(['recording_token' => Str::random(40)]);
            }
            $recordingToken = $play->recording_token;
        }

        $ranking = [];
        $position = null;

        if ($play->score !== null) {
            // Ranking só DESSE jogo (não soma com outros jogos do evento) —
            // pedido explícito do usuário.
            $ranking = EventRankingService::rankingForInstance($instance->id);
            $position = EventRankingService::positionOf($ranking, $play->player_name);

            if ($position !== null && $play->ranking_position !== $position) {
                $play->update(['ranking_position' => $position]);
            }
        }

        return response()->json([
            'id' => $play->id,
            'ranking_position' => $position,
            'recording_token' => $recordingToken,
            'ranking' => array_slice($ranking, 0, 10),
        ], 201);
    }

    private function normalizeInstagram(?string $value): ?string
    {
        $handle = ltrim(trim((string) $value), '@');

        return $handle !== '' ? '@'.$handle : null;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Lead;
use App\Services\EventRankingService;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        return response()->json([
            'leads' => $this->leadsFor($event)->map(fn (Lead $lead) => $this->present($lead))->values(),
        ]);
    }

    // CSV com BOM + ";" — abre direto no Excel em pt-BR com acentos certos.
    public function export(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $leads = $this->leadsFor($event);

        return response()->streamDownload(function () use ($leads) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'E-mail', 'Telefone', 'Instagram', 'Jogo', 'Capturado em'], ';');

            foreach ($leads as $lead) {
                $row = $this->present($lead);
                fputcsv($out, [
                    $row['name'], $row['email'], $row['phone'], $row['instagram'], $row['game'], $row['captured_at'],
                ], ';');
            }

            fclose($out);
        }, "leads-{$event->slug}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function leadsFor(Event $event)
    {
        $instanceIds = EventRankingService::instanceIdsForEvent($event);

        return Lead::with('gameInstance.userGame.game')
            ->whereIn('game_instance_id', $instanceIds)
            ->orderBy('captured_at')
            ->get();
    }

    private function present(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'instagram' => $lead->instagram,
            'game' => $lead->gameInstance?->title ?: $lead->gameInstance?->userGame?->game?->name,
            'captured_at' => $lead->captured_at?->format('d/m/Y H:i'),
        ];
    }
}

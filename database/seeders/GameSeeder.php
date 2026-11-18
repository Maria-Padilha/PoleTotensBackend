<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $games = [
            ['name' => 'Quiz', 'slug' => 'quiz', 'description' => 'Perguntas e respostas personalizadas para o evento.'],
            ['name' => 'Verdadeiro ou Falso', 'slug' => 'verdadeiro-ou-falso', 'description' => 'Afirmações que os convidados devem marcar como verdadeiras ou falsas.'],
            ['name' => 'Jogo da Forca', 'slug' => 'forca', 'description' => 'Clássico jogo da forca: adivinhe a palavra antes de perder as 6 chances.'],
            ['name' => 'Caça-Palavras', 'slug' => 'caca-palavras', 'description' => 'Encontre as palavras escondidas no tabuleiro de letras.'],
            ['name' => 'Roleta', 'slug' => 'roleta-de-brindes', 'description' => 'Roleta giratória personalizável — prêmios, desafios, decisões ou o que quiser.'],
            ['name' => 'Jogo da Memória', 'slug' => 'jogo-da-memoria', 'description' => 'Encontre os pares de cartas viradas com fotos personalizadas do evento.'],
            ['name' => 'Karaokê', 'slug' => 'karaoke', 'description' => 'Cante com a letra sincronizada, ganhe nota pela voz e dispute o ranking — com replay da apresentação.'],
            ['name' => 'Pare no Alvo', 'slug' => 'pare-no-alvo', 'description' => 'Uma barra vai e volta: toque para parar dentro da zona verde. A cada acerto a zona diminui e a barra acelera.'],
            ['name' => 'Acerte o Tempo', 'slug' => 'acerte-o-tempo', 'description' => 'Um tempo é sorteado e o cronômetro roda: pare exatamente nele. Ranking dos que chegaram mais perto.'],
            ['name' => 'Sorteio de Prêmios', 'slug' => 'sorteio-de-premios', 'description' => 'Sorteio de prêmios entre os participantes cadastrados.'],
        ];

        foreach ($games as $game) {
            $record = Game::query()->updateOrCreate(
                ['slug' => $game['slug']],
                [
                    'name' => $game['name'],
                    'description' => $game['description'],
                    'base_price' => 0,
                    'is_active' => true,
                    'created_by' => $admin?->id,
                ]
            );

            if ($admin) {
                $admin->userGames()->firstOrCreate(
                    ['game_id' => $record->id],
                    ['source' => 'admin_grant', 'price_paid' => 0, 'acquired_at' => now()]
                );
            }
        }
    }
}

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
            ['name' => 'Roleta de Brindes', 'slug' => 'roleta-de-brindes', 'description' => 'Roleta sorteando brindes entre os participantes.'],
            ['name' => 'Jogo da Memória', 'slug' => 'jogo-da-memoria', 'description' => 'Clássico jogo da memória com imagens personalizadas.'],
            ['name' => 'Caça-Palavras', 'slug' => 'caca-palavras', 'description' => 'Caça-palavras temático do evento.'],
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

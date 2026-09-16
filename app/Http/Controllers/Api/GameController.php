<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\UserGameResource;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Game::query()
            ->with(['userGames' => fn ($q) => $q->where('user_id', $user->id)])
            ->orderBy('name');

        if (! $user->isAdmin()) {
            $query->where('is_active', true);
        }

        return GameResource::collection($query->get());
    }

    public function store(StoreGameRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['created_by'] = $request->user()->id;

        $game = Game::create($data);

        $game->userGames()->create([
            'user_id' => $request->user()->id,
            'source' => 'admin_grant',
            'price_paid' => 0,
            'acquired_at' => now(),
        ]);

        $game->load(['userGames' => fn ($q) => $q->where('user_id', $request->user()->id)]);

        return new GameResource($game);
    }

    public function show(Request $request, Game $game)
    {
        $game->load(['userGames' => fn ($q) => $q->where('user_id', $request->user()->id)]);

        return new GameResource($game);
    }

    public function update(UpdateGameRequest $request, Game $game)
    {
        $game->update($request->validated());

        return new GameResource($game);
    }

    public function destroy(Game $game)
    {
        $game->delete();

        return response()->json(status: 204);
    }

    public function acquire(Request $request, Game $game)
    {
        $user = $request->user();

        if ($user->userGames()->where('game_id', $game->id)->exists()) {
            return response()->json([
                'message' => 'Você já possui este jogo na sua biblioteca.',
            ], 422);
        }

        $userGame = $user->userGames()->create([
            'game_id' => $game->id,
            'source' => $user->isAdmin() ? 'admin_grant' : 'purchase',
            'price_paid' => $user->isAdmin() ? 0 : $game->base_price,
            'acquired_at' => now(),
        ]);

        return new UserGameResource($userGame->load('game'));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Game::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}

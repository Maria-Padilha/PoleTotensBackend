<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTotemRequest;
use App\Http\Requests\UpdateTotemRequest;
use App\Http\Resources\TotemResource;
use App\Models\Totem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TotemController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Totem::class);

        $query = Totem::query()->latest();

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        return TotemResource::collection($query->paginate(20));
    }

    public function store(StoreTotemRequest $request)
    {
        $this->authorize('create', Totem::class);

        $user = $request->user();

        if (! $user->isAdmin()) {
            $maxTotems = $user->activeSubscription?->plan?->max_totems;

            if ($maxTotems !== null && $user->totems()->count() >= $maxTotems) {
                return response()->json([
                    'message' => 'Limite de totens do seu plano foi atingido.',
                ], 403);
            }
        }

        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($user->id, $data['name']);

        $totem = $user->totems()->create($data);

        return new TotemResource($totem);
    }

    private function uniqueSlug(int $userId, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Totem::where('user_id', $userId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function show(Totem $totem)
    {
        $this->authorize('view', $totem);

        return new TotemResource($totem);
    }

    public function update(UpdateTotemRequest $request, Totem $totem)
    {
        $this->authorize('update', $totem);

        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $totem->update($data);

        return new TotemResource($totem);
    }

    public function destroy(Totem $totem)
    {
        $this->authorize('delete', $totem);

        $totem->delete();

        return response()->json(status: 204);
    }

    // Link fixo que fica aberto no navegador do totem (kiosk) — gera uma vez
    // só, diferente do preview de jogo (que expira).
    public function generatePublicLink(Totem $totem)
    {
        $this->authorize('update', $totem);

        if (! $totem->public_token) {
            $totem->update(['public_token' => Str::random(40)]);
        }

        return response()->json([
            'public_token' => $totem->public_token,
            'public_path' => "/totem/{$totem->public_token}",
        ]);
    }
}

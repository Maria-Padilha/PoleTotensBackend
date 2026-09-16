<?php

namespace App\Policies;

use App\Models\Totem;
use App\Models\User;

class TotemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Totem $totem): bool
    {
        return $user->isAdmin() || $user->id === $totem->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Totem $totem): bool
    {
        return $user->isAdmin() || $user->id === $totem->user_id;
    }

    public function delete(User $user, Totem $totem): bool
    {
        return $user->isAdmin() || $user->id === $totem->user_id;
    }
}

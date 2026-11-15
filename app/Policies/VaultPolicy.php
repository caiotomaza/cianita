<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vault;

class VaultPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Vault $vault): bool
    {
        return $vault->owner_id === $user->getKey()
            || $vault->members()->whereKey($user->getKey())->exists();
    }

    public function update(User $user, Vault $vault): bool
    {
        return $vault->owner_id === $user->getKey()
            || $vault->members()->whereKey($user->getKey())->wherePivot('role', 'editor')->exists();
    }

    public function delete(User $user, Vault $vault): bool
    {
        return $vault->owner_id === $user->getKey();
    }
}

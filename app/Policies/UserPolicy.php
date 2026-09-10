<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function view(User $user, User $model): bool
    {
        return $user->esAdministrador();
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, User $model): bool
    {
        return $user->esAdministrador();
    }

    public function toggle(User $user, User $model): bool
    {
        return $user->esAdministrador() && $user->id !== $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }
}

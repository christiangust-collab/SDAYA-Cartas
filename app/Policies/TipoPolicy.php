<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tipo;
use App\Models\User;

final class TipoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Tipo $tipo): bool
    {
        return $user->esAdministrador();
    }

    public function toggle(User $user, Tipo $tipo): bool
    {
        return $user->esAdministrador();
    }
}

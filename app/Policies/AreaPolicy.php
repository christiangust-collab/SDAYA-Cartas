<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

final class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Area $area): bool
    {
        return $user->esAdministrador();
    }

    public function toggle(User $user, Area $area): bool
    {
        return $user->esAdministrador();
    }
}

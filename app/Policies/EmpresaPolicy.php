<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Empresa;
use App\Models\User;

final class EmpresaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function view(User $user, Empresa $empresa): bool
    {
        return $user->esAdministrador() || $user->empresa_id === $empresa->id;
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Empresa $empresa): bool
    {
        return $user->esAdministrador();
    }

    public function toggle(User $user, Empresa $empresa): bool
    {
        return $user->esAdministrador();
    }

    public function delete(User $user, Empresa $empresa): bool
    {
        return false; // Las empresas no se eliminan físicamente para preservar el historial, se desactivan.
    }
}
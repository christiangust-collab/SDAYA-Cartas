<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Documento;
use App\Models\User;

final class DocumentoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Documento $documento): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->puedeEditarDocumentos();
    }

    public function update(User $user, Documento $documento): bool
    {
        return $user->puedeEditarDocumentos() && $documento->estaBorrador();
    }

    public function emitir(User $user, Documento $documento): bool
    {
        return $user->puedeEditarDocumentos() && $documento->estaBorrador();
    }

    public function anular(User $user, Documento $documento): bool
    {
        return $user->esAdministrador() && $documento->estaEmitido();
    }

    public function descargar(User $user, Documento $documento): bool
    {
        return $documento->estado->estaPublicado();
    }
}

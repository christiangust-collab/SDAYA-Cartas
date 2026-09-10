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
        if ($documento->estaBorrador() && $documento->emitido_por !== null) {
            return $user->esAdministrador() || $documento->emitido_por === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->puedeEditarDocumentos();
    }

    public function update(User $user, Documento $documento): bool
    {
        if (! $documento->estaBorrador()) {
            return false;
        }

        if ($documento->emitido_por !== null) {
            return $user->esAdministrador() || $documento->emitido_por === $user->id;
        }

        return $user->puedeEditarDocumentos();
    }

    public function emitir(User $user, Documento $documento): bool
    {
        if (! $documento->estaBorrador()) {
            return false;
        }

        if ($documento->emitido_por !== null) {
            return $user->esAdministrador() || $documento->emitido_por === $user->id;
        }

        return $user->puedeEditarDocumentos();
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

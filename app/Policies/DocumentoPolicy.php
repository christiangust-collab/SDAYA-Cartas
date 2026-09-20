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
        if (! $this->perteneceAEmpresa($user, $documento)) {
            return false;
        }

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
        if (! $this->perteneceAEmpresa($user, $documento)) {
            return false;
        }

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
        if (! $this->perteneceAEmpresa($user, $documento)) {
            return false;
        }

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
        if (! $this->perteneceAEmpresa($user, $documento)) {
            return false;
        }

        return $user->esAdministrador() && $documento->estaEmitido();
    }

    public function descargar(User $user, Documento $documento): bool
    {
        if (! $this->perteneceAEmpresa($user, $documento)) {
            return false;
        }

        if ($documento->estado->estaPublicado()) {
            return true;
        }

        return $documento->estaBorrador() && $this->view($user, $documento);
    }

    private function perteneceAEmpresa(User $user, Documento $documento): bool
    {
        if ($user->esAdministrador()) {
            return true;
        }

        if ($user->empresa_id === null || $documento->empresa_id === null) {
            return true;
        }

        return (int) $user->empresa_id === (int) $documento->empresa_id;
    }
}

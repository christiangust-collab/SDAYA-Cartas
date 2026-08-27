<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\ArchivosGenerados;
use App\Models\Documento;

interface GeneradorArchivosDocumento
{
    public function generar(Documento $documento): ArchivosGenerados;
}

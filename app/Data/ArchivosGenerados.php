<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ArchivosGenerados
{
    public function __construct(
        public string $docx,
        public string $pdf,
        public string $hashPdf,
    ) {}
}

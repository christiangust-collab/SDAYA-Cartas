<?php

declare(strict_types=1);

namespace App\Data;

final readonly class CiteAsignado
{
    public function __construct(
        public string $cite,
        public int $correlativo,
    ) {}
}

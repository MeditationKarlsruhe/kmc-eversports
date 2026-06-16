<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final readonly class ClassGroup
{
    public function __construct(
        public string $id,
        public string $title,
    ) {
    }
}

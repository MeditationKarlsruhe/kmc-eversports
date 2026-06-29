<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final readonly class ClassGroup
{
    /** @param list<Appointment> $appointments */
    public function __construct(
        public string $id,
        public string $title,
        public string $descriptionHtml,
        public ?string $imageUrl,
        public array $appointments,
    ) {
    }
}

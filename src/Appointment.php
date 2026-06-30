<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final readonly class Appointment
{
    public function __construct(
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
        public ?string $registrationLink,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final readonly class ActivityNode
{
    public function __construct(
        public string $groupId,
        public string $groupName,
        public string $descriptionHtml,
        public ?string $imageUrl,
        public string $appointmentStart,
        public string $appointmentEnd,
        public string $registrationLink,
    ) {
    }
}

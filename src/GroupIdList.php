<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class GroupIdList
{
    /**
     * @return list<string>
     */
    public static function fromCommaSeparated(string $value): array
    {
        $trimmed = array_map('trim', explode(',', $value));

        return array_values(array_filter(
            $trimmed,
            static fn (string $groupId): bool => $groupId !== '',
        ));
    }
}

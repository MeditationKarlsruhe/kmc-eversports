<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class GroupsParser
{
    /** @return list<GroupSummary> */
    public static function parse(string $groupsJson): array
    {
        /**
         * @var array{
         *     data: array{
         *         activityGroups: array{
         *             nodes: list<array{id: string, name: string}>
         *         }
         *     }
         * } $decoded
         */
        $decoded = json_decode($groupsJson, true, 512, JSON_THROW_ON_ERROR);

        $result = [];
        foreach ($decoded['data']['activityGroups']['nodes'] as $node) {
            $result[] = new GroupSummary($node['id'], $node['name']);
        }

        return $result;
    }
}

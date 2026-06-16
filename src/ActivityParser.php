<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class ActivityParser
{
    /** @return list<ClassGroup> */
    public function parse(string $activitiesJson): array
    {
        $data = json_decode($activitiesJson, true, 512, JSON_THROW_ON_ERROR);
        $nodes = $data['data']['activities']['nodes'];

        $groups = [];
        foreach ($nodes as $node) {
            $group = $node['activityGroup'];
            $id = $group['id'];
            $name = $group['name'];
            $groups[$id] ??= new ClassGroup($id, $name);
        }

        return array_values($groups);
    }
}

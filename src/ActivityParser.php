<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class ActivityParser
{
    /** @return list<ClassGroup> */
    public function parse(string $activitiesJson): array
    {
        $data = json_decode($activitiesJson, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new MalformedActivitiesResponse('Activities response is not a JSON object.');
        }

        $payload = $data['data'] ?? null;
        if (!is_array($payload)) {
            throw new MalformedActivitiesResponse('Activities response is missing "data".');
        }

        $activities = $payload['activities'] ?? null;
        if (!is_array($activities)) {
            throw new MalformedActivitiesResponse('Activities response is missing "data.activities".');
        }

        $nodes = $activities['nodes'] ?? null;
        if (!is_array($nodes)) {
            throw new MalformedActivitiesResponse('Activities response is missing "data.activities.nodes".');
        }

        $groups = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                throw new MalformedActivitiesResponse('Activity node is not an object.');
            }

            $group = $node['activityGroup'] ?? null;
            if (!is_array($group)) {
                throw new MalformedActivitiesResponse('Activity is missing its activityGroup.');
            }

            $id = $group['id'] ?? null;
            $name = $group['name'] ?? null;
            if (!is_string($id) || !is_string($name)) {
                throw new MalformedActivitiesResponse('activityGroup.id and activityGroup.name must be strings.');
            }

            $groups[$id] ??= new ClassGroup($id, $name);
        }

        return array_values($groups);
    }
}

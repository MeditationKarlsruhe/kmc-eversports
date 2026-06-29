<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class ActivityParser
{
    /** @return list<ClassGroup> */
    public function parse(string $activitiesJson): array
    {
        $nodes = $this->decodeNodes($activitiesJson);
        return $this->toClassGroups($nodes);
    }

    /** @return list<ActivityNode> */
    private function decodeNodes(string $activitiesJson): array
    {
        $data = json_decode($activitiesJson, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new MalformedActivitiesResponse('Activities response is not a JSON object.');
        }

        $errors = $data['errors'] ?? null;
        if (is_array($errors)) {
            $messages = array_filter(array_column($errors, 'message'), 'is_string');
            throw new MalformedActivitiesResponse('GraphQL errors: ' . implode('; ', $messages));
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

        $result = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                throw new MalformedActivitiesResponse('Activity node is not an object.');
            }

            $group = $node['activityGroup'] ?? null;
            if (!is_array($group)) {
                throw new MalformedActivitiesResponse('Activity is missing its activityGroup.');
            }

            $groupId = $group['id'] ?? null;
            $groupName = $group['name'] ?? null;
            if (!is_string($groupId) || !is_string($groupName)) {
                throw new MalformedActivitiesResponse('activityGroup.id and activityGroup.name must be strings.');
            }

            $description = $group['description'] ?? null;
            if (!is_array($description)) {
                throw new MalformedActivitiesResponse('activityGroup.description must be an object.');
            }
            $descriptionHtml = $description['html'] ?? null;
            if (!is_string($descriptionHtml)) {
                throw new MalformedActivitiesResponse('activityGroup.description.html must be a string.');
            }

            $images = $group['images'] ?? null;
            $imageNodes = is_array($images) ? ($images['nodes'] ?? []) : [];
            $firstNode = is_array($imageNodes) ? ($imageNodes[0] ?? null) : null;
            $imageUrl = is_array($firstNode) && isset($firstNode['url']) && is_string($firstNode['url'])
                ? $firstNode['url']
                : null;

            $appointmentStart = $node['start'] ?? null;
            $appointmentEnd = $node['end'] ?? null;
            $registrationLink = $node['detailsPageURL'] ?? null;
            if (!is_string($appointmentStart) || !is_string($appointmentEnd) || !is_string($registrationLink)) {
                throw new MalformedActivitiesResponse('Activity start, end, and detailsPageURL must be strings.');
            }

            $result[] = new ActivityNode(
                $groupId,
                $groupName,
                $descriptionHtml,
                $imageUrl,
                $appointmentStart,
                $appointmentEnd,
                $registrationLink,
            );
        }

        return $result;
    }

    /**
     * @param  list<ActivityNode> $nodes
     * @return list<ClassGroup>
     */
    private function toClassGroups(array $nodes): array
    {
        /** @var array<string, array{0: string, 1: string, 2: ?string}> $meta */
        $meta = [];
        /** @var array<string, list<Appointment>> $appointments */
        $appointments = [];

        foreach ($nodes as $node) {
            $id = $node->groupId;
            $meta[$id] ??= [$node->groupName, $node->descriptionHtml, $node->imageUrl];
            $appointments[$id][] = new Appointment(
                new \DateTimeImmutable($node->appointmentStart),
                new \DateTimeImmutable($node->appointmentEnd),
                $node->registrationLink,
            );
        }

        $groups = [];
        foreach ($meta as $id => [$name, $description, $imageUrl]) {
            $groups[] = new ClassGroup($id, $name, $description, $imageUrl, $appointments[$id]);
        }

        return $groups;
    }
}

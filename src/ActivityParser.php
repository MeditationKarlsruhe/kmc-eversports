<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class ActivityParser
{
    /** @return list<ClassGroup> */
    public function parse(string $activitiesJson): array
    {
        return $this->toClassGroups($this->decodeNodes($activitiesJson));
    }

    /**
     * @return list<array{
     *     groupId: string,
     *     groupName: string,
     *     descriptionHtml: string,
     *     imageUrl: ?string,
     *     appointmentStart: string,
     *     appointmentEnd: string,
     *     registrationLink: string,
     * }>
     */
    private function decodeNodes(string $activitiesJson): array
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
            $firstImage = is_array($imageNodes) && count($imageNodes) > 0 ? $imageNodes[0] : null;
            $imageUrl = is_array($firstImage) && isset($firstImage['url']) && is_string($firstImage['url'])
                ? $firstImage['url']
                : null;

            $appointmentStart = $node['start'] ?? null;
            $appointmentEnd = $node['end'] ?? null;
            $registrationLink = $node['detailsPageURL'] ?? null;
            if (!is_string($appointmentStart) || !is_string($appointmentEnd) || !is_string($registrationLink)) {
                throw new MalformedActivitiesResponse('Activity start, end, and detailsPageURL must be strings.');
            }

            $result[] = [
                'groupId' => $groupId,
                'groupName' => $groupName,
                'descriptionHtml' => $descriptionHtml,
                'imageUrl' => $imageUrl,
                'appointmentStart' => $appointmentStart,
                'appointmentEnd' => $appointmentEnd,
                'registrationLink' => $registrationLink,
            ];
        }

        return $result;
    }

    /**
     * @param  list<array{
     *     groupId: string,
     *     groupName: string,
     *     descriptionHtml: string,
     *     imageUrl: ?string,
     *     appointmentStart: string,
     *     appointmentEnd: string,
     *     registrationLink: string,
     * }> $nodes
     * @return list<ClassGroup>
     */
    private function toClassGroups(array $nodes): array
    {
        /** @var array<string, ClassGroup> $groups */
        $groups = [];

        foreach ($nodes as $node) {
            $id = $node['groupId'];

            $appointment = new Appointment(
                new \DateTimeImmutable($node['appointmentStart']),
                new \DateTimeImmutable($node['appointmentEnd']),
                $node['registrationLink'],
            );

            if (!isset($groups[$id])) {
                $groups[$id] = new ClassGroup(
                    $id,
                    $node['groupName'],
                    $node['descriptionHtml'],
                    $node['imageUrl'],
                    [$appointment],
                );
            } else {
                $existing = $groups[$id];
                $groups[$id] = new ClassGroup(
                    $existing->id,
                    $existing->title,
                    $existing->descriptionHtml,
                    $existing->imageUrl,
                    [...$existing->appointments, $appointment],
                );
            }
        }

        return array_values($groups);
    }
}

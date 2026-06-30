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
        /**
         * @var array{
         *     data: array{
         *         activities: array{
         *             nodes: list<array{
         *                 start: string,
         *                 end: string,
         *                 detailsPageURL: string|null,
         *                 activityGroup: array{
         *                     id: string,
         *                     name: string,
         *                     description: array{html: string},
         *                     images: array{nodes: list<array{url: string}>}
         *                 }
         *             }>
         *         }
         *     }
         * } $decoded
         */
        $decoded = json_decode($activitiesJson, true, 512, JSON_THROW_ON_ERROR);

        $result = [];
        foreach ($decoded['data']['activities']['nodes'] as $node) {
            $result[] = new ActivityNode(
                $node['activityGroup']['id'],
                $node['activityGroup']['name'],
                $node['activityGroup']['description']['html'],
                $node['activityGroup']['images']['nodes'][0]['url'] ?? null,
                $node['start'],
                $node['end'],
                $node['detailsPageURL'],
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

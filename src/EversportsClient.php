<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class EversportsClient
{
    private const ENDPOINT = 'https://provider-api.eversportsmanager.io/api/graphql';

    private const QUERY = '
        query($s: DateInput!, $e: DateInput!, $ids: [ID!], $after: Cursor) {
            activities(
                first: 50,
                after: $after,
                timeRange: { start: $s, end: $e },
                isCancelled: false,
                isArchived: false,
                activityGroupIds: $ids
            ) {
                pageInfo { hasNextPage endCursor }
                nodes {
                    id start end detailsPageURL
                    activityGroup {
                        id name
                        description { html }
                        images(first: 1) { nodes { url } }
                    }
                }
            }
        }
    ';

    /** @param list<string> $groupIds */
    public function fetchActivities(array $groupIds): string
    {
        $key = $this->cacheKey($groupIds);
        $cached = get_transient($key);
        if (is_string($cached)) {
            return $cached;
        }
        $nodes = $this->fetchAllNodes($groupIds);
        $json = json_encode(
            ['data' => ['activities' => ['nodes' => $nodes]]],
            JSON_THROW_ON_ERROR,
        );
        set_transient($key, $json, HOUR_IN_SECONDS);
        return $json;
    }

    /** @param list<string> $groupIds */
    private function cacheKey(array $groupIds): string
    {
        $sorted = $groupIds;
        sort($sorted);
        return 'eversports_' . md5(implode(',', $sorted));
    }

    /**
     * @param  list<string> $groupIds
     * @return list<mixed>
     */
    private function fetchAllNodes(array $groupIds): array
    {
        $allNodes = [];
        $after = null;
        do {
            /**
             * @var array{
             *     data: array{
             *         activities: array{
             *             pageInfo: array{hasNextPage: bool, endCursor: string|null},
             *             nodes: list<mixed>
             *         }
             *     }
             * } $page
             */
            $page = json_decode($this->request($groupIds, $after), true);
            $activ = $page['data']['activities'];
            $allNodes = array_merge($allNodes, $activ['nodes']);
            $hasNextPage = $activ['pageInfo']['hasNextPage'];
            $after = $activ['pageInfo']['endCursor'];
        } while ($hasNextPage && $after !== null);
        return $allNodes;
    }

    /** @param list<string> $groupIds */
    private function request(array $groupIds, ?string $after): string
    {
        $tz = new \DateTimeZone('Europe/Berlin');
        $variables = [
            's'     => (new \DateTimeImmutable('today', $tz))->format('c'),
            'e'     => (new \DateTimeImmutable('+52 weeks', $tz))->format('c'),
            'after' => $after,
        ];
        if ($groupIds !== []) {
            $variables['ids'] = $groupIds;
        }

        $body = json_encode(
            ['query' => self::QUERY, 'variables' => $variables],
            JSON_THROW_ON_ERROR,
        );

        $response = wp_remote_post(self::ENDPOINT, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->bearerToken(),
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode !== 200) {
            throw new \RuntimeException("Eversports API returned HTTP {$statusCode}.");
        }

        $responseBody = wp_remote_retrieve_body($response);
        $decoded = json_decode($responseBody, true);
        $errors = is_array($decoded) ? ($decoded['errors'] ?? null) : null;
        if (is_array($errors)) {
            $messages = array_filter(array_column($errors, 'message'), 'is_string');
            throw new \RuntimeException('GraphQL errors: ' . implode('; ', $messages));
        }

        return $responseBody;
    }

    private function bearerToken(): string
    {
        $token = file_get_contents(dirname(__DIR__) . '/.secrets/eversports-api.txt');
        if ($token === false) {
            throw new \RuntimeException('.secrets/eversports-api.txt is missing.');
        }
        return $token;
    }
}

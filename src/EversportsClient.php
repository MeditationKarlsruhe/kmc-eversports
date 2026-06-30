<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class EversportsClient
{
    private const ENDPOINT = 'https://provider-api.eversportsmanager.io/api/graphql';

    private const QUERY = '
        query($startDate: DateInput!, $endDate: DateInput!, $after: Cursor) {
            activities(
                first: 50,
                after: $after,
                timeRange: { start: $startDate, end: $endDate },
                isCancelled: false,
                isArchived: false
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

    public function fetchActivities(): string
    {
        $cached = get_transient('eversports_activities');
        if (is_string($cached)) {
            return $cached;
        }
        $nodes = $this->fetchAllNodes();
        $json = json_encode(
            ['data' => ['activities' => ['nodes' => $nodes]]],
            JSON_THROW_ON_ERROR,
        );
        set_transient('eversports_activities', $json, HOUR_IN_SECONDS);
        return $json;
    }

    /** @return list<mixed> */
    private function fetchAllNodes(): array
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
            $page = json_decode($this->request($after), true);
            $activities = $page['data']['activities'];
            $allNodes = array_merge($allNodes, $activities['nodes']);
            $hasNextPage = $activities['pageInfo']['hasNextPage'];
            $after = $activities['pageInfo']['endCursor'];
        } while ($hasNextPage);
        return $allNodes;
    }

    private function request(?string $after): string
    {
        $timezone = new \DateTimeZone('Europe/Berlin');
        $variables = [
            'startDate' => (new \DateTimeImmutable('today', $timezone))->format('c'),
            'endDate'   => (new \DateTimeImmutable('+52 weeks', $timezone))->format('c'),
            'after'     => $after,
        ];

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
            $body = wp_remote_retrieve_body($response);
            throw new \RuntimeException("Eversports API returned HTTP {$statusCode}: {$body}");
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
        $token = @file_get_contents(dirname(__DIR__) . '/.secrets/eversports-api.txt');
        if ($token === false) {
            throw new \RuntimeException('.secrets/eversports-api.txt is missing.');
        }
        return $token;
    }
}

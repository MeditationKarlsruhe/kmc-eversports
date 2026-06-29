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
        $sortedIds = $groupIds;
        sort($sortedIds);
        $cacheKey = 'eversports_' . md5(implode(',', $sortedIds));

        $cached = get_transient($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }

        $allNodes = [];
        $after = null;
        do {
            $pageBody = $this->request($groupIds, $after);
            $page = json_decode($pageBody, true);
            if (!is_array($page)) {
                throw new EversportsApiException('Failed to decode API response.');
            }
            $data = is_array($page['data'] ?? null) ? $page['data'] : [];
            $activ = is_array($data['activities'] ?? null) ? $data['activities'] : [];
            $nodes = is_array($activ['nodes'] ?? null) ? $activ['nodes'] : [];
            $allNodes = array_merge($allNodes, $nodes);
            $pageInfo = is_array($activ['pageInfo'] ?? null) ? $activ['pageInfo'] : [];
            $hasNextPage = ($pageInfo['hasNextPage'] ?? false) === true;
            $after = is_string($pageInfo['endCursor'] ?? null) ? $pageInfo['endCursor'] : null;
        } while ($hasNextPage && $after !== null);

        $json = json_encode(
            ['data' => ['activities' => ['nodes' => $allNodes]]],
            JSON_THROW_ON_ERROR,
        );

        set_transient($cacheKey, $json, HOUR_IN_SECONDS);
        return $json;
    }

    /** @param list<string> $groupIds */
    private function request(array $groupIds, ?string $after): string
    {
        $tz = new \DateTimeZone('Europe/Berlin');
        $variables = [
            's' => (new \DateTimeImmutable('today', $tz))->format('c'),
            'e' => (new \DateTimeImmutable('+52 weeks', $tz))->format('c'),
        ];
        if ($groupIds !== []) {
            $variables['ids'] = $groupIds;
        }
        if ($after !== null) {
            $variables['after'] = $after;
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
            throw new EversportsApiException($response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode !== 200) {
            throw new EversportsApiException("Eversports API returned HTTP {$statusCode}.");
        }

        $responseBody = wp_remote_retrieve_body($response);
        $decoded = json_decode($responseBody, true);
        $errors = is_array($decoded) ? ($decoded['errors'] ?? null) : null;
        if (is_array($errors)) {
            $messages = array_filter(array_column($errors, 'message'), 'is_string');
            throw new EversportsApiException('GraphQL errors: ' . implode('; ', $messages));
        }

        return $responseBody;
    }

    private function bearerToken(): string
    {
        $token = file_get_contents(dirname(__DIR__) . '/.secrets/eversports-api.txt');
        if ($token === false) {
            throw new EversportsApiException('.secrets/eversports-api.txt is missing.');
        }
        return $token;
    }
}

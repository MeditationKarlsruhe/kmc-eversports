<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class EversportsClient
{
    private const ENDPOINT = 'https://provider-api.eversportsmanager.io/api/graphql';

    private const QUERY = '
        query($s: DateInput!, $e: DateInput!, $ids: [ID!]) {
            activities(
                first: 500,
                timeRange: { start: $s, end: $e },
                isCancelled: false,
                isArchived: false,
                activityGroupIds: $ids
            ) {
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

        $json = $this->request($groupIds);
        set_transient($cacheKey, $json, HOUR_IN_SECONDS);

        return $json;
    }

    /** @param list<string> $groupIds */
    private function request(array $groupIds): string
    {
        $tz = new \DateTimeZone('Europe/Berlin');
        $variables = [
            's' => (new \DateTimeImmutable('today', $tz))->format('c'),
            'e' => (new \DateTimeImmutable('+52 weeks', $tz))->format('c'),
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
            throw new EversportsApiException($response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode !== 200) {
            throw new EversportsApiException("Eversports API returned HTTP {$statusCode}.");
        }

        return wp_remote_retrieve_body($response);
    }

    private function bearerToken(): string
    {
        if (defined('EVERSPORTS_BEARER_TOKEN')) {
            $token = constant('EVERSPORTS_BEARER_TOKEN');
            if (!is_string($token)) {
                throw new EversportsApiException('EVERSPORTS_BEARER_TOKEN must be a string.');
            }
            return $token;
        }

        $secretFile = dirname(__DIR__) . '/.secrets/eversports-api.txt';
        if (file_exists($secretFile)) {
            return trim((string) file_get_contents($secretFile));
        }

        throw new EversportsApiException(
            'EVERSPORTS_BEARER_TOKEN is not defined and .secrets/eversports-api.txt does not exist.',
        );
    }
}

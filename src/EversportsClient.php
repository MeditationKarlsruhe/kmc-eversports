<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class EversportsClient
{
    public const OPTION_TOKEN  = 'kmc_eversports_api_token';
    public const ACTIVITIES_TRANSIENT_KEY = 'eversports_activities';

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

    public static function fetchActivities(): string
    {
        $cached = get_transient(self::ACTIVITIES_TRANSIENT_KEY);
        if (is_string($cached)) {
            return $cached;
        }
        $nodes = self::fetchAllNodes();
        $json = json_encode(
            ['data' => ['activities' => ['nodes' => $nodes]]],
            JSON_THROW_ON_ERROR,
        );
        set_transient(self::ACTIVITIES_TRANSIENT_KEY, $json, HOUR_IN_SECONDS);
        return $json;
    }

    /** @return list<mixed> */
    private static function fetchAllNodes(): array
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
            $page = json_decode(self::request($after), true, 512, JSON_THROW_ON_ERROR);
            $activities = $page['data']['activities'];
            array_push($allNodes, ...$activities['nodes']);
            $hasNextPage = $activities['pageInfo']['hasNextPage'];
            $after = $activities['pageInfo']['endCursor'];
        } while ($hasNextPage);
        return $allNodes;
    }

    private static function request(?string $after): string
    {
        $response = wp_remote_post(self::ENDPOINT, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . self::bearerToken(),
            ],
            'body' => self::buildRequestPayload($after),
        ]);

        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode !== 200) {
            $rawBody = wp_remote_retrieve_body($response);
            throw new \RuntimeException("Eversports API returned HTTP {$statusCode}: {$rawBody}");
        }

        $responseBody = wp_remote_retrieve_body($response);
        self::assertNoGraphQLErrors($responseBody);

        return $responseBody;
    }

    private static function buildRequestPayload(?string $after): string
    {
        $timezone = new \DateTimeZone('Europe/Berlin');
        $variables = [
            'startDate' => (new \DateTimeImmutable('today', $timezone))->format('c'),
            'endDate'   => (new \DateTimeImmutable('+52 weeks', $timezone))->format('c'),
            'after'     => $after,
        ];

        return json_encode(
            ['query' => self::QUERY, 'variables' => $variables],
            JSON_THROW_ON_ERROR,
        );
    }

    private static function assertNoGraphQLErrors(string $responseBody): void
    {
        $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        $errors = is_array($decoded) ? ($decoded['errors'] ?? null) : null;
        if (is_array($errors)) {
            $messages = array_filter(array_column($errors, 'message'), 'is_string');
            throw new \RuntimeException('GraphQL errors: ' . implode('; ', $messages));
        }
    }

    private static function bearerToken(): string
    {
        $raw   = get_option(self::OPTION_TOKEN, '');
        $token = is_string($raw) ? $raw : '';
        if ($token === '') {
            throw new \RuntimeException(
                'Eversports API token is not configured. Please set it in Settings → KMC Eversports.',
            );
        }
        return $token;
    }
}

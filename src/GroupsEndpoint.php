<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class GroupsEndpoint
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoute']);
    }

    public static function registerRoute(): void
    {
        register_rest_route('kmc-eversports/v1', '/groups', [
            'methods'  => 'GET',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle(): \WP_REST_Response
    {
        try {
            $groups = GroupsParser::parse(EversportsClient::fetchGroups());
        } catch (\Throwable $e) {
            error_log('KMC Eversports: ' . $e->getMessage());
            return new \WP_REST_Response(['message' => 'Failed to load groups.'], 500);
        }

        return new \WP_REST_Response(array_map(
            static fn (GroupSummary $group): array => ['id' => $group->id, 'name' => $group->name],
            $groups,
        ));
    }
}

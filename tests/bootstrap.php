<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

define('HOUR_IN_SECONDS', 3600);

class WP_Error
{
    public function __construct(
        private readonly string $code = '',
        private readonly string $message = '',
    ) {}

    public function get_error_message(): string
    {
        return $this->message;
    }
}

function is_wp_error(mixed $thing): bool
{
    return $thing instanceof WP_Error;
}

function wp_remote_retrieve_response_code(array $response): int
{
    return (int) ($response['response']['code'] ?? 0);
}

function wp_remote_retrieve_body(array $response): string
{
    return (string) ($response['body'] ?? '');
}

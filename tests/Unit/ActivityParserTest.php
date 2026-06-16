<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Kmc\Eversports\ActivityParser;
use Kmc\Eversports\ClassGroup;
use PHPUnit\Framework\TestCase;

final class ActivityParserTest extends TestCase
{
    public function test_it_extracts_unique_class_groups_from_the_graphql_response(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-activities.json');
        self::assertNotFalse($json);

        $activityParser = new ActivityParser();
        $groups = $activityParser->parse($json);

        self::assertEquals(
            [
                new ClassGroup('1cd940d5-2585-432c-bbd8-fc556159b421', 'Grundlagenprogramm'),
                new ClassGroup('33555851-d287-4224-aab7-9350b367661b', 'Herzjuwel mit Lamrim-Meditation'),
                new ClassGroup('f3991596-7670-46af-9168-8fb03a8d9d4c', 'Meditation & moderner Buddhismus'),
            ],
            $groups,
        );
    }
}

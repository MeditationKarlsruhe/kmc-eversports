<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Kmc\Eversports\ActivityParser;
use Kmc\Eversports\ClassGroup;
use Kmc\Eversports\MalformedActivitiesResponse;
use PHPUnit\Framework\TestCase;

final class ActivityParserTest extends TestCase
{
    public function testItExtractsUniqueClassGroupsFromTheResponse(): void
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

    public function testItReturnsNoGroupsForAnEmptyActivityList(): void
    {
        $activityParser = new ActivityParser();
        $groups = $activityParser->parse('{"data":{"activities":{"nodes":[]}}}');

        self::assertSame([], $groups);
    }

    public function testItThrowsOnAMalformedResponse(): void
    {
        $this->expectException(MalformedActivitiesResponse::class);

        $activityParser = new ActivityParser();
        $activityParser->parse('{"data":{"activities":{}}}');
    }
}

<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Kmc\Eversports\ActivityParser;
use Kmc\Eversports\Appointment;
use Kmc\Eversports\MalformedActivitiesResponse;
use PHPUnit\Framework\TestCase;

final class ActivityParserTest extends TestCase
{
    public function testItExtractsThreeUniqueGroupsFromTheFixture(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-activities.json');
        self::assertNotFalse($json);

        $groups = (new ActivityParser())->parse($json);

        self::assertCount(3, $groups);
        self::assertSame('1cd940d5-2585-432c-bbd8-fc556159b421', $groups[0]->id);
        self::assertSame('Grundlagenprogramm', $groups[0]->title);
        self::assertSame('33555851-d287-4224-aab7-9350b367661b', $groups[1]->id);
        self::assertSame('Herzjuwel mit Lamrim-Meditation', $groups[1]->title);
        self::assertSame('f3991596-7670-46af-9168-8fb03a8d9d4c', $groups[2]->id);
        self::assertSame('Meditation & moderner Buddhismus', $groups[2]->title);
    }

    public function testItCollectsAppointmentsPerGroup(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-activities.json');
        self::assertNotFalse($json);

        $groups = (new ActivityParser())->parse($json);

        // Grundlagenprogramm has 2 activities in the fixture
        self::assertCount(2, $groups[0]->appointments);
        // Herzjuwel has 2 activities
        self::assertCount(2, $groups[1]->appointments);
        // Meditation & moderner Buddhismus has 1 activity
        self::assertCount(1, $groups[2]->appointments);
    }

    public function testItParsesAppointmentFieldsCorrectly(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-activities.json');
        self::assertNotFalse($json);

        $groups = (new ActivityParser())->parse($json);

        $first = $groups[0]->appointments[0];
        self::assertInstanceOf(Appointment::class, $first);
        self::assertEquals(new \DateTimeImmutable('2026-06-15T18:00:00+02:00'), $first->start);
        self::assertEquals(new \DateTimeImmutable('2026-06-15T20:00:00+02:00'), $first->end);
        self::assertSame(
            'https://www.eversports.de/org/activity/993f80f6-632b-4fed-a248-2ddcddcf92d5',
            $first->registrationLink,
        );
    }

    public function testItSetsImageUrlFromFirstImage(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-activities.json');
        self::assertNotFalse($json);

        $groups = (new ActivityParser())->parse($json);

        $expectedUrl = 'https://files.eversports.com/f79b7bdb-1c2d-45ae-a60d-ceb5423ed045/'
            . 'eversports__bild-prasentation-43-11png-original.png';
        self::assertSame($expectedUrl, $groups[0]->imageUrl);
    }

    public function testItSetsImageUrlToNullWhenNoImagesArePresent(): void
    {
        $json = json_encode([
            'data' => [
                'activities' => [
                    'nodes' => [[
                        'id' => 'abc',
                        'start' => '2026-06-15T10:00:00+02:00',
                        'end' => '2026-06-15T11:00:00+02:00',
                        'detailsPageURL' => 'https://www.eversports.de/org/activity/abc',
                        'activityGroup' => [
                            'id' => 'grp-1',
                            'name' => 'Test Group',
                            'description' => ['html' => '<p>Test</p>'],
                            'images' => ['nodes' => []],
                        ],
                    ]],
                ],
            ],
        ]);
        self::assertIsString($json);

        $groups = (new ActivityParser())->parse($json);

        self::assertNull($groups[0]->imageUrl);
    }

    public function testItReturnsNoGroupsForAnEmptyActivityList(): void
    {
        $groups = (new ActivityParser())->parse('{"data":{"activities":{"nodes":[]}}}');

        self::assertSame([], $groups);
    }

    public function testItThrowsOnAMalformedResponse(): void
    {
        $this->expectException(MalformedActivitiesResponse::class);

        (new ActivityParser())->parse('{"data":{"activities":{}}}');
    }
}

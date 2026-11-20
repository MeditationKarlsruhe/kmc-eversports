<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Kmc\Eversports\ActivityParser;
use Kmc\Eversports\Appointment;
use Kmc\Eversports\ClassGroup;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

final class ActivityParserTest extends TestCase
{
    use MatchesSnapshots;

    public function testItParsesTheFixtureCorrectly(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-activities.json');
        self::assertNotFalse($json);

        $groups = ActivityParser::parse($json);

        $this->assertMatchesJsonSnapshot($this->groupsToArray($groups));
    }

    public function testItSetsImageUrlFromFirstImage(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-activities.json');
        self::assertNotFalse($json);

        $groups = ActivityParser::parse($json);
        $grundlagenprogramm = array_values(array_filter(
            $groups,
            static fn (ClassGroup $group): bool => $group->id === '1cd940d5-2585-432c-bbd8-fc556159b421',
        ));

        $expectedUrl = 'https://files.eversports.com/f79b7bdb-1c2d-45ae-a60d-ceb5423ed045/'
            . 'eversports__bild-prasentation-43-11png-original.png';
        self::assertSame($expectedUrl, $grundlagenprogramm[0]->imageUrl);
    }

    public function testItOrdersGroupsByTheirEarliestAppointment(): void
    {
        $json = $this->activitiesJson([
            ['later-group', '2026-12-27T10:00:00+01:00'],
            ['earlier-group', '2026-11-29T15:00:00+01:00'],
            ['later-group', '2026-12-28T10:00:00+01:00'],
        ]);

        $groups = ActivityParser::parse($json);

        self::assertSame(['earlier-group', 'later-group'], array_column($groups, 'id'));
    }

    public function testItOrdersAppointmentsWithinAGroupByStart(): void
    {
        $json = $this->activitiesJson([
            ['challenge', '2027-01-11T08:00:00+01:00'],
            ['challenge', '2027-01-12T08:00:00+01:00'],
            ['challenge', '2027-01-11T18:00:00+01:00'],
            ['challenge', '2027-01-12T18:00:00+01:00'],
        ]);

        $groups = ActivityParser::parse($json);
        $starts = array_map(
            static fn (Appointment $appointment): string => $appointment->start->format('c'),
            $groups[0]->appointments,
        );

        self::assertSame([
            '2027-01-11T08:00:00+01:00',
            '2027-01-11T18:00:00+01:00',
            '2027-01-12T08:00:00+01:00',
            '2027-01-12T18:00:00+01:00',
        ], $starts);
    }

    public function testItComparesStartTimesAcrossUtcOffsets(): void
    {
        $json = $this->activitiesJson([
            ['local-later', '2026-10-25T02:30:00+01:00'],
            ['local-earlier', '2026-10-25T02:45:00+02:00'],
        ]);

        $groups = ActivityParser::parse($json);

        self::assertSame(['local-earlier', 'local-later'], array_column($groups, 'id'));
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

        $groups = ActivityParser::parse($json);

        self::assertNull($groups[0]->imageUrl);
    }

    public function testItReturnsNoGroupsForAnEmptyActivityList(): void
    {
        $groups = ActivityParser::parse('{"data":{"activities":{"nodes":[]}}}');

        self::assertSame([], $groups);
    }

    /** @param list<array{0: string, 1: string}> $activities groupId and start of each activity */
    private function activitiesJson(array $activities): string
    {
        $nodes = array_map(static fn (array $activity): array => [
            'start' => $activity[1],
            'end' => $activity[1],
            'detailsPageURL' => null,
            'activityGroup' => [
                'id' => $activity[0],
                'name' => $activity[0],
                'description' => ['html' => ''],
                'images' => ['nodes' => []],
            ],
        ], $activities);

        return json_encode(['data' => ['activities' => ['nodes' => $nodes]]], JSON_THROW_ON_ERROR);
    }

    /**
     * @param  list<ClassGroup> $groups
     * @return list<array{
     *     id: string,
     *     title: string,
     *     descriptionHtml: string,
     *     imageUrl: ?string,
     *     appointments: list<array{start: string, end: string, registrationLink: string|null}>,
     * }>
     */
    private function groupsToArray(array $groups): array
    {
        return array_map(function (ClassGroup $group): array {
            return [
                'id' => $group->id,
                'title' => $group->title,
                'descriptionHtml' => $group->descriptionHtml,
                'imageUrl' => $group->imageUrl,
                'appointments' => array_map(fn (Appointment $appt): array => [
                    'start' => $appt->start->format('c'),
                    'end' => $appt->end->format('c'),
                    'registrationLink' => $appt->registrationLink,
                ], $group->appointments),
            ];
        }, $groups);
    }
}

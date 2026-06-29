<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Kmc\Eversports\ActivityParser;
use Kmc\Eversports\Appointment;
use Kmc\Eversports\ClassGroup;
use Kmc\Eversports\MalformedActivitiesResponse;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

final class ActivityParserTest extends TestCase
{
    use MatchesSnapshots;

    public function testItParsesTheFixtureCorrectly(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-activities.json');
        self::assertNotFalse($json);

        $parser = new ActivityParser();
        $groups = $parser->parse($json);

        $this->assertMatchesJsonSnapshot($this->groupsToArray($groups));
    }

    public function testItSetsImageUrlFromFirstImage(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-activities.json');
        self::assertNotFalse($json);

        $parser = new ActivityParser();
        $groups = $parser->parse($json);

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

        $parser = new ActivityParser();
        $groups = $parser->parse($json);

        self::assertNull($groups[0]->imageUrl);
    }

    public function testItReturnsNoGroupsForAnEmptyActivityList(): void
    {
        $parser = new ActivityParser();
        $groups = $parser->parse('{"data":{"activities":{"nodes":[]}}}');

        self::assertSame([], $groups);
    }

    public function testItThrowsOnAMalformedResponse(): void
    {
        $this->expectException(MalformedActivitiesResponse::class);

        $parser = new ActivityParser();
        $parser->parse('{"data":{"activities":{}}}');
    }

    /**
     * @param  list<ClassGroup> $groups
     * @return list<array{
     *     id: string,
     *     title: string,
     *     descriptionHtml: string,
     *     imageUrl: ?string,
     *     appointments: list<array{start: string, end: string, registrationLink: string}>,
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

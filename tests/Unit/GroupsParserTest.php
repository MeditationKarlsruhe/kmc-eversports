<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Kmc\Eversports\GroupsParser;
use Kmc\Eversports\GroupSummary;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

final class GroupsParserTest extends TestCase
{
    use MatchesSnapshots;

    public function testItParsesTheFixtureCorrectly(): void
    {
        $json = file_get_contents(__DIR__ . '/../../spike/sample-groups.json');
        self::assertNotFalse($json);

        $groups = GroupsParser::parse($json);

        $this->assertMatchesJsonSnapshot(array_map(
            static fn (GroupSummary $group): array => ['id' => $group->id, 'name' => $group->name],
            $groups,
        ));
    }

    public function testItReturnsNoGroupsForAnEmptyList(): void
    {
        $groups = GroupsParser::parse('{"data":{"activityGroups":{"nodes":[]}}}');

        self::assertSame([], $groups);
    }
}

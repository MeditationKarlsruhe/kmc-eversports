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

    public function testItSortsGroupsAlphabeticallyCaseInsensitive(): void
    {
        $json = json_encode([
            'data' => ['activityGroups' => ['nodes' => [
                ['id' => '1', 'name' => 'Zazen'],
                ['id' => '2', 'name' => 'anfängerkurs'],
                ['id' => '3', 'name' => 'Meditation'],
            ]]],
        ], JSON_THROW_ON_ERROR);

        $groups = GroupsParser::parse($json);

        self::assertSame(
            ['anfängerkurs', 'Meditation', 'Zazen'],
            array_map(static fn (GroupSummary $group): string => $group->name, $groups),
        );
    }
}

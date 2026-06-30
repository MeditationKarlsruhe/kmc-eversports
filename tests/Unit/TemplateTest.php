<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Kmc\Eversports\Appointment;
use Kmc\Eversports\ClassGroup;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

final class TemplateTest extends TestCase
{
    use MatchesSnapshots;

    public function testItRendersGroupsAsHtml(): void
    {
        $groups = [
            new ClassGroup(
                id: 'grp-1',
                title: 'Meditation für Anfänger',
                descriptionHtml: '<p>Ein Kurs für <a href="https://example.com">Einsteiger</a>.</p>',
                imageUrl: 'https://files.eversports.com/example.jpg',
                appointments: [
                    new Appointment(
                        start: new \DateTimeImmutable('2026-07-07T10:00:00+02:00'),
                        end: new \DateTimeImmutable('2026-07-07T11:00:00+02:00'),
                        registrationLink: 'https://www.eversports.de/org/activity/abc',
                    ),
                    new Appointment(
                        start: new \DateTimeImmutable('2026-07-14T10:00:00+02:00'),
                        end: new \DateTimeImmutable('2026-07-14T11:00:00+02:00'),
                        registrationLink: null,
                    ),
                ],
            ),
            new ClassGroup(
                id: 'grp-2',
                title: 'Fortgeschrittene Meditation',
                descriptionHtml: '<p>Für erfahrene Praktizierende.</p>',
                imageUrl: null,
                appointments: [
                    new Appointment(
                        start: new \DateTimeImmutable('2026-07-08T18:00:00+02:00'),
                        end: new \DateTimeImmutable('2026-07-08T19:30:00+02:00'),
                        registrationLink: 'https://www.eversports.de/org/activity/xyz',
                    ),
                ],
            ),
        ];

        $html = $this->renderTemplate($groups, showImage: true);

        $this->assertMatchesHtmlSnapshot($html);
    }

    public function testItHidesImagesWhenShowImageIsFalse(): void
    {
        $groups = [
            new ClassGroup(
                id: 'grp-1',
                title: 'Test Kurs',
                descriptionHtml: '<p>Beschreibung.</p>',
                imageUrl: 'https://files.eversports.com/example.jpg',
                appointments: [],
            ),
        ];

        $html = $this->renderTemplate($groups, showImage: false);

        self::assertStringNotContainsString('kmc-event-group__image', $html);
        self::assertStringNotContainsString('files.eversports.com', $html);
    }

    public function testItOmitsRegistrationLinkWhenNull(): void
    {
        $groups = [
            new ClassGroup(
                id: 'grp-1',
                title: 'Test Kurs',
                descriptionHtml: '<p>Beschreibung.</p>',
                imageUrl: null,
                appointments: [
                    new Appointment(
                        start: new \DateTimeImmutable('2026-07-07T10:00:00+02:00'),
                        end: new \DateTimeImmutable('2026-07-07T11:00:00+02:00'),
                        registrationLink: null,
                    ),
                ],
            ),
        ];

        $html = $this->renderTemplate($groups, showImage: false);

        self::assertStringNotContainsString('kmc-appointment__register', $html);
        self::assertStringNotContainsString('Anmeldung', $html);
    }

    /**
     * @param list<ClassGroup> $groups
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function renderTemplate(array $groups, bool $showImage): string
    {
        ob_start();
        include __DIR__ . '/../../templates/eversports-events.php';
        return (string) ob_get_clean();
    }
}

<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Kmc\Eversports\AviaCanvasCourseNames;
use PHPUnit\Framework\TestCase;

final class AviaCanvasCourseNamesTest extends TestCase
{
    public function testItRendersOneEscapedItemPerCourse(): void
    {
        $result = AviaCanvasCourseNames::render('Arya Tara Puja | Yoga &amp; <Ruhe> | ');

        self::assertSame(
            "<div class='avia-element-items kmc-eversports-course-names' data-update_with='course_names'"
            . " style='flex-direction:column;align-items:flex-start'>"
            . "<span class='avia-element-item'>Arya Tara Puja</span>"
            . "<span class='avia-element-item'>Yoga &amp; &lt;Ruhe&gt;</span>"
            . '</div>',
            $result,
        );
    }

    public function testItRendersAnEmptyContainerWithoutCourses(): void
    {
        $result = AviaCanvasCourseNames::render('');

        self::assertStringEndsWith("align-items:flex-start'></div>", $result);
    }
}

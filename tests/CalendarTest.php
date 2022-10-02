<?php


namespace EvanPiAlert\Test;

require_once(__DIR__ . "/../src/autoload.php");

use EvanPiAlert\Util\Cache;
use EvanPiAlert\Util\Calendar;
use PHPUnit\Framework\TestCase;

class CalendarTest extends TestCase {

    public function testAll() {
        Cache::hardClear();
        $calendar = new Calendar('ru', '2020');
        $hours = $calendar->getWorkingHoursBetween('2020-01-01', '2020-01-10');
        $this->assertEquals(2*8, $hours, 'Новогодние праздники');
        $hours = $calendar->getWorkingHoursBetween('2020-01-01', '2020-03-31');
        $this->assertEquals(440, $hours, '1 квартал');
        // 2 квартал пропустим, т.к. там была пандемия
        $hours = $calendar->getWorkingHoursBetween('2020-07-01', '2020-09-30');
        $this->assertEquals(520, $hours, '3 квартал');
        $hours = $calendar->getWorkingHoursBetween('2020-10-01', '2020-12-31');
        $this->assertEquals(518, $hours, '4 квартал');

        $calendar = new Calendar('ru', '2021');
        $this->assertTrue($calendar->isWorkingDay('2021-02-20'), 'Перенос рабочего дня не обработался');
        $this->assertFalse($calendar->isWorkingDay('2021-06-14'), 'Праздничный день не обработался');
        $this->assertFalse($calendar->isWorkingDay('2021-06-20'), 'Почему-то выходной отмечен как рабочий');
        $this->assertTrue($calendar->isWorkingDay('2021-06-22'), 'Почему-то будний отмечен как нерабочий');
    }
}
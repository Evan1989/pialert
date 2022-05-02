<?php


namespace EvanPiAlert\Util;

class Calendar {

    // нет значения - рабочий день
    // 1 - праздничный день
    // 2 - рабочий и сокращенный (может быть использован для любого дня недели)
    // 3 - рабочий день из-за переноса (суббота/воскресенье)

    private array $holidaysData = array(); // unix дата => тип особенности

    public function __construct( $year = false ) {
        if ( !$year ) {
            $year = date('Y');
        }
        $this->loadWorkCalendar( $year );
    }

    /**
     * Функция учитывает произведственный календарь
     * @param string $date
     * @return bool True, если день рабочий
     */
    public function isWorkingDay(string $date) :bool {
        $time = strtotime($date);
        if ( isset($this->holidaysData[$time]) ) {
            return $this->holidaysData[$time] != 1;
        }
        return date("N", $time) <= 5;
    }

    private function loadWorkCalendar( int $year ) : void {
        $cacheName = 'CalendarHolidayData_'.$year;
        if ( isset($_SESSION[$cacheName]) ) {
            $this->holidaysData = $_SESSION[$cacheName];
            return;
        }
        /** @noinspection HttpUrlsUsage */
        $xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
        $holidaysData = json_decode(json_encode($xml), true);
        foreach ($holidaysData['days']['day'] as $day) {
            $date = $day['@attributes']['d'];
            $date = strtotime($year.'-'.mb_substr($date,0,2).'-'.mb_substr($date,3,2));
            if ( date("N", $date) >= 6 && $day['@attributes']['t'] == 1 ) {
                continue;
            }
            $this->holidaysData[$date] = $day['@attributes']['t'];
        }
        $_SESSION[$cacheName] = $this->holidaysData;
    }

    /**
     * Получить количество рабочих часов между
     * @param string $from День в формате YYYY-MM-DD
     * @param string $to День в формате YYYY-MM-DD
     * @return int
     */
    public function getWorkingHoursBetween(string $from, string $to): int {
        $from = strtotime($from);
        $to = strtotime($to);
        $days = ($to-$from) / ONE_DAY + 1;
        $fullWeeks = floor($days / 7);
        $remainingDays = fmod($days, 7);
        $firstDayOfWeek = date("N", $from);
        $lastDayOfWeek = date("N", $to);
        if ($firstDayOfWeek <= $lastDayOfWeek) {
            if ($firstDayOfWeek <= 6 && 6 <= $lastDayOfWeek) $remainingDays--;
            if ($firstDayOfWeek <= 7 && 7 <= $lastDayOfWeek) $remainingDays--;
        } else {
            if ($firstDayOfWeek == 7) {
                $remainingDays--;
                if ($lastDayOfWeek == 6) {
                    $remainingDays--;
                }
            } else {
                $remainingDays -= 2;
            }
        }
        $workingDays = $fullWeeks * 5;
        if ($remainingDays > 0) {
            $workingDays += $remainingDays;
        }
        $workingHours = $workingDays*8;
        foreach ($this->holidaysData as $date => $type) {
            if ($from <= $date && $date <= $to) {
                switch ($type) {
                    case 1:
                        $workingHours -= 8;
                        $workingDays--;
                        break;
                    case 2:
                        if ( date("N", $date) <= 5 ) {
                            $workingHours -= 1;
                        } else {
                            $workingHours += 7;
                            $workingDays++;
                        }
                        break;
                    case 3:
                        $workingHours += 8;
                        $workingDays++;
                        break;
                }
            }
        }
        return $workingHours;
    }
}
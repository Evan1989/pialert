<?php

namespace EvanPiAlert\Util;

class AlertAnalytics {

    const CACHE_NAME_PREFIX = 'AlertAnalyticsCache_';
    const HOUR_IN_WEEK = 168;

    public function __construct() {}

    /**
     * @param string $system
     * @param string $extSystem
     * @param int $weekDay От 0 до 6
     * @param int $hour
     * @return int Стандартный интервал между алертами (если данных нет, то сутки)
     */
    public function getAverageAlertInterval(string $system, string $extSystem, int $weekDay, int $hour) : int {
        $absHour = $weekDay * 24 + $hour;
        $alertCountInHour = $this->getAverageAlertCount($system, $extSystem, $absHour, 2);
        if ( $alertCountInHour > 1) {
            return 3600 / $alertCountInHour;
        }
        $avgCounts = $this->getAverageAlertCounts($system, $extSystem);
        for($i = 2; $i <= 24; $i++) {
            $j = ($absHour - $i + static::HOUR_IN_WEEK) % static::HOUR_IN_WEEK;
            if ( ($avgCounts[$j]??0) > 1 ) {
                return $i * 3600;
            }
        }
        return ONE_DAY;
    }

    /**
     * Функция возвращает среднее количества алертов в данный день/час
     * Усреднение идет по дням недели и часам за месяц, плюс среднее на N часов от заказанного часа.
     * @param string $system
     * @param string $extSystem
     * @param int $absHour Часов от начала недели
     * @param int $hourInterval На сколько часов в прошлое усреднять
     * @return float Количество алертов
     */
    protected function getAverageAlertCount(string $system, string $extSystem, int $absHour, int $hourInterval) : float {
        $avgCounts = $this->getAverageAlertCounts($system, $extSystem);
        $variants = array();
        for($i = $absHour - $hourInterval; $i <= $absHour; $i++) {
            $j = ($i + static::HOUR_IN_WEEK) % static::HOUR_IN_WEEK;
            $variants[] = $avgCounts[$j]??0;
        }
        return array_sum($variants)/count($variants);
    }

    /**
     * Функция возвращает массив среднего количества алертов.
     * Усреднение идет по дням недели и часам за месяц.
     * @param string $system
     * @param string $extSystem
     * @return array absolute hour (часов от начала недели) => avg_count
     */
    public function getAverageAlertCounts(string $system, string $extSystem): array {
        if ( $cache = Cache::get(static::CACHE_NAME_PREFIX.$system.$extSystem) ) {
            return $cache;
        }
        $result = array();
        if(empty($extSystem)) {
            $query = DB::prepare("SELECT count(*) as count, WEEKDAY(timestamp) as week_day, HOUR(timestamp) as hour, min(timestamp) as min_timestamp FROM alerts WHERE piSystemName = ? AND timestamp > NOW() - INTERVAL 4 WEEK group by week_day, hour");
        $query->execute(array($system));
        }
        else{
            $query = DB::prepare("SELECT count(*) as count, WEEKDAY(timestamp) as week_day, HOUR(timestamp) as hour, min(timestamp) as min_timestamp FROM alerts WHERE (fromSystem = ? OR toSystem=?) AND timestamp > NOW() - INTERVAL 4 WEEK group by week_day, hour");
            $query->execute(array($extSystem, $extSystem));
        }
        while($row = $query->fetch()) {
            $hour = $row['week_day'] * 24 + $row['hour'];
            $weekCount = ceil( (time() - strtotime($row['min_timestamp'])) / ONE_WEEK );
            $result[ $hour ] = $row['count'] / $weekCount;
        }
        Cache::save(static::CACHE_NAME_PREFIX.$system.$extSystem, $result, ONE_DAY);
        return $result;
    }
}
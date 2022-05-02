<?php

namespace EvanPiAlert\Util;

use EvanPiAlert\Util\essence\PiAlertGroup;

class HTMLChart {

    const DIAGRAM_COLORS = array('green','Orange','grey','blue','purple','red','Cyan','yellow','Maroon','Chocolate','Lime','Indigo','Khaki', 'SALMON', 'black');

    public function __construct() {}

    /**
     * JavaScript код для отображения графика количества сообщений по дням
     * @param PiAlertGroup|string $param Либо группа ошибок, либо имя SAP PI, если пусто, то вернет по всем системам
     * @return string
     */
    public function getDailyAlertsChart(PiAlertGroup|string $param) : string {
        if ( $param instanceof PiAlertGroup) {
            $query = $param->getAlertCountForDiagram(ONE_MONTH);
        } else {
            $query = PiAlertGroup::getDailyAlertCountForDiagram($param, ONE_MONTH);
        }
        $data1= array();
        while($row = $query->fetch()) {
            $data1[$row['date']] = $row['count'];
        }
        $data = array(Text::alertCount() => $data1);
        return "<canvas class='w-100' id='alertDailyHistory' style='display: block; max-height: 200px; max-width: 800px;'></canvas>".
            $this->getLineChartJs('alertDailyHistory', $data, false);
    }

    /**
     * JavaScript код для отображения графика количества сообщений по часам
     * @param string $piSystemName
     * @return string
     */
    public function getHourAlertsChart(string $piSystemName) : string {
        $weekDay = date("N")-1; // от 0 до 6
        $hour = date("H");

        $query = PiAlertGroup::getHourAlertCountForDiagram($piSystemName);
        $data1= array();
        while($row = $query->fetch()) {
            $data1[$row['h']] = $row['count'];
        }

        $systems = array();
        if ( $piSystemName ) {
            $systems[] = $piSystemName;
        } else {
            $query = DB::prepare("SELECT DISTINCT piSystemName FROM alerts WHERE timestamp > NOW() - INTERVAL 4 WEEK");
            $query->execute(array());
            while($row = $query->fetch()) {
                $systems[] = $row['piSystemName'];
            }
        }
        $data2 = array();
        $data3 = array();
        $alertAnalytics = new AlertAnalytics();
        foreach ( $systems as $system ) {
            $avgCounts = $alertAnalytics->getAverageAlertCounts($system);
            for ($i = 0; $i < 24; $i++) {
                $data2[$i] = ($data2[$i]??0) + ($avgCounts[$weekDay*24+$i]??0);
            }
            for ($i = 0; $i < AlertAnalytics::HOUR_IN_WEEK; $i++) {
                $data3[$i % 24] = ($data3[$i% 24]??0) + ($avgCounts[$i]??0);
            }
        }
        for ($i = 0; $i < 24; $i++) {
            if ( $i <= $hour ) {
                $data1[$i] = $data1[$i]??0;
            }
            $data2[$i] = round10($data2[$i]??0) ;
            $data3[$i] = round10($data3[$i]??0 / 7 ) ;
        }
        $data = array(
            Text::today().' ('.date('Y-m-d').')' => $data1,
            Text::chartsNormalForDay(Text::dayNameArray()[$weekDay+1]) => $data2,
            Text::chartsAverageDay() => $data3,
        );
        return "<canvas class='w-100' id='alertHourHistory' style='display: block; height:150px; max-height: 200px; min-width: 300px; max-width: 800px;'></canvas>".
            $this->getLineChartJs('alertHourHistory', $data, false);
    }

    /**
     * @param string $id Идентификатор элемента на странице
     * @param array $data Массив в формате подпись => значение на оси Х (например, дата или час) => значение на оси Y
     * @param bool $fillEmptyPoints Заполнять точки на графике нулями, если там нет значения
     * @return string
     */
    protected function getLineChartJs(string $id, array $data, bool $fillEmptyPoints = true) : string {
        $datasets = array();
        $num = 0;
        $x_values = array();
        foreach ($data as $point) {
            foreach ($point as $x_value => $value) {
                if ( !in_array($x_value, $x_values) ) {
                    $x_values[] = $x_value;
                }
            }
        }
        asort($x_values);
        foreach ($data as $label => $point) {
            $dataset = array();
            foreach ( $x_values as $x_value ) {
                if ( $fillEmptyPoints || isset($point[$x_value]) ) {
                    $dataset[] = ($point[$x_value] ?? 0);
                } else {
                    $dataset[] = null;
                }
            }
            $datasets[] = array(
                'label' => $label,
                'data' => $dataset,
                'cubicInterpolationMode' => 'monotone',
                'backgroundColor' => self::DIAGRAM_COLORS[$num],
                'borderColor' => self::DIAGRAM_COLORS[$num],
                'fill' => false,
                'borderWidth' => 2,
                'hidden' => ($num>=2)
            );
            $num++;
        }
        return "<!--suppress ALL -->
            <script type='text/javascript'>
                $(document).ready(function() {
                    new Chart(
                        document.getElementById('".$id."').getContext('2d'),
                        {
                            type: 'line',
                            data: {
                                labels: ['".implode("','", $x_values)."'],
                                datasets: ".json_encode($datasets)."
                            }
                        }
                    );
                });
            </script>";
    }
}
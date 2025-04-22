<?php

namespace EvanPiAlert\Util;

use EvanPiAlert\Util\essence\MessageStatistic;

class MessageStatisticServiceCall {

    protected bool|string $username;
    protected bool|string $password;
    protected HttpProvider $httpProvider;


    public function __construct() {
       $this->username = Settings::get(Settings::MESSAGE_STAT_SERVICE_USER);
       $this->password = Settings::get(Settings::MESSAGE_STAT_SERVICE_PASSWORD);
       $this->httpProvider = new HttpProvider();
    }

    public function generateServiceUrl(string $begin, string $end, string $systemName,string $systemHostName):  string  {
        /** @noinspection HttpUrlsUsage */
        return 'http://'.$systemHostName.'/mdt/performancedataqueryservlet?component='.$systemName.'&begin='.urlencode($begin).'&end='.urlencode($end);
    }

    protected function isXml(string $data) : bool {
        return mb_substr($data, 0, 14) == '<?xml version=';
    }

    /**
     * @param string $systemName
     * @param string $systemHostName
     * @param string $begin
     * @param string $end
     * @return int HTTP response code, 200 = OK
     */
    public function serviceCallPerSystem(string $systemName, string $systemHostName, string $begin, string $end): int {
        $url = $this->generateServiceUrl($begin, $end, $systemName, $systemHostName);
        $response = $this->httpProvider->requestGET($url, $this->username . ':' . $this->password);
        if ($response['http_code']<>200) {
            //echo 'Ошибка при вызове сервиса статистики: '.$response['http_code'];
            $this->logError("receive HTTP ".$response['http_code']);
            return $response['http_code'];
        }
        if ( $this->isXml($response['http_body']) ) {
            $xml = simplexml_load_string($response['http_body']);
            if (isset($xml->Data->DataRows->Row)) {
                $db_input_array=array();
                $entries = iterator_to_array($xml->Data->ColumnNames->Column, false);
                $FROM_SERVICE_NAME_idx=null;
                $TO_SERVICE_NAME_idx=null;
                $SCENARIO_IDENTIFIER_idx=null;
                $ACTION_NAME_idx=null;
                $MESSAGE_COUNTER_idx=null;
                $MEASURING_POINTS_idx=null;
                $TOTAL_PROCESSING_TIME_idx=null;
                foreach ($entries as $index => $val)
                {
                    switch ($val) {
                        case "FROM_SERVICE_NAME":
                            $FROM_SERVICE_NAME_idx=$index;
                            break;
                        case "TO_SERVICE_NAME":
                            $TO_SERVICE_NAME_idx=$index;
                            break;
                        case "SCENARIO_IDENTIFIER":
                            $SCENARIO_IDENTIFIER_idx=$index;
                            break;
                        case "ACTION_NAME":
                            $ACTION_NAME_idx=$index;
                            break;
                        case "MESSAGE_COUNTER":
                            $MESSAGE_COUNTER_idx=$index;
                            break;
                        case "MEASURING_POINTS":
                            $MEASURING_POINTS_idx=$index;
                            break;
                        case "TOTAL_PROCESSING_TIME":
                            $TOTAL_PROCESSING_TIME_idx=$index;
                            break;
                    }
                }
                foreach ($xml->Data->DataRows->Row as $row) { //строка содержащая информацию о статистике обработки сообщений
                    $pi_proc_time = $row->Entry[$TOTAL_PROCESSING_TIME_idx]; //вычисляем время обработки в SAP PI
                    if(is_iterable($row->Entry[$MEASURING_POINTS_idx]->MeasuringPoints->MP)) {
                        foreach ($row->Entry[$MEASURING_POINTS_idx]->MeasuringPoints->MP as $MP) { //находим время обработки в Адаптере
                            if (mb_substr_count($MP->Name, 'module_') > 0) {
                                $pi_proc_time -= ($MP->Avg);
                            }
                        }
                    }
                    $key=$systemName.$row->Entry[$FROM_SERVICE_NAME_idx].$row->Entry[$TO_SERVICE_NAME_idx]. $row->Entry[$ACTION_NAME_idx];
                    if (!empty((string)$row->Entry[$TO_SERVICE_NAME_idx]) && !empty((string)$row->Entry[$SCENARIO_IDENTIFIER_idx])) { //исключаем ошибочные сообщения с неизвестным получателем
                        if(!isset( $db_input_array[$key])) {
                            $db_input_array[$key]['systemName'] = $systemName;
                            $db_input_array[$key]['fromSystem'] = $row->Entry[$FROM_SERVICE_NAME_idx];
                            $db_input_array[$key]['toSystem'] = $row->Entry[$TO_SERVICE_NAME_idx];
                            $db_input_array[$key]['interface'] = $row->Entry[$ACTION_NAME_idx];
                            $db_input_array[$key]['timestamp'] = $end;
                            $db_input_array[$key]['messageCount'] = (int)$row->Entry[$MESSAGE_COUNTER_idx];
                            $db_input_array[$key]['messageProcTime'] = (int)$row->Entry[$TOTAL_PROCESSING_TIME_idx];
                            $db_input_array[$key]['messageProcTimePI'] = (int)($pi_proc_time);
                        }
                        else
                        {
                            $db_input_array[$key]['messageCount'] += (int)$row->Entry[$MESSAGE_COUNTER_idx];
                            $db_input_array[$key]['messageProcTime'] += (int)$row->Entry[$TOTAL_PROCESSING_TIME_idx];
                            $db_input_array[$key]['messageProcTimePI'] += (int)($pi_proc_time);

                        }
                    }
                }
                foreach ($db_input_array as $item) {
                    $msg_stat = new MessageStatistic(
                        $item['systemName'],
                        $item['fromSystem'],
                        $item['toSystem'],
                        $item['interface'],
                        $item['timestamp'],
                        $item['messageCount'],
                        $item['messageProcTime'],
                        $item['messageProcTime'] -   $item['messageProcTimePI']
                    );
                    if (!$msg_stat->saveNewToDatabase()) { //сохраняем в БД
                        $this->logError("Error saving to DB row: SystemName=" . $systemName . ", fromSystem=" . $item['fromSystem'] . ", toSystem=" . $item['toSystem'] . ", interface=" .  $item['interface'] . ", timestamp=" . $item['timestamp'] . ", message_count=" .  $item['messageCount'] . ", messageProcTime=" .   $item['messageProcTime'] . ", messageProcTimePI=" .   $item['messageProcTime'] -   $item['messageProcTimePI']);
                    }
                }
                return $response['http_code'];
            }
        } else {
            $this->logError("Error in MessageStatistic service call. Error in response ".$response['http_body']);
            return 401;
        }
        return $response['http_code'];
    }

    public function updateStatisticForAllSystem() : bool {
        $res = false;
        foreach (ManagePiSystem::getPiSystems() as $piSystem) {
            if ( !$piSystem->getStatisticEnable() ) {
                continue;
            }
            $query = DB::prepare("SELECT MAX(timestamp) as max FROM messages_stat WHERE piSystemName=?");
            $query->execute(array($piSystem->getSystemName()));
            $end = strtotime(date("Y-m-d H:00:00.0"));
            while($row = $query->fetch()) {
                $begin = strtotime( (string)$row['max'] );
                if( $begin === false ) {
                    $begin = $end - 3600; //Данные за часовой интервал
                }
                if( $end - $begin > ONE_DAY) {
                    $begin = $end - ONE_DAY; // За последние сутки, данные по часовым интервалам хранятся 24 часа
                }
                while( $end - $begin >= 3600 ) {
                    $temp_end = $begin + 3600;
                    $this->serviceCallPerSystem($piSystem->getSystemName(), $piSystem->getHost(), date("Y-m-d H:00:0.0",$begin), date("Y-m-d H:00:0.0",$temp_end));
                    $begin = $temp_end;
                }
            }
            $res = true;
        }
        return $res;
    }

    protected function logError(?string $textToError): void {
        error_log(date("Y-m-d H:i:s")."MessageStatisticServiceCall catch error: ".($textToError??'').PHP_EOL);
    }
}
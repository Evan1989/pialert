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
                foreach ($xml->Data->DataRows->Row as $row) { //строка содержащая информацию о статистике обработки сообщений
                    $pi_proc_time = $row->Entry[20]; //вычисляем время обработки в SAP PI
                    foreach ($row->Entry[22]->MeasuringPoints->MP as $MP) { //находим время обработки в Адаптере
                        if (mb_substr_count($MP->Name, 'module_') > 0) {
                            $pi_proc_time -= ($MP->Avg);
                        }
                    }
                    if ( !empty( (string) $row->Entry[8]) && !empty( (string) $row->Entry[11]) ) { //исключаем ошибочные сообщения с неизвестным получателем
                        $msg_stat = new MessageStatistic(
                            $systemName, $row->Entry[6], $row->Entry[8],
                            $row->Entry[9], $end, (int) $row->Entry[13],
                            (int) $row->Entry[20], (int) ($pi_proc_time)
                        );
                        if ( !$msg_stat->saveNewToDatabase() ) { //сохраняем в БД
                            $this->logError("Error saving to DB row: SystemName=".$systemName.", fromSystem=".$row->Entry[6].", toSystem=".$row->Entry[8].", interface=".$row->Entry[9].", timestamp=".$end.", message_count=".$row->Entry[13].", messageProcTime=".$row->Entry[20].", messageProcTimePI=". $row->Entry[20] - $pi_proc_time);
                        }
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
        $piSystems = new ManagePiSystem();
        $res = false;
        foreach ($piSystems->getPiSystems() as $piSystem) {
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
                    $begin = $end - ONE_DAY; // За последние сутки, т.к. данные по часовым интервалам хранятся 24 часа
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
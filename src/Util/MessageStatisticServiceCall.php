<?php

namespace EvanPiAlert\Util;

use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\MessageStatistic;
use EvanPiAlert\Util\essence\PiSystem;
use SimpleXMLElement;


class MessageStatisticServiceCall{

    public ?string $username = null;
    public ?string $password = null;


    public function __construct() {
       $this->username=Settings::get(Settings::MESSAGE_STAT_SERVICE_USER);
       $this->password=Settings::get(Settings::MESSAGE_STAT_SERVICE_PASSWORD);
    }

    public function generateServiceUrl(string $begin, string $end, string $systemName,string $systemHostName):  string  {

        return 'http://' . $systemHostName . '/mdt/performancedataqueryservlet?component=' . $systemName . '&begin=' . rawurlencode($begin) . '&end=' . rawurlencode($end);
    }

    function isXml(string $value): bool
    {
        $prev = libxml_use_internal_errors(true);

        $doc = simplexml_load_string($value);
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return false !== $doc && empty($errors);
    }

    /**
     * @throws \Exception
     */
    public function serviceCallPerSystem(HttpProvider $serviceCall, string $systemName, string $systemHostName,$begin, $end): int {
        $url = $this->generateServiceUrl($begin, $end, $systemName, $systemHostName);
        $response = $serviceCall->requestGET($url, $this->username . ':' . $this->password);
        if ($response['http_code']<>200) {
            //echo 'Ошибка при вызове сервиса статистики: '.$response['http_code'];
            $this->logError("Error in MessageStatistic service call. HTTP_CODE=".$response['http_code']);
            return $response['http_code'];
        }
        else {
               if ($this->isXml($response['http_body']))
              {
                $xml = simplexml_load_string($response['http_body']);
                if (isset($xml->Data->DataRows->Row)) {
                    foreach ($xml->Data->DataRows->Row as $row) { //строка содержащая информацию о статистике обработки сообщений
                        $pi_proc_time = $row->Entry[20]; //вычисляем время обработки в SAP PI
                        foreach ($row->Entry[22]->MeasuringPoints->MP as $MP) //находим время обработки в Адаптере
                        {
                            if (mb_substr_count($MP->Name, 'module_out') > 0 && mb_substr_count($MP->Name, 'Adapter') > 0) {
                                $pi_proc_time = (($row->Entry[20]) - ($MP->Avg));
                                break;
                            }
                        }
                        if (!(empty((string)$row->Entry[8]))) { //исключаем ошибочные сообщения с неизвестным получателем
                            $msg_stat = new MessageStatistic($systemName, (string)$row->Entry[6], (string)$row->Entry[8], (string)$row->Entry[9], $end, (string)$row->Entry[13], (string)$row->Entry[20], ($row->Entry[20] - $pi_proc_time));
                            if (!$msg_stat->saveNewToDatabase()) //сохраняем в БД
                            {
                                $this->logError("Error saving to DB row: SystemName=" . $systemName . ", fromSystem=" . (string)$row->Entry[6] . ", toSystem=" . (string)$row->Entry[8] . ", interface=" . (string)$row->Entry[9] . ", timestamp=" . $end . ", message_count=" . (string)$row->Entry[13] . ", messageProcTime=" . (string)$row->Entry[20] . ", messageProcTimePI=" . ($row->Entry[20] - $pi_proc_time));
                            }
                        }
                    }
                    return $response['http_code'];
                }
              } else {
                    $this->logError("Error in MessageStatistic service call. Error in response" . $response['http_body']);
                    return 400;
                }
        }
        return $response['http_code'];
    }

    /**
     * @throws \Exception
     */
    public function serviceCall(): bool {
        $piSystems=new ManagePiSystem();
        $serviceCall = new HttpProvider();
        $res=false;
        foreach ($piSystems->getPiSystems() as $value) {
            if($value->getStatisticEnable()) {
                $query = DB::prepare("SELECT MAX(timestamp) FROM messages_stat WHERE piSystemName=?");
                $query->execute(array($value->getSystemName()));
                $end = date("Y-m-d H:00:00.0");
                while($row = $query->fetch()) {
                    $begin=$row['MAX(timestamp)'];
                    if(!isset($begin)) {
                        $begin = date("Y-m-d H:00:0.0", strtotime($end) - 3600); //запрашиваем данные за часовой интервал
                    }
                    if(strtotime($end)- strtotime($begin)>ONE_DAY) {
                        $begin=date("Y-m-d H:00:0.0", strtotime($end) - ONE_DAY); //запрашиваем за послединие сутки, т.к. данные по часовым интервалам хранятся 24 часа
                    }
                    while(strtotime($end)- strtotime($begin)>=3600) {
                        $end_iterator=date("Y-m-d H:00:0.0", strtotime($begin) + 3600);
                        $this->serviceCallPerSystem($serviceCall, $value->getSystemName(), $value->getHost(), $begin, $end_iterator);
                        $begin=$end_iterator;
                    }
                }
                $res=true;
            }

        }
        return $res;
    }

    protected function logError(?string $textToError): void {
        error_log(date("Y-m-d H:i:s")."MessageStatisticServiceCall catch error: ".($textToError??'').PHP_EOL);
    }
}
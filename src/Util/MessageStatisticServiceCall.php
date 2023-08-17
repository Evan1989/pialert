<?php

namespace EvanPiAlert\Util;

use EvanPiAlert\Util\essence\MessageStatistic;
use SimpleXMLElement;


class MessageStatisticServiceCall{

    public ?string $username = null;
    public ?string $password = null;


    public function __construct() {
       $this->username=Settings::get(Settings::MESSAGE_STAT_SERVICE_USER);
       $this->password=Settings::get(Settings::MESSAGE_STAT_SERVICE_PASSWORD);
    }

    protected function generateServiceUrl(string $begin, string $end, string $systemName,string $systemHostName):  string  {

        return 'http://' . $systemHostName . '/mdt/performancedataqueryservlet?component=' . $systemName . '&begin=' . rawurlencode($begin) . '&end=' . rawurlencode($end);
    }
    /**
     * @throws \Exception
     */
    protected function serviceCallPerSystem(HttpProvider $serviceCall, string $systemName, string $systemHostName): void {
        $end = date("Y-m-d H:00:00.0");
        $begin = date("Y-m-d H:00:0.0", strtotime($end) - 3600); //запрашиваем данные за часовой интервал
        $url = $this->generateServiceUrl($begin, $end, $systemName, $systemHostName);
        $response = $serviceCall->requestGET($url, $this->username . ':' . $this->password);
        if ($response['http_code']<>200) {
            //echo 'Ошибка при вызове сервиса статистики: '.$response['http_code'];
            $this->logError("Error in MessageStatistic service call. HTTP_CODE=".$response['http_code']);
        }
        else {
            $xml = new SimpleXMLElement($response['http_body']);
            foreach ($xml->Data->DataRows->Row as $row) { //строка содержащая информацию о статистике обработки сообщений
                $pi_proc_time = $row->Entry[20]; //вычисляем время обработки в SAP PI
                foreach ($row->Entry[22]->MeasuringPoints->MP as $MP) //находим время обработки в Адаптере
                {
                    if (mb_substr_count($MP->Name, 'module_out')>0 && mb_substr_count($MP->Name, 'Adapter')>0) {
                        $pi_proc_time = (($row->Entry[20]) - ($MP->Avg));
                        break;
                    }
                }
                if(!(empty((string)$row->Entry[8]))) { //исключаем ошибочные сообщения с неизвестным получателем
                    $msg_stat = new MessageStatistic( $systemName,(string)$row->Entry[6],(string)$row->Entry[8],(string)$row->Entry[9],$end,(string)$row->Entry[13],(string)$row->Entry[20],($row->Entry[20] - $pi_proc_time));
                    if (!$msg_stat->saveNewToDatabase()) //сохраняем в БД
                    {
                        $this->logError("Error saving to DB row= ".$systemName."".(string)$row->Entry[6]""."".""."".""."".""."".""."".""."".);
                    }
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function serviceCall(): void {
        $systemInfo=(json_decode(Settings::get(Settings::SYSTEMS_SETTINGS),true)); //считывание параметров систем
        $serviceCall = new HttpProvider();
        foreach ($systemInfo as $key => $value) {
            if($value['StatEnable']=='true') {
                $this->serviceCallPerSystem($serviceCall,$key,$value['Host']);
            }
        }
    }

    protected function logError(?string $textToError): void {
        error_log(date("Y-m-d H:i:s")."MessageStatisticServiceCall catch error: ".($textToError??'').PHP_EOL);
    }



}
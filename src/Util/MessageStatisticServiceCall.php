<?php

namespace EvanPiAlert\Util;

use EvanPiAlert\Util\essence\MessageStatistic;
use EvanPiAlert\Util\HttpProvider;
use SimpleXMLElement;


class MessageStatisticServiceCall   {

    public static function serviceCall(){

        $end = date("Y-m-d H:00:00.0");
        $begin = date("Y-m-d H:00:0.0", strtotime($end) - 3600); //запрашиваем данные за часовой интервал
        $SystemInfo=(json_decode(Settings::get(Settings::SYSTEMS_SETTINGS),true)); //считывание параметров систем
        $username=Settings::get(Settings::MESSAGE_STAT_SERVICE_USER);
        $password=Settings::get(Settings::MESSAGE_STAT_SERVICE_PASSWORD);
        foreach ($SystemInfo as $key => $value) {
            if($value['StatEnable']=='true') {
                $serviceCall = new HttpProvider();
                $SystemName = $key;
                $SystemHostName = $value['Host'];
                $url = 'http://' . $SystemHostName . '/mdt/performancedataqueryservlet?component=' . $SystemName . '&begin=' . rawurlencode($begin) . '&end=' . rawurlencode($end);
                $response = $serviceCall->requestGET($url, $username . ':' . $password);
                if ($response['http_code']<>200) {
                    echo 'Ошибка при вызове сервиса статистики: '.$response['http_code'];
                //    error_log('Ошибка при вызове сервиса статистики: '.$response['http_code'].PHP_EOL,3,'error_log.txt');
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
                        if(!(empty((string)$row->Entry[8]))) {
                            $newRow = array(
                                'piSystemName' => $SystemName,
                                'fromSystem' => (string)$row->Entry[6],
                                'toSystem' => (string)$row->Entry[8],
                                'interface' => (string)$row->Entry[9],
                                'timestamp' => $end,
                                'messageCount' => (string)$row->Entry[13],
                                'messageProcTime' => (string)$row->Entry[20],
                                'messageProcTimePI' => ($row->Entry[20] - $pi_proc_time)
                            );
                            $msg_stat = new MessageStatistic($newRow);
                            if (!$msg_stat->saveNewToDatabase()) //сохраняем в БД
                            {
                                http_response_code(500);
                            }
                        }
                    }
                }
            }
        }
    }


}
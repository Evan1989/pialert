<?php

namespace EvanPiAlert\Util\jobs;

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\Cache;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\MessageStat;
use EvanPiAlert\Util\essence\PiAlertGroup;
use EvanPiAlert\Util\Settings;
use SimpleXMLElement;

class JobSaveMessageStat extends JobAbstract {

    const JOB_INTERVAL = 120; // Раз в сутки

    protected function executeJobInternal(): void {
        $end = date("Y-m-d H:00:00.0");
        $begin = date("Y-m-d H:i:s.v", strtotime($end) - 3600); //запрашиваем данные за часовой интервал
        $SystemInfo=(json_decode(Settings::get(Settings::SYSTEMS_SETTINGS),true)); //считывание параметров систем
        foreach ($SystemInfo as $key => $value) {
            if($SystemInfo[$key]['StatEnable']=='true'){
                $username=$SystemInfo[$key]['user'];
                $password=$SystemInfo[$key]['password'];
                $SystemName=$key;
                $SystemHostName=$SystemInfo[$key]['Host'];
                $url='http://'.$SystemHostName.'/mdt/performancedataqueryservlet?component='.$SystemName.'&begin='.rawurlencode($begin).'&end='.rawurlencode($end);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
                $response = curl_exec($ch);
                curl_close($ch);
                $xml = new SimpleXMLElement($response);
                foreach($xml->Data->DataRows->Row as $row)
                {
                    $pi_proc_time=$row->Entry[20]; //вычисляем время обработки в SAP PI
                    foreach($row->Entry[22]->MeasuringPoints->MP as $MP) //находим время обработки в Адаптере
                    {
                        if(str_contains($MP->Name, 'module_out')&&str_contains($MP->Name, 'Adapter')){
                            $pi_proc_time=(($row->Entry[20])-($MP->Avg));
                            break;
                        }
                    }
                    $newRow = array(
                        'piSystemName'=> $SystemName,
                        'fromSystem' => (string)$row->Entry[6],
                        'toSystem' => (string)$row->Entry[8],
                        'interface' => (string)$row->Entry[9],
                        'timestamp' =>$end,
                        'message_count' => (string)$row->Entry[13],
                        'messageProcTime' => (string)$row->Entry[20],
                        'messageProcTimePI' => ($row->Entry[20]-$pi_proc_time)
                    );
                    MessageStat::saveNewToDatabase($newRow);
                }
            }
        }
    }

    protected function getSettingName(): string {
        return Settings::JOB_MESSAGE_STAT_REFRESH_TIME;
    }
}
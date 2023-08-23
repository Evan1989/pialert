<?php

namespace EvanPiAlert\Util;
use EvanPiAlert\Util\essence\PiAlert;
use EvanPiAlert\Util\Settings;

/**
 * Class MessageStatAlertGen Класс для генерации алертов по данным статистики обработки сообщений
 * @package EvanPiAlert\Util
 */
class MessageStatAlertGen{

    public function __construct()
    {

    }

    /**
     * @throws \Exception
     */
    public function generatePiAlertMessageCount():bool{
        $query = DB::prepare("SELECT * FROM messages_stat WHERE timestamp>NOW() - INTERVAL 1 DAY");
        $query->execute();
        while($row = $query->fetch()){
               if(!$this->savePiAlert($row,'Error: message count per hour is low'));
            {
                error_log(date("Y-m-d H:i:s")."MessageStatAlertGen catch error: ".('DB save error'.$row).PHP_EOL);
            }
        }
    }
    public function generatePiAlertMessageProcTime():bool{
        $query = DB::prepare("SELECT * FROM messages_stat WHERE piSystemName = ? AND fromSystem = ? AND toSystem = ? AND interface = ? AND timestamp = ?");
        $query->execute(array( $this->piSystemName,$this->fromSystem,$this->toSystem,$this->interface,$this->timestamp));
        while($row = $query->fetch()) {
            if(!$this->savePiAlert($row,'Error: message count per hour is low'));
            {
                error_log(date("Y-m-d H:i:s")."MessageStatAlertGen catch error: ".('DB save error'.$row).PHP_EOL);
            }
        }
    }

    protected function savePiAlert($row,$errText):bool{
        $newRow = array(
            'group_id' => '',
            'alertRuleId' => '',
            'piSystemName' => $row['piSystemName'],
            'priority' => '',
            'timestamp' => $row['timestamp'],
            'messageId' =>'',
            'fromSystem' => $row['fromSystem'],
            'toSystem' => $row['toSystem'],
            'adapterType' => '',
            'channel' => '',
            'interface' => $row['interface'],
            'errText' => $errText
        );
        $piAlert=new PiAlert($newRow);
        $alertGroup = AlertAggregationUtil::createOrFindGroupForAlert($piAlert);
        $piAlert->group_id = $alertGroup->group_id;
        return $piAlert->saveNewToDatabase();
    }
}
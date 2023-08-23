<?php

namespace EvanPiAlert\Util;
use EvanPiAlert\Util\essence\PiAlert;
use EvanPiAlert\Util\Settings;

class MessageStatAlertUtil{

    // проверка по времени обработки /1000000 для получения значения в секундах

    public function __construct()
    {

    }

    /**
     * @throws \Exception
     */
    public function generatePiAlertMessageCount():bool{//алерт при увеличенном времени обработки сообщений
        $query = DB::prepare("SELECT * FROM messages_stat WHERE HAVING AVG(messageProcTime)/1000000>1 AND timestamp>NOW() - INTERVAL 1 DAY");
        $query->execute(array( $this->durationAlert));
        while($row = $query->fetch()){
               $this->savePiAlert($row,'Ошибка: количество сообщений за час минимально или отсутсвует');
        }
    }
    public function generatePiAlertMessageProcTime():bool{  //алерт
        $query = DB::prepare("SELECT * FROM messages_stat WHERE piSystemName = ? AND fromSystem = ? AND toSystem = ? AND interface = ? AND timestamp = ?");
        $query->execute(array( $this->piSystemName,$this->fromSystem,$this->toSystem,$this->interface,$this->timestamp));
        while($row = $query->fetch()) {
                $this->savePiAlert($row,'Ошибка: время обработки сообщений превышает пороговое значение');
        }
    }

    protected function savePiAlert($row,$errText){
        $newRow = array(
            'group_id' => '',
            'alertRuleId' => vsprintf( '%s%s%s%s%s%s%s%s', str_split(bin2hex(random_bytes(16)), 4) ),
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
        if ( $piAlert->saveNewToDatabase() !== true ) {
            http_response_code( 500 );
        }
    }
}
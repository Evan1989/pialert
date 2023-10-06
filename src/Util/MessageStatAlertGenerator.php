<?php

namespace EvanPiAlert\Util;
use EvanPiAlert\Util\essence\PiAlert;

/**
 * Class MessageStatAlertGen Класс для генерации алертов по данным статистики обработки сообщений
 */
class MessageStatAlertGenerator {

    protected string $message_count_alert;
    protected string $message_procTime_alert;
    protected string $stat_enable_piSystem_list;


    public function __construct() {
        $this->message_count_alert = Settings::get(Settings::ALERT_MESSAGE_COUNT);
        $this->message_procTime_alert = Settings::get(Settings::ALERT_PROC_TIME);
        $this->stat_enable_piSystem_list = $this->getStatEnablePiSystemList();
    }

    public function generatePiAlertMessageCount() : void {
        $query = DB::prepare("SELECT ms1.messageCount,ms2.avg_msg_cnt,ms1.piSystemName,ms1.interface,ms1.fromSystem,ms1.toSystem,ms1.timestamp FROM 
            (SELECT ms1.piSystemName,ms1.interface,ms1.fromSystem,ms1.toSystem,ms1.id,ms1.timestamp,ms1.messageCount
            FROM   messages_stat ms1  INNER JOIN 
            (SELECT MAX(TIMESTAMP) AS max_time,piSystemName,interface,fromSystem,toSystem  FROM messages_stat WHERE timestamp>NOW() - INTERVAL 2 MONTH GROUP BY piSystemName,interface,fromSystem,toSystem ORDER BY TIMESTAMP DESC) ms2
            ON
                ms2.piSystemName=ms1.piSystemName AND
                ms2.interface=ms1.interface AND
                ms2.fromSystem=ms1.fromSystem AND
                ms2.toSystem=ms1.toSystem AND
                ms2.max_time=ms1.timestamp)  
            AS ms1   RIGHT OUTER JOIN 
            (SELECT ROUND(AVG(messageCount)) AS avg_msg_cnt,piSystemName,interface,fromSystem,toSystem FROM messages_stat WHERE timestamp>NOW() - INTERVAL 2 MONTH
            GROUP BY piSystemName,interface,fromSystem,toSystem) AS ms2
                ON ms1.piSystemName=ms2.piSystemName AND
                ms1.interface=ms2.interface AND
                ms1.fromSystem=ms2.fromSystem AND
                ms1.toSystem=ms2.toSystem  
                WHERE  (ms1.messageCount IS NULL OR ms2.avg_msg_cnt/ms1.messageCount>?) AND ms1.piSystemName NOT IN (?)");
        $query->execute(array($this->message_count_alert, $this->stat_enable_piSystem_list));
        $errorText = Text::messageAlertCount();
        while($row = $query->fetch()) {
            if ( !$this->savePiAlert($row, $errorText.$row['interface'].PHP_EOL.Text::averageMessageCount().$row['avg_msg_cnt'].PHP_EOL.Text::currentMessageCount().$row['messageCount']) ) {
                $this->logError("Don't save newStatCountAlert for ".json_encode($row));
            }
        }
    }
    public function generatePiAlertMessageProcTime() : void {
        $query = DB::prepare("SELECT ms1.msg_proc_time,ms2.avg_msg_proc_time,ms1.piSystemName,ms1.interface,ms1.fromSystem,ms1.toSystem,ms1.timestamp FROM 
            (SELECT ms1.piSystemName,ms1.interface,ms1.fromSystem,ms1.toSystem,ms1.id,ROUND(ms1.messageProcTime/1000) AS msg_proc_time,ms1.timestamp
            FROM   messages_stat ms1  INNER JOIN 
            (SELECT MAX(TIMESTAMP) AS max_time,piSystemName,interface,fromSystem,toSystem  FROM messages_stat WHERE timestamp>NOW() - INTERVAL 2 MONTH GROUP BY piSystemName,interface,fromSystem,toSystem ORDER BY TIMESTAMP DESC) ms2
            ON
                ms2.piSystemName=ms1.piSystemName AND
                ms2.interface=ms1.interface AND
                ms2.fromSystem=ms1.fromSystem AND
                ms2.toSystem=ms1.toSystem AND
                ms2.max_time=ms1.timestamp)  as ms1 JOIN
            (SELECT ROUND(AVG(messageProcTime)/1000) AS avg_msg_proc_time,piSystemName,interface,fromSystem,toSystem FROM messages_stat 
            WHERE timestamp>NOW() - INTERVAL 2 MONTH
            GROUP BY piSystemName,interface,fromSystem,toSystem) AS ms2 ON
                ms1.piSystemName=ms2.piSystemName AND
                ms1.interface=ms2.interface AND
                ms1.fromSystem=ms2.fromSystem AND
                ms1.toSystem=ms2.toSystem  
            WHERE ms1.msg_proc_time/ms2.avg_msg_proc_time>? AND ms1.piSystemName NOT IN (?)");
        $query->execute(array($this->message_procTime_alert, $this->stat_enable_piSystem_list));
        $errorText = Text::messageAlertProcTime();
        while($row = $query->fetch()) {
            if ( !$this->savePiAlert($row,$errorText.$row['interface'].PHP_EOL.Text::averageMessageProcessingTime().$row['avg_msg_proc_time'].PHP_EOL.Text::currentMessageProcessingTime().$row['msg_proc_time']) ) {
                $this->logError("Don't save newStatProcTimeAlert for ".json_encode($row));
            }
        }
    }

    protected function getStatEnablePiSystemList() : string {
        $piSystems = new ManagePiSystem();
        $res = '';
        foreach ($piSystems->getPiSystems() as $piSystem) {
            if ( !$piSystem->getStatisticEnable() )
            {
                $res=$res.$piSystem->getSystemName().',';
            }
        }
        return substr($res, 0, -1);
    }

    protected function savePiAlert($row,$errText) : bool {
        $newRow = array(
            'group_id' => 0,
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
            'errText' => $errText,
            'namespace' =>'',
            'monitoringUrl' =>'',
            'errCategory' =>'',
            'errCode' =>'',
            'UDSAttributes' =>''
        );
        $piAlert=new PiAlert($newRow);
        $alertGroup = AlertAggregationUtil::createOrFindGroupForAlert($piAlert);
        $pattern = '/=\s(\d+)/';
        $replace='= *';
        if(!str_contains($alertGroup->errTextMask, $replace))
        {
            $alertGroup->errTextMask=preg_replace($pattern, $replace, $alertGroup->errTextMask);
            $alertGroup->saveToDatabase();
        }
        $piAlert->group_id = $alertGroup->group_id;
        return $piAlert->saveNewToDatabase();
    }

    protected function logError(?string $textToError): void {
        error_log(date("Y-m-d H:i:s")." MessageStatAlertGenerator catch error: ".($textToError??'').PHP_EOL);
    }
}
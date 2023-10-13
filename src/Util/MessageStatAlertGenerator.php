<?php

namespace EvanPiAlert\Util;
use EvanPiAlert\Util\essence\PiAlert;
use EvanPiAlert\Util\essence\PiAlertGroup;

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
        $query = DB::prepare("
            SELECT curStat.messageCount,avgStat.avg_msg_cnt,curStat.piSystemName,curStat.interface,curStat.fromSystem,curStat.toSystem,curStat.timestamp
            FROM 
                (SELECT ROUND(AVG(messageCount)) AS messageCount,piSystemName,interface,fromSystem,toSystem,MAX(TIMESTAMP) AS timestamp FROM messages_stat 
                 WHERE timestamp>NOW() - INTERVAL 1 DAY
                 GROUP BY piSystemName,interface,fromSystem,toSystem
                ) AS curStat
            RIGHT OUTER JOIN 
                (SELECT ROUND(AVG(messageCount)) AS avg_msg_cnt,piSystemName,interface,fromSystem,toSystem FROM messages_stat
                 WHERE timestamp>NOW() - INTERVAL 2 MONTH
                 GROUP BY piSystemName,interface,fromSystem,toSystem
                ) AS avgStat
            ON curStat.piSystemName=avgStat.piSystemName AND
                curStat.interface=avgStat.interface AND
                curStat.fromSystem=avgStat.fromSystem AND
                curStat.toSystem=avgStat.toSystem  
            WHERE  (curStat.messageCount IS NULL AND avgStat.avg_msg_cnt>? OR avgStat.avg_msg_cnt>?*curStat.messageCount OR curStat.messageCount>?*avgStat.avg_msg_cnt) AND curStat.piSystemName NOT IN (?)");
        $query->execute(array($this->message_count_alert, $this->message_count_alert, $this->message_count_alert, $this->stat_enable_piSystem_list));
        while($row = $query->fetch()) {
            if ( !$this->savePiAlert($row, Text::messageAlertCount($row['interface'], $row['avg_msg_cnt'], $row['messageCount']), Text::messageAlertCount($row['interface'],'','')) ) {
                $this->logError("Don't save newStatCountAlert for ".json_encode($row));
            }
        }
    }
    public function generatePiAlertMessageProcTime() : void {
        $query = DB::prepare("
            SELECT curStat.msg_proc_time,avgStat.avg_msg_proc_time,curStat.piSystemName,curStat.interface,curStat.fromSystem,curStat.toSystem,curStat.timestamp FROM 
                (SELECT ROUND(AVG(messageProcTime)/1000) AS msg_proc_time,piSystemName,interface,fromSystem,toSystem,MAX(TIMESTAMP) AS TIMESTAMP FROM messages_stat 
                 WHERE  timestamp>NOW() - INTERVAL 1 DAY
                 GROUP BY piSystemName,interface,fromSystem,toSystem)
                as curStat
            JOIN
                (SELECT ROUND(AVG(messageProcTime)/1000) AS avg_msg_proc_time,piSystemName,interface,fromSystem,toSystem FROM messages_stat 
                 WHERE timestamp>NOW() - INTERVAL 2 MONTH
                 GROUP BY piSystemName,interface,fromSystem,toSystem
                ) AS avgStat
            ON
                curStat.piSystemName=avgStat.piSystemName AND
                curStat.interface=avgStat.interface AND
                curStat.fromSystem=avgStat.fromSystem AND
                curStat.toSystem=avgStat.toSystem  
            WHERE curStat.msg_proc_time > ? * avgStat.avg_msg_proc_time AND curStat.piSystemName NOT IN (?)");
        $query->execute(array($this->message_procTime_alert, $this->stat_enable_piSystem_list));
        while($row = $query->fetch()) {
            if ( !$this->savePiAlert($row,Text::messageAlertProcTime($row['interface'], $row['avg_msg_proc_time'], $row['msg_proc_time']), Text::messageAlertProcTime($row['interface'],'','')) ) {
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
                $res = $res.$piSystem->getSystemName().',';
            }
        }
        return substr($res, 0, -1);
    }

    protected function savePiAlert(array $row, string $errText, string $defaultText) : bool {
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
        if( $alertGroup->status == PiAlertGroup::NEW )  {
            $alertGroup->errTextMask = TextAnalysisUtil::getMaskFromTexts($errText, $defaultText);
            $alertGroup->saveToDatabase();
        }
        $piAlert->group_id = $alertGroup->group_id;
        return $piAlert->saveNewToDatabase();
    }

    protected function logError(?string $textToError): void {
        error_log(date("Y-m-d H:i:s")." MessageStatAlertGenerator catch error: ".($textToError??'').PHP_EOL);
    }
}
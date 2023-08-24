<?php

namespace EvanPiAlert\Util;
use EvanPiAlert\Util\essence\PiAlert;

/**
 * Class MessageStatAlertGen Класс для генерации алертов по данным статистики обработки сообщений
 */
class MessageStatAlertGenerator {

    public function generatePiAlertMessageCount() : void {
        // TODO Поменять SQL запрос, для анализа статистики, может поток раз в неделю должен работать
        $query = DB::prepare("SELECT * FROM messages_stat WHERE timestamp>NOW() - INTERVAL 1 DAY");
        $query->execute();
        $errorText = "Error: message count per hour is low";
        while($row = $query->fetch()) {
            if ( !$this->savePiAlert($row, $errorText) ) {
                $this->logError("Don't save newStatCountAlert for ".json_encode($row));
            }
        }
    }
    public function generatePiAlertMessageProcTime() : void {
        // TODO Поправить запрос, откуда методу брать данные на вход о системе/интерфейсе и прочее...
        $query = DB::prepare("SELECT * FROM messages_stat WHERE piSystemName = ? AND fromSystem = ? AND toSystem = ? AND interface = ? AND timestamp = ?");
        $query->execute(array( $this->piSystemName,$this->fromSystem,$this->toSystem,$this->interface,$this->timestamp));
        $errorText = 'Error: message count per hour is low';
        while($row = $query->fetch()) {
            if ( !$this->savePiAlert($row,$errorText) ) {
                $this->logError("Don't save newStatProcTimeAlert for ".json_encode($row));
            }
        }
    }

    protected function savePiAlert($row,$errText) : bool {
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

    protected function logError(?string $textToError): void {
        error_log(date("Y-m-d H:i:s")." MessageStatAlertGenerator catch error: ".($textToError??'').PHP_EOL);
    }
}
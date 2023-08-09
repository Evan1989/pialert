<?php

namespace EvanPiAlert\Util\essence;

use PDOException;
use EvanPiAlert\Util\DB;

class MessageStatistic{
    public string $piSystemName = '';
    public string $timestamp;

    public ?string $fromSystem = null;
    public ?string $toSystem = null;
    public ?string $interface = null;

    public ?string $messageCount=null;

    public ?string $messageProcTime=null;

    public ?string $messageProcTimePI=null;

    public function __construct(array $inputData) {
        if ( is_array($inputData) ) {
            $this->createFromRow($inputData);
        }
    }

    private function createFromRow(array $row ) : void {
        $this->piSystemName = $row['piSystemName'];
        $this->timestamp = $row['timestamp'];
        $this->fromSystem = $row['fromSystem'];
        $this->toSystem = $row['toSystem'];
        $this->interface = $row['interface'];
        $this->messageCount = $row['messageCount'];
        $this->messageProcTime = $row['messageProcTime'];
        $this->messageProcTimePI = $row['messageProcTimePI'];
    }

	   public function saveNewToDatabase() : bool {
           $query = DB::prepare("INSERT INTO messages_stat (piSystemName, fromSystem, toSystem, interface, timestamp, messageСount, messageProcTime, messageProcTimePI) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
           return $query->execute(array( $this->piSystemName,$this->fromSystem,$this->toSystem,$this->interface,$this->timestamp,$this->messageCount,$this->messageProcTime,$this->messageProcTimePI));
       }

    public static function DeleteFromDatabase(int $store_in_days) : bool {
        $query = DB::prepare("DELETE FROM messages_stat WHERE timestamp<=NOW()- INTERVAL ? DAY"); //подготовка запроса для удаления данных статистики
        return $query->execute(array($store_in_days));
    }

}
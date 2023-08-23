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

    public function __construct($piSystemName,$fromSystem,$toSystem,$interface,$timestamp,$messageCount,$messageProcTime,$messageProcTimePI) {
        $this->piSystemName = $piSystemName;
        $this->fromSystem = $fromSystem;
        $this->toSystem = $toSystem;
        $this->interface = $interface;
        $this->timestamp = $timestamp;
        $this->messageCount = $messageCount;
        $this->messageProcTime = $messageProcTime;
        $this->messageProcTimePI = $messageProcTimePI;
    }


	   public function saveNewToDatabase() : bool {
           $query = DB::prepare("SELECT piSystemName FROM messages_stat WHERE piSystemName = ? AND fromSystem = ? AND toSystem = ? AND interface = ? AND timestamp = ?");  //проверям на наличие существующей записи
           $query->execute(array( $this->piSystemName,$this->fromSystem,$this->toSystem,$this->interface,$this->timestamp));
           if(!$query->fetch()){
               $query = DB::prepare("INSERT INTO messages_stat (piSystemName, fromSystem, toSystem, interface, timestamp, messageCount, messageProcTime, messageProcTimePI) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
               return $query->execute(array($this->piSystemName, $this->fromSystem, $this->toSystem, $this->interface, $this->timestamp, $this->messageCount, $this->messageProcTime, $this->messageProcTimePI));
           }
           return false;
    }

    public static function DeleteFromDatabase(int $store_in_days) : bool {
        $query = DB::prepare("DELETE FROM messages_stat WHERE timestamp<=NOW()- INTERVAL ? DAY"); //подготовка запроса для удаления данных статистики
        return $query->execute(array($store_in_days));
    }


}
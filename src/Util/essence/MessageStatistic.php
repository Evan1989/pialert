<?php

namespace EvanPiAlert\Util\essence;

use EvanPiAlert\Util\DB;

class MessageStatistic{
    public string $piSystemName = '';
    public string $timestamp;

    public ?string $fromSystem = null;
    public ?string $toSystem = null;
    public ?string $interface = null;

    public ?int $messageCount = null;

    public ?int $messageProcTime = null;

    public ?int $messageProcTimePI = null;

    public function __construct(
            string $piSystemName, ?string $fromSystem, ?string $toSystem,
            ?string $interface, ?string $timestamp, ?int $messageCount,
            ?int $messageProcTime, ?int $messageProcTimePI
    ) {
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
        // Проверяем на наличие существующей записи
       $query = DB::prepare("SELECT * FROM messages_stat WHERE piSystemName = ? AND fromSystem = ? AND toSystem = ? AND interface = ? AND timestamp = ?");
       $query->execute(array( $this->piSystemName,$this->fromSystem,$this->toSystem,$this->interface,$this->timestamp));
       if ( $query->fetch() ) {
           $query = DB::prepare("DELETE FROM messages_stat WHERE piSystemName = ? AND fromSystem = ? AND toSystem = ? AND interface = ? AND timestamp = ?");
           $query->execute(array( $this->piSystemName,$this->fromSystem,$this->toSystem,$this->interface,$this->timestamp));
       }
       // Пишем новые данные
       $query = DB::prepare("INSERT INTO messages_stat (piSystemName, fromSystem, toSystem, interface, timestamp, messageCount, messageProcTime, messageProcTimePI) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
       return $query->execute(array($this->piSystemName, $this->fromSystem, $this->toSystem, $this->interface, $this->timestamp, $this->messageCount, $this->messageProcTime, $this->messageProcTimePI));
    }

    public static function DeleteFromDatabase(int $store_in_days) : bool {
        $query = DB::prepare("DELETE FROM messages_stat WHERE timestamp <= NOW()- INTERVAL ? DAY");
        return $query->execute(array($store_in_days));
    }

}
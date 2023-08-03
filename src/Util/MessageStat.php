<?php

namespace EvanPiAlert\Util;

use PDOException;
use EvanPiAlert\Util\DB;
//require_once('DB.php');

class MessageStat{

    public static function saveNewToDatabase(array $row) : bool {
        $query = DB::prepare("INSERT INTO messages_stat (piSystemName, fromSystem, toSystem, interface, timestamp, message_count, messageProcTime, messageProcTimePI) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $query->execute(array( $row['piSystemName'],$row['fromSystem'],$row['toSystem'],$row['interface'],$row['timestamp'],$row['message_count'],$row['messageProcTime'],$row['messageProcTimePI']));
    }

    public static function DeleteFromDatabase(int $store_in_days) : bool {
        $query = DB::prepare("SELECT MAX(TIMESTAMP) FROM messages_stat where ?=?"); //определяем самый поздний интервал для соотв. системы
        $query->execute(array($store_in_days,$store_in_days));
        if ($row = $query->fetch()) {
            $max_timestamp = $row['MAX(TIMESTAMP)'];
        }
        if(isset($max_timestamp)){
            $timestamp_to_keep = date("Y-m-d H:i:s.v", strtotime($max_timestamp) - $store_in_days*86400); //вычитаем из позднего интервала количество дней из аргумента
        }
        else
        {
            $timestamp_to_keep = date("Y-m-d H:i:s.v", strtotime(date("Y-m-d H:i:s.v")) - $store_in_days*86400); //вычитаем текущей даты количество дней из аргумента
        }

        $query = DB::prepare("DELETE FROM messages_stat where timestamp<=?"); //подготовка запроса для удаления
        return $query->execute(array($timestamp_to_keep));
    }
}
<?php

namespace EvanPiAlert\Util\essence;

use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\Text;
use PDOStatement;

class PiAlertGroup {

    const NEW = 0;
    const IGNORE = 1;
    const MANUAL = 2;
    const WAIT = 3;
    const CLOSE = 4;
    const REOPEN = 5;

    private static ?array $statusNames = null;
    public static function getStatusName(int $statusCode = -1) : string|array {
        if ( is_null(self::$statusNames) ) {
            self::$statusNames = array(
                self::NEW => Text::statusNew(),
                self::IGNORE => Text::statusIgnore(),
                self::MANUAL => Text::statusManual(),
                self::WAIT => Text::statusWait(),
                self::CLOSE => Text::statusClose(),
                self::REOPEN => Text::statusReopen(),
            );
        }
        if ( $statusCode >= 0 ) {
            return self::$statusNames[$statusCode];
        }
        return self::$statusNames;
    }

    const STATUS_COLORS = array(
        self::NEW => "danger",
        self::IGNORE => "success",
        self::MANUAL => "warning",
        self::WAIT => "success",
        self::CLOSE => "success",
        self::REOPEN => "danger",
    );

    public int $group_id = -1;
    public int $status = self::NEW;
    public ?string $comment = null;
    public ?int $user_id = null;

    public string $piSystemName = '';
    public string $fromSystem = '';
    public string $toSystem = '';
    public string $channel = '';
    public string $interface = '';
    public string $errText = '';
    public string $errTextMask = '';

    public string $firstAlert = '';
    public string $lastAlert = '';
    public ?string $lastUserAction = '';
    public int $maybe_need_union = 0;

    public function __construct(array|int|null $inputData = null) {
        if ( is_array($inputData) ) {
            $this->loadFromRow($inputData);
        } elseif( $inputData>0 ) {
            $this->loadFromDB($inputData);
        }
    }

    private function loadFromDB( int $group_id ) : void {
        $query = DB::prepare(" SELECT *  FROM alert_group WHERE group_id = ?");
        $query->execute(array( $group_id ));
        if ($row = $query->fetch()) {
            $this->loadFromRow($row);
        }
    }

    private function loadFromRow(array $row ) : void {
        $this->group_id = $row['group_id'];
        $this->status = $row['status'];
        $this->comment = $row['comment'];
        $this->user_id = $row['user_id'];

        $this->piSystemName = $row['piSystemName'];
        $this->fromSystem = $row['fromSystem'];
        $this->toSystem = $row['toSystem'];
        $this->channel = $row['channel'];
        $this->interface = $row['interface'];
        $this->errText = $row['errText'];
        $this->errTextMask = $row['errTextMask'];

        $this->firstAlert = $row['first_alert'];
        $this->lastAlert = $row['last_alert'];
        $this->lastUserAction = $row['last_user_action'];
        $this->maybe_need_union = $row['maybe_need_union'];
    }


    public function saveToDatabase() : bool {
        if ( $this->group_id > 0 ) {
            $query = DB::prepare("UPDATE alert_group SET status=?, comment=?, user_id=?, piSystemName=?, fromSystem=?, toSystem=?, channel=?, interface=?, errText=?, errTextMask=?, first_alert=?, last_alert=?, last_user_action=?, maybe_need_union=? WHERE group_id = ?");
            return $query->execute(array( $this->status, $this->comment, $this->user_id, $this->piSystemName, $this->fromSystem, $this->toSystem, $this->channel, $this->interface, $this->errText, $this->errTextMask, $this->firstAlert, $this->lastAlert, $this->lastUserAction, $this->maybe_need_union, $this->group_id ));
        } else {
            $query = DB::prepare("INSERT INTO alert_group (status, comment, user_id, piSystemName, fromSystem, toSystem, channel, interface, errText, errTextMask, first_alert, last_alert, last_user_action, maybe_need_union) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $query->execute(array( $this->status, $this->comment, $this->user_id, $this->piSystemName, $this->fromSystem, $this->toSystem, $this->channel, $this->interface, $this->errText, $this->errTextMask, $this->firstAlert, $this->lastAlert, $this->lastUserAction, $this->maybe_need_union ));
            $this->group_id = DB::lastInsertId();
            return ($this->group_id > 0);
        }
    }

    public function getStatusColor(?int $user_id = null) :string {
        return self::statusColor($this->status, $user_id > 0 && $this->user_id == $user_id, $this->lastAlert > $this->lastUserAction);
    }

    public static function statusColor(string $status, bool $taskOfCurrentUser = false, bool $hasNewAlert = true) :string {
        if ( $status == PiAlertGroup::WAIT && $taskOfCurrentUser ) {
            return PiAlertGroup::STATUS_COLORS[PiAlertGroup::MANUAL];
        }
        if ( $status == PiAlertGroup::MANUAL && !$hasNewAlert ) {
            return PiAlertGroup::STATUS_COLORS[PiAlertGroup::IGNORE];
        }
        return PiAlertGroup::STATUS_COLORS[$status];
    }

    public function getUserColor(?int $user_id = null) :string {
        if ( ($this->status == PiAlertGroup::NEW || $this->status == PiAlertGroup::MANUAL || $this->status == PiAlertGroup::WAIT || $this->status == PiAlertGroup::REOPEN ) &&
             $user_id > 0 && $this->user_id == $user_id ) {
            return PiAlertGroup::STATUS_COLORS[PiAlertGroup::MANUAL];
        }
        return '';
    }

    public function getPiSystemSID() :string {
        $systemNames = Settings::get(Settings::SYSTEMS_NAMES);
        if ( $systemNames !== false ) {
            $systemNames = json_decode($systemNames, true);
            if ( isset($systemNames[$this->piSystemName]) ) {
                return $systemNames[$this->piSystemName];
            }
        }
        $parts = explodeWithDefault('.', $this->piSystemName, 3, $this->piSystemName);
        return mb_strtoupper($parts[1]);
    }

    public function getAbout(): string {
        return $this->getPiSystemSID().PHP_EOL.
            $this->fromSystem.
            ($this->toSystem?' → '.$this->toSystem:'').PHP_EOL.
            ($this->interface?:$this->channel);
    }

    public function getHTMLErrorTextMask() :string {
        $text = replaceLinksWithATag($this->errTextMask);
        return str_replace('*', "<span class='text-danger'>*</span>", nl2br($text));
    }

    public function getHTMLComment() :string {
        return nl2br(replaceLinksWithATag($this->comment));
    }

    public function getAlertCount(int|null $timeLimit = null) : int {
        if ( is_null($timeLimit) ) {
            $query = DB::prepare("SELECT count(*) as c  FROM alerts WHERE group_id = ?");
            $query->execute(array($this->group_id));
        } else {
            $query = DB::prepare("SELECT count(*) as c  FROM alerts WHERE group_id = ? AND timestamp > NOW() - INTERVAL ? SECOND ");
            $query->execute(array($this->group_id, $timeLimit));
        }
        if ($row = $query->fetch()) {
            return $row['c'];
        }
        return 0;
    }

    /**
     * @param string $piSystemName Если значение пусто, то возвращается статистика по всем системам
     * @param int|null $timeLimit
     * @return int
     */
    public static function getTotalAlertCount(string $piSystemName, int|null $timeLimit = null) : int {
        if ( is_null($timeLimit) ) {
            if ( $piSystemName ) {
                $query = DB::prepare("SELECT count(*) as c  FROM alerts WHERE piSystemName = ?");
                $query->execute(array($piSystemName));
            } else {
                $query = DB::prepare("SELECT count(*) as c  FROM alerts");
                $query->execute();
            }
        } else {
            if ( $piSystemName ) {
                $query = DB::prepare("SELECT count(*) as c  FROM alerts WHERE piSystemName = ? AND timestamp > NOW() - INTERVAL ? SECOND ");
                $query->execute(array($piSystemName, $timeLimit));
            } else {
                $query = DB::prepare("SELECT count(*) as c  FROM alerts WHERE timestamp > NOW() - INTERVAL ? SECOND ");
                $query->execute(array($timeLimit));
            }
        }
        if ($row = $query->fetch()) {
            return $row['c'];
        }
        return 0;
    }

    /**
     * @param int $timeLimit
     * @return PDOStatement Execute уже выполнен
     */
    public function getAlertCountForDiagram(int $timeLimit = 1) : PDOStatement {
        $query = DB::prepare("
            SELECT count(*) as count, substring(timestamp, 1, 10) as date
            FROM alerts
            WHERE group_id = ? AND timestamp > NOW() - INTERVAL ? SECOND
            GROUP BY date
        ");
        $query->execute(array( $this->group_id, $timeLimit ));
        return $query;
    }

    /**
     * @param string $piSystemName Если значение пусто, то возвращается статистика по всем системам
     * @param int $timeLimit
     * @return PDOStatement Execute уже выполнен
     */
    public static function getDailyAlertCountForDiagram(string $piSystemName, int $timeLimit = 1) : PDOStatement {
        if ( $piSystemName ) {
            $query = DB::prepare("
                SELECT count(*) as count, substring(timestamp, 1, 10) as date
                FROM alerts
                WHERE piSystemName = ? AND timestamp > NOW() - INTERVAL ? SECOND
                GROUP BY date
            ");
            $query->execute(array($piSystemName, $timeLimit));
        } else {
            $query = DB::prepare("
                SELECT count(*) as count, substring(timestamp, 1, 10) as date
                FROM alerts
                WHERE timestamp > NOW() - INTERVAL ? SECOND
                GROUP BY date
            ");
            $query->execute(array( $timeLimit ));
        }
        return $query;
    }

    /**
     * @param string $piSystemName Если значение пусто, то возвращается статистика по всем системам
     * @return PDOStatement Execute уже выполнен
     */
    public static function getHourAlertCountForDiagram(string $piSystemName) : PDOStatement {
        if ( $piSystemName ) {
            $query = DB::prepare("
                SELECT count(*) as count, HOUR(timestamp) as h
                FROM alerts
                WHERE piSystemName = ? AND timestamp >= CURDATE()
                GROUP BY h
            ");
            $query->execute(array($piSystemName));
        } else {
            $query = DB::prepare("
                SELECT count(*) as count, HOUR(timestamp) as h
                FROM alerts
                WHERE timestamp >= CURDATE()
                GROUP BY h
            ");
            $query->execute(array());
        }
        return $query;
    }

    /**
     * Удаление данной группы алертов
     * Сработает, только если нет привязанных алертов
     * @return bool
     */
    public function deleteAlertGroup() : bool {
        $query = DB::prepare("DELETE FROM alert_group WHERE group_id = ?");
        return $query->execute(array($this->group_id));
    }
}
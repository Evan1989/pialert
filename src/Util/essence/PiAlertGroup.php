<?php

namespace EvanPiAlert\Util\essence;

use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\HTML\HTMLPageTemplate;
use EvanPiAlert\Util\ManagePiSystem;
use EvanPiAlert\Util\Text;
use EvanPiAlert\Util\TextAnalysisUtil;
use PDOStatement;

class PiAlertGroup {

    const int NEW = 0;
    const int IGNORE = 1;
    const int MANUAL = 2;
    const int WAIT = 3;
    const int CLOSE = 4;
    const int REOPEN = 5;

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

    const array STATUS_COLORS = array(
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
    public ?string $comment_datetime = null;
    public ?int $user_id = null;
    public ?int $last_user_id = null;

    public string $piSystemName = '';
    public string $fromSystem = '';
    public string $toSystem = '';
    public string $channel = '';
    public string $interface = '';
    public int $multi_interface = 0; // В данной группе ошибки разных интерфейсов, полученных по общему названию канала
    public string $errText = '';
    public string $errTextMask = '';

    public string $firstAlert = '';
    public string $lastAlert = '';
    public ?string $lastUserAction = null;
    public int $maybe_need_union = 0;

    public ?string $alert_link= null;

    protected ?bool $hasGenericException = null;

    public function __construct(array|int|null $inputData = null) {
        if ( is_array($inputData) ) {
            $this->loadFromRow($inputData);
        } elseif( $inputData>0 ) {
            $this->loadFromDB($inputData);
        }
    }

    private function loadFromDB( int $group_id ) : void {
        $query = DB::prepare(" SELECT * FROM alert_group WHERE group_id = ?");
        $query->execute(array( $group_id ));
        if ($row = $query->fetch()) {
            $this->loadFromRow($row);
        }
    }

    private function loadFromRow(array $row ) : void {
        $this->group_id = $row['group_id'];
        $this->status = $row['status'];
        $this->comment = $row['comment'];
        $this->comment_datetime = $row['comment_datetime'];
        $this->user_id = $row['user_id'];
        $this->last_user_id = $row['last_user_id'];

        $this->piSystemName = $row['piSystemName'];
        $this->fromSystem = $row['fromSystem'];
        $this->toSystem = $row['toSystem'];
        $this->channel = $row['channel'];
        $this->interface = $row['interface'];
        $this->multi_interface = $row['multi_interface'];
        $this->errText = $row['errText'];
        $this->errTextMask = $row['errTextMask'];

        $this->firstAlert = $row['first_alert'];
        $this->lastAlert = $row['last_alert'];
        $this->lastUserAction = $row['last_user_action'];
        $this->maybe_need_union = $row['maybe_need_union'];
        $this->alert_link = $row['alert_link'];
    }

    /** Data representation for APIs. */
    public function toArray(): array {
        return [
            'group_id' => $this->group_id,
            'status' => ['code' => $this->status, 'name' => self::getStatusName($this->status)],
            'comment' => $this->comment, 'comment_datetime' => $this->comment_datetime,
            'assigned_user_id' => $this->user_id, 'last_user_id' => $this->last_user_id,
            'pi_system_name' => $this->piSystemName, 'from_system' => $this->fromSystem,
            'to_system' => $this->toSystem, 'channel' => $this->channel, 'interface' => $this->interface,
            'multi_interface' => (bool)$this->multi_interface, 'error_text' => $this->errText,
            'error_mask' => $this->errTextMask, 'first_alert' => $this->firstAlert,
            'last_alert' => $this->lastAlert, 'last_user_action' => $this->lastUserAction,
            'maybe_needs_union' => (bool)$this->maybe_need_union, 'alert_link' => $this->alert_link,
        ];
    }

    public function saveToDatabase() : bool {
        if ( $this->group_id > 0 ) {
            $query = DB::prepare("UPDATE alert_group SET 
                status=?, comment=?, comment_datetime=?, 
                user_id=?, last_user_id=?, piSystemName=?, 
                fromSystem=?, toSystem=?, channel=?, 
                interface=?, errText=?, errTextMask=?,
                errTextMainPart=?, multi_interface=?,
                first_alert=?, last_alert=?, last_user_action=?, 
                maybe_need_union=?, alert_link=? WHERE group_id = ?");
            return $query->execute(array(
                $this->status, $this->comment, $this->comment_datetime,
                $this->user_id, $this->last_user_id, $this->piSystemName,
                $this->fromSystem, $this->toSystem, $this->channel,
                $this->interface, $this->errText, $this->errTextMask,
                $this->getMainPartOfError(), $this->multi_interface,
                $this->firstAlert, $this->lastAlert, $this->lastUserAction,
                $this->maybe_need_union, $this->alert_link, $this->group_id
            ));
        } else {
            $query = DB::prepare("INSERT INTO alert_group (
                 status, comment, comment_datetime,
                 user_id, last_user_id, piSystemName,
                 fromSystem, toSystem, channel,
                 interface, errText, errTextMask,   
                 errTextMainPart, multi_interface,
                 first_alert, last_alert, last_user_action,
                 maybe_need_union, alert_link) VALUES (
                       ?, ?, ?,
                       ?, ?, ?, 
                       ?, ?, ?, 
                       ?, ?, ?, 
                       ?, ?,
                       ?, ?, ?, 
                       ?, ?
                 )");
            $query->execute(array(
                $this->status, $this->comment, $this->comment_datetime,
                $this->user_id, $this->last_user_id, $this->piSystemName,
                $this->fromSystem, $this->toSystem, $this->channel,
                $this->interface, $this->errText, $this->errTextMask,
                $this->getMainPartOfError(), $this->multi_interface,
                $this->firstAlert, $this->lastAlert, $this->lastUserAction,
                $this->maybe_need_union, $this->alert_link
            ));
            $this->group_id = DB::lastInsertId();
            return ($this->group_id > 0);
        }
    }

    public function getStatusColor(?int $user_id = null) :string {
        if ( is_null($this->hasGenericException) ) {
            $this->getHTMLErrorTextMask();
        }
        return self::statusColor($this->status,
            $user_id > 0 && $this->user_id == $user_id,
            $this->lastAlert > $this->lastUserAction,
            $this->hasGenericException
        );
    }

    public static function statusColor(string $status, bool $taskOfCurrentUser = false, bool $hasNewAlert = true, bool $hasGenericException = false) :string {
        if ( $status == PiAlertGroup::WAIT && $taskOfCurrentUser ) {
            return PiAlertGroup::STATUS_COLORS[PiAlertGroup::MANUAL];
        }
        if ( $status == PiAlertGroup::MANUAL && !$hasNewAlert ) {
            return PiAlertGroup::STATUS_COLORS[PiAlertGroup::IGNORE];
        }
        if ( ($status == PiAlertGroup::NEW || $status == PiAlertGroup::REOPEN) && $hasGenericException ) {
            return PiAlertGroup::STATUS_COLORS[PiAlertGroup::MANUAL];
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
        if ( $system = ManagePiSystem::getPiSystems()[$this->piSystemName] ) {
            return $system->getSID();
        }
        $system = new PiSystem($this->piSystemName);
        return $system->getSID();
    }

    /**
     * Описание ICO, которого касается данный тип ошибок
     * @return string
     */
    public function getHTMLAbout(): string {
        if ( $this->multi_interface == 0 ) {
            $object = ($this->interface?:$this->channel);
        } else {
            $object = ($this->channel?:'');
        }
        return nl2br($this->getPiSystemSID().PHP_EOL.
           "<span  class='system_contact'>". $this->fromSystem."</span>".
          ($this->toSystem?' → '  ."<span class='system_contact'>".$this->toSystem:'')."</span>".PHP_EOL.
            $object);
    }


    protected ?string $_HTMLErrorTextMask = null;
    public function getHTMLErrorTextMask() :string {
        if ( is_null($this->_HTMLErrorTextMask) ) {
            $text = htmlspecialchars($this->errTextMask);
            $text = replaceLinksWithATag($text);
            $text = preg_replace('~(GenericException|generic Exception|genric error)~iu', '\\1 <span data-toggle="tooltip" data-placement="top" title="'.Text::genericExceptionTitle().'">'.HTMLPageTemplate::getIcon('patch-question').'</span>', $text, -1, $count);
            $this->hasGenericException = $count > 0;
            $this->_HTMLErrorTextMask = str_replace('*', "<span class='text-danger'>*</span>", nl2br($text));
        }
        return $this->_HTMLErrorTextMask;
    }

    public function getMainPartOfError() : string {
        return TextAnalysisUtil::getMainPartOfPiErrorText($this->errTextMask);
    }

    public function getHTMLComment() :string {
        return nl2br(replaceLinksWithATag($this->comment));
    }

    public function getHTMLAlertLink() :string {
        return nl2br(replaceLinksWithATag($this->alert_link));
    }

    /**
     * Насколько значение алертов за сутки больше, чем обычно
     * @return int 0 - как обычно, чем число больше, тем более превышены средние
     */
    public function getAlert24HourCountCompareVsAverage() : int {
        if ( time() - strtotime($this->firstAlert) < ONE_MONTH ) {
            return 0;
        }
        $sum = 0;
        $lastDay = 0;
        $count = 0;
        $query = $this->getAlertCountForDiagram(ONE_MONTH);
        while($row = $query->fetch()) {
            $lastDay = $row['count'];
            $sum += $row['count'];
            $count++;
        }
        if ( $count < 2 ) {
            return 0;
        }
        $avgCount = ceil(($sum - $lastDay) / ($count - 1));

        // На случай, если наступили новые сутки, и там всего один алерт то, чтобы отразить вчерашние проблемы
        $lastDay = max( $lastDay, $this->getAlertCount(ONE_DAY) );
        if ( $lastDay < 5 || $lastDay < 3 * $avgCount ) {
            return 0;
        }
        if ( $lastDay > 50 * $avgCount ) {
            return 3;
        } elseif ( $lastDay > 10 * $avgCount ) {
            return 2;
        }
        return 1;
    }

    /**
     * @param array $group_ids
     * @param int $timeLimit
     * @return array [group_id => int]
     */
    static public function getAlertsCount(array $group_ids, int $timeLimit) : array {
        if ( empty($group_ids) ) {
            return [];
        }
        $query = DB::prepare("
            SELECT group_id, COUNT(*) AS c FROM alerts
            WHERE group_id IN (".DB::getQuestionMarkForPDO($group_ids).") AND timestamp > NOW() - INTERVAL ? SECOND
            GROUP BY group_id
        ");
        $group_ids[] = $timeLimit;
        $query->execute($group_ids);
        $result = array();
        while ($weekRow = $query->fetch()) {
            $result[$weekRow['group_id']] = (int)$weekRow['c'];
        }
        return $result;
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

    public function setComment(string $comment) : void {
        $this->comment_datetime = date("Y-m-d H:i:s");
        $this->comment = str_replace("<", "&lt;", $comment);
    }

    public function setAlertLink(string $alert_link) : void {
        $this->alert_link= str_replace("<", "&lt;", $alert_link);
    }

    public function setUserId(?int $userID) : void {
        if ( $userID ) {
            $this->last_user_id = null;
            $this->user_id = $userID;
        } else {
            $this->last_user_id = $this->user_id;
            $this->user_id = null;
        }
    }

    static function getSqlSystemFilter(array $piSystemNames) : string {
        if ( empty($piSystemNames) ) {
            return 'false';
        }
        return "piSystemName IN (".DB::getQuestionMarkForPDO($piSystemNames).")";
    }

    /**
     * @param string|array $piSystemName Фильтр по системам источникам алертов
     * @param string $externalSystem Если заполнено, то возвращается статистика по внешней системе
     * @return array
     */
    public static function getTotalAlertCount(string|array $piSystemName, string $externalSystem) : array {
        if ( is_string($piSystemName) ) {
            $sqlParams = array($piSystemName);
        } else {
            $sqlParams = $piSystemName;
        }
        $sqlSystemFilter = static::getSqlSystemFilter($sqlParams);
        if ( empty($externalSystem) ) {
            $query = DB::prepare("
                SELECT count(*) as total_count, 
                       SUM(timestamp > NOW() - INTERVAL 1 DAY) AS day_count,
                       SUM(timestamp > NOW() - INTERVAL 7 DAY) AS week_count,
                       SUM( timestamp > NOW() - INTERVAL 31 DAY) AS month_count
                FROM alerts WHERE $sqlSystemFilter");
        } else {
            $sqlParams[] = $externalSystem;
            $sqlParams[] = $externalSystem;
            $query = DB::prepare("
                SELECT count(*) as total_count, 
                       SUM(timestamp > NOW() - INTERVAL 1 DAY) AS day_count,
                       SUM(timestamp > NOW() - INTERVAL 7 DAY) AS week_count,
                       SUM( timestamp > NOW() - INTERVAL 31 DAY) AS month_count
                FROM alerts WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?)");
        }
        $query->execute($sqlParams);
        $row = $query->fetch();
        return [
            'total' => (int)$row['total_count'],
            'day'   => (int)$row['day_count'],
            'week'  => (int)$row['week_count'],
            'month' => (int)$row['month_count'],
        ];
    }

    /**
     * @param string|array $piSystemName Фильтр по системам источникам алертов
     * @param string $externalSystem Если заполнено, то возвращается статистика по внешней системе
     * @param int|null $timeLimit фильтр по времени
     * @return float
     */
    public static function getAlertPercent(string|array $piSystemName, string $externalSystem, int|null $timeLimit = null) : float {
        if ( is_string($piSystemName) ) {
            $sqlParams = array($piSystemName);
        } else {
            $sqlParams = $piSystemName;
        }
        $sqlSystemFilter = static::getSqlSystemFilter($sqlParams);
        if ( empty($externalSystem) ) {
            if ( is_null($timeLimit) ) {
                $query = DB::prepare("SELECT ((SELECT count(*) FROM alerts WHERE $sqlSystemFilter)/sum(messageCount))*100 AS c FROM messages_stat WHERE $sqlSystemFilter");
            } else {
                $sqlParams[] = $timeLimit;
                $query = DB::prepare("SELECT ((SELECT count(*) FROM alerts WHERE $sqlSystemFilter AND timestamp > NOW() - INTERVAL ? SECOND )/sum(messageCount))*100 AS c FROM messages_stat WHERE $sqlSystemFilter AND timestamp > NOW() - INTERVAL ? SECOND ");
            }
        } else {
            $sqlParams[] = $externalSystem;
            $sqlParams[] = $externalSystem;
            if ( is_null($timeLimit) ) {
                $query = DB::prepare("SELECT ((SELECT count(*) FROM alerts  WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?))/sum(messageCount))*100 AS c FROM messages_stat WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?) ");
            } else {
                $sqlParams[] = $timeLimit;
                $query = DB::prepare("SELECT ((SELECT count(*) FROM alerts  WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?) AND timestamp > NOW() - INTERVAL ? SECOND)/sum(messageCount))*100 AS c FROM messages_stat WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?) AND timestamp > NOW() - INTERVAL ? SECOND ");
            }
        }
        $sqlParams = array_merge($sqlParams, $sqlParams);
        $query->execute($sqlParams);
        if ($row = $query->fetch()) {
            return round($row['c']??0,2);
        }
        return 0;
    }

    /**
     * @param string|array $piSystemName Фильтр по системам источникам алертов
     * @param string $externalSystem Если заполнено, то возвращается статистика по внешней системе
     * @param int|null $timeLimit фильтр по времени
     * @return float мс
     */
    public static function getMessageTimeProc(string|array $piSystemName, string $externalSystem, int|null $timeLimit = null) : float {
        if ( is_string($piSystemName) ) {
            $sqlParams = array($piSystemName);
        } else {
            $sqlParams = $piSystemName;
        }
        $sqlSystemFilter = static::getSqlSystemFilter($sqlParams);
        if( empty($externalSystem) ) {
            if ( is_null($timeLimit) ) {
                $query = DB::prepare("SELECT sum(messageProcTime)/sum(messageCount)  AS c FROM messages_stat WHERE $sqlSystemFilter");
            } else {
                $sqlParams[] = $timeLimit;
                $query = DB::prepare("SELECT sum(messageProcTime)/sum(messageCount)  AS c FROM messages_stat WHERE $sqlSystemFilter AND timestamp > NOW() - INTERVAL ? SECOND ");
            }
        } else {
            $sqlParams[] = $externalSystem;
            $sqlParams[] = $externalSystem;
            if ( is_null($timeLimit) ) {
                $query = DB::prepare("SELECT  sum(messageProcTime)/sum(messageCount) AS c FROM messages_stat WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?) ");
            } else {
                $sqlParams[] = $timeLimit;
                $query = DB::prepare("SELECT sum(messageProcTime)/sum(messageCount)  AS c FROM messages_stat WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?) AND timestamp > NOW() - INTERVAL ? SECOND ");
            }
        }
        $query->execute($sqlParams);
        if ($row = $query->fetch()) {
            return round($row['c'] / 1000);
        }
        return 0;
    }

    /**
     * @param string|array $piSystemName Фильтр по системам источникам алертов
     * @param string $externalSystem Если заполнено, то возвращается статистика по внешней системе, без учета первого параметра
     * @param int|null $timeLimit фильтр по времени
     * @return PDOStatement Execute уже выполнен
     */
    public static function getDailyMessageTimeProc(string|array $piSystemName, string $externalSystem, int|null $timeLimit = null) : PDOStatement {
        if ( is_string($piSystemName) ) {
            $sqlParams = array($piSystemName);
        } else {
            $sqlParams = $piSystemName;
        }
        $sqlSystemFilter = static::getSqlSystemFilter($sqlParams);
        if( empty($externalSystem) ) {
            if ( is_null($timeLimit) ) {
                $query = DB::prepare("SELECT sum(messageProcTime)/sum(messageCount) AS timeProc, substring(timestamp, 1, 10) AS date FROM messages_stat WHERE $sqlSystemFilter GROUP BY DATE");
            } else {
                $sqlParams[] = $timeLimit;
                $query = DB::prepare("SELECT sum(messageProcTime)/sum(messageCount) AS timeProc, substring(timestamp, 1, 10) AS date FROM messages_stat WHERE $sqlSystemFilter AND timestamp > NOW() - INTERVAL ? SECOND GROUP BY DATE");
            }
        } else {
            $sqlParams[] = $externalSystem;
            $sqlParams[] = $externalSystem;
            if ( is_null($timeLimit) ) {
                $query = DB::prepare("SELECT sum(messageProcTime)/sum(messageCount) AS timeProc, substring(timestamp, 1, 10) AS date FROM messages_stat WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?) GROUP BY DATE");
            } else {
                $sqlParams[] = $timeLimit;
                $query = DB::prepare("SELECT sum(messageProcTime)/sum(messageCount) AS timeProc, substring(timestamp, 1, 10) AS date FROM messages_stat WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?) AND timestamp > NOW() - INTERVAL ? SECOND GROUP BY DATE");
            }
        }
        $query->execute($sqlParams);
        return $query;
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
            ORDER BY date
        ");
        $query->execute(array( $this->group_id, $timeLimit ));
        return $query;
    }

    /**
     * @param string|array $piSystemName Фильтр по системам источникам алертов
     * @param string $externalSystem Если заполнено, то возвращается статистика по внешней системе
     * @param int $timeLimit фильтр по времени
     * @return PDOStatement Execute уже выполнен
     */
    public static function getDailyAlertCountForDiagram(string|array $piSystemName, string $externalSystem, int $timeLimit = 1) : PDOStatement {
        if ( is_string($piSystemName) ) {
            $sqlParams = array($piSystemName);
        } else {
            $sqlParams = $piSystemName;
        }
        $sqlSystemFilter = static::getSqlSystemFilter($sqlParams);
        if ( empty($externalSystem) ){
            $sqlParams[] = $timeLimit;
            $query = DB::prepare("
                SELECT count(*) as count, substring(timestamp, 1, 10) as date
                FROM alerts
                WHERE $sqlSystemFilter AND timestamp > NOW() - INTERVAL ? SECOND
                GROUP BY date
            ");
        } else {
            $sqlParams[] = $externalSystem;
            $sqlParams[] = $externalSystem;
            $sqlParams[] = $timeLimit;
            $query = DB::prepare("
                SELECT count(*) as count, substring(timestamp, 1, 10) as date
                FROM alerts
                WHERE $sqlSystemFilter AND (fromSystem = ? OR toSystem= ?) AND timestamp > NOW() - INTERVAL ? SECOND
                GROUP BY date
            ");
        }
        $query->execute($sqlParams);
        return $query;
    }

    /**
     * @param string|array $piSystemName Фильтр по системам источникам алертов
     * @param string $externalSystem Если заполнено, то возвращается статистика по внешней системе
     * @return PDOStatement Execute уже выполнен
     */
    public static function getHourAlertCountForDiagram(string|array $piSystemName, string $externalSystem) : PDOStatement {
        if ( is_string($piSystemName) ) {
            $sqlParams = array($piSystemName);
        } else {
            $sqlParams = $piSystemName;
        }
        $sqlSystemFilter = static::getSqlSystemFilter($sqlParams);
        if ( empty($externalSystem) ) {
            $query = DB::prepare("
                SELECT count(*) as count, HOUR(timestamp) as h
                FROM alerts
                WHERE $sqlSystemFilter AND timestamp >= CURDATE()
                GROUP BY h
            ");
        } else {
            $sqlParams[] = $externalSystem;
            $sqlParams[] = $externalSystem;
            $query = DB::prepare("
                SELECT count(*) as count, HOUR(timestamp) as h
                FROM alerts
                WHERE $sqlSystemFilter AND (toSystem = ? OR fromSystem = ?) AND timestamp >= CURDATE()
                GROUP BY h
            ");
        }
        $query->execute($sqlParams);
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

    /**
     * @param string|array $piSystemName Фильтр по системам источникам алертов
     * @param string $externalSystem Если заполнено, то возвращается статистика по внешней системе
     * @param int $timeLimit
     * @return PDOStatement Execute уже выполнен
     */
    public static function getDailyAlertPercentForDiagram(string|array $piSystemName, string $externalSystem, int $timeLimit = 1) : PDOStatement {
        if ( is_string($piSystemName) ) {
            $sqlParams = array($piSystemName);
        } else {
            $sqlParams = $piSystemName;
        }
        $sqlSystemFilter = static::getSqlSystemFilter($sqlParams);
        if ( empty($externalSystem) ){
            $sqlParams[] = $timeLimit;
            $query = DB::prepare("
                SELECT (t1.count/t2.mc)*100 as percent, t1.date
                FROM
                (
                    (SELECT COUNT(*) as count,substring(a.timestamp, 1, 10) as date FROM alerts as a WHERE $sqlSystemFilter AND a.timestamp > NOW() - INTERVAL ? SECOND  GROUP BY DATE) AS t1 
                    JOIN
                    ( SELECT sum(messageCount) AS mc, substring(m.timestamp, 1, 10) as date FROM messages_stat as m WHERE $sqlSystemFilter AND m.timestamp > NOW() - INTERVAL ? SECOND GROUP BY DATE ) AS t2
                    ON t1.date=t2.date
                )
            ");
        } else {
            $sqlParams[] = $externalSystem;
            $sqlParams[] = $externalSystem;
            $sqlParams[] = $timeLimit;
            $query = DB::prepare("
                SELECT (t1.count/t2.mc)*100 as percent, t1.date
                FROM
                (
                    (SELECT COUNT(*) as count,substring(a.timestamp, 1, 10) as date  FROM alerts as a WHERE $sqlSystemFilter AND (a.fromSystem = ? OR a.toSystem= ?) AND a.timestamp > NOW() - INTERVAL ? SECOND  GROUP BY DATE) AS t1
                    JOIN 
                    (SELECT sum(messageCount) AS mc, substring(m.timestamp, 1, 10) as date FROM messages_stat as m WHERE $sqlSystemFilter AND (m.fromSystem = ? OR m.toSystem= ?) AND m.timestamp > NOW() - INTERVAL ? SECOND GROUP BY DATE ) AS t2
                    ON t1.date=t2.date
                )
            ");
        }
        $sqlParams = array_merge($sqlParams, $sqlParams);
        $query->execute($sqlParams);
        return $query;
    }
}

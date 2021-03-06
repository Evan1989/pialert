<?php

namespace EvanPiAlert\Util\essence;

use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\HTMLPageTemplate;
use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\Text;
use EvanPiAlert\Util\TextAnalysisUtil;
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
    public ?string $comment_datetime = null;
    public ?int $user_id = null;
    public ?int $last_user_id = null;

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
        $this->errText = $row['errText'];
        $this->errTextMask = $row['errTextMask'];

        $this->firstAlert = $row['first_alert'];
        $this->lastAlert = $row['last_alert'];
        $this->lastUserAction = $row['last_user_action'];
        $this->maybe_need_union = $row['maybe_need_union'];
    }


    public function saveToDatabase() : bool {
        if ( $this->group_id > 0 ) {
            $query = DB::prepare("UPDATE alert_group SET 
                status=?, comment=?, comment_datetime=?, 
                user_id=?, last_user_id=?, piSystemName=?, 
                fromSystem=?, toSystem=?, channel=?, 
                interface=?, errText=?, errTextMask=?,
                errTextMainPart=?,
                first_alert=?, last_alert=?, last_user_action=?, 
                maybe_need_union=? WHERE group_id = ?");
            return $query->execute(array(
                $this->status, $this->comment, $this->comment_datetime,
                $this->user_id, $this->last_user_id, $this->piSystemName,
                $this->fromSystem, $this->toSystem, $this->channel,
                $this->interface, $this->errText, $this->errTextMask,
                $this->getMainPartOfError(),
                $this->firstAlert, $this->lastAlert, $this->lastUserAction,
                $this->maybe_need_union, $this->group_id
            ));
        } else {
            $query = DB::prepare("INSERT INTO alert_group (
                 status, comment, comment_datetime,
                 user_id, last_user_id, piSystemName,
                 fromSystem, toSystem, channel,
                 interface, errText, errTextMask,   
                 errTextMainPart,
                 first_alert, last_alert, last_user_action,
                 maybe_need_union) VALUES (
                       ?, ?, ?,
                       ?, ?, ?, 
                       ?, ?, ?, 
                       ?, ?, ?, 
                       ?, 
                       ?, ?, ?, 
                       ?
                 )");
            $query->execute(array(
                $this->status, $this->comment, $this->comment_datetime,
                $this->user_id, $this->last_user_id, $this->piSystemName,
                $this->fromSystem, $this->toSystem, $this->channel,
                $this->interface, $this->errText, $this->errTextMask,
                $this->getMainPartOfError(),
                $this->firstAlert, $this->lastAlert, $this->lastUserAction,
                $this->maybe_need_union
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
            ($this->toSystem?' ??? '.$this->toSystem:'').PHP_EOL.
            ($this->interface?:$this->channel);
    }


    protected ?string $_HTMLErrorTextMask = null;
    public function getHTMLErrorTextMask() :string {
        if ( is_null($this->_HTMLErrorTextMask) ) {
            $text = replaceLinksWithATag($this->errTextMask);
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

    /**
     * ?????????????????? ???????????????? ?????????????? ???? ?????????? ????????????, ?????? ????????????
     * @return int 0 - ?????? ????????????, ?????? ?????????? ????????????, ?????? ?????????? ?????????????????? ??????????????
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

        // ???? ????????????, ???????? ?????????????????? ?????????? ??????????, ?? ?????? ?????????? ???????? ?????????? ????, ?????????? ???????????????? ?????????????????? ????????????????
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

    public function setUserId(?int $userID) : void {
        if ( $userID ) {
            $this->last_user_id = null;
            $this->user_id = $userID;
        } else {
            $this->last_user_id = $this->user_id;
            $this->user_id = null;
        }
    }

    /**
     * @param string $piSystemName ???????? ???????????????? ??????????, ???? ???????????????????????? ???????????????????? ???? ???????? ????????????????
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
     * @return PDOStatement Execute ?????? ????????????????
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
     * @param string $piSystemName ???????? ???????????????? ??????????, ???? ???????????????????????? ???????????????????? ???? ???????? ????????????????
     * @param int $timeLimit
     * @return PDOStatement Execute ?????? ????????????????
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
     * @param string $piSystemName ???????? ???????????????? ??????????, ???? ???????????????????????? ???????????????????? ???? ???????? ????????????????
     * @return PDOStatement Execute ?????? ????????????????
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
     * ???????????????? ???????????? ???????????? ??????????????
     * ??????????????????, ???????????? ???????? ?????? ?????????????????????? ??????????????
     * @return bool
     */
    public function deleteAlertGroup() : bool {
        $query = DB::prepare("DELETE FROM alert_group WHERE group_id = ?");
        return $query->execute(array($this->group_id));
    }
}
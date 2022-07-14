<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AlertAnalytics;
use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\CalendarRussia;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\PiAlertGroup;
use EvanPiAlert\Util\essence\User;
use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\HTMLPageTemplate;
use EvanPiAlert\Util\Text;

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

require_once(__DIR__ . "/util/dashboard_ajax.php");

$support_online = false;

/** @var User[] $users */
$users = array();
$query = DB::prepare(" SELECT * FROM users ORDER BY FIO");
$query->execute(array());
while($row = $query->fetch()) {
    $user = new User($row);
    $users[ $user->user_id ] = $user;
    if ( $user->user_id != $authorizationAdmin->getUserId() && $user->isOnline() ) {
        $support_online = true;
    }
}

function getStatusChoice(PiAlertGroup $alertGroup): string {
    global $authorizationAdmin;
    $result = "";
    foreach (PiAlertGroup::getStatusName() as $statusCode => $statusName) {
        $result .= "<option value='".$statusCode."' ".($statusCode==$alertGroup->status?'selected':'').">".$statusName."</option>";
    }
    return "<select id='status_".$alertGroup->group_id."' class='alert-group-status-select form-control-plaintext bg-".$alertGroup->getStatusColor($authorizationAdmin->getUserId())."'>".$result."</select>";
}

function getUserChoice(PiAlertGroup $alertGroup): string {
    global $users, $authorizationAdmin;
    $result = "<option value='' ".(is_null($alertGroup->user_id)?'selected':'').">-</option>";
    foreach ($users as $user) {
        if ( $user->isBlocked() && $user->user_id != $alertGroup->user_id ) {
            continue;
        }
        $result .= "<option value='".$user->user_id."' ".($user->user_id==$alertGroup->user_id?'selected':'').">".$user->getCaption()."</option>";
    }
    $result = "<select id='user_".$alertGroup->group_id."' class='alert-group-user-select form-control-plaintext bg-".$alertGroup->getUserColor($authorizationAdmin->getUserId())."'>".$result."</select>";
    if ( $alertGroup->last_user_id ) {
        $result .= "<span class='alert-group-help-data' data-toggle='tooltip' title='".Text::dashboardLastUserId()."'>".$users[$alertGroup->last_user_id]->getCaption()."</span>";
    }
    return $result;
}

function getComment(PiAlertGroup $alertGroup): string {
    $div = '';
    if ( $alertGroup->comment ) {
        $div = "<div class='alert-group-comment-html-div overflow-auto' title='".Text::dashboardEditDate($alertGroup->comment_datetime)."'>".$alertGroup->getHTMLComment()."</div>";
    }
    return "<textarea class='d-none' id='comment_".$alertGroup->group_id."' maxlength='2000' placeholder='".Text::dashboardCommentPlaceholder()."'>".$alertGroup->comment."</textarea>".
        $div;
}

$page = new HTMLPageTemplate($authorizationAdmin);

{ // Ajax обработка
    if ( isset($_POST['element']) ) {
        list($type, $group_id) = explode('_', $_POST['element']);
        echo saveInputNewValueToAlertGroup( $type, $group_id, $_POST['value']??null);
        exit();
    }

    if ( isset($_GET['loadAlertGroupFullInfo']) ) {
        echo getAlertGroupFullInfo($authorizationAdmin, (int) $_GET['loadAlertGroupFullInfo']);
        exit();
    }

    if ( isset($_GET['unionAlertGroup']) ) {
        echo getUnionAlertGroupForm($page, $authorizationAdmin, (int) $_GET['unionAlertGroup']);
        exit();
    }

    if ( isset($_GET['unionAlertGroupStep2']) ) {
        echo getUnionAlertGroupResult((int)$_GET['unionAlertGroupStep2'], (int)$_GET['group_id_to']);
        exit();
    }

    if ( isset($_GET['loadAlertsForGroup']) ) {
        $group_id = (int) $_GET['loadAlertsForGroup'];
        $query = DB::prepare(" SELECT *  FROM alerts WHERE group_id = ? ORDER BY id desc LIMIT 300");
        $query->execute(array( $group_id ));
        echo $page->getAlertTable($query);
        exit();
    }

    if ( isset($_GET['checkAlertGroupAsComplete']) ) {
        $group_id = (int) $_GET['checkAlertGroupAsComplete'];
        $alertGroup = new PiAlertGroup($group_id);
        if ( ( $alertGroup->status == PiAlertGroup::NEW OR $alertGroup->status == PiAlertGroup::REOPEN ) AND is_null($alertGroup->user_id) ) {
            echo Text::dashboardCheckAlertGroupAsCompleteFail();
        } else {
            $alertGroup->lastUserAction = date("Y-m-d H:i:s");
            $alertGroup->saveToDatabase();
            echo 'true';
        }
        exit();
    }
}

echo $page->getPageHeader(Text::dashboardPageHeader());

$today = date("Y-m-d"); // сегодня
$calendar = new CalendarRussia();
$last_work_day = time()-ONE_DAY;
while ($calendar->isWorkingDay( date("Y-m-d", $last_work_day) ) == false) {
    $last_work_day = $last_work_day-ONE_DAY;
}
$last_work_day = date("Y-m-d 16:00", $last_work_day); // предыдущий рабочий день, 16:00. Считаем, что все что раньше, уже обработано

$defaultSearch = '';
if ( isset($_GET['search']) ) {
    $defaultSearch = htmlspecialchars($_GET['search']);
}

if ( isset($_GET['filter']) ) {
    $showHistoryAlerts = true;
    $query = DB::prepare("SELECT *  FROM alert_group order by last_alert desc");
} else {
    $showHistoryAlerts = false;
    $query = DB::prepare("SELECT *  FROM alert_group WHERE last_alert > NOW() - INTERVAL 14 DAY order by last_alert desc");
}

echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>
	        ".Text::dashboardPageHeader()."
	        <div class='float-end mx-2 form-check form-switch' data-toggle='tooltip' data-placement='top' title='".Text::dashboardShowOldAlerts()."'>
                <input class='form-check-input' type='checkbox' id='showHistoryAlerts' ".($showHistoryAlerts?'checked':'').">
	        </div><div class='float-end mx-2'>
	            <input class='d-inline form-control form-control-sm' id='mainTableSearch' type='text' placeholder='".Text::search()."...' value='".$defaultSearch."'>
            </div>";
if ( $support_online ) {
    echo "  <div class='float-end mx-2'>
	            <span class='badge bg-success' data-toggle='tooltip' data-placement='top' title='".Text::dashboardSupportOnline()."'>support online</span>
            </div>";
}
echo "      <div class='float-end mx-2 d-none no-alert-warning' data-toggle='tooltip' data-placement='top' title='".Text::dashboardNoConnectToPiSystem()."'><span class='badge bg-danger mx-1'>no new alerts</span></div>
            <div class='float-end new-alert-count d-none' data-toggle='tooltip' data-placement='top' title='".Text::dashboardTitleToTopBillCounter()."'>
	            <span class='count'>0</span>
	            <span class='bell-fill'>".$page->getIcon('bell-fill')."</span>
	            <span class='bell'>".$page->getIcon('bell')."</span>
            </div>
	    </div>
        <div class='card-body overflow-auto'>
            <table class='tablesorter table table-sm table-hover table-responsive-lg alert-group main-table-for-filter'>
            <thead>
              <tr>
                  <th>".Text::status()."</th>
                  <th data-toggle='tooltip' data-placement='bottom' title='".Text::responsibleEmployee()."'>".Text::employee()."</th>
                  <th>".Text::comment()."</th>
                  <th>".Text::dashboardRequisites()."</th>
                  <th data-toggle='tooltip' data-placement='bottom' title='".Text::dashboardMaskOrErrorTitle()."'>".Text::dashboardMaskOrError()."</th>
                  <th data-toggle='tooltip' data-placement='left' title='".Text::dashboardLastAlert()."'>".Text::last()."</th>
                  <th data-toggle='tooltip' data-placement='left' title='".Text::statisticAlertWeekCount()."'>Week</th>
              </tr>
            </thead> 
            <tbody>";
$query->execute(array());
$globalLastAlert = array();
while($row = $query->fetch()) {
    $alertGroup = new PiAlertGroup($row);
    $intervalFromLastError = time() - strtotime($alertGroup->lastAlert);
    $globalLastAlert[$alertGroup->piSystemName] = min($globalLastAlert[$alertGroup->piSystemName]??time(), $intervalFromLastError);
    if ($intervalFromLastError > 8*3600 ) {
        $lastAlertDateShow = $alertGroup->lastAlert;
    } else {
        $lastAlertDateShow = getIntervalRoundLength($intervalFromLastError);
    }
    if ( $alertGroup->lastAlert > $last_work_day && $alertGroup->lastAlert > $alertGroup->lastUserAction ) {
        $newAlertFlag = match ($alertGroup->status) {
            PiAlertGroup::NEW, PiAlertGroup::REOPEN, PiAlertGroup::MANUAL => 'bell-fill',
            PiAlertGroup::CLOSE, PiAlertGroup::IGNORE, PiAlertGroup::WAIT => 'bell'
        };
        $newAlertFlag = " <a data-toggle='tooltip' data-placement='right' class='new-alert-flag ".$newAlertFlag."' title='".Text::dashboardCheckAlertGroupAsCompleteButton()."' href=\"javascript:checkAlertGroupAsComplete(".$alertGroup->group_id.")\" id='checkAlertGroupAsCompleteLink_".$alertGroup->group_id."'>".$page->getIcon($newAlertFlag)."</a>";
    } else {
        $newAlertFlag = '';
    }
    $weekCount = $alertGroup->getAlertCount(ONE_WEEK);
    $length = ceil( (time() - strtotime($alertGroup->firstAlert) ) / ONE_DAY);
    $growIcon = '';
    if ( $weekCount > 0 && $length > 30 ) {
        $compareResult = $alertGroup->getAlert24HourCountCompareVsAverage();
        if ( $compareResult > 0 ) {
            $growIcon = "<span style='color:red;font-size:150%' data-toggle='tooltip' title='".Text::dashboardAvgBigCount()."'>".str_repeat('↑', $compareResult)."</span>";
        }
    }
    $linkToAlertGroup = 'ID'.$alertGroup->group_id;
    echo "<tr filter-value='".$linkToAlertGroup."'>
                <td>".getStatusChoice($alertGroup).$newAlertFlag."</td>
                <td>".getUserChoice($alertGroup)."</td>
                <td>".getComment($alertGroup)."</td>
                <td>".nl2br($alertGroup->getAbout())."</td>
                <td style='max-width: 400px'>".$alertGroup->getHTMLErrorTextMask()."</td>
                <td><input type='hidden' value='".$alertGroup->lastAlert."'>".$lastAlertDateShow."</td>
                <td>".
                    ($intervalFromLastError<=ONE_WEEK?$alertGroup->getAlertCount(ONE_WEEK):0).$growIcon."
                    <br>
                    <a href=\"javascript:loadAlertsForGroup(".$alertGroup->group_id.")\" data-toggle='tooltip' data-placement='top' title='".Text::dashboardShowAlertButton()."'>".$page->getIcon('envelope')."</a>
                    <a href=\"javascript:loadAlertGroupFullInfo(".$alertGroup->group_id.")\" data-toggle='tooltip' data-placement='left' title='".Text::dashboardShowStatisticButton()."'>".$page->getIcon('graph-up')."</a>
                    <a href=\"".SERVER_HOST."src/pages/dashboard.php?".($showHistoryAlerts?'filter=1&':'')."search=".$linkToAlertGroup."\" data-toggle='tooltip' data-placement='top' title='".Text::dashboardShareLinkButton()."'>".$page->getIcon('share')."</a>";
    if ( $alertGroup->maybe_need_union ) {
        echo "      <a href=\"javascript:unionAlertGroup(".$alertGroup->group_id.")\" data-toggle='tooltip' data-placement='left' title='".Text::dashboardUnionGroupButton()."'>".$page->getIcon('boxes')."</a>";
    }
    echo "      </td>
            </tr>";
}
echo "        </tbody>
		    </table>
		</div>
	</div>";

$legend = array(
    PiAlertGroup::NEW => Text::dashboardLegendNew(),
    PiAlertGroup::IGNORE => Text::dashboardLegendIgnore(),
    PiAlertGroup::MANUAL => Text::dashboardLegendManual(),
    PiAlertGroup::WAIT => Text::dashboardLegendWait(),
    PiAlertGroup::CLOSE => Text::dashboardLegendClose(),
    PiAlertGroup::REOPEN => Text::dashboardLegendReopen()
);

echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>".Text::dashboardLegend()."</div>
        <div class='card-body overflow-auto'>
            <table class='table table-sm table-responsive-lg'>
                <tbody>";
foreach ($legend as $status => $text ) {
    echo "        <tr>
                        <td class='bg-".PiAlertGroup::STATUS_COLORS[$status]."'>".PiAlertGroup::getStatusName($status)."</td>
                        <td>".$text."<td>
                   </tr>";
}
echo "              <tr>
                        <td>".$page->getIcon('bell-fill')." ".Text::or()." ".$page->getIcon('bell')."</td>
                        <td>".Text::dashboardLegendActualAlert()."</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class='modal fade' id='modal_alertsForGroup' tabindex='-1' role='dialog' aria-hidden='true'>
        <div class='modal-dialog modal-xl' role='document'>
            <div class='modal-content'>
              <div class='modal-body overflow-auto'></div>
            </div>
        </div>
    </div>";

$noConnectPiSystems = array();
// Проверка сетевой доступности SAP PI через частоту вызова сервиса /api/network_check.php
$checks = Settings::get(Settings::SYSTEMS_NETWORK_CHECK);
$checks = json_decode($checks, true);
foreach ($globalLastAlert as $system => $timeFromLastAlert) {
    // Проверяем только если от этой системы хотя бы раз приходил ping
    if ( !empty($checks[$system]) && time() - strtotime($checks[$system]) > 600 ) {
        $noConnectPiSystems[$system] = 1;
    }
}
// Проверка сетевой доступности SAP PI через статистический анализ
if ( Settings::get(Settings::AVERAGE_ALERT_INTERVAL_RATIO) > 0) {
    $hour = date("H");
    $day = date("N") - 1;
    $alertAnalytics = new AlertAnalytics();
    foreach ($globalLastAlert as $system => $timeFromLastAlert) {
        // если нет алертов (уже в N раз больше времени, чем обычно), наверно отвалился сценарий отправки из SAP PI
        if ($timeFromLastAlert > Settings::get(Settings::AVERAGE_ALERT_INTERVAL_RATIO) * $alertAnalytics->getAverageAlertInterval($system, $day, $hour)) {
            $noConnectPiSystems[$system] = 1;
        }
    }
}

$additionalScript = "<script type='text/javascript'>
        $(document).ready(function() {
            initJavascriptForDashboard();";
foreach ($noConnectPiSystems as $system => $temp) {
    $additionalScript .= "showNoAlertWarningBadge('" . $system . "');";
}
$additionalScript .= "})
    </script>";
echo $page->getPageFooter( $additionalScript );
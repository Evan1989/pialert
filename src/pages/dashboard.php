<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AlertAnalytics;
use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\Calendar;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\PiAlertGroup;
use EvanPiAlert\Util\essence\PiSystem;
use EvanPiAlert\Util\essence\User;
use EvanPiAlert\Util\HTML\HTMLPageAlerts;
use EvanPiAlert\Util\Settings;
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
    $selectedUser = $users[$alertGroup->user_id]??false;
    if ( $selectedUser !== false ) {
        $avatar = $selectedUser->getAvatarImg('alert-group-user-avatar');
    } else {
        $avatar = '';
    }
    $result = $avatar."<select id='user_".$alertGroup->group_id."' data-toggle='tooltip' title='".Text::responsibleEmployee()."' class='alert-group-user-select form-control-plaintext ".($avatar?'has-avatar':'')." bg-".$alertGroup->getUserColor($authorizationAdmin->getUserId())."'>".$result."</select>";
    if ( $alertGroup->last_user_id ) {
        $result .= "<div class='alert-group-help-data' data-toggle='tooltip' title='".Text::dashboardLastUserId()."'>".$users[$alertGroup->last_user_id]->getCaption()."</div>";
    }
    return $result;
}
function getComment(PiAlertGroup $alertGroup): string {
    $div = '';
    if ( $alertGroup->comment ) {
        $div = "<div class='alert-group-comment-html-div' title='".Text::dashboardEditDate($alertGroup->comment_datetime)."'>".$alertGroup->getHTMLComment()."</div>";
    }
    return "<textarea class='d-none' id='comment_".$alertGroup->group_id."' maxlength='2000' placeholder='".Text::dashboardCommentPlaceholder()."'>".$alertGroup->comment."</textarea>".
        $div;
}

function getAlertLink(PiAlertGroup $alertGroup): string {
    $div = '';
    if ( $alertGroup->alert_link ) {
        $div = "<div class='alert-group-comment-html-div narrow' title='".Text::requestList()."'>".$alertGroup->getHTMLAlertLink()."</div>";
    }
    return "<textarea class='d-none narrow' id='alertLink_".$alertGroup->group_id."' maxlength='2000' placeholder='".Text::dashboardAlertLinkPlaceholder()."'>".$alertGroup->alert_link."</textarea>".
        $div;
}

$page = new HTMLPageAlerts($authorizationAdmin);

$today = date("Y-m-d"); // сегодня
$calendar = new Calendar( $authorizationAdmin->getUser()->language );
$last_work_day = time()-ONE_DAY;
while ($calendar->isWorkingDay( date("Y-m-d", $last_work_day) ) == false) {
    $last_work_day = $last_work_day-ONE_DAY;
}
$last_work_day = date("Y-m-d 16:00", $last_work_day); // Предыдущий рабочий день, 16:00. Считаем, что все что раньше, уже обработано

//  Фильтры выборки //
$defaultSearch = '';
$additionalHeader = '';
$sqlParams = $authorizationAdmin->getAccessedSystemNames();
$sqlSystemFilter = '('.str_repeat('piSystemName = ? OR ', count($sqlParams)).' false)';
if ( isset($_GET['id']) ) {
    $group_id = (int) $_GET['id'];
    $sqlParams[] = $group_id;
    $showOnlyImportant = false;
    $showOnlyNewAlerts = false;
    if (isset($_GET['showSameErrors'])) {
        $additionalHeader = ' <i>(GroupID='.$group_id.' and same alerts)</i>';
        $query = "SELECT *  FROM alert_group WHERE errTextMainPart = (SELECT errTextMainPart FROM alert_group WHERE $sqlSystemFilter AND group_id = ?)";
    } else {
        $additionalHeader = ' <i>(GroupID='.$group_id.')</i>';
        $query = "SELECT *  FROM alert_group WHERE $sqlSystemFilter AND group_id = ?";
    }
    $additionalHeader .= "<input type='hidden' id='getFilterGroupID' value='".$group_id."'>";
} else {
    if (isset($_GET['search'])) {
        $defaultSearch = htmlspecialchars($_GET['search']);
    }
    $showOnlyImportant = true;
    if (isset($_GET['showNotImportant']) && $_GET['showNotImportant'] == 1) {
        $showOnlyImportant = false;
    }
    if (isset($_GET['showHistoryAlerts']) && $_GET['showHistoryAlerts'] == 1) {
        $showOnlyNewAlerts = false;
        if ( $defaultSearch ) {
            $query = "SELECT *  FROM alert_group WHERE $sqlSystemFilter AND errText LIKE ? order by last_alert desc";
            $sqlParams[] = '%'.$defaultSearch.'%';
        } else {
            $query = "SELECT *  FROM alert_group WHERE $sqlSystemFilter order by last_alert desc";
        }
    } else {
        $showOnlyNewAlerts = true;
        $query = "SELECT *  FROM alert_group WHERE $sqlSystemFilter AND last_alert > NOW() - INTERVAL 14 DAY order by last_alert desc";
    }
}
//  Фильтры выборки //


$globalLastAlert = array();
const ALERT_GROUP_PER_INSTANT_LOAD = 250;
function showDashboardPage(int $pageNum) : void {
    global $page, $query, $sqlParams, $last_work_day, $showOnlyImportant, $globalLastAlert;
    $query2 = DB::prepare("SELECT * FROM alert_group WHERE group_id != ? AND errTextMainPart = ? AND comment IS NOT NULL ORDER BY last_alert DESC");
    $query = DB::prepare($query." LIMIT ".(ALERT_GROUP_PER_INSTANT_LOAD*($pageNum-1)).','.ALERT_GROUP_PER_INSTANT_LOAD);
    $query->execute($sqlParams);
    $find = false;
    while($row = $query->fetch()) {
        $find = true;
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
        $growIcon = '';
        if ( $weekCount > 0 ) {
            $compareResult = $alertGroup->getAlert24HourCountCompareVsAverage();
            if ( $compareResult > 0 ) {
                $growIcon = "<span style='color:red;font-size:150%' data-toggle='tooltip' title='".Text::dashboardAvgBigCount()."'>".str_repeat('↑', $compareResult)."</span>";
            }
        }
        $important = ( $alertGroup->status != PiAlertGroup::IGNORE && $alertGroup->status != PiAlertGroup::CLOSE ) || !empty($growIcon);
        if ( $showOnlyImportant && !$important) {
            continue;
        }
        echo "<tr filter-value=''>  
                <td>
                    <div>".getStatusChoice($alertGroup)."</div>
                    <div class='alert-group-user'>".getUserChoice($alertGroup)."</div>
                    ".$newAlertFlag."
                </td>
                <td>".getComment($alertGroup)."</td>
                <td>".getAlertLink($alertGroup)."</td>
                <td>".$alertGroup->getHTMLAbout()."</td>
                <td><div class='alert-group-comment-html-div flex-large'>".$alertGroup->getHTMLErrorTextMask()."</div></td>
                <td>
                    ".$lastAlertDateShow."
                    <br>
                    ".($intervalFromLastError<=ONE_WEEK?$alertGroup->getAlertCount(ONE_WEEK):0)." ".Text::pieces()." ".$growIcon."
                </td>
                <td>
                    <a href=\"javascript:loadAlertsForGroup(".$alertGroup->group_id.")\" data-toggle='tooltip' data-placement='top' title='".Text::dashboardShowAlertButton()."'>".$page->getIcon('envelope')."</a>
                    <a href=\"javascript:loadAlertGroupFullInfo(".$alertGroup->group_id.")\" data-toggle='tooltip' data-placement='left' title='".Text::dashboardShowStatisticButton()."'>".$page->getIcon('graph-up')."</a>
                    <a href='dashboard.php?id=".$alertGroup->group_id."' data-toggle='tooltip' data-placement='top' title='".Text::dashboardShareLinkButton()."'>".$page->getIcon('share')."</a>";
        if ( $alertGroup->status == PiAlertGroup::NEW || $alertGroup->status == PiAlertGroup::REOPEN ) {
            $query2->execute(array($alertGroup->group_id, $alertGroup->getMainPartOfError()));
            if ($query2->fetch()) {
                echo "  <a href='dashboard.php?id=".$alertGroup->group_id."&showSameErrors' data-toggle='tooltip' data-placement='top' title='".Text::dashboardFindSameErrors()."'>".$page->getIcon('magic')."</a>";
            }
        }
        if ( $alertGroup->maybe_need_union ) {
            echo "      <a href=\"javascript:unionAlertGroup(".$alertGroup->group_id.")\" data-toggle='tooltip' data-placement='left' title='".Text::dashboardUnionGroupButton()."'>".$page->getIcon('boxes')."</a>";
        }
        echo "      </td>
            </tr>";
    }
    if ( $find ) {
        $pageNum++;
        echo "<script type='text/javascript'>$(document).ready(function(){ajaxUploadDashboardPages(".$pageNum.");})</script>";
    } else {
        echo "<script type='text/javascript'>$(document).ready(function(){ajaxUploadDashboardPagesFinish();})</script>";
    }
}

{ // Ajax обработка

    if ( isset($_GET['page']) ) {
        showDashboardPage( (int) $_GET['page'] );
        exit();
    }

    if ( isset($_POST['code']) ) {
        $query = DB::prepare("SELECT name, contact FROM bs_systems WHERE code = ?");
        $query->execute(array($_POST['code']));
        while($row = $query->fetch()) {
            echo json_encode(array(
                'name' => $row['name'],
                'contact' => nl2br($row['contact'])
            ), JSON_UNESCAPED_UNICODE);
        }
        exit();
    }

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



echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>
	        ".Text::dashboardPageHeader().$additionalHeader."
            <div class='mx-2 page-ajax-load-loader badge btn-warning'></div>
	        <div class='float-end mx-2 form-check form-switch' data-toggle='tooltip' data-placement='top' title='".Text::dashboardShowOnlyNewAlerts()."'>
                ".HTMLPageAlerts::getIcon( ($showOnlyNewAlerts?'newspaper':'h-circle') )."
                <input class='form-check-input' type='checkbox' id='showOnlyNewAlerts' ".($showOnlyNewAlerts?'checked':'').">
	        </div><div class='float-end mx-2 form-check form-switch' data-toggle='tooltip' data-placement='top' title='".Text::dashboardShowOnlyImportantAlerts()."'>
                ".HTMLPageAlerts::getIcon( ($showOnlyImportant?'cup-hot':'cup') )."
                <input class='form-check-input' type='checkbox' id='showOnlyImportant' ".($showOnlyImportant?'checked':'').">
	        </div><div class='float-end mx-2'>";
if ( $defaultSearch ) {
    echo "      <input class='d-inline form-control form-control-sm' disabled id='mainTableSearch' type='text' value='".$defaultSearch."'>";
} else {
    echo "      <input class='d-inline form-control form-control-sm' id='mainTableSearch' type='text' placeholder='".Text::search()."...' value=''>";
}
echo "      </div>";
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
                      <th>".Text::comment()."</th>
                      <th>".Text::requestList()."</th>
                      <th>".Text::dashboardRequisites()."</th>
                      <th data-toggle='tooltip' title='".Text::dashboardMaskOrErrorTitle()."'>".Text::dashboardMaskOrError()."</th>
                      <th data-toggle='tooltip' title='".Text::dashboardLastAlert()." + ".Text::statisticAlertWeekCount()."'>".Text::menuStatistics()."</th>
                      <th></th>
                  </tr>
                </thead> 
                <tbody class='page-ajax-load-data'>";
showDashboardPage(1);
echo "          </tbody>
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
    </div>";

$noConnectPiSystems = array();
// Проверка сетевой доступности SAP PI через частоту вызова сервиса /api/network_check.php
$checks = Settings::get(Settings::SYSTEMS_NETWORK_CHECK);
$checks = json_decode($checks, true);
foreach ($globalLastAlert as $piSystemName => $timeFromLastAlert) {
    // Проверяем только если от этой системы хотя бы раз приходил ping
    if ( !empty($checks[$piSystemName]) && time() - strtotime($checks[$piSystemName]) > 600 ) {
        $noConnectPiSystems[$piSystemName] = 1;
    }
}
// Проверка сетевой доступности SAP PI через статистический анализ
if ( Settings::get(Settings::AVERAGE_ALERT_INTERVAL_RATIO) > 0) {
    $hour = date("H");
    $day = date("N") - 1;
    $alertAnalytics = new AlertAnalytics();
    foreach ($globalLastAlert as $piSystemName => $timeFromLastAlert) {
        // если нет алертов (уже в N раз больше времени, чем обычно), наверно отвалился сценарий отправки из SAP PI
        if ($timeFromLastAlert > Settings::get(Settings::AVERAGE_ALERT_INTERVAL_RATIO) * $alertAnalytics->getAverageAlertInterval($piSystemName, $day, $hour)) {
            $noConnectPiSystems[$piSystemName] = 1;
        }
    }
}

$additionalScript = "<script type='text/javascript'>
        $(document).ready(function() {
            initJavascriptForDashboardStep1();
            ";
foreach ($noConnectPiSystems as $piSystemName => $temp) {
    $piSystem = new PiSystem($piSystemName);
    $additionalScript .= "showNoAlertWarningBadge('" . $piSystem->getSID() . "');";
}
$additionalScript .= "})
    </script>";
echo $page->getPageFooter( $additionalScript );
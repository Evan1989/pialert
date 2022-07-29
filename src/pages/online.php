<?php

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\User;
use EvanPiAlert\Util\HTMLPageTemplate;
use EvanPiAlert\Util\Text;

require_once(__DIR__."/../autoload.php");

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

$page = new HTMLPageTemplate($authorizationAdmin);
echo $page->getPageHeader(Text::onlinePageHeader());


$mode = $_GET['mode']??'';
$todayWeekDay = date("N")-1; // от 0 до 6
if ( $mode == 'thisWeek' ) {
    $startWeekDay = $todayWeekDay; // от 0 до 6
    $startWeekDay = ($startWeekDay + 1) % 7; // сместим так, чтобы показать за последние 6 дней + сегодня
    $weekCount = 1;
} else {
    $mode = 'usually';
    $startWeekDay = 0;
    $weekCount = 4;
    $query = DB::prepare("SELECT min(date) as min_date FROM user_statistic_online WHERE date > NOW() - INTERVAL ? WEEK");
    $query->execute(array( $weekCount ));
    if ($row = $query->fetch()) {
        $weekCount = 1 + floor((time() - strtotime($row['min_date']))/ONE_WEEK);
    } else {
        $weekCount = 1;
    }
}

$userOnlineStatistic = array();
$query = DB::prepare("
    SELECT user_id, WEEKDAY(date) as week_day, sum(seconds) as seconds
    FROM user_statistic_online
    WHERE date > NOW() - INTERVAL ? WEEK
    GROUP by user_id, week_day
");
$query->execute(array( $weekCount ));
while ($row = $query->fetch()) {
    if ( $weekCount <= 1 ) {
        $countForAvgCalc = $weekCount;
    } else {
        $countForAvgCalc = $weekCount - ($row['week_day']>$todayWeekDay?1:0);
    }
    $userOnlineStatistic[$row['user_id']][$row['week_day']] = round($row['seconds'] / $countForAvgCalc);
}

echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>
	        <a href='online.php?mode=usually' class='btn btn-primary ".($mode=='usually'?'disabled':'')."'>".Text::onlineModeUsually()."</a> 
	        <a href='online.php?mode=thisWeek' class='btn btn-primary ".($mode=='thisWeek'?'disabled':'')."'>".Text::onlineModeThisWeek()."</a> 
	    </div>
        <form action='' method='POST'>
            <div class='card-body overflow-auto'>
                <table class='table table-sm table-responsive-lg admin-users tablesorter'>
                    <thead>
                      <tr>
                          <th>Сотрудник</th>";
for ($i = 0; $i < 7; $i++) {
    $title = mb_convert_case(Text::dayNameArray()[ ($i+$startWeekDay)%7 + 1], MB_CASE_TITLE );
    if ( $mode == 'thisWeek' && $i == 6 || $mode != 'thisWeek' && $i == $todayWeekDay ) {
        $title .= '='.Text::today();
    }
    echo "                <th>".$title."</th>";
}
echo "                    <th>".Text::chartsAverageDay()."</th>
                      <tr>
                    </thead> 
                    <tbody>";
$query = DB::prepare("SELECT * FROM users ORDER BY FIO");
$query->execute(array());
while ($row = $query->fetch()) {
    $user = new User($row);
    if ( !isset($userOnlineStatistic[$user->user_id]) ) {
        continue;
    }
    echo "<tr>
            <td>".$user->getAvatarImg('online-user-avatar').$user->getHTMLCaption()."</td>";
    $sumSeconds = 0;
    for ($i = 0; $i < 7; $i++) {
        $secondsOnline = $userOnlineStatistic[$user->user_id][($i+$startWeekDay)%7]??0;
        $online = min(100, round($secondsOnline/ (8 * 36) )); // 8 часов = 100%
        if ( $secondsOnline > 0 ) {
            $title = 'Online '.getIntervalRoundLength($secondsOnline);
        } else {
            $title = 'Offline';
        }
        if ( $secondsOnline <= 3*3600 ) {
            $color = 'bg-warning';
        } else {
            $color = 'bg-success';
        }
        echo "<td><input type='hidden' value='".$secondsOnline."'>
                <div class='progress' data-toggle='tooltip' data-placement='top' title='".$title."'>
                    <div class='progress-bar ".$color."' style='width:".$online."%' role='progressbar' aria-valuenow='".$online."' aria-valuemin='0' aria-valuemax='100'></div>
                </div>
            </td>";
        $sumSeconds += $secondsOnline;
    }
    echo "  <td><input type='hidden' value='".$sumSeconds."'>".getIntervalRoundLength( round($sumSeconds/7) )."</td>
        </tr>";
}
echo "            </tbody>
                </table>
            </div>
        </form>
    </div>";

$additionalScript = "
    <script type='text/javascript'>
        $(document).ready(function() {
            initJavascriptForOnline();
        })
    </script>";
echo $page->getPageFooter( $additionalScript );
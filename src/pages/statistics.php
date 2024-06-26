<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\PiAlertGroup;
use EvanPiAlert\Util\HTML\HTMLChart;
use EvanPiAlert\Util\HTML\HTMLPageTemplate;
use EvanPiAlert\Util\ManagePiSystem;
use EvanPiAlert\Util\Text;

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

$systemNames = array();
foreach (ManagePiSystem::getPiSystems() as $piSystem) {
    $systemNames[$piSystem->getSystemName()] = $piSystem->getSID();
}
$userPiSystems = $authorizationAdmin->getAccessedSystemNames();

// Фильтрация только по SAP PI или внешней системе
$choosePiSystem = $_GET['choosePiSystem']??'';
if ( !isset($systemNames[$choosePiSystem]) || !in_array($choosePiSystem, $userPiSystems) ) {
    $choosePiSystem = null;
}
$chooseBusinessSystem= $_GET['chooseBusinessSystem']??'';

$page = new HTMLPageTemplate($authorizationAdmin);

if ( isset($_GET['loadMainStatistics']) ) {

    $sqlParams = $userPiSystems;
    $sqlSystemFilter = '('.str_repeat('g.piSystemName = ? OR ', count($sqlParams)).' false)';
    if ( $chooseBusinessSystem ) {
        // По внешней системе
        $sqlParams[] = $chooseBusinessSystem;
        $sqlParams[] = $chooseBusinessSystem;
        $query = DB::prepare("
            SELECT count(*) as alert_count, count(DISTINCT a.group_id) as group_count, status
            FROM alerts a LEFT JOIN alert_group g ON a.group_id=g.group_id
            WHERE  $sqlSystemFilter AND (a.fromSystem = ? OR a.toSystem = ?)
            GROUP BY status
            ORDER BY group_count 
        ");
    } elseif ( empty($choosePiSystem) ) {
        // Вся статистика
        $query = DB::prepare("
            SELECT count(*) as alert_count, count(DISTINCT a.group_id) as group_count, status
            FROM alerts a LEFT JOIN alert_group g ON a.group_id=g.group_id
            WHERE $sqlSystemFilter
            GROUP BY status
            ORDER BY group_count 
        ");
    } else {
        // По конкретной интеграционной платформе
        $sqlParams = array($choosePiSystem);
        $query = DB::prepare("
            SELECT count(*) as alert_count, count(DISTINCT a.group_id) as group_count, status
            FROM alerts a LEFT JOIN alert_group g ON a.group_id=g.group_id
            WHERE a.piSystemName = ?
            GROUP BY status
            ORDER BY group_count 
        ");
    }
    $data = array();
    $total_alert_count = 0;
    $query->execute($sqlParams);
    while ($row = $query->fetch()) {
        $data[] = $row;
        $total_alert_count += $row['alert_count'];
    }
    $group_count = 0;
    $alert_count = 0;
    echo "<table class='table table-sm table-hover'>
                <thead>
                    <tr>
                        <th>".Text::status()."</th>
                        <th>".Text::statisticAlertGroupCount()."</th>
                        <th>".Text::alertCount()."</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>";
    foreach ($data as $row) {
        echo "  <tr>
                        <td>
                            <span class='bg-".PiAlertGroup::statusColor($row['status'])."'>&nbsp;</span>
                            ".PiAlertGroup::getStatusName($row['status'])."
                             <a href='/src/pages/dashboard.php?search=".PiAlertGroup::getStatusName($row['status'])."'>".$page->getIcon('eye')."</a>
                        </td>
                        <td>".$row['group_count']."</td>
                        <td>".$row['alert_count']."</td>
                        <td>".round10(100*$row['alert_count']/$total_alert_count)."%</td>
                    </tr>";
        $group_count += $row['group_count'];
        $alert_count += $row['alert_count'];
    }
    echo "      <tr>
                        <td><i>".Text::summary()."</i></td>
                        <td>".$group_count."</td>
                        <td>".$alert_count."</td>
                        <td>100%</td>
                    </tr>
                </tbody>
            </table>";
    exit();
}



echo $page->getPageHeader(Text::menuStatistics());

$piSystemFilterForStat = $choosePiSystem??$userPiSystems;
$week_alert = PiAlertGroup::getTotalAlertCount($piSystemFilterForStat, $chooseBusinessSystem, ONE_WEEK);
$month_alert = PiAlertGroup::getTotalAlertCount($piSystemFilterForStat, $chooseBusinessSystem, ONE_MONTH);
$week_alertPercent = PiAlertGroup::getAlertPercent($piSystemFilterForStat, $chooseBusinessSystem, ONE_WEEK);
$month_alertPercent = PiAlertGroup::getAlertPercent($piSystemFilterForStat, $chooseBusinessSystem, ONE_MONTH);
$message_ProcTimeWeek = PiAlertGroup::getMessageTimeProc($piSystemFilterForStat, $chooseBusinessSystem, ONE_WEEK);
$message_ProcTimeMonth = PiAlertGroup::getMessageTimeProc($piSystemFilterForStat, $chooseBusinessSystem, ONE_MONTH);

// Выбор фильтра по системе SAP PI
echo "<div class='card mb-4 shadow'>
        <div class='card-header'>";
$systemNames[''] = Text::statisticAllSystems();
foreach ($systemNames as $systemName => $systemSID) {
    if ( in_array($systemName, $userPiSystems) || $systemName == '' ) {
        echo "<a href='statistics.php?choosePiSystem=" . $systemName . "' class='btn btn-primary " . ($choosePiSystem == $systemName ? 'disabled' : '') . "'>" . $systemSID . "</a> ";
    }
}

// Выбор фильтра по внешней системе
$query = DB::prepare("SELECT code from bs_systems");
$query->execute();
$caption = $chooseBusinessSystem?:Text::externalSystems();
echo" <div class='btn-group'>
  <button type='button' class='btn btn-primary dropdown-toggle' data-bs-toggle='dropdown' aria-expanded='false'>".$caption."</button>
  <ul class='dropdown-menu'><li>
    <a class='dropdown-item' href='statistics.php?choosePiSystem=".$choosePiSystem."&chooseBusinessSystem='>".Text::statisticAllSystems()."</a></li>";
while ($row = $query->fetch()) {
    echo "<li><a class='dropdown-item' href='statistics.php?choosePiSystem=&chooseBusinessSystem=".$row['code']."'>".$row['code']."</a></li>";
}
echo  "</ul>
    </div>";
$chart = new HTMLChart();

echo "  </div>
        <div class='card-body overflow-auto'>
            <table class='table table-sm table-hover'>
                <tbody>";
if( !empty($chooseBusinessSystem) ) {
    echo "            <tr>
                          <td>".Text::statistic4ExtSystem()."</td>
                          <td><b>".$chooseBusinessSystem."</b></td>
                      </tr>";
}
echo "                <tr>
                          <td>" . Text::statisticAlert24HourCount() . "</td>
                          <td>" . PiAlertGroup::getTotalAlertCount($piSystemFilterForStat, $chooseBusinessSystem, ONE_DAY) . " " . Text::pieces() . "</td>
                      </tr>
                      <tr>
                            <td>" . Text::statisticAlertTodayChart() . "</td>
                            <td>" . $chart->getHourAlertsChart($piSystemFilterForStat, $chooseBusinessSystem) . "</td>
                      </tr>
                      <tr>
                          <td>" . Text::statisticAlertWeekCount() . "</td>
                          <td>" . $week_alert . " " . Text::pieces() . " ≈ " . round10($week_alert / 7) . ' ' . Text::perDay() . "</td>
                      </tr>
                      <tr>
                          <td>" . Text::statisticAlertMonthCount() . "</td>
                          <td>" . $month_alert . " " . Text::pieces() . " ≈ " . round10($month_alert / 30.5) . ' ' . Text::perDay() . "</td>
                      </tr>
                      <tr>
                            <td>" . Text::statisticAlertMonthChart() . "</td>
                            <td>" . $chart->getDailyAlertsChart($piSystemFilterForStat, $chooseBusinessSystem) . "</td>
                      </tr>
                      <tr>
                          <td>" . Text::statisticAlertTotalCount() . "</td>
                          <td>" . PiAlertGroup::getTotalAlertCount($piSystemFilterForStat, $chooseBusinessSystem) . " " . Text::pieces() . "</td>
                      </tr>";
$enableExtStatistic = false;
if ( is_null($choosePiSystem) ) {
    foreach ($userPiSystems as $systemName) {
        $enableExtStatistic = $enableExtStatistic || ManagePiSystem::getPiSystems()[$systemName]->getStatisticEnable();
    }
} else {
    $enableExtStatistic = ManagePiSystem::getPiSystems()[$choosePiSystem]->getStatisticEnable();
}
if ( $enableExtStatistic ) {
    echo "            <tr>
                          <td>" . Text::statisticAlertTotalPercent() . "</td>
                          <td>" . PiAlertGroup::getAlertPercent($piSystemFilterForStat, $chooseBusinessSystem) . "%</td>
                      </tr>  
                      <tr>
                          <td>" . Text::statisticAlertWeekPercent() . "</td>
                          <td>" . $week_alertPercent . "%</td>
                      </tr> 
                      <tr>
                          <td>" . Text::statisticAlertMonthPercent() . "</td>
                          <td>" . $month_alertPercent . "%</td>
                      </tr>  
                      <tr>
                            <td>" . Text::statisticAlertPercentMonthChart() . "</td>
                            <td>" . $chart->getDailyAlertsPercentChart($piSystemFilterForStat, $chooseBusinessSystem) . "</td>
                      </tr>
                      <tr>
                          <td>" . Text::statisticMessageTimeProc() . "</td>
                          <td>" . PiAlertGroup::getMessageTimeProc($piSystemFilterForStat, $chooseBusinessSystem) . ' '.Text::msecs()."</td>
                      </tr>
                      <tr>
                          <td>" . Text::statisticMessageWeekTimeProc() . "</td>
                          <td>" . $message_ProcTimeWeek .' '.Text::msecs()."</td>
                      </tr>  
                      <tr>
                          <td>" . Text::statisticMessageMonthTimeProc() . "</td>
                          <td>" . $message_ProcTimeMonth .' '.Text::msecs()."</td>
                      </tr>  
                      <tr>
                          <td>" . Text::statisticMessageTimeProcMonthChart() . "</td>
                          <td>" . $chart->getDailyMessageTimeProcChart($piSystemFilterForStat, $chooseBusinessSystem)."</td>
                      </tr>";
}
echo "          </tbody>
            </table>
            <div class='main-statistic'></div>
        </div>
    </div>";

$additionalScript = "<script type='text/javascript'>
        $(document).ready(function() {
            $.get( 'statistics.php?loadMainStatistics=1&choosePiSystem=".$choosePiSystem.".&chooseBusinessSystem=".$chooseBusinessSystem."', function( data ) {
                $('.main-statistic').html( data );
            });
        })
    </script>";
echo $page->getPageFooter( $additionalScript );

<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\PiAlertGroup;
use EvanPiAlert\Util\essence\PiSystem;
use EvanPiAlert\Util\HTML\HTMLChart;
use EvanPiAlert\Util\HTML\HTMLPageTemplate;
use EvanPiAlert\Util\ManagePiSystem;
use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\Text;

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

// Фильтрация только по одному SAP PI

//var_dump($_GET);
$choseSystem = $_GET['choseSystem']??'';
$choseBusinessSystem= $_GET['choseBusinessSystem']??'';
$piSystems = new ManagePiSystem();
$piSystemNames=$piSystems->getPiSystems();
if ( count($piSystemNames) == 0 ) {
    $piSystemNames = array();
}
foreach ($piSystemNames as $piSystem)
{
    $systemNames[$piSystem->getSystemName()]=$piSystem->getSID();
}


if ( !isset($systemNames[$choseSystem]) ) {
    $choseSystem = '';
}

$page = new HTMLPageTemplate($authorizationAdmin);

if ( isset($_GET['loadMainStatistics']) ) {
    if ( !empty($choseSystem)) {
        $query = DB::prepare("
            SELECT count(*) as alert_count, count(DISTINCT a.group_id) as group_count, status
            FROM alerts a LEFT JOIN alert_group g ON a.group_id=g.group_id
            WHERE a.piSystemName = ?
            GROUP BY status
            ORDER BY group_count 
        ");
        $query->execute(array( $choseSystem ));
    }

    if ($choseBusinessSystem)
    {
        $query = DB::prepare("
            SELECT count(*) as alert_count, count(DISTINCT a.group_id) as group_count, status
            FROM alerts a LEFT JOIN alert_group g ON a.group_id=g.group_id
            WHERE  a.fromSystem = ? OR a.toSystem = ?
            GROUP BY status
            ORDER BY group_count 
        ");
        $query->execute(array( $choseBusinessSystem, $choseBusinessSystem));
    }
   else{
        $query = DB::prepare("
            SELECT count(*) as alert_count, count(DISTINCT a.group_id) as group_count, status
            FROM alerts a LEFT JOIN alert_group g ON a.group_id=g.group_id
            GROUP BY status
            ORDER BY group_count 
        ");
        $query->execute(array());
    }

    $data = array();
    $total_alert_count = 0;
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

        $week_alert = PiAlertGroup::getTotalAlertCount($choseSystem, $choseBusinessSystem, ONE_WEEK);
        $month_alert = PiAlertGroup::getTotalAlertCount($choseSystem, $choseBusinessSystem, ONE_MONTH);
        $week_alertPercent= PiAlertGroup::getAlertPercent($choseSystem, $choseBusinessSystem, ONE_WEEK);
        $month_alertPercent= PiAlertGroup::getAlertPercent($choseSystem, $choseBusinessSystem, ONE_MONTH);

echo "<div class='card mb-4 shadow'>
        <div class='card-header'>";
$systemNames[''] = Text::statisticAllSystems();
foreach ($systemNames as $systemCode => $systemName) {
    echo "<a href='statistics.php?choseSystem=".$systemCode."' class='btn btn-primary ".($choseSystem==$systemCode?'disabled':'')."'>".$systemName."</a> ";

    echo "";
}

$query = DB::prepare("SELECT code from bs_systems");
$query->execute();
echo" <div class='btn-group'>
  <button type='button' class='btn btn-primary dropdown-toggle' data-bs-toggle='dropdown' aria-expanded='false'>".Text::externalSystems()."</button>
  <ul class='dropdown-menu'>";
    while ($row = $query->fetch()) {
        echo "<li><a class='dropdown-item' href='statistics.php?choseBusinessSystem=".$row['code']."'>".$row['code']."</a></li>";
}
echo  "</ul>
</div>";
$chart = new HTMLChart();

echo "  </div>
        <div class='card-body overflow-auto'>
            <table class='table table-sm table-hover'>
                <tbody> 
                <tr>
                          <td>" . Text::statisticAlert24HourCount() . "</td>
                          <td>" . PiAlertGroup::getTotalAlertCount($choseSystem, $choseBusinessSystem, ONE_DAY) . " " . Text::pieces() . "</td>
                      </tr>
                      <tr>
                            <td>" . Text::statisticAlertTodayChart() . "</td>
                            <td>" . $chart->getHourAlertsChart($choseSystem, $choseBusinessSystem) . "</td>
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
                            <td>" . $chart->getDailyAlertsChart($choseSystem, $choseBusinessSystem) . "</td>
                      </tr>
                      <tr>
                          <td>" . Text::statisticAlertTotalCount() . "</td>
                          <td>" . PiAlertGroup::getTotalAlertCount($choseSystem, $choseBusinessSystem) . " " . Text::pieces() . "</td>
                      </tr>
                      <tr>
                          <td>" . Text::statisticAlertTotalPercent() . "</td>
                          <td>" . PiAlertGroup::getAlertPercent($choseSystem, $choseBusinessSystem) . "%</td>
                      </tr>  
                      <tr>
                          <td>" . Text::statisticAlertWeekPercent() . "</td>
                          <td>" . $week_alertPercent . "%  ≈ " . round10($week_alertPercent / 7) . ' ' . Text::perDay() . "</td>
                      </tr> 
                      <tr>
                          <td>" . Text::statisticAlertMonthPercent() . "</td>
                          <td>" . $month_alertPercent . "%  ≈ " . round10($month_alertPercent / 7) . ' ' . Text::perDay() . "</td>
                      </tr>     
                      </tbody>
            </table>
            <div class='main-statistic'></div>
        </div>
    </div>";

$additionalScript = "<script type='text/javascript'>
        $(document).ready(function() {
            $.get( 'statistics.php?loadMainStatistics=1&choseSystem=".$choseSystem.".&choseBusinessSystem=".$choseBusinessSystem."', function( data ) {
                $('.main-statistic').html( data );
            });
        })
    </script>";
echo $page->getPageFooter( $additionalScript );

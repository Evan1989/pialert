<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\PiAlertGroup;
use EvanPiAlert\Util\essence\User;
use EvanPiAlert\Util\ExcelFile;
use EvanPiAlert\Util\HTML\HTMLPageTemplate;
use EvanPiAlert\Util\Text;

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

if ( isset($_GET['report']) ) {
    $excelFile = new ExcelFile( 'PiAlert_export_'.date("Y-m-d") );
    switch ($_GET['report']) {
        case 'main_base':
            $excelFile->addCell("Group ID", 1, 1, true);
            $excelFile->addCell("Status", 1, 1, true);
            $excelFile->addCell("User", 1, 1, true);
            $excelFile->addCell("User comment", 1, 1, true);
            $excelFile->addCell("PI system", 1, 1, true);
            $excelFile->addCell("Sender system", 1, 1, true);
            $excelFile->addCell("Receiver system", 1, 1, true);
            $excelFile->addCell("Interface", 1, 1, true);
            $excelFile->addCell("Channel", 1, 1, true);
            $excelFile->addCell("Error text mask", 1, 1, true);
            $excelFile->addCell("First alert", 1, 1, true);
            $excelFile->addCell("Last alert", 1, 1, true);
            $excelFile->newLine();
            $query = DB::prepare("SELECT *  FROM alert_group");
            $query->execute(array());
            while($row = $query->fetch()) {
                $alertGroup = new PiAlertGroup($row);
                $user = new User($alertGroup->user_id);
                $excelFile->addCell($alertGroup->group_id);
                $excelFile->addCell(PiAlertGroup::getStatusName($alertGroup->status));
                $excelFile->addCell($user->getCaption('-'));
                $excelFile->addCell($alertGroup->comment);
                $excelFile->addCell($alertGroup->getPiSystemSID());
                $excelFile->addCell($alertGroup->fromSystem);
                $excelFile->addCell($alertGroup->toSystem);
                $excelFile->addCell($alertGroup->interface);
                $excelFile->addCell($alertGroup->channel);
                $excelFile->addCell($alertGroup->errTextMask);
                $excelFile->addCell($alertGroup->firstAlert);
                $excelFile->addCell($alertGroup->lastAlert);
                $excelFile->newLine();
            }
            break;
        case 'support_online':
            $excelFile->addCell("Date", 1, 1, true);
            $excelFile->addCell("User", 1, 1, true);
            $excelFile->addCell("Seconds per day", 1, 1, true);
            $excelFile->addCell("Time per day (text)", 1, 1, true);
            $excelFile->newLine();
            $query = DB::prepare("SELECT *  FROM user_statistic_online order by date");
            $query->execute(array());
            while($row = $query->fetch()) {
                $user = new User($row['user_id']);
                $excelFile->addCell($row['date']);
                $excelFile->addCell($user->getCaption('-'));
                $excelFile->addCell($row['seconds']);
                $excelFile->addCell(getIntervalRoundLength($row['seconds']));
                $excelFile->newLine();
            }
            break;
        default:
            $excelFile->addCell(Text::exportErrorOnGenerateReport());
            break;
    }
    $excelFile->serializeFileForUser();
    exit();
}

$page = new HTMLPageTemplate($authorizationAdmin);
echo $page->getPageHeader(Text::exportPageHeader());

echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>".Text::exportPageHeader()."</div>
        <div class='card-body overflow-auto'>
            <table class='table table-sm table-hover tablesorter'>
                <thead>
                    <tr>
                        <th>".Text::name()."</th>
                        <th>".Text::exportLoadOnSystem()."</th>
                        <th>".Text::actions()."</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>".Text::exportMainAlertGroupBase()."</td>
                        <td>".Text::exportLow()."</td>
                        <td><a href='export.php?report=main_base' class='btn btn-sm btn-primary'>download</a></td>
                    </tr>
                    <tr>
                        <td>".Text::onlinePageHeader()."</td>
                        <td>".Text::exportLow()."</td>
                        <td><a href='export.php?report=support_online' class='btn btn-sm btn-primary'>download</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>";

echo $page->getPageFooter();

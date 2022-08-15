<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\essence\PiAlertGroup;
use EvanPiAlert\Util\HTMLPageTemplate;
use EvanPiAlert\Util\Text;

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

$page = new HTMLPageTemplate($authorizationAdmin);

// TODO галочки, чтобы исключить часть строк

if ( isset($_POST['errorText']) ) {
    $system = htmlspecialchars($_POST['system']);
    $errorText = $_POST['errorText'];
    $_SESSION['system'] = $system;
    $_SESSION['errorText'] = $errorText;
} elseif ( isset($_SESSION['system']) ) {
    $system = $_SESSION['system'];
    $errorText = $_SESSION['errorText'];
} else {
    $system = '';
    $errorText = '';
}

$groups = array();
if ( $errorText ) {
    $status = $_POST['status']??null;
    if ( $status == PiAlertGroup::getStatusName(PiAlertGroup::CLOSE) ) {
        $status = PiAlertGroup::CLOSE;
    } elseif ( $status == PiAlertGroup::getStatusName(PiAlertGroup::IGNORE) ) {
        $status = PiAlertGroup::IGNORE;
    } else {
        $status = PiAlertGroup::WAIT;
    }
    $query = DB::prepare("
        SELECT *
        FROM alert_group
        WHERE
            last_alert > NOW() - INTERVAL 14 DAY
            AND (fromSystem LIKE ? OR toSystem LIKE ?)
            AND errTextMask LIKE ?
            AND status != ?
        order by last_alert desc
    ");
    $systemForSearch = '%'.str_replace('*', '%',$system).'%';
    $errorTextForSearch = '%'.str_replace('*', '%',$errorText).'%';
    $query->execute(array($systemForSearch, $systemForSearch, $errorTextForSearch, PiAlertGroup::CLOSE));
    while ($row = $query->fetch()) {
        $alertGroup = new PiAlertGroup($row);
        if ( isset($_POST['comment']) ) {
            $alertGroup->setComment( $_POST['comment'] );
            $alertGroup->status = $status;
            $alertGroup->setUserId( $authorizationAdmin->getUserId() );
            if ( $status == PiAlertGroup::CLOSE ) {
                $alertGroup->setUserId( null );
            }
            $alertGroup->lastUserAction = date("Y-m-d H:i:s");
            $alertGroup->saveToDatabase();
        }
        $groups[] = $alertGroup;
    }
}
$groupCount = count($groups);

echo $page->getPageHeader(Text::massAlertsPageHeader());

$systems = array();
$query = DB::prepare("SELECT fromSystem, count(*) as count FROM alert_group WHERE last_alert > NOW() - INTERVAL 14 DAY AND status != ? AND fromSystem IS NOT NULL AND fromSystem != '' GROUP BY fromSystem order by count desc LIMIT 3");
$query->execute(array(PiAlertGroup::CLOSE));
while ($row = $query->fetch()) {
    if ( $row['count'] > 1 ) {
        $systems[ $row['fromSystem'] ] = "<span class='btn btn-sm btn-secondary mass-alert-help-button-system m-1'>".$row['fromSystem']."</span>";
    }
}
$query = DB::prepare("SELECT toSystem, count(*) as count FROM alert_group WHERE last_alert > NOW() - INTERVAL 14 DAY AND status != ? AND toSystem IS NOT NULL AND toSystem != '' GROUP BY toSystem order by count desc LIMIT 3");
$query->execute(array(PiAlertGroup::CLOSE));
while ($row = $query->fetch()) {
    if ( $row['count'] > 1 && !isset($systems[$row['toSystem']]) ) {
        $systems[] = "<span class='btn btn-sm btn-secondary mass-alert-help-button-system m-1'>".$row['toSystem']."</span>";
    }
}
$errors = array();
$query = DB::prepare("SELECT errTextMainPart, count(*) as count FROM alert_group WHERE last_alert > NOW() - INTERVAL 14 DAY AND status != ? GROUP BY errTextMainPart order by count desc LIMIT 5");
$query->execute(array(PiAlertGroup::CLOSE));
while ($row = $query->fetch()) {
    if ( $row['count'] > 1 ) {
        $errors[] = "<span class='btn btn-sm btn-secondary mass-alert-help-button-error m-1'>".$row['errTextMainPart']."</span>";
    }
}

echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>
	        ".Text::massAlertsSearchBlockHeader()."
        </div>
        <div class='card-body overflow-auto'>
            <form action='' method='POST'>
                <div class='row mb-1'>
                    <div class='col-sm-6'>
                        <input class='form-control' type='text' name='system' maxlength='100' placeholder='".Text::sender().' '.Text::or().' '.Text::receiver()."' value=\"".$system."\">
                    </div>
                    <label class='col-sm-6'>".implode(' ', $systems)."</label>
                </div>
                <div class='row mb-1'>
                    <div class='col-sm-6'>
                        <textarea class='form-control' name='errorText' maxlength='2000' placeholder='".Text::error()."' required>".$errorText."</textarea>
                    </div>
                    <label class='col-sm-6'>".implode(' ', $errors)."</label>
                </div>
                <input class='btn btn-primary mb-1' type='submit' value='".Text::search()."'>
                </form>
            </div>
        </div>
    </div>";
if ( $system && $errorText && $groupCount == 0 ) {
    echo "<div class='alert alert-warning' role='alert'>
        ".Text::massAlertsFoundedCount()." 0 ".Text::pieces()."
    </div>";
}
if ( $groupCount >= 1 ) {
    echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>
	        ".Text::massAlertsFoundedBlockHeader()."
        </div>
        <div class='card-body overflow-auto'>
            <div class='row'>
                <div class='col-md-6 mb-4'>
                    <form action='' method='POST'>
                        <input type='hidden' name='system' value=\"".$system."\">
                        <input type='hidden' name='errorText' value=\"".$errorText."\">
                        <div class='row'>
                            <label class='col-form-label col-sm-4'>".Text::massAlertsFoundedCount()."</label>
                            <div class='col-sm-8'>".$groupCount." ".Text::pieces()."</div>
                        </div>
                        <div class='row'>
                            <label class='col-form-label col-sm-4'>".Text::massAlertsReplace()." ".Text::comment()."</label>
                            <div class='col-sm-8'>
                                <textarea class='form-control' name='comment' maxlength='2000' placeholder='".Text::dashboardCommentPlaceholder()."' required></textarea>
                            </div>
                        </div>
                        <div class='row'>
                            <label class='col-form-label col-sm-4'>".Text::massAlertsRemoveIcon()."</label>
                            <div class='col-sm-8'>".$page->getIcon('bell-fill')." ".Text::or()." ".$page->getIcon('bell')."</div>
                        </div>
                        <div class='row'>
                            <label class='col-form-label col-sm-4'>".Text::massAlertsReplace()." ".Text::status().":</label>
                            <div class='col-sm-8'>
                               <input class='btn btn-".PiAlertGroup::statusColor(PiAlertGroup::CLOSE, true)."' type='submit' name='status' value='".Text::statusClose()."'>
                                <input class='btn btn-".PiAlertGroup::statusColor(PiAlertGroup::IGNORE, true)."' type='submit' name='status' value='".Text::statusIgnore()."'>
                                <input class='btn btn-".PiAlertGroup::statusColor(PiAlertGroup::WAIT, true)."' type='submit' name='status' value='".Text::statusWait()."'>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <table class='tablesorter table table-sm table-hover table-responsive-lg'>
                <thead>
                    <th>".Text::sender()."</th>
                    <th>".Text::receiver()."</th>
                    <th>".Text::dashboardMaskOrError()."</th>
                    <th>".Text::status()."</th>
                    <th>".Text::comment()."</th>
                    <th>".mb_convert_case(Text::perDay(), MB_CASE_TITLE )."</th>
                </thead>
                <tbody>";
    foreach($groups as $alertGroup) {
        /** @var PiAlertGroup $alertGroup */
        echo "          <tr>
                        <td>".$alertGroup->fromSystem."</td>
                        <td>".$alertGroup->toSystem."</td>
                        <td>".$alertGroup->getHTMLErrorTextMask()."</td>
                        <td class='bg-".$alertGroup->getStatusColor($authorizationAdmin->getUserId())."'>".PiAlertGroup::getStatusName($alertGroup->status)."</td>
                        <td>".$alertGroup->getHTMLComment()."</td>
                        <td>".$alertGroup->getAlertCount(ONE_DAY)." ".Text::pieces()."
                            <br>
                            <a href=\"".SERVER_HOST."src/pages/dashboard.php?id=".$alertGroup->group_id."\" data-toggle='tooltip' data-placement='top' title='".Text::dashboardShareLinkButton()."' target='_blank'>".$page->getIcon('share')."</a>
                        </td>
                    </tr>";
    }
    echo "      </tbody>
            </table>
        </div>
	</div>";
}

$additionalScript = "<script type='text/javascript'>
        $(document).ready(function() {
            initJavascriptForMassAlerts();
        });
    </script>";
echo $page->getPageFooter( $additionalScript );
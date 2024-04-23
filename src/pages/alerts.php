<?php

require_once(__DIR__."/../autoload.php");

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;
use EvanPiAlert\Util\HTML\HTMLPageAlerts;
use EvanPiAlert\Util\Text;

$authorizationAdmin = new AuthorizationAdmin();
$authorizationAdmin->ifNotAccessGoErrorPage();

$page = new HTMLPageAlerts($authorizationAdmin);
echo $page->getPageHeader(Text::alertsPageHeader());

echo "<div class='card mb-4 shadow'>
	    <div class='card-header'>
	        ".Text::alertsPageHeader()."
	        <div class='float-end mx-2'>
	            <input class='d-inline form-control form-control-sm' id='mainTableSearch' type='text' placeholder='".Text::search()."...' value=''>
            </div>
	    </div>
        <div class='card-body overflow-auto main-table-for-filter'>";
$sqlParams = $authorizationAdmin->getAccessedSystemNames();
$sqlSystemFilter = '('.str_repeat('piSystemName = ? OR ', count($sqlParams)).' false)';
$query = DB::prepare(" SELECT * FROM alerts WHERE $sqlSystemFilter ORDER BY id desc LIMIT 1000");
$query->execute($sqlParams);
echo $page->getAlertTable($query);
echo "  </div>
	</div>";

$additionalScript = "
    <script type='text/javascript'>
        $(document).ready(function() {
            $('.tablesorter').tablesorter( {
				headers: {}
            });
            setTimeout(function(){
                location.reload();
            }, 300000);
        })
    </script>";
echo $page->getPageFooter( $additionalScript );
<?php

//////////////////////////////////////////////////
//   Сервис для приема CBMA алертов из SAP PI   //
//////////////////////////////////////////////////

use EvanPiAlert\Util\AlertAggregationUtil;
use EvanPiAlert\Util\essence\PiAlert;
use EvanPiAlert\Util\HTML\HTMLPageTemplate;
use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\Text;

require_once(__DIR__."/../autoload.php");
require_once(__DIR__."/common.php");

$postData = file_get_contents('php://input');

if ( empty($postData) ) {
    $page = new HTMLPageTemplate();
    echo $page->getPageHeader('PiAlert');
    echo Text::apiCBMAServiceInfo(SERVER_HOST.$_SERVER['PHP_SELF']);
    echo $page->getPageFooter();
    exit();
}

checkAPICallAuthorization(Settings::get(Settings::CBMA_SERVICE_PASSWORD));

$alert = new PiAlert($postData);
if ( $alert->piSystemName == '' ) {
    http_response_code( 500 );
}
$alertGroup = AlertAggregationUtil::createOrFindGroupForAlert($alert);
$alert->group_id = $alertGroup->group_id;
if ( $alert->saveNewToDatabase() !== true ) {
	http_response_code( 500 );
}
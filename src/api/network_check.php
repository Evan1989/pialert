<?php

//////////////////////////////////////////////////
//     Сервис для свидетельства канарейки.      //
//    PI тыкает PiAlert, чтобы проверить сеть   //
//////////////////////////////////////////////////

use EvanPiAlert\Util\HTMLPageTemplate;
use EvanPiAlert\Util\Settings;
use EvanPiAlert\Util\Text;

require_once(__DIR__."/../autoload.php");
require_once(__DIR__."/common.php");

$system = $_GET['system']??null;

if ( empty($system) ) {
    $page = new HTMLPageTemplate();
    echo $page->getPageHeader('PiAlert');
    echo Text::apiNetworkCheckServiceInfo(SERVER_HOST.$_SERVER['PHP_SELF']);
    echo $page->getPageFooter();
    exit();
}

checkAPICallAuthorization(Settings::get(Settings::CBMA_SERVICE_PASSWORD));

$checks = Settings::get(Settings::SYSTEMS_NETWORK_CHECK);
$checks = json_decode($checks, true);
$checks[$system] = date("Y-m-d H:i:s");
Settings::set(Settings::SYSTEMS_NETWORK_CHECK, json_encode($checks));

echo "{}";
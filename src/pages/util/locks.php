<?php

//////////////////////////////////////////////////
//        Скрипт вызывается через ajax          //
//////////////////////////////////////////////////

require_once(__DIR__."/../../autoload.php");

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\BlockSystem;

$action = $_GET['action']??'';
$menu_id = $_GET['menu_id']??0;
$element_id = $_GET['element_id']??'';

$authorizationAdmin = new AuthorizationAdmin();
if ( $authorizationAdmin->checkAccessToMenu($menu_id) === false ) {
    http_response_code( 403 );
    exit();
}

$blockSystem = new BlockSystem($authorizationAdmin->getUserId(), $menu_id);

switch ($action) {
    case 'delete':
        echo json_encode($blockSystem->deleteBlock($element_id));
        break;
    case 'create':
        echo json_encode($blockSystem->createBlock($element_id));
        break;
    case 'check':
        echo json_encode($blockSystem->getBlocks());
}
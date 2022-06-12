<?php

use EvanPiAlert\Util\AuthorizationAdmin;
use EvanPiAlert\Util\DB;

require_once(__DIR__."/../autoload.php");

$authorizationAdmin = new AuthorizationAdmin();
if ( !$authorizationAdmin->login() ) {
    $authorizationAdmin->showAuthorizationPage();
    exit();
}
$query = DB::prepare("
    SELECT *
    FROM
        user_rights as r LEFT JOIN
        pages p on r.menu_id = p.menu_id
    WHERE r.user_id = ?
    order by p.number
    limit 1");
$query->execute(array( $authorizationAdmin->getUserId() ));
if ($row = $query->fetch()) {
    Header("Location: ".$row['url']);
    exit();
}
Header("Location: profile.php");
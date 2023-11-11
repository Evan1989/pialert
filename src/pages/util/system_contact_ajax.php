<?php

require_once(__DIR__ . "/../../autoload.php");

use EvanPiAlert\Util\DB;

if( isset($_POST['code']) ) {
    $query = DB::prepare("SELECT name, contact FROM bs_systems WHERE code = ?");
    $query->execute(array($_POST['code']));
    while($row = $query->fetch()) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE);
    }
}
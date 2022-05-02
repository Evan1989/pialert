<?php

function checkAPICallAuthorization(string $password) : bool {
    // $login = $_SERVER['PHP_AUTH_USER'] ?? null; // Логин нигде не проверяется
    $pass = $_SERVER['PHP_AUTH_PW'] ?? null;
    if ( empty($pass) || $pass != $password ) {
        Header('WWW-Authenticate: Basic realm="PiAlert API"');
        http_response_code(401);
        exit();
    }
    return true;
}
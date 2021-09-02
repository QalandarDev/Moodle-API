<?php
include "api.php";
$username = $_GET["username"];
$password = $_GET["password"];
$api = new api([
    "username" => $username,
    "password" => $password,
]);
$res = $api->getMyPage();
if ($res["ok"]) {
    header(
        "Location: {$CFG->host}/link.php?username={$username}&password={$password}&sessionkey={$api->sessionKey}"
    );
}

<?php
include "api.php";
$id = $_GET["id"];
$username = $_GET["username"];
$password = $_GET["password"];
$api = new api([
    "username" => $username,
    "password" => $password,
    "url" => "http://dl.urdu.uz",
]);
$res = $api->testsession($id);
if ($res["ok"]) {
    print_r($res);
    header(
        "Location: $CFG->host/my.php?username={$username}&password={$password}"
    );
}

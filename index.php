<?php
include "api.php";
header("Content-type:application/json");
if (
    array_key_exists("username", $_GET) and array_key_exists("password", $_GET)
) {
    $api = new api([
        "username" => $_GET["username"],
        "password" => $_GET["password"],
    ]);
    $res = $api->login();
    echo $api->arrayToJson($res);
    // exit();
    if ($res["ok"]) {
        $id = $res["id"];
        echo "USER ID=" . $id;
        header(
            "Location: {$CFG->host}/testsession.php?id={$id}&username={$_GET["username"]}&password={$_GET["password"]}"
        );
    }
} else {
    $res = [
        "ok" => false,
        "error" => "Login yoki parol kiritilmadi",
    ];
    $api = new api();
    echo $api->arrayToJson($res);
}

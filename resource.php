<?php
include "api.php";
if (array_key_exists("sessionkey", $_GET) and array_key_exists("id", $_GET)) {
    header("Content-type:application/json");
    $api = new api([
        "sessionkey" => $_GET["sessionkey"],
    ]);
    $api->getUsername();
    $res = $api->getResourseList($_GET["id"]);
    echo $api->arrayToJson($res);
}

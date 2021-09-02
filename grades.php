<?php
include "api.php";
if (array_key_exists("sessionkey", $_GET)) {
    if (!array_key_exists("mode", $_GET)) {
        header("Content-type:application/json");
        $api = new api([
            "sessionkey" => $_GET["sessionkey"],
        ]);
        $api->getUsername();
        $res = $api->getAllGrades();
        echo $api->arrayToJson($res);
    }
}

<?php
include "api.php";
if (array_key_exists("sessionkey", $_GET)) {
    $api = new api([
        "sessionkey" => $_GET["sessionkey"],
    ]);
    $api->getUsername();
    $res = $api->getSessionsList();

    if (!array_key_exists("mode", $_GET)) {
        header("Content-type:application/json");
        echo $api->arrayToJson($res);
    } else {
        switch ($_GET['mode']) {
            case 'PDF':
                header(
                    "Location: {$CFG->host}/session_mode_pdf.php"
                );
                break;
            case "XLS":
                header(
                    "Location: {$CFG->host}/session_mode_xls.php"
                );
                break;
            case 'HTML':
                header(
                    "Location: {$CFG->host}/session_mode_html.php"
                );
                break;
            default:
                # code...
                break;
        }

    }
}

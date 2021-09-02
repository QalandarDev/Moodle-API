<?php
include "api.php";
if (array_key_exists("sessionkey", $_GET) and array_key_exists("id", $_GET)) {
    $api = new api([
        "sessionkey" => $_GET["sessionkey"],

    ]);
    $api->getUsername();
    $res = $api->getDownloadFile($_GET["id"]);
    if ($res['headers']) {
        header("Content-type:application/json");
        $filename = "@UrDUMoodleBot " . str_replace("\"", "", explode("filename=", $res['headers']['content-disposition'])[1]);
        $fh = fopen($filename, "w");
        fwrite($fh, $res['data']);
        fclose($fh);
        $url_path_str = 'http://transfer.sh/' . urlencode($filename);
        $file_path_str = $filename;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, '' . $url_path_str . '');
        curl_setopt($ch, CURLOPT_PUT, 1);
        $fh_res = fopen($file_path_str, 'r');
        curl_setopt($ch, CURLOPT_INFILE, $fh_res);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file_path_str));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $js = [
            'ok' => true,
            'url' => curl_exec($ch),
            'name' => $filename,
        ];
        fclose($fh_res);
    } else {
        $js = [
            'ok' => false,
            'error' => "Headers not found",
        ];
    }
    echo $api->arrayToJson($js);
}

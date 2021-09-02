<?php
include "simple_html_dom.php";
require_once __DIR__ . "/myconfig.php";
$username = $_GET["username"];
$password = $_GET["password"];
$cookiePath = "temp_cookies.txt";
header("Content-type:application/json");
$ch = curl_init($CFG->moodle . "/login/index.php");
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);
$html = str_get_html($res);
$logintoken = $html->find('input[name="logintoken"]')[0]->value;
$ch = curl_init($CFG->moodle . "/login/index.php");
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'username' => $_GET['username'],
    'password' => $_GET['password'],
    'logintoken' => $logintoken,
    'anchor' => "",
    'rememberusername' => false,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);
$html = str_get_html($res);
if ($html->find('a[id="loginerrormessage"]')) {
    $js = [
        'ok' => false,
        'error' => "Invalid username or password",
        'errcode' => "1",
    ];
} else {
    $sessionkey = $html->find('a[data-title="logout,moodle"]')[0]->href;
    $ch = curl_init($sessionkey);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    $js = [
        'ok' => true,
        'username' => $_GET['username'],
        'password' => $_GET['password'],
    ];
}
echo json_encode(
    $js,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
unlink($cookiePath);

<?php
include "api.php";
$api = new api([
    'username' => $_GET['username'],
    'password' => $_GET['password'],
]);
header("Content-type:application/json");
$url = [
    'Sessions' => "{$CFG->host}/sessions.php?sessionkey=" . $_GET['sessionkey'],
    'Courses' => "$CFG->host/course.php?sessionkey=" . $_GET['sessionkey'],
    'Grades' => "$CFG->host/grades.php?sessionkey=" . $_GET['sessionkey'],
    'Profile' => "$CFG->host/profile.php?sessionkey=" . $_GET['sessionkey'],
    "Logout" => "$CFG->host/logout.php?sessionkey=" . $_GET['sessionkey']];
echo $api->arrayToJson($url);

<?php
$lines = file("sessions.txt");
$data = array();
$data[] = array('Log in', 'Last access', 'Last IP address', 'ID');
foreach ($lines as $line) {
    $data[] = explode(';', trim(str_replace(",", " ", $line)));
}

$data[] = array('@UrDUMoodleBot', 'tomonidan ', 'tayyorlandi', '@URDUFM');
$data;

// Filter Customer Data
function filterCustomerData(&$str)
{
    $str = preg_replace("/\t/", "\\t", $str);
    $str = preg_replace("/\r?\n/", "\\n", $str);
    if (strstr($str, '"')) {
        $str = '"' . str_replace('"', '""', $str) . '"';
    }

}

// File Name & Content Header For Download
$file_name = "customers_data.xls";
header("Content-Disposition: attachment; filename=\"$file_name\"");
header("Content-Type: application/vnd.ms-excel");

//To define column name in first row.
// run loop through each row in $customers_data
foreach ($data as $row) {

    // The array_walk() function runs each array element in a user-defined function.
    array_walk($row, 'filterCustomerData');
    echo implode("\t", array_values($row)) . "\n";
}
exit;

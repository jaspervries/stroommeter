<?php
/*
*   stroommeter - toolchain for reading energy meters
*   Copyright (C) 2022-2025  Jasper Vries
*
*   This program is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.

*   You should have received a copy of the GNU General Public License
*   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo 'invalid method';
    exit;
}
$json = json_decode(file_get_contents('php://input'), true);
if ($json == null) {
    http_response_code(400);
    echo 'invalid json';
    exit;
}
//check if there is a key
if (!array_key_exists('key', $json)) {
    http_response_code(406);
    echo 'key required';
    exit;
}
//check if time format is valid
if (!array_key_exists('time', $json)
&& (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $json['time']))) {
    http_response_code(400);
    echo 'invalid time';
    exit;
}
//check key and counters
include('../config.inc.php');
include('../dbconnect.inc.php');
$checked_counters = false;
foreach ($counters as $i => $counter) {
    if (($counter['key'] == $json['key']) 
    && array_key_exists('counter' . $counter['counter'], $json)
    && is_numeric($json['counter' . $counter['counter']])) {
        $checked_counters = true;
        //insert into database
        $qry = "INSERT INTO `" . $db['prefix'] . "usage` SET
        `datetime` = '" . $json['time'] . "',
        `counter` = " . $i . ",
        `usage` = '" . $json['counter' . $counter['counter']] . "'";
        mysqli_query($db['link'], $qry);
        //also insert in temporary table 
        if ($use_temptable == TRUE) {
            $qry = "INSERT INTO `" . $db['prefix'] . "temp` SET
            `datetime` = '" . $json['time'] . "',
            `counter` = " . $i . ",
            `usage` = '" . $json['counter' . $counter['counter']] . "'";
            mysqli_query($db['link'], $qry);
        }
    }
}
//report final status code
if ($checked_counters === true) {
    http_response_code(200);
    echo 'ok';
}
else {
    http_response_code(400);
    echo 'invalid request';
}
exit;
?>
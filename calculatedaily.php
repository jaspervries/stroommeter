<?php
/*
*   stroommeter - toolchain for reading energy meters
*   Copyright (C) 2025  Jasper Vries
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

/*
This script is intended to be used in a cronjob to calculate the daily total usage
*/

include('dbconnect.inc.php');

//find the most recent day that has been calculated
$qry = "SELECT MAX(`date`) FROM `" . $db['prefix'] . "daily`";
$res = mysqli_query($db['link'], $qry);
if (!mysqli_num_rows($res)) {
    exit;
}
$date = mysqli_fetch_row($res);
$date = $date[0];
//no recent day calculated, find first date available
if ($date == NULL) {
    $qry = "SELECT MIN(DATE(`datetime`)) FROM `" . $db['prefix'] . "usage`";
    $res = mysqli_query($db['link'], $qry);
}
//if date found, calculate everything since this date up to yesterday (today is not useful because it's not complete)
if (mysqli_num_rows($res)) {
    $date = mysqli_fetch_row($res);
    $date = $date[0];
    //calculate all days since most recent day
    $qry = "INSERT INTO `" . $db['prefix'] . "daily` (`date`, `counter`, `usage`)
    SELECT * FROM (SELECT DATE(`datetime`), `counter`, SUM(`usage`) AS `us` FROM `" . $db['prefix'] . "usage`
    WHERE DATE(`datetime`) BETWEEN '" . $date . "' AND DATE_ADD(CURDATE(), INTERVAL -1 DAY)
    GROUP BY `counter`, DATE(`datetime`)) AS `t1`
    ON DUPLICATE KEY UPDATE `" . $db['prefix'] . "daily`.`usage` = `us`";
    mysqli_query($db['link'], $qry);
}
?>
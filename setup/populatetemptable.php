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
include('../config.inc.php');
include('../dbconnect.inc.php');

$qry = "INSERT IGNORE INTO `" . $db['prefix'] . "temp` (`datetime`, `counter`, `usage`)
        SELECT * FROM (
            SELECT `datetime`, `counter`, `usage` FROM `" . $db['prefix'] . "usage`
            WHERE DATE(`datetime`) >= DATE_ADD(CURDATE(), INTERVAL -" . ((is_numeric($temptable_window) && ($temptable_window > 0)) ? $temptable_window : 31) . " DAY)
        ) AS `t1`";
if (mysqli_query($db['link'], $qry)) {
    echo 'done';
}
else {
    echo 'failed';
}
?>
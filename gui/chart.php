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
include('../config.inc.php');
include('../dbconnect.inc.php');

//check date format is valid
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    http_response_code(400);
    echo 'invalid date';
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date2'])) {
    http_response_code(400);
    echo 'invalid date';
    exit;
}

$date = $_GET['date'];
$date2 = $_GET['date2'];

//decide modified date if results will be in the future
//not for daily view, as this doesn't list the date in the chart
switch ($_GET['type']) {
    case 1:
        $time_fromnow = strtotime(date('Y-m-d') . ' -6 day');
        $time_selected = strtotime($_GET['date']);
        if ($time_selected > $time_fromnow) {
            $date = date('Y-m-d', $time_fromnow);
        }
        break;
    case 2:
        $time_fromnow = strtotime(date('Y-m-d') . ' -1 month +1 day');
        $time_selected = strtotime($_GET['date']);
        if ($time_selected > $time_fromnow) {
            $date = date('Y-m-d', $time_fromnow);
        }
        break;
    case 3:
        $time_fromnow = strtotime(date('Y-m-01') . ' -1 year +1 month');
        $time_selected = strtotime(substr($_GET['date'], 0, 8) . '01');
        if ($time_selected > $time_fromnow) {
            $date = date('Y-m-01', $time_fromnow);
        }
        break;
}

//build chart arrays
$series = array();
foreach($counters as $i => $counter) {
    $series[$i] = array(
        'name' => $counter['name'],
        'data' => array()
    );
}
$options = array('xaxis' => array('categories' => array()));

//decide number of entries in result
$num_entries = 0;
switch ($_GET['type']) {
    case 0:
    case 4:
    case 5:
        $num_entries = 24;
        break;
    case 1:
        $num_entries = 7;
        break;
    case 2:
        $num_entries = round((strtotime($date . ' +1 month') - strtotime($date)) / 86400);
        break;
    case 3:
        $num_entries = 12;
        break;
    case 6:
        $num_entries = 1;
        break;
    default:
        http_response_code(400);
        echo 'invalid type';
        exit;
}

//build chart content arrays
$data = array();
$categories = array();
for ($h = 0; $h < $num_entries; $h++) {
    switch ($_GET['type']) {
        case 0:
        case 4:
        case 5:
            $k = $h;
            break;
        case 1:
            $k = date('Y-m-d', strtotime($date . ' +' . $h . ' day'));
            break;
        case 2:
            $k = date('Y-m-d', strtotime($date . ' +' . $h . ' day'));
            break;
        case 3:
            $k = date('Y-m', strtotime($date . ' +' . $h . ' month'));
            break;
        case 6:
            $k = 'totaal';
            break;
    }
    
    $data[$k] = null;
    $categories[] = $k;
}

$options['xaxis']['categories'] = $categories;
$colors = array();
foreach($counters as $i => $counter) {
    $series[$i]['data'] = $data;
    //set query
    switch ($_GET['type']) {
        case 0:
            $qry = "SELECT HOUR(`datetime`), SUM(`usage`) FROM `" . $db['prefix'] . "usage` 
            WHERE DATE(`datetime`) = '" . $date . "' AND `counter` = " . $i . " 
            GROUP BY HOUR(`datetime`)";
            break;
        case 1:
        case 2:
            $qry = "SELECT DATE(`datetime`), SUM(`usage`) FROM `" . $db['prefix'] . "usage` 
            WHERE DATE(`datetime`) BETWEEN '" . $date . "' AND DATE_ADD('" . $date . "', INTERVAL " . count($categories) . " DAY) AND `counter` = " . $i . " 
            GROUP BY DATE(`datetime`)";
            if (($use_dailytable == TRUE) && ($_GET['type'] == 2)) {
                $qry = "SELECT `date`, `usage` FROM `" . $db['prefix'] . "daily` 
                WHERE `date` BETWEEN '" . $date . "' AND DATE_ADD('" . $date . "', INTERVAL " . count($categories) . " DAY) AND `counter` = " . $i;
            }
            break;
        case 3:
            $qry = "SELECT YEAR(`datetime`), MONTH(`datetime`), SUM(`usage`) FROM `" . $db['prefix'] . "usage` 
            WHERE DATE(`datetime`) BETWEEN '" . $date . "' AND DATE_ADD('" . $date . "', INTERVAL " . count($categories) . " MONTH) AND `counter` = " . $i . " 
            GROUP BY YEAR(`datetime`), MONTH(`datetime`)";
            if ($use_dailytable == TRUE) {
                $qry = "SELECT YEAR(`date`), MONTH(`date`), SUM(`usage`) FROM `" . $db['prefix'] . "daily` 
                WHERE DATE(`date`) BETWEEN '" . $date . "' AND DATE_ADD('" . $date . "', INTERVAL " . count($categories) . " MONTH) AND `counter` = " . $i . " 
                GROUP BY YEAR(`date`), MONTH(`date`)";
            }
            break;
        case 4:
            $qry = "SELECT HOUR(`datetime`), ROUND(AVG(`usage`)*12, 3) FROM `" . $db['prefix'] . "usage` 
            WHERE DATE(`datetime`) BETWEEN '" . $date2 . "' AND '" . $date . "' AND `counter` = " . $i . " 
            GROUP BY HOUR(`datetime`)";
            break;
        case 5:
            $qry = "SELECT `t1`.`hour`, MAX(`t1`.`sum`) FROM (
                SELECT DATE(`datetime`) AS `date`, HOUR(`datetime`) AS `hour`, SUM(`usage`) AS `sum` FROM `" . $db['prefix'] . "usage`
                    WHERE DATE(`datetime`) BETWEEN '" . $date2 . "' AND '" . $date . "' AND `counter` = " . $i . "
                    GROUP BY DATE(`datetime`), HOUR(`datetime`)
                )  AS `t1`
            GROUP BY `hour`";
            break;
        case 6:
            $qry = "SELECT 'totaal', SUM(`usage`) FROM `" . $db['prefix'] . "usage`
            WHERE DATE(`datetime`) BETWEEN '" . $date2 . "' AND '" . $date . "' AND `counter` = " . $i;
            if ($use_dailytable == TRUE) {
                $qry = "SELECT 'totaal', SUM(`usage`) FROM `" . $db['prefix'] . "daily`
                WHERE `date` BETWEEN '" . $date2 . "' AND '" . $date . "' AND `counter` = " . $i;
            }
            break;
    }
    $res = mysqli_query($db['link'], $qry);
    while ($row = mysqli_fetch_row($res)) {
        switch ($_GET['type']) {
            case 0:
            case 1:
            case 2:
            case 4:
            case 5:
            case 6:
                $series[$i]['data'][($row[0])] = $row[1];
                break;
            case 3:
                $series[$i]['data'][($row[0] . '-' . str_pad($row[1], 2, '0', STR_PAD_LEFT))] = $row[2];
                break;
        }
    }
    //remove named keys from series
    $series[$i]['data'] = array_values($series[$i]['data']);
    //add to colours array
    if (array_key_exists('color', $counter) && preg_match('/#[0-9A-F]{6}/i', $counter['color'])) {
        $colors[] = $counter['color'];
    }
}

//only add color array if there is a colour provided for each counter
if (count($counters) == count($colors)) {
    $options['colors'] = $colors;
}

$json = json_encode(array('series' => $series, 'options' => $options));
header('Content-Type:text/json');
echo $json;
exit;
?>
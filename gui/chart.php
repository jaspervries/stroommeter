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
switch ($_GET['type']) {
    case 7:
    case 8:
        //TODO: find first year
        $qry = "SELECT MIN(YEAR(`datetime`)) FROM `" . $db['prefix'] . "usage`";
        if ($use_dailytable == TRUE) {
            $qry = "SELECT MIN(YEAR(`date`)) FROM `" . $db['prefix'] . "daily`";
        }
        $res = mysqli_query($db['link'], $qry);
        $year = mysqli_fetch_row($res);
        $year = $year[0];
        
        for($i = $year; $i <= date('Y'); $i++) {
            $series[$i - $year] = array(
                'name' => (string) $i,
                'data' => array()
            );
        }
        break;
    default:
        foreach($counters as $i => $counter) {
            $series[$i] = array(
                'name' => $counter['name'],
                'data' => array()
            );
        }
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
    case 7:
    case 8:
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
        case 7:
        case 8:
            $k = $h + 1;
            break;
    }
    
    $data[$k] = null;
    $categories[] = $k;
}

//chart contents
//types 0-6
if ($_GET['type'] <= 6) {
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
                //use temptable
                if (($use_temptable == TRUE) && strtotime($date) >= strtotime('-' . $temptable_window . ' day')) {
                    $qry = "SELECT HOUR(`datetime`), SUM(`usage`) FROM `" . $db['prefix'] . "temp` 
                    WHERE DATE(`datetime`) = '" . $date . "' AND `counter` = " . $i . " 
                    GROUP BY HOUR(`datetime`)";
                }
                break;
            case 1:
            case 2:
                $qry = "SELECT DATE(`datetime`), SUM(`usage`) FROM `" . $db['prefix'] . "usage` 
                WHERE DATE(`datetime`) BETWEEN '" . $date . "' AND DATE_ADD('" . $date . "', INTERVAL " . count($categories) . " DAY) AND `counter` = " . $i . " 
                GROUP BY DATE(`datetime`)";
                //use temptable for week view only
                if (($use_temptable == TRUE) && ($_GET['type'] == 1) && strtotime($date) >= strtotime('-' . $temptable_window . ' day')) {
                    $qry = "SELECT DATE(`datetime`), SUM(`usage`) FROM `" . $db['prefix'] . "temp` 
                    WHERE DATE(`datetime`) BETWEEN '" . $date . "' AND DATE_ADD('" . $date . "', INTERVAL " . count($categories) . " DAY) AND `counter` = " . $i . " 
                    GROUP BY DATE(`datetime`)";
                }
                //use dailytable for month view only
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
                $qry = "SELECT HOUR(`datetime`), ROUND(AVG(`usage`*12), 3) FROM `" . $db['prefix'] . "usage` 
                WHERE DATE(`datetime`) BETWEEN '" . $date2 . "' AND '" . $date . "' AND `counter` = " . $i . " 
                GROUP BY HOUR(`datetime`)";
                if ($use_hourlytable == TRUE) {
                    $qry = "SELECT `hour`, ROUND(AVG(`usage`), 3) FROM `" . $db['prefix'] . "hourly` 
                    WHERE `date` BETWEEN '" . $date2 . "' AND '" . $date . "' AND `counter` = " . $i . " 
                    GROUP BY `hour`";
                }
                break;
            case 5:
                $qry = "SELECT `t1`.`hour`, MAX(`t1`.`sum`) FROM (
                    SELECT DATE(`datetime`) AS `date`, HOUR(`datetime`) AS `hour`, SUM(`usage`) AS `sum` FROM `" . $db['prefix'] . "usage`
                        WHERE DATE(`datetime`) BETWEEN '" . $date2 . "' AND '" . $date . "' AND `counter` = " . $i . "
                        GROUP BY DATE(`datetime`), HOUR(`datetime`)
                    )  AS `t1`
                GROUP BY `hour`";
                if ($use_hourlytable == TRUE) {
                    $qry = "SELECT `hour`, MAX(`usage`) FROM `" . $db['prefix'] . "hourly` 
                    WHERE `date` BETWEEN '" . $date2 . "' AND '" . $date . "' AND `counter` = " . $i . " 
                    GROUP BY `hour`";
                }
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
}
//type 7-8
elseif ($_GET['type'] >= 7) {
    $options['xaxis']['categories'] = $categories;
    //set query
    switch ($_GET['type']) {
        case 7:
            $qry = "SELECT YEAR(`datetime`), MONTH(`datetime`), SUM(`usage`) FROM `" . $db['prefix'] . "usage`
            WHERE `counter` = " . (is_numeric($_GET['counter']) ? $_GET['counter'] - 1 : 0) . " AND YEAR(`datetime`) >= " . $year . "
            GROUP BY YEAR(`datetime`), MONTH(`datetime`)";
            if ($use_dailytable == TRUE) {
                $qry = "SELECT YEAR(`date`), MONTH(`date`), SUM(`usage`) FROM `" . $db['prefix'] . "daily`
                WHERE `counter` = " . (is_numeric($_GET['counter']) ? $_GET['counter'] - 1 : 0) . " AND YEAR(`date`) >= " . $year . "
                GROUP BY YEAR(`date`), MONTH(`date`)";
            }
            break;
        case 8:
            /*Example:
            SELECT `t_base`.`year`, `t_base`.`month`, `t_1`.`counter1`, `t_2`.`counter2` FROM
                (SELECT YEAR(`date`) AS `year`, MONTH(`date`) AS `month` FROM `eng_daily`
                WHERE YEAR(`date`) >= 2022
                GROUP BY YEAR(`date`), MONTH(`date`)) AS `t_base`
            LEFT JOIN 
                (SELECT YEAR(`date`) AS `year`, MONTH(`date`) AS `month`, SUM(`usage`) AS `counter1` FROM `eng_daily`
                WHERE `counter` = 1 AND YEAR(`date`) >= 2022
                GROUP BY YEAR(`date`), MONTH(`date`)) AS `t_1`
            ON (`t_base`.`year` = `t_1`.`year` AND `t_base`.`month` = `t_1`.`month`)
            LEFT JOIN 
                (SELECT YEAR(`date`) AS `year`, MONTH(`date`) AS `month`, SUM(`usage`) AS `counter2` FROM `eng_daily`
                WHERE `counter` = 2 AND YEAR(`date`) >= 2022
                GROUP BY YEAR(`date`), MONTH(`date`)) AS `t_2`
            ON (`t_base`.`year` = `t_2`.`year` AND `t_base`.`month` = `t_2`.`month`)
            */
            $qry = "SELECT `t_base`.`year`, `t_base`.`month`, ";
            foreach ($custom_charts[(is_numeric($_GET['counter']) ? $_GET['counter'] : 1)]['counters'] as $counter => $operation) {
                $counter--;
                switch($operation) {
                    case 'subtract':
                    case '-';
                        $operation = '-';
                        break;
                    default:
                        $operation = '+';
                }
                $qry .= $operation . " COALESCE(`t_" . $counter . "`.`counter" . $counter . "`, 0) ";
            }
            $qry .= " FROM
                (SELECT YEAR(`datetime`) AS `year`, MONTH(`datetime`) AS `month` FROM `" . $db['prefix'] . "usage`
                WHERE YEAR(`datetime`) >= " . $year . "
                GROUP BY YEAR(`datetime`), MONTH(`datetime`)) AS `t_base`";
            //for each counters
            foreach ($custom_charts[(is_numeric($_GET['counter']) ? $_GET['counter'] : 1)]['counters'] as $counter => $operation) {
                $counter--;
                $qry .= "LEFT JOIN 
                    (SELECT YEAR(`datetime`) AS `year`, MONTH(`datetime`) AS `month`, SUM(`usage`) AS `counter" . $counter . "` FROM `" . $db['prefix'] . "usage`
                    WHERE `counter` = " . $counter . " AND YEAR(`datetime`) >= " . $year . "
                    GROUP BY YEAR(`datetime`), MONTH(`datetime`)) AS `t_" . $counter . "`
                ON (`t_base`.`year` = `t_" . $counter . "`.`year` AND `t_base`.`month` = `t_" . $counter . "`.`month`)";
            }
            if ($use_dailytable == TRUE) {
                $qry = "SELECT `t_base`.`year`, `t_base`.`month`, ";
                foreach ($custom_charts[(is_numeric($_GET['counter']) ? $_GET['counter'] : 1)]['counters'] as $counter => $operation) {
                    $counter--;
                    switch($operation) {
                        case 'subtract':
                        case '-';
                            $operation = '-';
                            break;
                        default:
                            $operation = '+';
                    }
                    $qry .= $operation . " COALESCE(`t_" . $counter . "`.`counter" . $counter . "`, 0) ";
                }
                $qry .= " FROM
                    (SELECT YEAR(`date`) AS `year`, MONTH(`date`) AS `month` FROM `" . $db['prefix'] . "daily`
                    WHERE YEAR(`date`) >= " . $year . "
                    GROUP BY YEAR(`date`), MONTH(`date`)) AS `t_base`";
                //for each counters
                foreach ($custom_charts[(is_numeric($_GET['counter']) ? $_GET['counter'] : 1)]['counters'] as $counter => $operation) {
                    $counter--;
                    $qry .= "LEFT JOIN 
                        (SELECT YEAR(`date`) AS `year`, MONTH(`date`) AS `month`, SUM(`usage`) AS `counter" . $counter . "` FROM `" . $db['prefix'] . "daily`
                        WHERE `counter` = " . $counter . " AND YEAR(`date`) >= " . $year . "
                        GROUP BY YEAR(`date`), MONTH(`date`)) AS `t_" . $counter . "`
                    ON (`t_base`.`year` = `t_" . $counter . "`.`year` AND `t_base`.`month` = `t_" . $counter . "`.`month`)";
                }
            }
            break;
    }
    $res = mysqli_query($db['link'], $qry);
    while ($row = mysqli_fetch_row($res)) {
        switch ($_GET['type']) {
            case 7:
            case 8:
                if (empty($series[$row[0] - $year]['data'])) {
                    $series[$row[0] - $year]['data'] = $data;
                }
                $series[$row[0] - $year]['data'][$row[1]] = $row[2];
                break;
        }
    }
    //remove named keys from series
    foreach ($series as $i => $serie) {
        $series[$i]['data'] = array_values($series[$i]['data']);
    }
}

$json = json_encode(array('series' => $series, 'options' => $options));
header('Content-Type:text/json');
echo $json;
exit;
?>
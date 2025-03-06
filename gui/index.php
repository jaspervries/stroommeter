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
require('logincheck.inc.php');
include('../config.inc.php')
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stroommeter</title>
    <script src="bundled/jquery/jquery.min.js"></script>
    <script src="bundled/jquery-ui/jquery-ui.min.js"></script>
    <script src="bundled/apexcharts/apexcharts.js"></script>
    <script src="bundled/dayjs/dayjs.min.js"></script>
    <link rel="stylesheet" type="text/css" href="bundled/jquery-ui/jquery-ui.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<form>
<div id="tabs">
    <ul>
        <li><a href="#tabs-0">Dag</a></li>
        <li><a href="#tabs-1">Week</a></li>
        <li><a href="#tabs-2">Maand</a></li>
        <li><a href="#tabs-3">Jaar</a></li>
        <li><a href="#tabs-4">Gem/uur</a></li>
        <li><a href="#tabs-5">Max/uur</a></li>
        <li><a href="#tabs-6">Totaal</a></li>
        <li><a href="#tabs-7">Vergelijk</a></li>
    </ul>
    <div id="tabs-0">
        <button id="day-previous">Vorige dag</button>
        <label for="date-0">Datum: </label><input type="date" id="date-0">
        <button id="day-next">Volgende dag</button>
    </div>
    <div id="tabs-1">
        <button id="week-previous">Vorige week</button>    
        <label for="date-1">Startdatum: </label><input type="date" id="date-1">
        <button id="week-next">Volgende week</button>
    </div>
    <div id="tabs-2">
        <button id="month-previous">Vorige maand</button>    
        <label for="date-2">Startdatum: </label><input type="date" id="date-2">
        <button id="month-next">Volgende maand</button>
    </div>
    <div id="tabs-3">
        <button id="year-previous">Vorig jaar</button>    
        <label for="date-3">Startdatum: </label><input type="number" id="date-3" readonly>
        <button id="year-next">Volgend jaar</button>
    </div>
    <div id="tabs-4">
        <label for="date-4-s">Startdatum: </label><input type="date" id="date-4-s">
        <label for="date-4-e">Einddatum: </label><input type="date" id="date-4-e">
    </div>
    <div id="tabs-5">
        <label for="date-5-s">Startdatum: </label><input type="date" id="date-5-s">
        <label for="date-5-e">Einddatum: </label><input type="date" id="date-5-e">
    </div>
    <div id="tabs-6">
        <label for="date-6-s">Startdatum: </label><input type="date" id="date-6-s">
        <label for="date-6-e">Einddatum: </label><input type="date" id="date-6-e">
    </div>
    <div id="tabs-7">
        <label for="counter-7">Stroommeter: </label><select id="counter-7">
        <?php
        foreach ($counters as $counter) {
            echo '<option value="' . $counter['counter'] . '">' . $counter['name'] . '</option>';
        }
        ?>
        </select>
    </div>
</div>
</form>

<div id="chart"></div>

<script src="stroommeter.js"></script>

</body>
</html>
<?php
/*
 	assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2020 Gemeente Den Haag, Netherlands
    Developed by Jasper Vries
	Modified for:
	stroommeter - toolchain for reading energy meters
	Copyright (C) 2022-2025  Jasper Vries
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

if (!file_exists('../dbconnect.inc.php')) {
	echo '* Geen ../dbconnect.inc.php beschikbaar.' . PHP_EOL;
	
	if (file_exists('../dbconnect.inc.php.example')) {
		if (copy('../dbconnect.inc.php.example', '../dbconnect.inc.php')) {
			echo '* ../dbconnect.inc.php aangemaakt.' . PHP_EOL;
			echo '  Controleer de instellingen in ../dbconnect.inc.php en voer de installatie opnieuw uit' . PHP_EOL;
		}
	}

	echo '* Installatie afgebroken';
	exit;
}

if (!file_exists('../config.inc.php')) {
	echo '* Geen ../config.inc.php beschikbaar.' . PHP_EOL;
	
	if (file_exists('../config.inc.php.example')) {
		if (copy('../config.inc.php.example', '../config.inc.php')) {
			echo '* ../config.inc.php aangemaakt.' . PHP_EOL;
		}
	}
}

@include('../dbconnect.inc.php');

$db['link'] = mysqli_connect($db['server'], $db['username'], $db['password']);

if ($db['link'] === FALSE) {
	echo '* Kan niet verbinden met database' . PHP_EOL;
	echo '  Controleer de instellingen in ../dbconnect.inc.php' . PHP_EOL;
	echo '* Installatie afgebroken';
	exit;
}

$qry = "CREATE DATABASE IF NOT EXISTS `".$db['database']."`
CHARACTER SET 'utf8' 
COLLATE 'utf8_general_ci'";
$res = mysqli_query($db['link'], $qry);

if ($res === FALSE) {
	echo '* Kan database niet aanmaken' . PHP_EOL;
	echo '  Oorzaak: ' . mysqli_error($db['link']) . PHP_EOL;
	echo '* Installatie afgebroken';
	exit;
}
else {
	echo '* Database aangemaakt of al beschikbaar' . PHP_EOL;
}

$db['link'] = mysqli_connect($db['server'], $db['username'], $db['password'], $db['database']);
mysqli_set_charset($db['link'], 'utf8');

$qry = array();

$qry[] = "CREATE TABLE `".$db['prefix']."usage` (
	`datetime` DATETIME NOT NULL DEFAULT NOW(),
	`counter` INT UNSIGNED NOT NULL,
	`usage` DECIMAL(5,4) NOT NULL DEFAULT 0,
	PRIMARY KEY (`datetime`, `counter`)
	)
	ENGINE=MyISAM";

$qry[] = "CREATE TABLE `".$db['prefix']."temp` (
	`datetime` DATETIME NOT NULL DEFAULT NOW(),
	`counter` INT UNSIGNED NOT NULL,
	`usage` DECIMAL(5,4) NOT NULL DEFAULT 0,
	PRIMARY KEY (`datetime`, `counter`)
	)
	ENGINE=MyISAM";

$qry[] = "CREATE TABLE `".$db['prefix']."hourly` (
	`date` DATE NOT NULL,
	`hour` TINYINT UNSIGNED NOT NULL,
	`counter` INT UNSIGNED NOT NULL,
	`usage` DECIMAL(6,4) NOT NULL DEFAULT 0,
	PRIMARY KEY (`date`, `hour`, `counter`)
	)
	ENGINE=MyISAM";

$qry[] = "CREATE TABLE `".$db['prefix']."daily` (
	`date` DATE NOT NULL,
	`counter` INT UNSIGNED NOT NULL,
	`usage` DECIMAL(6,4) NOT NULL DEFAULT 0,
	PRIMARY KEY (`date`, `counter`)
	)
	ENGINE=MyISAM";

foreach($qry as $qry_this) {
	$res = @mysqli_query($db['link'], $qry_this);
	//get table name
	preg_match('/(.*)\h+.+`(.+)`.+/U', $qry_this, $table_name);
	$qry_type = strtoupper($table_name[1]);
	$table_name = $table_name[2];
	//echo result
	if ($res !== TRUE) {
		switch ($qry_type) {
			case 'CREATE':
				echo '* Kan tabel `' . $table_name . '` niet aanmaken.' . PHP_EOL;
				break;
			case 'INSERT':
				echo '* Kan rijen op `' . $table_name . '` niet invoegen.' . PHP_EOL;
				break;
			default:
				echo '* ' . $qry_type .' op `' . $table_name . '` niet uitgevoerd.' . PHP_EOL;
		}
		echo '  Oorzaak: ' . mysqli_error($db['link']) . PHP_EOL;
	}
	else {
		switch ($qry_type) {
			case 'CREATE':
				echo '* Tabel `' . $table_name . '` aangemaakt.' . PHP_EOL;
				break;
			case 'INSERT':
				echo '* Rijen op `' . $table_name . '` ingevoegd.' . PHP_EOL;
				break;
			default:
				echo '* ' . $qry_type .' op `' . $table_name . '` uitgevoerd.' . PHP_EOL;
		}
	}
}

echo '* Done' . PHP_EOL;
?>
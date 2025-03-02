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
$url = 'http://example.com/api/index.php';
$json = '{"key": "MfbFdky9UtrwrYC2BCLZhK5Q", "time": "2000-01-02 03:04:05", "counter1": "0.0350", "counter2": "0.0040"}';
$crl = curl_init($url);
curl_setopt($crl, CURLOPT_POST, true);
curl_setopt($crl, CURLOPT_POSTFIELDS, $json);
curl_setopt($crl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($crl);
echo $result;
?>
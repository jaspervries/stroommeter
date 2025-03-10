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
$result = '';
if (!empty($_POST)) {
    $result = '\'' . htmlspecialchars($_POST['username']) . '\' => \'' . password_hash($_POST['password'], PASSWORD_DEFAULT) . '\'';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stroommeter</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<h1>Gebruikersnaam/wachtwoord coderen</h1>
<form method="POST">
    <label for="username">Gebruikersnaam: </label><input type="text" id="username" name="username"><br>
    <label for="password">Wachtwoord: </label><input type="password" id="password" name="password"><br>
    <input type="submit" value="Coderen">
</form>
<?php
echo $result;
?>

</body>
</html>
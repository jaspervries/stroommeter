<?php
session_start();
include('../config.inc.php');
//check username and password
if (!empty($_SESSION['username']) && !empty($_SESSION['password']) && array_key_exists($_SESSION['username'], $users) && password_verify($_SESSION['password'], $users[$_SESSION['username']])) {

}
else {
    header('Location:http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/login.php');
    exit;
}
?>
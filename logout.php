<?php
session_start();
require_once("config.php");
require_once("class/Auth.php");

Auth::logout();
header("Location: login.php");
die();
?>

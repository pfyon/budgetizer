<?php
require_once("class/Auth.php");

if(!Auth::isAuthenticated())
{
	header("Location: login.php");
	die();
}
?>

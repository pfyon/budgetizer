<?php
$host = 'localhost';
$dbname = 'budgetizer';
$user = 'budget';
$password = 'budgetpassword';

$db_cnx = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
?>

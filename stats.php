<?php
session_start();
require_once("config.php");
require_once("class/Transactions.php");

echo '<html><head>';
require_once("include/head.php");
echo '</head><body>';

require_once("include/menu.php");


$tags = array('food', 'alcohol', 'coffee');

$cnx = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
$first_of_month = (new DateTime("first day of last month"))->format('m/d/Y');
$last_of_month = (new DateTime("last day of last month"))->format('m/d/Y');

$total_credits_last_month = Transactions::getCreditsByDateRange($first_of_month, $last_of_month, $cnx) / 100;
$total_debits_last_month = Transactions::getDebitsByDateRange($first_of_month, $last_of_month, $cnx) / 100;
$total_last_month_food = Transactions::getTotalByTagsAndDateRange($tags, $first_of_month, $last_of_month, $cnx) / 100;
$average_last_year_food = Transactions::getMonthlyAverageOverYearByTags($tags, $cnx) / 100;

echo "<div>";
echo "<div>Total credits last month:</div><div>" . $total_credits_last_month . "</div>";
echo "<div>Total debits last month:</div><div>" . $total_debits_last_month . "</div>";
echo "<div>Total spent on food (TODO: make this any tag) last month:</div><div>" . sprintf('%1.2f', $total_last_month_food) . "</div>";
echo "<div>Average spent on food (TODO: make this any tag) every month over last year:</div><div>" . sprintf('%1.2f', $average_last_year_food) . "</div>";
echo "</div>";


echo '</body></html>';
?>

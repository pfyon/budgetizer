<?php
@session_start();
require_once("config.php");

//This include basically checks that they're logged in and redirects them to login.php if they're not
require_once("include/auth.php");

require_once("class/Transactions.php");
require_once("class/Tags.php");
require_once("class/TagStats.php");

echo '<html><head>';
require_once("include/head.php");
echo 	'<script type="text/javascript">var availableTags = ' . json_encode(Tags::getAll(Auth::currentUserId(), $db_cnx)) . ';</script>
	<script src="include/functions.js" type="text/javascript"></script>
</head><body>';

require_once("include/menu.php");

if(isset($_POST['statistics_submit']))
{
	$tag = trim($_POST['statistics_tag']);
	if($tag != '')
	{
		TagStats::addTag(Auth::currentUserId(), $tag, $db_cnx);
	}
} else if(isset($_POST['budget_submit']))
{
	foreach($_POST['budget'] as $label => $amount)
	{
		$amount = trim($amount) * 100;
		TagStats::updateBudgetByLabel(Auth::currentUserId(), trim($label), $amount, $db_cnx);
	}
}


$tags = TagStats::getTagsByUserId(Auth::currentUserId(), $db_cnx);

echo '
<div class="container">
	<form name="statistics_addtag" method="POST">
		<fieldset>
			<legend>Add Tag to Budget Tracking</legend>
			<div class="form_div">Tag: <input type="text" name="statistics_tag" class="tag_autocomplete" value="" /></div>
			<div class="form_div"><input type="submit" name="statistics_submit" value="Add Tag" /></div>
		</fieldset>
	</form>
</div>';


$first_of_last_month = (new DateTime("first day of last month"))->format('m/d/Y');
$last_of_last_month = (new DateTime("last day of last month"))->format('m/d/Y');

$first_of_this_month = (new DateTime("first day of this month"))->format('m/d/Y');
$last_of_this_month = (new DateTime("last day of this month"))->format('m/d/Y');

echo '<div class="container"><form method="POST"><table class="column_100">
	<tr>
		<th></th>
		<th>Monthly Budget</th>
		<th>This Month</th>
		<th>Last Month</th>
		<th>12 Month Average</th>
</tr>';

$total_this_month = 0;
$total_last_month = 0;
$total_last_year = 0;
$total_budget = 0;

foreach($tags as $tag)
{
	$last_month = Transactions::getTotalByTagsAndDateRange(array($tag), $first_of_last_month, $last_of_last_month, $db_cnx) / 100;
	$this_month = Transactions::getTotalByTagsAndDateRange(array($tag), $first_of_this_month, $last_of_this_month, $db_cnx) / 100;
	$last_year = Transactions::getMonthlyAverageOverYearByTags(array($tag), $db_cnx) / 100;

	$budget = TagStats::getBudgetByLabel(Auth::currentUserId(), $tag, $db_cnx) / 100;

	if($budget == NULL)
	{
		$budget = 0;
	}

	if($this_month > $budget)
	{
		echo '<tr class="red">';
	} else
	{
		echo '<tr>';
	}
	echo '<td>' . $tag . '</td>
		<td><input type="text" name="budget[' . $tag . ']" value="' . number_format($budget, 2, ".", "") . '" /></td>
		<td>' . number_format($this_month, 2, '.', '') . '</td>
		<td>' . number_format($last_month, 2, '.', '') . '</td>
		<td>' . number_format($last_year, 2, '.', '') . '</td>
	</tr>';

	$total_budget += $budget;
	$total_this_month += $this_month;
	$total_last_month += $last_month;
	$total_last_year += $last_year;
}
echo '<tr>
	<td>Total:</td>
	<td>' . number_format($total_budget, 2, '.', '') . '</td>
	<td>' . number_format($total_this_month, 2, '.', '') . '</td>
	<td>' . number_format($total_last_month, 2, '.', '') . '</td>
	<td>' . number_format($total_last_year, 2, '.', '') . '</td>
	</tr>';
echo '</table>';
echo '<input type="submit" name="budget_submit" value="Save Budget" /></form></div>';

echo '</body></html>';
?>

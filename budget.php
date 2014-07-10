<?php
session_start();
require_once("config.php");

//This include basically checks that they're logged in and redirects them to login.php if they're not
require_once("include/auth.php");

require_once("class/Transactions.php");
require_once("class/Tags.php");
require_once("class/TagStats.php");

echo '<html><head>';
require_once("include/head.php");
echo 	'<script type="text/javascript">var availableTags = ' . json_encode(Tags::getAll($db_cnx)) . ';</script>
	<script src="include/functions.js" type="text/javascript"></script>
</head><body>';

require_once("include/menu.php");

if(isset($_POST['statistics_submit']))
{
	$tag = trim($_POST['statistics_tag']);
	if($tag != '')
	{
		TagStats::addTag($_SESSION['auth']['user_id'], $tag, $db_cnx);
	}
}


$tags = TagStats::getTagsByUserId($_SESSION['auth']['user_id'], $db_cnx);

echo '
<div class="container">
	<form name="statistics_addtag" method="POST">
		<fieldset>
			<legend>Add Tag to Statistics Tracking</legend>
			<div class="form_div">Tag: <input type="text" name="statistics_tag" class="tag_autocomplete" value="" /></div>
			<div class="form_div"><input type="submit" name="statistics_submit" value="Add Tag" /></div>
		</fieldset>
	</form>
</div>';


$first_of_month = (new DateTime("first day of last month"))->format('m/d/Y');
$last_of_month = (new DateTime("last day of last month"))->format('m/d/Y');

echo '<div class="container"><table class="column_100">
	<tr>
		<th></th>
		<th>Total Last Month</th>
		<th>Monthly Average</th>
</tr>';
foreach($tags as $tag)
{
	$total_last_month = Transactions::getTotalByTagsAndDateRange(array($tag), $first_of_month, $last_of_month, $db_cnx) / 100;
	$average_last_year = Transactions::getMonthlyAverageOverYearByTags(array($tag), $db_cnx) / 100;
	echo '<tr>
		<td>' . $tag . '</td>
		<td>' . sprintf('%1.2f', $total_last_month) . '</td>
		<td>' . sprintf('%1.2f', $average_last_year) . '</td>
	</tr>';
}

echo '</table></div>';

echo '</body></html>';
?>

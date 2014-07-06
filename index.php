<?php
session_start();
require_once("config.php");

//This include basically checks that they're logged in and redirects them to login.php if they're not
require_once("include/auth.php");

require_once("class/Parser.php");
require_once("class/Transactions.php");
require_once("class/Tags.php");

echo '
<html>
<head>';

require_once("include/head.php");

//We'll create the availableTags javascript array here
echo 	'<script type="text/javascript">var availableTags = ' . json_encode(Tags::getAll($db_cnx)) . ';</script>
	<script src="include/functions.js" type="text/javascript"></script>
</head>';

require_once("include/menu.php");

if($db_cnx)
{
	$date_start = '';
	$date_end = '';

	if(!empty($_POST['submit_upload']))
	{
		//Handle File uploads
		foreach($_FILES['csv_file']['tmp_name'] as $index => $tmpfile)
		{
			$file_error = $_FILES['csv_file']['error'][$index];
			$file_size = $_FILES['csv_file']['size'][$index];
			$file_orig_name = $_FILES['csv_file']['name'][$index];
			$file_tmp_path = $_FILES['csv_file']['tmp_name'][$index];

			if($file_error == 0 && $file_size < 100000)
			{
				//No error and approximately 100k filesize
				echo "Parsing \"" . htmlentities($file_orig_name) . "<br />";

				$parser = new Parser($file_tmp_path, $_POST['accounttype'], $_POST['label'], $db_cnx);
				$parser->parseFile();

				if($parser->errorOccurred())
				{
					echo "At least one error occurred during processing: <br />" . implode('<br />', $parser->getErrors()) . '<br />';
				} else
				{
					echo "No error occurred<br />";
				}
			}
		}
 
	}

	if(!empty($_POST['submit_tags']))
	{
		//Handle a query by tags
		$tag_str = trim($_POST['filter_tags']);
		$date_start = trim($_POST['date_start']);
		$date_end = trim($_POST['date_end']);

		if($tag_str != '')
		{
			
			if($date_start != '' || $date_end != '')
			{
				$transactions = Transactions::getByTagsAndDateRange(explode(" ", $tag_str), $date_start, $date_end, $db_cnx);
			} else
			{
				$transactions = Transactions::getByTags(explode(" ", $tag_str), $db_cnx);
			}
		} else
		{
			if($date_start != '' || $date_end != '')
			{
				$transactions = Transactions::getByDateRange($date_start, $date_end, $db_cnx);
			} else
			{
				$transactions = Transactions::getAll($db_cnx);
			}
		}
	} else if(!empty($_POST['show_all']))
	{
		$transactions = Transactions::getAll($db_cnx);
	} else if(!empty($_POST['submit_search']))
	{
		$search_str = trim($_POST['search_description']);
		if($search_str != '')
		{
			$transactions = Transactions::getByDescription($search_str, $db_cnx);
		} else
		{
			$transactions = Transactions::getAll($db_cnx);
		}
	} else
	{
		//Our default case, query the untagged stuff 
		$transactions = Transactions::getUntagged($db_cnx);
	}

	echo '
<div id="form_menu_div" class="container">
	<form method="POST" name="filter_by_tags" class="column_66">
		<fieldset class="form_menu">
			<legend>Filter Results</legend>
			Start Date: <input type="text" name="date_start" id="date_start" value="' . $date_start . '"/>
			End Date: <input type="text" name="date_end" id="date_end" value="' . $date_end . '"/><br />
			Tags: <input type="text" name="filter_tags" value="" class="filter_tags" id="filter_tags" /><br />
			<br />
			<input type="submit" name="submit_tags" value="Perform Query" />
			<input type="submit" name="show_all" value="Show All Transactions" id="tags_show_all" />
			<input type="submit" name="show_untagged" value="Show Untagged Transactions" id="tags_show_untagged" />
			<input type="reset" value="Reset Form" id="tags_reset" />
			
		</fieldset>
	</form>
	<form method="POST" name="search_by_description" class="column_34">
		<fieldset class="form_menu">
			<legend>Search</legend>
			Description: <input type="text" name="search_description" value="" class="search_description" /><br />
			<br />
			<input type="submit" name="submit_search" value="Search" />
		</fieldset>
	</form>
</div>';

	if(!empty($_POST['submit_search']) || !empty($_POST['submit_tags']))
	{
		//We only show this if they've done a search because they probably don't want to apply this to everything
		echo '
	<form name="bulk_tag" method="POST">
		<fieldset>
			<legend>Bulk Tag</legend>
			Tag: <input type="text" name="bulk_tags" value="" class="filter_tags" id="input_bulk_tag" /><br />
			<br />
			<input type="submit" name="submit_bulk_tag" value="Tag These Transactions" id="submit_bulk_tag" />
		</fieldset>
	</form>';
	}

	$total_debit = 0;
	$total_credit = 0;

	echo '<div class="container">
		<table class="column_100">
		<tr>
			<th>Date</th>
			<th>Amount</th>
			<th>Description</th>
			<th>Account</th>
			<th>Tags</th>
			<th>Add Tag</th>
		</tr>';

	foreach($transactions as $row)
	{
		echo '<tr id="' . $row['id'] . '">
			<td class="transaction_date">' . date('Y-m-d', $row['date']) . '</td>
			<td class="transaction_amt">' . sprintf('%1.2f', $row['amount'] / 100) . '</td>
			<td class="transaction_desc">' . $row['description'] . '</td>
			<td class="transaction_acct">' . $row['account'] . '</td>
			<td class="transaction_taglist">';

		foreach($row['taglist'] as $tag)
		{
			if($tag != '')
			{
				echo '<div class="tag">' . $tag . '</div>';
			}
		}
 		echo 	'</td>
			<td><input type="text" class="transaction_addtag"></input></td>
		</tr>';

		if($row['amount'] > 0)
		{
			$total_credit += $row['amount'];
		} else if($row['amount'] < 0)
		{
			$total_debit += $row['amount'];
		}
	}

	echo '<tr>
		<td>TOTAL DEBITS:</td>
		<td>' . sprintf('%1.2f', $total_debit / 100) . '</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>
	<tr>
		<td>TOTAL CREDITS:</td>
		<td>' . sprintf('%1.2f', $total_credit / 100) . '</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>
	<tr>
		<td>NET TOTAL:</td>
		<td>' . sprintf('%1.2f', ($total_debit + $total_credit) / 100) . '</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>';

	echo '</table>
</div>';
}
echo '</body></html>';
?>

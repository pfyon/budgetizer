<?php
session_start();
require_once("config.php");
require_once("class/Parser.php");
require_once("class/Transactions.php");
require_once("class/Tags.php");

$cnx = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");

echo '
<html>
<head>
	<link rel="stylesheet" type="text/css" href="style.css" />
	<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/redmond/jquery-ui.css" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js" type="text/javascript"></script>';

//We'll create the availableTags javascript array here
echo 	'<script type="text/javascript">var availableTags = ' . json_encode(Tags::getAll($cnx)) . ';</script>
	<script src="include/functions.js" type="text/javascript"></script>
</head>';

if($cnx)
{
	$accounttypes = pg_query($cnx, "SELECT * FROM accounttype ORDER BY description, id");

	$date_start = '';
	$date_end = '';

	if(!empty($_POST['submit_upload']))
	{
		//Handle File uploads
		if($_FILES['csv_file']['error'][0] == 0 && $_FILES['csv_file']['size'][0] < 100000)
		{
			//No error and approximately 100k filesize
			$file = @fopen($_FILES['csv_file']['tmp_name'], 'r');
			$parser = new Parser($_FILES['csv_file']['tmp_name'], $_POST['accounttype'], $_POST['label'], $cnx);
			$parser->parseFile();

			if($parser->errorOccurred())
			{
				echo "At least one error occurred during processing: <br />" . implode('<br />', $parser->getErrors());
			} else
			{
				echo "No error occurred";
			}
		} else
		{
			echo "Error uploading File<br />";
		}
	}

	//TODO: handle some way to show untagged traffic
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
				$transactions = Transactions::getByTagsAndDateRange(explode(" ", $tag_str), $date_start, $date_end, $cnx);
			} else
			{
				$transactions = Transactions::getByTags(explode(" ", $tag_str), $cnx);
			}
		} else
		{
			if($date_start != '' || $date_end != '')
			{
				$transactions = Transactions::getByDateRange($date_start, $date_end, $cnx);
			} else
			{
				$transactions = Transactions::getAll($cnx);
			}
		}
	} else if(!empty($_POST['show_all']))
	{
		$transactions = Transactions::getAll($cnx);
	} else
	{
		//Our default case, query the untagged stuff 
		$transactions = Transactions::getUntagged($cnx);
	}

	if($accounttypes !== false)
	{
		echo '
		<form method="POST" name="file_upload" enctype="multipart/form-data">
			<fieldset>
				<legend>Upload a File</legend>
				Select File: <input type="file" name="csv_file" /><br />
				Account Type: <select name="accounttype">
					<option value="default">Choose Account Type</option>';
		while(($row = pg_fetch_assoc($accounttypes)) !== false)
		{
			echo '		<option value="' . $row['code'] . '">' . $row['description'] . '</option>';
		}

		echo '		</select>
				Account Label: <input type="text" name="label" />
				<input type="submit" name="submit_upload" value="Upload File for Processing">
			</fieldset>
		</form>';
	}

	echo '
	<form method="POST" name="filter_by_tags">
		<fieldset>
			<legend>Filter Results</legend>
			Start Date: <input type="text" name="date_start" id="date_start" value="' . $date_start . '"/>
			End Date: <input type="text" name="date_end" id="date_end" value="' . $date_end . '"/><br />
			Tags: <input type="text" name="filter_tags" value="" class="filter_tags" />
			<br />
			<input type="submit" name="submit_tags" value="Perform Query" />
			<input type="submit" name="show_all" value="Show All Transactions" id="tags_show_all" />
			<input type="reset" value="Reset Form" id="tags_reset" />
			
		</fieldset>
	</form>';

	$total_debit = 0;
	$total_credit = 0;

	echo '<table>
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

	echo '</table>';
}
?>
</html>

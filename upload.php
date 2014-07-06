<?php
session_start();
require_once("config.php");

//This include basically checks that they're logged in and redirects them to login.php if they're not
require_once("include/auth.php");

echo '
<html>
<head>';

require_once("include/head.php");

echo '</head><body>';
require_once("include/menu.php");

if($db_cnx)
{
	$accounttypes = pg_query($db_cnx, "SELECT * FROM accounttype ORDER BY description, id");
	
	if($accounttypes !== false)
	{
		echo '
		<div id="form_menu_div" class="container">
		<form method="POST" name="file_upload" enctype="multipart/form-data" action="index.php">
			<fieldset>
				<legend>Upload a File</legend>
				Select File: <input type="file" name="csv_file[]" multiple /><br />
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
		</form>
		</div>';
	}
}
echo '</body></html>';
?>

<?php
session_start();
require_once("../config.php");
require_once("../class/Auth.php");
if(!Auth::isAuthenticated())
{
	die();
}

require_once("../class/Transactions.php");

$tags = array();
if(empty($_POST))
{
	//TODO: optional transaction ID to retrieve just the tags
	//This is a GET request, we're just replying with all the unique tags
	//TODO: in the future, restrict tags by the 'owner' column
	$result = pg_query($db_cnx, "SELECT label FROM tag_labels ORDER BY label");

	while(($row = pg_fetch_assoc($result)) !== false)
	{
		$tags[] = $row['label'];
	}
} else
{
	switch($_POST['action'])
	{
		case "add":
			$tag_line = strtolower(trim($_POST['tagname']));
			$transaction_id = trim($_POST['transaction_id']);
			$tagid = null;

			if($tag_line != '')
			{
				if(count(explode(" ", $tag_line)) > 1)
				{
					$tag_line = explode(" ", $tag_line);
				} else
				{
					//It's only one word, put it into an array to make this easier
					$tag_line = array($tag_line);
				}

				foreach($tag_line as $tagname)
				{
					$result = pg_query($db_cnx, "SELECT id FROM tag_labels WHERE label = '" . pg_escape_string($tagname) . "'");
					if(pg_num_rows($result) < 1)
					{
						//Gotta insert the tag so we get an ID first
						//TODO: using 1 as the owner for now
						$result = pg_query($db_cnx, "INSERT INTO tag_labels (label, owner) VALUES ('" . pg_escape_string($tagname) . "', " . $_SESSION['auth']['user_id'] . ") RETURNING id");
						if(pg_num_rows($result) == 1)
						{
							$row = pg_fetch_assoc($result, 0);
							$tagid = $row['id'];
						}
					} else
					{
						//Get first row
						$row = pg_fetch_assoc($result, 0);
		
						$tagid = $row['id'];
					}
		
					if($tagid !== null)
					{
						pg_query($db_cnx, "INSERT INTO tag_list (tag, transaction_id) values (" . $tagid . ", " . pg_escape_string($transaction_id) . ")");
					}
				}
			}

			//And finally, we return the tags that are on the transaction
			$result = pg_query($db_cnx, "SELECT l.label FROM tag_labels l JOIN tag_list t ON l.id = t.tag AND t.transaction_id = " . pg_escape_string($transaction_id) . " ORDER BY l.label");
			while(($row = pg_fetch_assoc($result)) !== false)
			{
				$tags[] = $row['label'];
			}
			break;
		case "remove":
			$tag = trim($_POST['tagname']);
			$transaction_id = trim($_POST['transaction_id']);

			Transactions::removeTag($tag, $transaction_id, $db_cnx);

			//Check to see if anything has this tag on it anymore
			$result = pg_query($db_cnx, "SELECT COUNT(i.id) FROM tag_list i JOIN tag_labels l ON i.tag = l.id WHERE l.label = '" . pg_escape_string($tag) . "'");
			if($result)
			{
				$row = pg_fetch_assoc($result, 0);
				if($row['count'] == '0')
				{
					//Nothing has this tag on it anymore, we can remove it from the tag list
					pg_query($db_cnx, "DELETE FROM tag_labels WHERE label = '" . pg_escape_string($tag) . "'");

					//We'll return the deleted tag in case the client wants to remove it from its list (not implemented)
					$tags = array($tag);
				}
			}
			break;
		default:
			break;

	}
}
echo json_encode($tags);
?>

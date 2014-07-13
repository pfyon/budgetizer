<?php
@session_start();
require_once("../config.php");
require_once("../class/Auth.php");
if(!Auth::isAuthenticated())
{
	die();
}

require_once("../class/Transactions.php");
require_once("../class/TagList.php");
require_once("../class/TagStats.php");
require_once("../class/Tags.php");

$tags = array();
if(empty($_POST))
{
	//TODO: optional transaction ID to retrieve just the tags
	//This is a GET request, we're just replying with all the unique tags
	$result = pg_query($db_cnx, "SELECT label FROM tag_labels WHERE owner = " . Auth::currentUserId() . " ORDER BY label");

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

					$tagid = Tags::getIdByLabel(Auth::currentUserId(), $tagname, $db_cnx);
					if($tagid === false)
					{
						//Gotta insert the tag so we get an ID first
						$tagid = Tags::addLabel(Auth::currentUserId(), $tagname, $db_cnx);
					}
		
					if($tagid !== false)
					{
						TagList::addTag($tagid, $transaction_id, $db_cnx);
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
			if(TagList::getNumTransactionsByTag(Auth::currentUserId(), $tag, $db_cnx) === '0' && TagStats::getNumBudgetsByTag(Auth::currentUserId(), $tag, $db_cnx) === '0')
			{
				//Nothing has this tag on it anymore, we can remove it from the tag list
				Tags::removeLabel(Auth::currentUserId(), $tag, $db_cnx);
				//We'll return the deleted tag in case the client wants to remove it from its list (not implemented)
				$tags = array($tag);
			}
			break;
		default:
			break;

	}
}
echo json_encode($tags);
?>

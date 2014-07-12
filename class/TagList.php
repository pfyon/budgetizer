<?php
class TagList
{
	public static function addTag($tagid, $transaction_id, $cnx)
	{
		//We don't enforce the owner here since it gets enforced on the transaction and labels
		if(pg_query($cnx, "INSERT INTO tag_list (tag, transaction_id) values (" . $tagid . ", " . pg_escape_string($transaction_id) . ")"))
		{
			return true;
		}
		return false;
	}

	public static function getNumTransactionsByTag($owner, $tag, $cnx)
	{
		$result = pg_query($cnx, "SELECT COUNT(i.id) FROM tag_list i JOIN tag_labels l ON i.tag = l.id WHERE l.label = '" . pg_escape_string($tag) . "' AND l.owner = " . $owner);
		
		if($result)
		{
			$row = pg_fetch_assoc($result, 0);
			return $row['count'];
		}

		return false;
	}
}
?>

<?php
require_once("Tags.php");

class TagStats
{
	public static function addTag($user_id, $label, $cnx)
	{
		$tagid = Tags::getIdByLabel($user_id, $label, $cnx);

		if(!$tagid)
		{
			//Tag doesn't exist, we need to add it
			$tagid = Tags::addLabel($user_id, $label, $cnx);
		}

		if($tagid)
		{
			$result = pg_query($cnx, "INSERT INTO tag_stats (user_id, tag_label_id) VALUES (" . pg_escape_string($user_id) . ", " . pg_escape_string($tagid) . ")");
			if($result)
			{
				return true;
			}
		}

		return false;
	}

	public static function getTagsByUserId($user_id, $cnx)
	{
		$result = pg_query($cnx, "SELECT l.label FROM tag_labels l JOIN tag_stats t ON l.id = t.tag_label_id WHERE t.user_id = " . pg_escape_string($user_id) . " AND l.owner = " . pg_escape_string($user_id));

		$tags = array();
		if($result)
		{
			while(($row = pg_fetch_assoc($result)) !== false)
			{
				$tags[] = $row['label'];
			}
		}

		return $tags;
	}

	public static function getBudgetByLabel($user_id, $label, $cnx)
	{
		$tagid = Tags::getIdByLabel($user_id, $label, $cnx);
		$result = pg_query($cnx, "SELECT budget FROM tag_stats WHERE user_id = " . $user_id . " AND tag_label_id = " . $tagid . " LIMIT 1");

		if($result)
		{
			$row = pg_fetch_assoc($result, 0);
			return $row['budget'];
		}
		return NULL;
	}

	public static function updateBudgetByLabel($user_id, $label, $budget, $cnx)
	{
		$tagid = Tags::getIdByLabel($user_id, $label, $cnx);

		if($tagid)
		{
			$result = pg_query($cnx, "UPDATE tag_stats SET budget = " . $budget . " WHERE tag_label_id = " . $tagid . " AND user_id = " . $user_id);
			if($result)
			{
				return true;
			}
		}
		return false;
	}

	public static function getNumBudgetsByTag($user_id, $tag, $cnx)
	{
		$result = pg_query($cnx, "SELECT COUNT(s.id) FROM tag_stats s JOIN tag_labels l ON s.tag_label_id = l.id WHERE l.label = '" . pg_escape_string($tag) . "' AND l.owner = " . $user_id . " AND s.user_id = " . $user_id);
		
		if($result)
		{
			$row = pg_fetch_assoc($result, 0);
			return $row['count'];
		}

		return false;
	}
}
?>

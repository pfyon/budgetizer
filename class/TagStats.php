<?php
require_once("class/Tags.php");

class TagStats
{
	public static function addTag($user_id, $label, $cnx)
	{
		$tagid = Tags::getTagIdByLabel($user_id, $label, $cnx);

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
}
?>

<?php
class Tags
{
	public static function getAll($user_id, $cnx)
	{
		$tags = array();
		if($cnx)
		{
			$result = pg_query($cnx, "SELECT label FROM tag_labels WHERE owner = " . $user_id . " ORDER BY label");

			if($result !== false)
			{
				while(($row = pg_fetch_assoc($result)) !== false)
				{
					$tags[] = $row['label'];
				}
			}
		}
		return $tags;
	}

	public static function addLabel($user_id, $label, $cnx)
	{
		$result = pg_query($cnx, "INSERT INTO tag_labels (label, owner) VALUES ('" . pg_escape_string($label) . "', " . $user_id . ") RETURNING id");
		if(pg_num_rows($result) == 1)
		{
			$row = pg_fetch_assoc($result, 0);
			return $row['id'];
		}

		return false;
	}

	public static function getIdByLabel($user_id, $label, $cnx)
	{
		$result = pg_query($cnx, "SELECT id FROM tag_labels WHERE label = '" . pg_escape_string($label) . "' AND owner = " . $user_id . " LIMIT 1");

		if(pg_num_rows($result) == 1)
		{
			$row = pg_fetch_assoc($result, 0);

			return $row['id'];
		}

		//Label doesn't exist
		return false;
	}

	public static function removeLabel($owner, $label, $cnx)
	{
		$result = pg_query($cnx, "DELETE FROM tag_labels WHERE label = '" . pg_escape_string($label) . "' AND owner = " . $owner);

		if($result)
		{
			return true;
		}
		return false;
	}
}
?>

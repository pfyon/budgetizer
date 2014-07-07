<?php
class Tags
{
	public static function getAll($cnx)
	{
		$tags = array();
		if($cnx)
		{
			$result = pg_query($cnx, "SELECT label FROM tag_labels ORDER BY label");

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

	public static function getTagIdByLabel($user_id, $label, $cnx)
	{
		$result = pg_query($cnx, "SELECT id FROM tag_labels WHERE owner = " . pg_escape_string($user_id) . " AND label = '" . pg_escape_string($label) . "' LIMIT 1");
		if($result && pg_num_rows($result) > 0)
		{
			$row = pg_fetch_assoc($result, 0);
			return $row['id'];
		}

		return false;
	}
}
?>

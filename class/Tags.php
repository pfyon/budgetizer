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
}
?>

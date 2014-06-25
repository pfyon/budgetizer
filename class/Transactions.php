<?php
class Transactions
{
	public static function removeTag($tag, $id, $cnx)
	{
		$result = pg_query($cnx, "DELETE FROM tag_list i USING tag_labels l WHERE i.tag = l.id AND l.label = '" . pg_escape_string($tag) . "' AND i.transaction_id = " . pg_escape_string($id));

		if($result) return true;
		return false;
	}

	public static function getUntagged($cnx)
	{
		if($cnx)
		{
			$rows = array();

			$result = pg_query($cnx, "SELECT t.*, '' as label FROM transactions t LEFT JOIN tag_list i ON t.id = i.transaction_id WHERE i.transaction_id IS NULL");
			if($result)
			{
				return self::preProcessRows($result);
			}
		}
		return false;
	}

	public static function getByTags($tags, $cnx)
	{
		if($cnx)
		{
			$rows = array();

			foreach($tags as $index => $value)
			{
				//Sanitize the tag
				$tags[$index] = pg_escape_string($value);
			}

			//Let's do this all in one query, shall we?
			$result = pg_query($cnx, "SELECT t.*, l.label FROM transactions t LEFT JOIN tag_list i ON t.id = i.transaction_id LEFT JOIN tag_labels l ON i.tag = l.id WHERE t.id IN (SELECT distinct(t.id) FROM transactions t JOIN tag_list i ON t.id = i.transaction_id JOIN tag_labels l ON i.tag = l.id WHERE l.label IN ('" . implode("','", $tags) . "'))");
			if($result)
			{
				return self::preProcessRows($result);
			}
		}
		return false;
		
	}

	public static function getByTagsAndDateRange($tags, $date_start, $date_end, $cnx)
	{
		if($cnx)
		{
			$rows = array();

			foreach($tags as $index => $value)
			{
				//Sanitize the tag
				$tags[$index] = pg_escape_string($value);
			}

			$query = "SELECT t.*, l.label FROM transactions t LEFT JOIN tag_list i ON t.id = i.transaction_id LEFT JOIN tag_labels l ON i.tag = l.id WHERE t.id IN (SELECT distinct(t.id) FROM transactions t JOIN tag_list i ON t.id = i.transaction_id JOIN tag_labels l ON i.tag = l.id WHERE l.label IN ('" . implode("','", $tags) . "'))";

			//Dates comes in (by default) looking like MM/DD/YYYY
			if($date_start != '')
			{
				$query .= " AND t.date >= " . mktime(0, 0, 0, substr($date_start, 0, 2), substr($date_start, 3, 2), substr($date_start, 6, 4));
			}

			if($date_end != '')
			{
				$query .= " AND t.date <= " . mktime(0, 0, 0, substr($date_end, 0, 2), substr($date_end, 3, 2), substr($date_end, 6, 4));
			}

			$result = pg_query($cnx, $query);

			if($result)
			{
				return self::preProcessRows($result);
			}
		}
		return false;
		
	}

	public static function getByDateRange($date_start, $date_end, $cnx)
	{
		if($cnx)
		{
			$query = "SELECT t.*, l.label FROM transactions t LEFT JOIN tag_list i ON t.id = i.transaction_id LEFT JOIN tag_labels l ON i.tag = l.id";
		
			$where_parts = array();

			//Dates comes in (by default) looking like MM/DD/YYYY
			if($date_start != '')
			{
				$where_parts[] = "t.date >= " . mktime(0, 0, 0, substr($date_start, 0, 2), substr($date_start, 3, 2), substr($date_start, 6, 4));
			}

			if($date_end != '')
			{
				$where_parts[] = "t.date <= " . mktime(0, 0, 0, substr($date_end, 0, 2), substr($date_end, 3, 2), substr($date_end, 6, 4));
			}

			if(!empty($where_parts))
			{
				//This handles the case where both date_start and date_end are blank strings (failing gracefully and all)
				$query .= ' WHERE ' . implode(' AND ', $where_parts);
			}

			$result = pg_query($cnx, $query);
			if($result)
			{
				return self::preProcessRows($result);
			}
		}
		return false;
	}

	public static function getAll($cnx)
	{
		if($cnx)
		{
			$result = pg_query($cnx, "SELECT t.*, l.label FROM transactions t LEFT JOIN tag_list i ON t.id = i.transaction_id LEFT JOIN tag_labels l ON i.tag = l.id");
			if($result)
			{
				return self::preProcessRows($result);
			}
		}
		return false;
	}

	protected static function preProcessRows($result)
	{
		$transactions = array();

		while(($row = pg_fetch_assoc($result)) !== false)
		{
			if(array_key_exists($row['id'], $transactions))
			{
				//We already have an entry for this row, append the tag to the list
				$transactions[$row['id']]['taglist'][] = $row['label'];
			} else
			{
				//First time seeing this row
				$transactions[$row['id']] = $row;
				$transactions[$row['id']]['taglist'] = array($row['label']);
			}
		}

		//Sort with our custom sorting function
		usort($transactions, array("Transactions", "sortTransactionsByDate"));
		return $transactions;
	}

	//TODO: optional param for asc/desc
	protected static function sortTransactionsByDate($a, $b)
	{
		if($a['date'] > $b['date'])
		{
			return -1;
		} else if($a['date'] < $b['date'])
		{
			return 1;
		}

		return 0;
	}
}
?>

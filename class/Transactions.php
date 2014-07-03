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

	public static function getCreditsByDateRange($date_start, $date_end, $cnx)
	{
		$transactions = self::getByDateRange($date_start, $date_end, $cnx);

		$credits = 0;		
		if($transactions !== false)
		{
			foreach($transactions as $row)
			{
				if($row['amount'] > 0)
				{
					$credits += $row['amount'];
				}
			}

			return $credits;
		}

		return 0;
	}

	public static function getDebitsByDateRange($date_start, $date_end, $cnx)
	{
		$transactions = self::getByDateRange($date_start, $date_end, $cnx);

		$debits = 0;		
		if($transactions !== false)
		{
			foreach($transactions as $row)
			{
				if($row['amount'] < 0)
				{
					$debits += $row['amount'];
				}
			}

			return $debits;
		}

		return 0;
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

	public static function getByDescription($desc, $cnx)
	{
		if($cnx)
		{
			//This takes a search for a string like "this is a test string", escapes each word, and implodes it into "this%is%a%test%string"
			//This fixes an issue where some transactions could have words separated by multiple whitespace which wouldn't come up in a search normally
			$fields = explode(" ", $desc);
			foreach($fields as $index => $field)
			{
				$fields[$index] = pg_escape_string($field);
			}

			$result = pg_query($cnx, "SELECT t.*, l.label FROM transactions t LEFT JOIN tag_list i ON t.id = i.transaction_id LEFT JOIN tag_labels l ON i.tag = l.id WHERE t.description ILIKE '%" . implode("%", $fields) . "%'");
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

	public static function getTotalByTagsAndDateRange($tags = null, $date_start, $date_end, $cnx)
	{
		if($tags === null)
		{
			$transactions = self::getByDateRange($date_start, $date_end, $cnx);
		} else
		{
			$transactions = self::getByTagsAndDateRange($tags, $date_start, $date_end, $cnx);
		}

		$credit_debit = 0;
		foreach($transactions as $row)
		{
			$credit_debit += $row['amount'];	
		}

		return $credit_debit;
	}

	public static function getMonthlyAverageOverYearByTags($tags, $cnx)
	{
		$date_start = (new DateTime('first day of last month last year'))->format('m/d/Y');
		$date_end = (new DateTime('last day of last month'))->format('m/d/Y');

		if($tags === null)
		{
			$transactions = self::getByDateRange($date_start, $date_end, $cnx);
		} else
		{
			$transactions = self::getByTagsAndDateRange($tags, $date_start, $date_end, $cnx);
		}

		$credit_debit = 0;

		foreach($transactions as $row)
		{
			$credit_debit += $row['amount'];
		}

		$average = $credit_debit / 12;

		return $average;
	}
}
?>

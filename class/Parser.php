<?php
//TODO: stupid include paths, figure this out
//TODO: using subclasses, make parsers separate classes
//require_once("../config.php");
class Parser
{
	protected $_owner = null;
	protected $_filename = null;
	protected $_type = null;
	protected $_label = null;
	protected $_cnx = null;

	protected $_errors = array();
	protected $_error = false;

	public function __construct($owner, $filename, $type, $label, $cnx = null)
	{
		$this->_owner = $owner;
		$this->_filename = $filename;
		$this->_type = $type;
		$this->_label = $label;

		if($cnx === null)
		{
			$this->_cnx = pg_connect("host='" . $host . "' dbname='" . $dbname . "' user='" . $user . "' password='" . $password . "'");
		} else
		{
			$this->_cnx = $cnx;
		}
	}

	public function getErrors()
	{
		return $this->_errors;
	}

	public function getOwner()
	{
		return $this->_owner;
	}

	public function errorOccurred()
	{
		return $this->_error;
	}

	protected function error($err)
	{
		$this->_errors[] = $err;
		if(!$this->_error)
		{
			$this->_error = true;
		}
	}
	public function parseFile()
	{
		$this->_error = false;
		$this->_errors = array();

		$types = pg_query($this->_cnx, "SELECT * FROM accounttype WHERE code = '" . pg_escape_string($this->_type) . "'");
		if(pg_num_rows($types) < 1)
		{
			//The type does not exist in our database
			$this->error("Unsupported type");
		} else
		{
			$file = @fopen($this->_filename, 'r');
	
			if($file)
			{
				$line = fgetcsv($file);
				while($line !== false && $line !== null)
				{
					switch($this->_type)
					{
						case 'bmo':
							$this->handleBmo($line);
							break;
						case 'bmo_mastercard':
							$this->handleBmoMc($line);
							break;
						case 'td':
							$this->handleTd($line);
							break;
						case 'td_visa':
							//TODO: get this working
		//					$this->handleTdVisa($cnx, $line, $label);
							break;
						default:
							break;
					}
					$line = fgetcsv($file);
				}
				
				fclose($file);
			} else
			{
				$this->error("Unable to open file");
			}
		}
	}

	protected function handleBmo($line_arr)
	{
		if(count($line_arr) == 5 && preg_match("/^'[0-9]+'$/", $line_arr[0]))
		{
			$trantype = trim($line_arr[1], " []");
			$date = trim($line_arr[2]);
	
			//Amount comes in like a money amount, eg $8 looks like 8.0, 4 cents 0.04
			$amount = trim($line_arr[3]) * 100;
			$desc = trim($line_arr[4]);
	
			//linehash is a sha1 hash of the entire line to avoid duplicate values
			$linehash = sha1(implode(',', $line_arr));
	
			//Convert a date in the form of YYYYMMDD to unix timestamp
			$timestamp = mktime(0,0,0, substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4));
	
			//Found a valid line
			//TODO: move this into a static Transactions function
			$result = pg_query($this->_cnx, "INSERT INTO transactions (date, amount, description, hash, trantype, account, source, owner) values(" 
				. $timestamp . ","
				. pg_escape_string($amount) . ",'"
				. pg_escape_string($desc) . "','"
				. $linehash . "','"
				. pg_escape_string($trantype) . "','"
				. pg_escape_string($this->_label) . "','bmo'," . $this->getOwner() . ")"
			);
	
			if($result)
			{
				return true;
			} else
			{
				$this->error(pg_last_error());
				return false;
			}
		} else
		{
			$this->error("Invalid line length (length " . count($line_arr) . ", expected 5) OR invalid card number: " . implode(',', $line_arr));
			return false;
		}
		$this->error("Unknown error: " . implode(',', $line_arr));
		return false;
	}
	
	protected function handleBmoMc($line_arr)
	{
		if(count($line_arr) == 6 && preg_match("/^'[0-9]+'$/", $line_arr[1]))
		{
			$line_item_number = trim($line_arr[0]);
			$trans_date = trim($line_arr[2]);
			$posting_date = trim($line_arr[3]); //We'll only use trans_date for now
	
			//On the credit cards, debits look like positive values and credits negative values. For consistency, we'll invert the symbol so all debits are negative and all credits are positive
			$amount = trim($line_arr[4]) * 100 * -1;
			if($amount < 0)
			{
				$trantype = 'DEBIT';
			} else
			{
				$trantype = 'CREDIT';
			}
	
			$desc = trim($line_arr[5]);
	
			//linehash is a sha1 hash of the entire line to avoid duplicate values
			$linehash = sha1(implode(',', $line_arr));
	
			//Convert a date in the form of YYYYMMDD to unix timestamp
			$timestamp = mktime(0,0,0, substr($trans_date, 4, 2), substr($trans_date, 6, 2), substr($trans_date, 0, 4));
	
			$result = pg_query($this->_cnx, "INSERT INTO transactions (date, amount, description, hash, trantype, account, source, owner) values(" 
				. $timestamp . ","
				. pg_escape_string($amount) . ",'"
				. pg_escape_string($desc) . "','"
				. $linehash . "','"
				. $trantype . "','"
				. pg_escape_string($this->_label) . "','bmo_mastercard'," . $this->getOwner() . ")"
			);
	
			if($result)
			{
				return true;
			} else
			{
				$this->error(pg_last_error());
				return false;
			}
		} else
		{
			$this->error("Invalid line length (length " . count($line_arr) . ", expected 5) OR invalid card number: " . implode(',', $line_arr));
			return false;
		}
		$this->error("Unknown error: " . implode(',', $line_arr));
		return false;
	}
	
	protected function handleTd($line_arr)
	{
		if(count($line_arr) == 5 && preg_match("/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/", $line_arr[0]))
		{
			$date = trim($line_arr[0]);
			$desc = trim($line_arr[1]);
			$debit_amt = trim($line_arr[2]);
			$credit_amt = trim($line_arr[3]);
			$final_bal = trim($line_arr[4]); // We don't use this
	
			if(strlen($debit_amt) == 0)
			{
				//This was a credit
				$amount = $credit_amt * 100;
				$trantype = 'CREDIT';
			} else if(strlen($credit_amt) == 0)
			{
				//This was a debit
				$amount = $debit_amt * 100 * -1;
				$trantype = 'DEBIT';
			}
	
			$linehash = sha1(implode(',', $line_arr));
	
			$timestamp = mktime(0,0,0, substr($date, 0, 2), substr($date, 3, 2), substr($date, 6, 4));
	
			error_log("INSERT INTO transactions (date, amount, description, hash, trantype, account, source, owner) values(" 
				. $timestamp . ","
				. pg_escape_string($amount) . ",'"
				. pg_escape_string($desc) . "','"
				. $linehash . "','"
				. $trantype . "','"
				. pg_escape_string($this->_label) . "','td'," . $this->getOwner() . ")");
			$result = pg_query($this->_cnx, "INSERT INTO transactions (date, amount, description, hash, trantype, account, source, owner) values(" 
				. $timestamp . ","
				. pg_escape_string($amount) . ",'"
				. pg_escape_string($desc) . "','"
				. $linehash . "','"
				. $trantype . "','"
				. pg_escape_string($this->_label) . "','td'," . $this->getOwner() . ")"
			);
	
			if($result)
			{
				return true;
			} else
			{
				$this->error(pg_last_error());
				return false;
			}
		} else
		{
			$this->error("Invalid line length (length " . count($line_arr) . ", expected 5) OR invalid card number: " . implode(',', $line_arr));
			return false;
		}
		$this->error("Unknown error: " . implode(',', $line_arr));
		return false;
	}
}
?>

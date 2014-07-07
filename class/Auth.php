<?php
session_start();

class Auth
{
	public static function login($email, $password, $cnx)
	{
		$user = self::getUser($email, $cnx);

		if($user && password_verify($password, $user['password']))
		{
			//Email and password match
			$_SESSION['auth']['logged_in'] = 1;
			$_SESSION['auth']['user_id'] = $user['id'];

			//TODO: create a logger that logs stuff based on a config setting
			pg_query($cnx, "UPDATE users SET last_login = " . time() . " WHERE id = " . $user['id']);
			return true;
		}

		return false;
	}

	public static function logout()
	{
		if(isset($_SESSION['auth']))
		{
			unset($_SESSION['auth']);
		}

		return true;
	}

	public static function isAuthenticated()
	{
		if($_SESSION['auth']['logged_in'])
		{
			return true;
		}

		return false;
	}

	public static function createUser($email, $password, $cnx)
	{
		if(self::getUser($email, $cnx))
		{
			//User already exists
			return false;
		}

		$result = pg_query($cnx, "INSERT INTO users (email, password, last_login) VALUES ('" . pg_escape_string($email) . "','" . pg_escape_string(password_hash($password, PASSWORD_DEFAULT)) . "'," . time() . ")");

		if(!$result)
		{
			return false;
		}

		return true;
	}

	protected static function getUser($email, $cnx)
	{
		$result = pg_query($cnx, "SELECT * FROM users WHERE email = '" . pg_escape_string($email) . "' LIMIT 1");
		if($result && pg_num_rows($result) > 0)
		{
			//Return the user (row 0)
			return pg_fetch_assoc($result, 0);
		}
		return false;
	}
}
?>

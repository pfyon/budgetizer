<?php
@session_start();
require_once("config.php");
require_once("class/Auth.php");

if(Auth::isAuthenticated())
{
	header("Location: index.php");
	die();
}

if(!empty($_POST['login_submit']))
{
	if(Auth::login($_POST['login_email'], $_POST['login_password'], $db_cnx))
	{
		header("Location: index.php");
		die();
	}

	echo "Invalid email or password";
}


echo "<html><head>";
require_once("include/head.php");
echo "</head><body>";

echo '
<div class="container">
	<form name="login" method="POST">
		<fieldset>
			<legend>Login</legend>
			<div class="form_div">Email: <input type="text" name="login_email" value="" /></div>
			<div class="form_div">Password: <input type="password" name="login_password" value="" /></div>
			<div class="form_div"><input type="submit" name="login_submit" value="Login" /></div>
		</fieldset>
	</form>
</div>';
echo "</body></html>";
?>

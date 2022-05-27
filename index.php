<?php
include_once ('src/defines.php');
include_once ('src/Context.php');

class WELauncher
{


	/**
	 * Establish connection to database
	 *
	 * @return boolean false if database connection failed
	 */
	private static function connectDb (&$context)
	{
		$context->mysqli = new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);
		if ($context->mysqli->connect_errno)
		{
			print ('Database conectiopn failed. Wait a minute, or contact with a administrator.');
			print ('Errno: ' . $context->mysqli->connect_errno . '<br />');
			print ('Error: ' . $context->mysqli->connect_error . '<br />');

			return false;
		}

		$context->mysqli->set_charset ("utf8");
		// $mysqli->query ("SET NAMES 'UTF8'");

		return true;
	}


	/**
	 * Check if installation is OK
	 *
	 * @return boolean false if There is no config file, or if version does not match
	 */
	private static function checkInstallation ()
	{
		if (! file_exists ($GLOBALS ['fileCfg']))
		{
			echo 'Waiting installation';
			return false;
		}

		include_once ($GLOBALS ['fileCfg']);
		setCfgGlobals ();

		if ($GLOBALS ['Version'] != VERSION)
		{
			echo 'Maintenance in progress. Please, return later';
			return false;
		}
		return true;
	}


	/**
	 * Check the user credentials
	 *
	 * @return boolean false If there is no valid user logged
	 */
	private static function checkAuth (&$context)
	{
		session_start ();

		// Check Auth
		include_once ($GLOBALS ['moduleAuth']);
		$context->userId = Auth::login ($context->mysqli);

		return ($context->userId !== NULL);
	}


	/**
	 * Entry point
	 */
	public static function main ()
	{
		$context = new Context ();
		if (WELauncher::checkInstallation () && WELauncher::connectDb ($context) && WELauncher::checkAuth ($context))
		{
			require_once ($GLOBALS ['moduleMenu']);
			require_once ('src/Plugin.php');
			require_once ('src/WebEngine.php');

			// load the web Menu
			$context->mnu = new Menu ();
			$menuLoader = basename ($GLOBALS ['moduleMenu'], '.php');
			$menuLoader::load ($context, $context->mnu);

			WebEngine::launch ($context, isset ($_GET ['ajax']));
		}
	}
}

WELauncher::main ();


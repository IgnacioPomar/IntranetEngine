<?php
include_once ('src/defines.php');
include_once ('src/Plugin.php');
include_once ('src/installer.php');

class WebEngineAdmin
{
	private $mysqli;
	private $userId;
	public $mnu;


	/**
	 * Establish connection to database
	 *
	 * @return boolean false if database connection failed
	 */
	private function connectDb ()
	{
		$this->mysqli = new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);
		if ($this->mysqli->connect_errno)
		{

			$outputMessage = '<div class="fail">';
			$outputMessage .= '<b>Error</b>: Database connection Failed.<br />';
			$outputMessage .= 'Error number: ' . $this->mysqli->connect_errno . '.<br />';
			$outputMessage .= 'Error Description: ' . $this->mysqli->connect_error . '.<br />';
			$outputMessage .= '</div>';

			$outputMessage .= '<div class="fail">You must <b>FIX the site_cfg</b> file.</div>';

			echo $outputMessage;
			return false;
		}

		// $mysqli->query ("SET NAMES 'UTF8'");
		$this->mysqli->set_charset ("utf8");

		return true;
	}


	/**
	 * Check if the installation is working fine
	 *
	 * @return boolean false if we called the installer
	 */
	private function checkInstallation ()
	{
		return $this->checkFileCfg () && $this->connectDb () && $this->checkBaseTables ();
	}


	/**
	 * Check if this is a new installation with a manual site_cfg file
	 *
	 * @return boolean false if we called the installer
	 */
	private function checkBaseTables ()
	{
		$sql = 'SELECT 1 FROM weUsers LIMIT 1;';
		if (! $resultado = $this->mysqli->query ($sql))
		{
			$installer = new Installer ($this->mysqli);
			$installer->installWithConfigFile ();
			return false;
		}
		else
		{
			$resultado->close ();
			return true;
		}
	}


	/**
	 * Check if this is a fresh installation
	 *
	 * @return boolean false if we called the installer
	 */
	private function checkFileCfg ()
	{
		if (! file_exists ($GLOBALS ['fileCfg']))
		{
			Installer::installFromScratch ();

			return false;
		}

		// WE have the config File
		include_once ($GLOBALS ['fileCfg']);
		setCfgGlobals ();

		return true;
	}


	/**
	 * Check if we need to reinstall, and reinstalls if needed
	 */
	private function updateIfOutdated ()
	{
		if ($GLOBALS ['Version'] != VERSION)
		{
			// TODO: instead of force, let the user decide if wants to apply now
			$installer = new Installer ($this->mysqli);
			echo $installer->createCoreTables ();
			echo $installer->registerPlugins ();
		}
		else
		{
			echo '<link rel="stylesheet" type="text/css" href="./src/rsc/css/skel.css">';

			$this->showAdminMnu ();

			echo '<div id="mainContainer">';

			if (isset ($_GET ['a']))
			{
				$installer = new Installer ($this->mysqli);

				switch ($_GET ['a'])
				{
					case 'reinstallCore':
						echo '<h1>Reinstalling Core Tables</h1>';
						echo $installer->createCoreTables ();
						break;

					case 'rePlugins':
						echo '<h1>Reinstalling Plugins</h1>';
						echo $installer->registerPlugins ();
						break;
					case 'options':
						require_once 'src/menuEditOptions.php';
						echo EditOptions::main ($this->mysqli);
						break;
					case 'users':
						require_once 'src/users.php';
						echo Users::main ($this->mysqli);
						break;
					case 'groups':
						require_once 'src/groups.php';
						echo Groups::main ($this->mysqli);
						break;
				}
			}
			else
			{
				echo 'Version engine matches.';
			}
			echo '</div>';
		}
	}


	/**
	 * Show admin posibilities
	 */
	private function showAdminMnu ()
	{
		$jsonMenu = './src/rsc/default/adminMenu.json';
		$this->mnu = json_decode (file_get_contents ($jsonMenu), TRUE);

		// This is used to mark the selected menu option from the siteAdmin view
		$_SERVER ['PATH_INFO'] = '?' . strrev (strtok (strrev ($_SERVER ['REQUEST_URI']), '?'));

		require_once ('./src/menu.php');
		$menu = new Menu ($this);

		echo "<div id='toolbar'>{$menu->getMenu ($this->mnu)}</div>";
	}


	/**
	 * Check user login
	 *
	 * @return boolean false if no user or user without admin role
	 */
	private function checkAdminAuth ()
	{
		session_start ();

		// Comprobamos si estamos autenticados
		include_once ($GLOBALS ['moduleAuth']);
		$this->userId = Auth::setupLogin ($this->mysqli);

		// TODO; comprobar que tiene permisos de admin

		// Mostramos la página de verdad
		if ($this->userId !== NULL)
		{
			if ($_SESSION ['isAdmin'] == 1)
			{
				return TRUE;
			}
			else
			{
				echo 'You need ADMIN privileges to enter here';
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}


	/**
	 * Entry Point
	 */
	public static function main ()
	{
		$adminModule = new WebEngineAdmin ();

		if ($adminModule->checkInstallation () && $adminModule->checkAdminAuth ())
		{
			$adminModule->updateIfOutdated ();
		}
	}
}

WebEngineAdmin::main ();


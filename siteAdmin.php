<?php
include_once ('src/defines.php');
include_once ('src/Context.php');
include_once ('src/Plugin.php');
require_once ('src/menu.php');
include_once ('src/admin/installer.php');

class WebEngineAdmin
{

	// @formatter:off
    const ADMIN_PLUGINS = array(
        // 'name' => 'path',
		'ReinstallCore'		=> './src/admin/ReinstallCore.php',
		'ReinstallPlugins'	=> './src/admin/ReinstallPlugins.php',
    		
		'MaintenanceUsers' 	=> './src/admin/MaintenanceUsers.php',
		'MaintenanceGroups'	=> './src/admin/MaintenanceGroups.php',
		'EditPerms'			=> './src/admin/editPerm.php',
		'EditOptions'		=> './src/admin/menuEditOptions.php',
		'EditMenu'			=> './src/admin/EditMenu.php'
    );

    // @formatter:on
	private Menu $mnuAdmin;
	private Context $context;


	public function __construct ()
	{
		$this->context = new Context ();
		$menuAdminLoader = 'src/menuLoaderJson.php';
		include_once ($GLOBALS ['moduleMenu']);
		if (0 != strcmp ($menuAdminLoader, $GLOBALS ['moduleMenu']))
		{
			include_once ($menuAdminLoader);
		}

		// Load the admin Menu
		$this->mnuAdmin = new Menu ();
		MenuLoaderJson::loadFromFile ('./src/admin/adminMenu.json', $this->mnuAdmin);

		// load the web Menu
		$menuLoader = basename ($GLOBALS ['moduleMenu'], '.php');
		$this->context->mnu = new Menu ();
		$menuLoader::load ($this->context, $this->context->mnu);
	}


	/**
	 * Establish connection to database
	 *
	 * @return boolean false if database connection failed
	 */
	function connectDb ()
	{
		$this->context->mysqli = new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);
		if ($this->context->mysqli->connect_errno)
		{

			$outputMessage = '<div class="fail">';
			$outputMessage .= '<b>Error</b>: Database connection Failed.<br />';
			$outputMessage .= 'Error number: ' . $this->context->mysqli->connect_errno . '.<br />';
			$outputMessage .= 'Error Description: ' . $this->context->mysqli->connect_error . '.<br />';
			$outputMessage .= '</div>';

			$outputMessage .= '<div class="fail">You must <b>FIX the site_cfg</b> file.</div>';

			echo $outputMessage;
			return false;
		}

		// $mysqli->query ("SET NAMES 'UTF8'");
		$this->context->mysqli->set_charset ("utf8");

		return true;
	}


	/**
	 * Check if the installation is working fine
	 *
	 * @return boolean false if we called the installer
	 */
	private function checkInstallation ()
	{
		return $this->connectDb () && $this->checkBaseTables ();
	}


	/**
	 * Check if this is a new installation with a manual site_cfg file
	 *
	 * @return boolean false if we called the installer
	 */
	private function checkBaseTables ()
	{
		$sql = 'SELECT 1 FROM weUsers LIMIT 1;';
		if (! $resultado = $this->context->mysqli->query ($sql))
		{
			$installer = new Installer ($this->context->mysqli);
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
	private static function checkFileCfg ()
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
	 * Show the view of the selected option in the admin menu
	 */
	private function showAdminView ()
	{
		// If the installation is done in a subdirectory, it would not load the styles correctly
		$uriPath = str_replace ('site', '', $GLOBALS ['uriPath']);
		$retVal = "<link rel='stylesheet' type='text/css' href='{$uriPath}src/rsc/css/skel.css'>";

		$retVal .= "<div id='toolbar'>{$this->mnuAdmin->getMenu ($this->context->mnu)}</div>";

		$retVal .= '<div id="mainContainer">';

		$plgName = $this->mnuAdmin->getPlugin ();

		if (isset (self::ADMIN_PLUGINS [$plgName]) && ! empty ($plgName))
		{
			require_once (self::ADMIN_PLUGINS [$plgName]);
			$plg = new $plgName ($this->context);
			$retVal .= $plg->main ();
		}
		else
		{
			$retVal .= '<h1>Admin area</h1><p>You can use the menu.</p>';
		}

		$retVal .= '</div>';
		return $retVal;
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
		$this->userId = Auth::setupLogin ($this->context->mysqli);

		// TODO: comprobar que tiene permisos de admin

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
		if (self::checkFileCfg ())
		{
			$adminModule = new WebEngineAdmin ();

			if ($adminModule->checkInstallation () && $adminModule->checkAdminAuth ())
			{
				echo $adminModule->showAdminView ();
			}
		}
	}
}

WebEngineAdmin::main ();


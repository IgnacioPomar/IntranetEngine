<?php

// De momento no se hace por falta de tiempo
class Installer
{
	public $mysqli;


	public static function installFromScratch ()
	{
		if (isset ($_POST ['dbname']))
		{
			readfile ('./src/rsc/html/installResult.htm');
			$installer = new Installer ();
			print ($installer->installProcessFromScratch ());
		}
		else
		{
			Installer::showInstallSetup ();
		}
	}


	public function installWithConfigFile ()
	{
		if (isset ($_POST ['adminlogin']))
		{
			readfile ('./src/rsc/html/installResult.htm');
			print ($this->installProcessCommon ());
		}
		else
		{
			readfile ('./src/rsc/html/installFormNoCfg.htm');
		}
	}


	private static function showInstallSetup ()
	{
		$options = '';
		foreach (glob ("./plgs/*", GLOB_ONLYDIR) as $filename)
		{
			$filename = str_replace ("./plgs/", "", $filename);
			$options .= '<option value="' . $filename . '">' . $filename . '</option>';
		}

		$skins = '';
		foreach (glob ("./skins/*", GLOB_ONLYDIR) as $filename)
		{
			$filename = str_replace ("./skins/", "", $filename);
			$skins .= '<option value="' . $filename . '">' . $filename . '</option>';
		}

		$layout = file_get_contents ('./src/rsc/html/installForm.htm');
		$layout = str_replace ('<option>@@plgs@@</option>', $options, $layout);
		$layout = str_replace ('<option>@@skins@@</option>', $skins, $layout);

		header ('Content-Type: text/html; charset=utf-8');
		print ($layout);
	}


	public function __construct ($mysqli = NULL)
	{
		if ($mysqli == NULL)
		{
			$this->mysqli = @new mysqli ($_POST ['dbserver'], $_POST ['dbuser'], $_POST ['dbpass'], $_POST ['dbname'], $_POST ['dbport']);
		}
		else
		{
			$this->mysqli = $mysqli;
		}
	}


	/**
	 * Steps for a full fresh installs
	 *
	 * @return string
	 */
	private function installProcessFromScratch ()
	{
		$outputMessage = '';

		// 0.- Check Params
		if (! $this->checkDbAccess ($outputMessage))
		{
			return $outputMessage;
		}

		// 1.- Guardamos el nuevo fichero de configuración
		if (! $this->saveNewCfgFile ($outputMessage))
		{
			return $outputMessage;
		}

		include_once ($GLOBALS ['fileCfg']);
		setCfgGlobals ();

		return $this->installProcessCommon ();
	}


	/**
	 * Steps starting with the database inicialization
	 *
	 * @return string
	 */
	private function installProcessCommon ()
	{
		// Out Msg
		$outputMessage = '';

		// 2.- Creamos la estructura de la base de datos y metemos datos iniciales
		$outputMessage .= $this->createCoreTables ();

		if (! $this->addInitialData ($outputMessage))
		{
			return $outputMessage;
		}

		$outputMessage .= $this->registerPlugins ($outputMessage);
		return $outputMessage;
	}


	/**
	 * Creamos el esquema de la base de datos
	 *
	 * @param string $outputMessage
	 */
	public function createCoreTables ()
	{
		include_once ('dbSchema.php');

		$retVal = '';

		$dir = new FilesystemIterator ('./src/tables/');
		foreach ($dir as $fileinfo)
		{
			if ($fileinfo->getExtension () == 'jsonTable')
			{
				$upd = DbSchema::createOrUpdateTable ($this->mysqli, $fileinfo->getPathname ());

				$retVal .= $this->formatDBAction ($upd);
			}
		}

		return $retVal;
	}


	private function createOrUpdateTables ($basePath)
	{
		include_once ('dbSchema.php');

		$retVal = '';

		$dir = new RecursiveIteratorIterator (new RecursiveDirectoryIterator ($basePath));
		foreach ($dir as $file)
		{
			if ($file->isDir ())
				continue;
			else if ($file->getExtension () == 'jsonTable')
			{
				$upd = DbSchema::createOrUpdateTable ($this->mysqli, $file->getPathname ());

				$retVal .= $this->formatDBAction ($upd);
			}
		}

		return $retVal;
	}


	private function formatDBAction ($upd)
	{
		if ($upd [1] == - 1)
		{
			return '<div class="fail"><b>Error</b>: ' . $upd [0] . ' <br /> ' . $this->mysqli->connect_error . '</div>';
		}
		else if ($upd [1] == 1)
		{
			return '<div class="OK"><b>' . $upd [0] . ' </b>: Ok</div>';
		}
		else
		{
			return '<div class="none">' . $upd [0] . ' [No changes]</div>';
		}
	}


	public function registerPlugins ()
	{
		$out = '';
		// Get list of currently installed Plugins
		$installedPLgs = array ();
		if ($resultado = $this->mysqli->query ('SELECT plgName FROM wePlugins;'))
		{
			while ($row = $resultado->fetch_assoc ())
			{
				$installedPLgs [] = $row ['plgName'];
			}
		}

		// Get current plgs info
		$currentPlgs = array ();
		foreach (glob ($GLOBALS ['plgsPath'] . "*.plg/*.php") as $filename)
		{
			$clases = self::getPhpClasses ($filename);
			if (sizeof ($clases) > 0)
			{
				include_once $filename;

				foreach ($clases as $className)
				{
					if (is_subclass_of ($className, 'Plugin'))
					{
						$currentPlgs [$className] = call_user_func ($className . '::getPlgInfo');
						$currentPlgs [$className] ['path'] = $filename;
					}
				}
			}
		}

		// Update existing plugins
		foreach ($installedPLgs as $plgName)
		{
			if (isset ($currentPlgs [$plgName]))
			{
				$plgInfo = & $currentPlgs [$plgName];
				foreach ($plgInfo as &$val)
				{
					$val = $this->mysqli->real_escape_string ($val);
				}

				$sql = 'UPDATE wePlugins SET plgDescrip = "' . $plgInfo ['plgDescription'] . '", ';
				$sql .= 'plgFile = "' . $plgInfo ['path'] . '", plgParams= "' . $plgInfo ['params'] . '", ';
				$sql .= 'plgPerms = "' . $plgInfo ['perms'] . '", isMenu = ' . $plgInfo ['isMenu'] . ' ';
				$sql .= "WHERE plgName = \"$plgName\";";

				if ($this->mysqli->query ($sql) === TRUE)
				{
					$out .= '<div class="ok">Plugin updated: <b>' . $plgName . '</b></div>';
				}
				else
				{
					$out .= '<div class="fail"><b>Error</b>: Unable to update plugin: <b>' . $plgName . '</b</div>';
				}
			}
		}

		// Create new Plugins
		foreach ($currentPlgs as $plgName => $plgInfo)
		{
			if (! in_array ($plgName, $installedPLgs))
			{
				foreach ($plgInfo as &$val)
				{
					$val = $this->mysqli->real_escape_string ($val);
				}

				$sql = 'INSERT INTO wePlugins (plgName,plgDescrip,plgFile,plgParams,plgPerms,isMenu) VALUES (';
				$sql .= '"' . $plgName . '","' . $plgInfo ['plgDescription'] . '","' . $plgInfo ['path'] . '",';
				$sql .= '"' . $plgInfo ['params'] . '","' . $plgInfo ['perms'] . '",' . $plgInfo ['isMenu'] . ');';
				if ($this->mysqli->query ($sql) === TRUE)
				{
					$out .= '<div class="ok">New plugin registered: <b>' . $plgName . '</b></div>';
				}
				else
				{
					$out .= '<div class="fail"><b>Error</b>: Unablle to register plugin: <b>' . $plgName . '</b></div>';
				}
			}
		}

		// Delete NOT existing plugins
		$diff = array_diff ($installedPLgs, array_keys ($currentPlgs));
		if (count ($diff) > 0)
		{
			$sql = 'DELETE FROM wePlugins WHERE plgName IN (';
			$sep = '';
			foreach ($diff as $plgName)
			{
				$sql .= $sep . '"' . $plgName . '"';
				$sep = ',';
			}
			$sql .= ');';

			if ($this->mysqli->query ($sql) === TRUE)
			{
				$out .= '<div class="ok">Removed inexistent PLugins</div>';
			}
			else
			{
				$out .= '<div class="fail"><b>Error</b>: Unable to remove old plugins<br />: ' . $this->mysqli->error . '</div>';
			}
		}
		else
		{
			$out .= '<div class="none">No Plugin was nedded to remove</div>';
		}

		// Create or Update PLugins Tables
		return $out . '<h3>Updating plugin tables</h3>' . $this->createOrUpdateTables ($GLOBALS ['plgsPath']);
	}


	/**
	 * Get the classes defined in a PHP file
	 *
	 * @param string $filename
	 * @see https://stackoverflow.com/a/2051010/74785
	 * @return mixed[] Devuelve una lista de las clases que contiene el fichero
	 */
	private static function getPhpClasses ($filename)
	{
		$classes = array ();
		$php_code = file_get_contents ($filename);
		$tokens = token_get_all ($php_code);
		$count = count ($tokens);
		for($i = 2; $i < $count; $i ++)
		{
			if ($tokens [$i - 2] [0] == T_CLASS && $tokens [$i - 1] [0] == T_WHITESPACE && $tokens [$i] [0] == T_STRING)
			{

				$class_name = $tokens [$i] [1];
				$classes [] = $class_name;
			}
		}
		return $classes;
	}


	/**
	 * Add the just created admin account
	 *
	 * @param boolean $outputMessage
	 */
	public function addInitialData (&$out)
	{
		$sqlCmd = 'INSERT INTO weUsers (name, email, password, isActive, isAdmin) VALUES (';
		$sqlCmd .= '"' . $_POST ['adminname'] . '",';
		$sqlCmd .= '"' . $_POST ['adminlogin'] . '",';
		$sqlCmd .= '"' . password_hash ($_POST ['adminpass1'], PASSWORD_DEFAULT) . '",';
		$sqlCmd .= '1,1);';

		// Finalmente, insertamos el nuevo registro
		if ($this->mysqli->query ($sqlCmd) === TRUE)
		{
			$out .= '<div class="ok">Creating admin credentials:  <b>OK</b></div>';
			return TRUE;
		}
		else
		{
			$out .= '<div class="fail"><b>Error</b>: Unablle to create admin user.<br />: ' . $this->mysqli->error . '</div>';
			return FALSE;
		}
	}


	/**
	 * Check if the database info is correct
	 *
	 * @param string $outputMessage
	 *        	the output message in we will append the new report
	 */
	public function checkDbAccess (&$outputMessage)
	{
		if ($this->mysqli->connect_error)
		{
			$outputMessage .= '<div class="fail">';
			$outputMessage .= '<b>Error</b>: MySQL connection Failed.<br />';
			$outputMessage .= 'Codigo de error: ' . $this->mysqli->connect_errno . '.<br />';
			$outputMessage .= 'Descripci&oacute;n: ' . $this->mysqli->connect_error . '.<br />';
			$outputMessage .= '</div>';
			return FALSE;
		}
		else
		{
			$outputMessage .= '<div class="ok">Database Conection: <b>OK</b></div>';
			return TRUE;
		}
	}


	/**
	 *
	 * @param boolean $outputMessage
	 */
	public function saveNewCfgFile (&$outputMessage)
	{
		// TODO: Detect and apply the modules (auth....)
		$outputSkin = './src/rsc/default/site_cfg_def.php';
		$cfgFile = file_get_contents ($outputSkin);
		$cfgFile = str_replace ('@@dbserver@@', $_POST ['dbserver'], $cfgFile);
		$cfgFile = str_replace ('@@dbport@@', $_POST ['dbport'], $cfgFile);
		$cfgFile = str_replace ('@@dbuser@@', $_POST ['dbuser'], $cfgFile);
		$cfgFile = str_replace ('@@dbpass@@', $_POST ['dbpass'], $cfgFile);
		$cfgFile = str_replace ('@@dbname@@', $_POST ['dbname'], $cfgFile);
		$cfgFile = str_replace ('@@plgs@@', $_POST ['plgs'], $cfgFile);
		$cfgFile = str_replace ('@@skins@@', $_POST ['skins'], $cfgFile);

		if (! file_put_contents ($GLOBALS ['fileCfg'], $cfgFile))
		{
			$outputMessage .= '<div class="fail">';
			$outputMessage .= '<b>Error</b>: Unable to save the config file<br />';
			$outputMessage .= 'Workaround: save the following file<br /><pre>';
			$outputMessage .= $cfgFile;
			$outputMessage .= '</pre></div>';
			return FALSE;
		}
		else
		{
			$outputMessage .= '<div class="ok">Config file: <b>OK</b></div>';
		}

		return TRUE;
	}
}
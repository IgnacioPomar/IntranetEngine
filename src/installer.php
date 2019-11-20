<?php
include_once ('defines.php');
include_once ('crud.model.php');
include_once ('plg.php');

/**
 * Database maintenance
 */
class Installer
{


	/**
	 * Install a new database, or asks the necesary Data
	 */
	public static function install ($lang)
	{
		if (isset ($_POST ['dbname']))
		{
			$outputSkin = Installer::loadOutputSkin ($lang);

			$outputSkin = str_replace ('@@Operation@@', 'Proceso de Instalacion', $outputSkin);
			$outputSkin = str_replace ('@@Body@@', Installer::installProcess ($lang), $outputSkin);

			print ($outputSkin);
		}
		else
		{
			Installer::showInstallSetup ($lang);
		}
	}


	/**
	 *
	 * @param string $lang
	 */
	public static function installProcess ($lang)
	{
		$mysqli = @new mysqli ($_POST ['dbserver'], $_POST ['dbuser'], $_POST ['dbpass'], $_POST ['dbname'], $_POST ['dbport']);

		// TODO: Hacer las funciones que faltan
		// Pasos a realizar
		$outputMessage = '';

		// 0.- Check Params
		if (! Installer::checkDbAccess ($mysqli, $outputMessage))
		{
			return $outputMessage;
		}

		// 1.- Guardamos el nuevo fichero de configuración
		if (! Installer::saveNewCfgFile ($outputMessage))
		{
			return $outputMessage;
		}

		// Las rutas "finales" las obtenemos para lograr código "transparene"
		$GLOBALS ['skinPath'] = $GLOBALS ['basePath'] . 'skins' . DIRECTORY_SEPARATOR . $_POST ['skins'] . DIRECTORY_SEPARATOR;
		$GLOBALS ['plgsPath'] = $GLOBALS ['basePath'] . 'plgs' . DIRECTORY_SEPARATOR . $_POST ['plgs'] . DIRECTORY_SEPARATOR;

		// 2.- Creamos la estructura de la base de datos y metemos datos iniciales
		if (! Installer::createNewDBSchema ($mysqli, $outputMessage))
		{
			return $outputMessage;
		}

		if (! Installer::registerPlugins ($mysqli, $outputMessage))
		{
			return $outputMessage;
		}

		if (! Installer::addInitialData ($mysqli, $outputMessage))
		{
			return $outputMessage;
		}

		// 3.- Creamos directorios y damos permisos si es necesario
		// 4.- Creamos fichero HtAccess

		return $outputMessage;
	}


	/**
	 * Check if we can connect with the database
	 *
	 * @param string $outputMessage
	 *        	the output message in we will append the new report
	 */
	public static function checkDbAccess ($mysqli, &$outputMessage)
	{
		if ($mysqli->connect_error)
		{
			$outputMessage .= '<div class="fail">';
			$outputMessage .= '<b>Error</b>: No se pudo conectar a MySQL.<br />';
			$outputMessage .= 'Codigo de error: ' . $mysqli->connect_errno . '.<br />';
			$outputMessage .= 'Descripci&oacute;n: ' . $mysqli->connect_error . '.<br />';
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
	public static function saveNewCfgFile (&$outputMessage)
	{
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
			$outputMessage .= '<b>Error</b>: No se pudo grabar la configuración<br />';
			$outputMessage .= 'Posible solución: Por favor, guarde suba el ficherod eocnfiguracion:<br />';
			$outputMessage .= htmlspecialchars ($cfgFile);
			$outputMessage .= '</div>';
			return FALSE;
		}
		else
		{
			$outputMessage .= '<div class="ok">Fichero de configuración: <b>OK</b></div>';
		}

		return TRUE;
	}


	/**
	 * Metemos dentro de la base de datos los datos iniciales
	 *
	 * @param boolean $outputMessage
	 */
	public static function addInitialData ($mysqli, &$outputMessage)
	{

		// Para poder cifrar la contraseña en versiones antiguas de PHP
		include_once ('lib/password.php');

		// A día de hoy sólo es necesario crear el usuario inicial
		$sqlCmd = 'INSERT INTO {usuarios} (nombre, email, password, isAdmin) VALUES (';
		$sqlCmd .= '"' . $_POST ['adminname'] . '",';
		$sqlCmd .= '"' . $_POST ['adminlogin'] . '",';
		$sqlCmd .= '"' . password_hash ($_POST ['adminpass1'], PASSWORD_DEFAULT) . '",';
		$sqlCmd .= '1);';

		// Finalmente, insertamos el nuevo registro
		if ($mysqli->query (prefixQuery ($sqlCmd)) === TRUE)
		{
			$outputMessage .= '<div class="ok">Carga de datos iniciales:  <b>OK</b></div>';
			return TRUE;
		}
		else
		{
			$outputMessage .= '<div class="fail">';
			$outputMessage .= '<b>Error</b>: No se pudo crear los datos inciciales.<br />: ';
			$outputMessage .= $mysqli->error;
			$outputMessage .= '</div>';
			return FALSE;
		}
	}


	/**
	 *
	 * @param string $filename
	 * @return mixed[] Devuelve una lista de las clases que contiene el fichero
	 */
	private static function get_php_classes ($filename)
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


	public static function registerPluginParamsConf ($mysqli, $idPlugin, $clase = NULL)
	{
		// los parámetros de los menús que ya no se usan se borran
		$sqlAux = "DELETE FROM {plugins_params} WHERE idPlugin = " . $idPlugin . " AND idNodoMenu NOT IN (SELECT idNodoMenu FROM {nodosMenu} WHERE idPlugin = " . $idPlugin . ")";
		CrudModel::executeQuery ($mysqli, $sqlAux);

		if (is_null ($clase))
		{
			$sql = 'SELECT plgClass,plgFile FROM {plugins} WHERE idPlugin=' . $idPlugin . ';';
			$resultPlg = CrudModel::executeQuery ($mysqli, $sql);
			if ($plgInfo = $resultPlg->fetch_assoc ())
			{
				$clase = $plgInfo ['plgClass'];
				include_once ($plgInfo ['plgFile']);
			}
			else
			{
				return;
			}
		}

		// Actualización tabla de parámetros del plugin según los nodos del menú que contengan el plugin
		if (is_callable ($clase . '::getParamsConf'))
		{
			$plgParamsConf = call_user_func ($clase . '::getParamsConf');

			if (count ($plgParamsConf) > 0)
			{
				$sqlMenu = "SELECT idNodoMenu FROM {nodosMenu} WHERE idPlugin = " . $idPlugin;
				$resultado = CrudModel::executeQuery ($mysqli, $sqlMenu);
				while ($menu = $resultado->fetch_assoc ())
				{
					foreach ($plgParamsConf as $idParam => $paramInfo)
					{
						$sqlParam = 'SELECT idParam FROM {plugins_params} WHERE idPlugin = ' . $idPlugin . ' AND idNodoMenu = ' . $menu ['idNodoMenu'] . ' AND paramName = "' . $idParam . '";';
						$resultParam = CrudModel::executeQuery ($mysqli, $sqlParam);
						if ($resultParam->num_rows == 0)
						{
							$sqlAux = 'INSERT INTO {plugins_params} (idPlugin, idNodoMenu, paramName, paramValor) VALUES (' . $idPlugin . ', ' . $menu ['idNodoMenu'] . ', "' . $idParam . '", ' . ($paramInfo ['defaultValue'] ?: 0) . ');';
							CrudModel::executeQuery ($mysqli, $sqlAux);
						}
					}
				}
			}
		}
	}


	/**
	 *
	 * @param string $path
	 *        	Ruta de la que debemos obtener los plugins
	 * @return string[] Lista de sentencias SQL para insertar losplugins existentes
	 */
	public static function registerPluginsFromPath ($mysqli, $path, $oldIdPlugins)
	{
		// echo realpath ( $path );
		$sqlPLugins = array ();

		foreach (glob ("$path/*.php") as $filename)
		{
			$clases = Installer::get_php_classes ($filename);
			if (sizeof ($clases) > 0)
			{
				include_once $filename;

				foreach ($clases as $clase)
				{
					if (is_callable ($clase . '::getPlgInfo'))
					{
						$plgInfo = call_user_func ($clase . '::getPlgInfo');

						$sql = 'INSERT INTO {plugins} ';
						if (array_key_exists ($clase, $oldIdPlugins))
						{
							$sql .= '(idPlugin,plgMenuName,plgDescription,plgClass,plgFile,';
							$sql .= 'isMenu,isMenuAdmin,isSkinnable,isEnabled) ';
							$sql .= 'VALUES (' . $oldIdPlugins [$clase] . ',"' . $plgInfo ['plgName'] . '",';

							Installer::registerPluginParamsConf ($mysqli, $oldIdPlugins [$clase], $clase);
						}
						else
						{
							$sql .= '(plgMenuName,plgDescription,plgClass,plgFile,';
							$sql .= 'isMenu,isMenuAdmin,isSkinnable,isEnabled) ';
							$sql .= 'VALUES ("' . $plgInfo ['plgName'] . '",';
						}
						$sql .= '"' . $plgInfo ['plgDescription'] . '",';
						$sql .= '"' . $clase . '",';
						$sql .= '"' . $filename . '",';
						$sql .= $plgInfo ['isMenu'] . ',';
						$sql .= $plgInfo ['isMenuAdmin'] . ',';
						$sql .= $plgInfo ['isSkinable'] . ',1);';

						$sqlPLugins [$clase] = $sql;
					}
				}
			}
		}

		return $sqlPLugins;
	}


	/**
	 * Creamos o actualizamos la tablas que se usan en los plugins
	 *
	 * @param mysqli $mysqli
	 *        	Conexión a la base de datos
	 * @param string $outputMessage
	 *        	cadena que se mostrara luego al usuario con los mensajes de exito/error
	 */
	public static function updatePluginsTables ($mysqli, &$outputMessage)
	{
		// Primero listamos todos los plugins que estan en la base de datos
		$sql = 'SELECT plgClass FROM {plugins};';

		$outMsg = 'Actualizando Tablas: ';

		if ($resultado = $mysqli->query (prefixQuery ($sql)))
		{
			$sep = '';
			while ($plg = $resultado->fetch_assoc ())
			{
				// No se hace un include_once del plugin....porque teoricamente se cargaron previamente
				$clase = $plg ['plgClass'];

				// luego comprobamos que tengan la función de actualizar tablas, y la invocamos si la tienen
				if (is_callable ($clase . '::updateTables'))
				{
					$outMsg .= $sep . call_user_func ($clase . '::updateTables', $mysqli);

					$sep = ', ';
				}
			}

			$outputMessage .= $outMsg;
		}
	}


	/**
	 * Elimina el registro de los plugins existentes en la base de datos y los reinstala
	 *
	 * @param string $outputMessage
	 */
	public static function updateRegisteredPlugins ($mysqli, &$outputMessage)
	{
		$oldIdPlugins = array ();
		// Obtenemos un array con los identificadores y clases de los plugins existentes
		$sql = 'SELECT plgClass, idPlugin FROM {plugins};';

		// $mysqli = @new mysqli ( $_POST ['dbserver'], $_POST ['dbuser'], $_POST ['dbpass'], $_POST ['dbname'], $_POST ['dbport'] );
		if ($resultado = $mysqli->query (prefixQuery ($sql)))
		{
			while ($oldPlg = $resultado->fetch_assoc ())
			{
				$oldIdPlugins [$oldPlg ['plgClass']] = $oldPlg ['idPlugin'];
			}
		}

		Installer::deletePLugins ($mysqli, $outputMessage);
		Installer::registerPLugins ($mysqli, $outputMessage, $oldIdPlugins);
		Installer::registerRBACPermissions ($mysqli);
	}


	public static function registerRBACPermissions ($mysqli)
	{
		RBAC::deletePluginPermissions ($mysqli);
		$sqlMenu = "SELECT idNodoMenu, nombre FROM {nodosMenu}";
		$resultado = CrudModel::executeQuery ($mysqli, $sqlMenu);
		while ($menu = $resultado->fetch_assoc ())
		{
			RBAC::registerPluginPermissions ($menu ['idNodoMenu'], $menu ['nombre'], $mysqli);
		}
	}


	/**
	 * Elimina el registro de todos los plugins existentes en la base de datos
	 *
	 * @param string $outputMessage
	 * @return boolean
	 */
	public static function deletePlugins ($mysqli, &$outputMessage)
	{
		$sql = array (
				"truncate" => "TRUNCATE  {plugins};"
		);
		$sqlTxtResult = '';
		if (Installer::executeArraySqlCommand ($mysqli, $sql, $sqlTxtResult))
		{
			$outputMessage .= '<div class="ok">Eliminando Plugins existentes: <b>OK</b></div>';
			return TRUE;
		}
		else
		{
			$outputMessage .= '<div class="fail">';
			$outputMessage .= '<b>Error</b>: No se han podido eliminar los plugins existentes.<br />';
			$outputMessage .= '</div>';
			return FALSE;
		}
	}


	/**
	 * Leemos los ficheros existentes en el directorio plugin, y los guardamos en la BBDD
	 *
	 * @param string $outputMessage
	 *        	Mensaje de errir si procede
	 * @return boolean devuelve si ha dado algún error o no
	 */
	public static function registerPlugins ($mysqli, &$outputMessage, $oldIdPlugins = array ())
	{
		$GLOBALS ['InsideRegisterPlugins'] = true;
		$plgs = '';
		if (isset ($_POST ['plgs']))
		{
			$plgs = $_POST ['plgs'];
		}
		else if (isset ($GLOBALS ['plgs']))
		{
			$plgs = $GLOBALS ['plgs'];
		}

		// Detactamos los plugins existentes
		$sqlPLugins = Installer::registerPluginsFromPath ($mysqli, './plgs/' . $plgs, $oldIdPlugins);
		$sqlModules = Installer::registerPluginsFromPath ($mysqli, './src/modules', $oldIdPlugins);

		$sqlPLugins = array_merge ($sqlPLugins, $sqlModules);

		// Separamos los plugins en una lista de los que existían previamente, y en otra de los nuevos
		// Para evitar que el autonumerico de un valor que poseriormente insertemos
		$sqlOldPlugins = array ();
		$sqlNewPlugins = array ();
		foreach ($sqlPLugins as $clase => $sql)
		{
			if (strpos ($sql, 'idPlugin') !== false)
			{
				$sqlOldPlugins [$clase] = $sql;
			}
			else
			{
				$sqlNewPlugins [$clase] = $sql;
			}
		}

		// Una vez preparados lo plugins, los metemos en la base de datos
		$sqlTxtResult = '';
		if ((Installer::executeArraySqlCommand ($mysqli, $sqlOldPlugins, $sqlTxtResult)) && (Installer::executeArraySqlCommand ($mysqli, $sqlNewPlugins, $sqlTxtResult)))
		{
			$outputMessage .= '<div class="ok">Registrando Plugins existentes: ' . $sqlTxtResult . '<br />Registrando Plugins existentes:: <b>OK</b></div>';
			return TRUE;
		}
		else
		{
			$outputMessage .= '<div class="fail">';
			$outputMessage .= '<b>Error</b>: No se han podido registrar los plugins existentes.<br />Plugins registrados: ';
			$outputMessage .= $sqlTxtResult;
			$outputMessage .= '</div>';
			return FALSE;
		}
	}


	/**
	 * Creamos el esquema de la base de datos
	 *
	 * @param string $outputMessage
	 */
	public static function createNewDBSchema ($mysqli, &$outputMessage)
	{
		$sqlTablas = array (
				'{usuarios}' => "CREATE TABLE `{usuarios}`
                   (
	               `idUsuario` integer (11) NOT NULL AUTO_INCREMENT ,
	               `nombre` varchar (50),
	               `email` varchar (50) NOT NULL,
	               `password` varchar (255) NOT NULL,
				   `departamento` tinyint (3) NOT NULL DEFAULT 0,
	               `isAdmin` tinyint (4) NOT NULL DEFAULT 0,
	               `isActive` tinyint (4) NOT NULL DEFAULT 1,
                   `windowsUser` varchar (100),
	               PRIMARY KEY (`idUsuario`)
                   );",
				'{grupos}' => 'CREATE TABLE `{grupos}` ( `idGrupo` INT NOT NULL AUTO_INCREMENT,
                              `descripcion` VARCHAR(45) NULL,
                              PRIMARY KEY (`idGrupo`));',
				'{usuarios_grupos}' => 'CREATE TABLE `{usuarios_grupos}` (
						`idUsuario` INT NOT NULL,
						`idGrupo` INT NOT NULL);',
				'{{plugins_grupos}}' => 'CREATE TABLE {plugins_grupos} (
						`idPlugin` INT NOT NULL,
  						`idGrupo` INT NOT NULL,
  						PRIMARY KEY (`idPlugin`, `idGrupo`))
						COMMENT = "Esta tabla debe desaparecer: Deberemos generar otra con nodos de menu, que es la que realmente tendremos que ligar a los grupos";',

				'idx_usuarios_email' => "CREATE INDEX {usuarios_email} ON {usuarios} (email(50));",
				'{plugins}' => "CREATE TABLE `{plugins}` (
                  `idPlugin` int(11) NOT NULL AUTO_INCREMENT,
                  `plgMenuName` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
                  `plgDescription` varchar(122) COLLATE utf8_spanish_ci DEFAULT NULL,
                  `plgClass` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
                  `plgFile` varchar(120) COLLATE utf8_spanish_ci DEFAULT NULL,
                  `isMenu` tinyint(4) DEFAULT '0',
				  `isMenuAdmin` tinyint(4) DEFAULT '0',
                  `isSkinnable` tinyint(4) DEFAULT '1',
                  `isEnabled` tinyint(4) DEFAULT '1',
                  PRIMARY KEY (`idPlugin`)
                  );",
				'{sesioncookie}' => "CREATE TABLE {sesioncookie} (
                    cookieId varchar(30) COLLATE utf8_spanish2_ci NOT NULL,
                    cookiePass varchar(45) COLLATE utf8_spanish2_ci DEFAULT NULL,
                    realUserId int(11) DEFAULT NULL,
                    firstAccess datetime DEFAULT NULL,
                    expires datetime DEFAULT NULL,
                    browserInfo varchar(250) COLLATE utf8_spanish2_ci DEFAULT NULL,
                    PRIMARY KEY (`cookieId`)
                    );",
				'{nodosMenu}' => "CREATE TABLE {nodosMenu} (
  					`idNodoMenu` int(11) NOT NULL AUTO_INCREMENT,
  					`idNodoMenuPadre` int(11) NOT NULL DEFAULT '0',
    				`idPlugin` int(11) DEFAULT NULL,
    				`nombre` varchar(45) DEFAULT NULL,
    				`descripcion` varchar(122) DEFAULT NULL,
    				`classCss` varchar(45)  DEFAULT NULL,
    				`orden` int(11) DEFAULT '0',
    				`target` tinyint(4) DEFAULT NULL,
					`menuIcon` varchar(255) DEFAULT NULL,
    				PRIMARY KEY (`idNodoMenu`),
    				KEY `idNodoMenu` (`idNodoMenu`)
  					) ;",
				'{nodosMenu_grupos}' => "CREATE TABLE {nodosMenu_grupos} (
  					`idNodoMenu` int(11) NOT NULL,
					`idGrupo` int(11) NOT NULL,
					PRIMARY KEY (`idNodoMenu`,`idGrupo`)
					);",
				'{plugins_params}' => "CREATE TABLE {plugins_params} (
  					`idParam` int(11) NOT NULL AUTO_INCREMENT,
  					`idNodoMenu` int(11) NOT NULL,
  					`idPlugin` int(11) NOT NULL,
  					`paramName` varchar(45) DEFAULT NULL,
  					`paramValor` int(11) DEFAULT NULL,
  					PRIMARY KEY (`idParam`,`idNodoMenu`,`idPlugin`)
					) ;",
				'{rbac}' => "CREATE TABLE {rbac} (
					`idUsuario` int (11) NOT NULL,
					`permissions` varchar (765) DEFAULT NULL,
					`templates` varchar (765) DEFAULT NULL,
  					PRIMARY KEY (`idUsuario`)
					) ;",
				'{rbac_permissions}' => "CREATE TABLE {rbac_permissions} (
					`id` int (11),
					`permission` varchar (765),
  					PRIMARY KEY (`id`)
				) ;",
				'{rbac_templates}' => "CREATE TABLE {rbac_templates} (
					`id` int (11),
					`rbacName` varchar (765),
					`permissions` varchar (765),
  					PRIMARY KEY (`id`)
				); "
		);

		$sqlTxtResult = '';

		if (Installer::executeArraySqlCommand ($mysqli, $sqlTablas, $sqlTxtResult))
		{
			$outputMessage .= '<div class="ok">Creando tablas: ' . $sqlTxtResult . '<br />Creacion de Tablas: <b>OK</b></div>';
			return TRUE;
		}
		else
		{
			$outputMessage .= '<div class="fail">';
			$outputMessage .= '<b>Error</b>: No se pudo crear la estructura de tablas.<br />Tablas ceradas: ';
			$outputMessage .= $sqlTxtResult;
			$outputMessage .= '</div>';
			return FALSE;
		}
	}


	/**
	 * Ejecutamos un array de sentencias SQL hasta que se produzca el primer error
	 *
	 * @param mixed $salArray
	 * @param string $outputMessage
	 * @return boolean
	 */
	public static function executeArraySqlCommand ($mysqli, $sqlArray, &$outputMessage)
	{
		// $mysqli = new mysqli ( $_POST ['dbserver'], $_POST ['dbuser'], $_POST ['dbpass'], $_POST ['dbname'], $_POST ['dbport'] );
		foreach ($sqlArray as $tabla => $sqlCmd)
		{
			$res = $mysqli->query (prefixQuery ($sqlCmd));
			if ($res === TRUE)
			{
				$outputMessage .= $tabla . ', ';
			}
			else
			{
				$outputMessage = rtrim ($outputMessage);
				$outputMessage = rtrim ($outputMessage, ',');
				$outputMessage .= "<br />Fallo ejecutando el comando:  $tabla <br />$sqlCmd<br />$mysqli->error";
				return FALSE;
			}
		}

		$outputMessage = rtrim ($outputMessage);
		$outputMessage = rtrim ($outputMessage, ',');

		return TRUE;
	}


	/**
	 * Load the basic skin for the installer.
	 *
	 * @param string $lang
	 * @return string
	 */
	public static function loadOutputSkin ($lang)
	{
		$outputSkin = "./src/rsc/html/$lang/installResult.htm";

		if (! file_exists ($outputSkin))
		{
			$outputSkin = './src/rsc/html/installResult.htm';
		}

		return file_get_contents ($outputSkin);
	}


	/**
	 * Show the Install Form
	 *
	 * @param string $lang
	 *        	lnag we have to use to show the form
	 */
	public static function showInstallSetup ($lang)
	{
		$setupSkin = "./src/rsc/html/$lang/installForm.htm";

		if (! file_exists ($setupSkin))
		{
			$setupSkin = './src/rsc/html/installForm.htm';
		}

		// and we show it
		readfile ($setupSkin);

		$options = '';
		foreach (glob ("./plgs/*", GLOB_ONLYDIR) as $filename)
		{
			$filename = str_replace ("./plgs/", "", $filename);
			$options .= '<option value="' . $filename . '">' . $filename . '</option>';
		}
		echo '<script>document.getElementById("plgs").innerHTML = \'' . $options . '\';</script>';

		$skins = '';
		foreach (glob ("./skins/*", GLOB_ONLYDIR) as $filename)
		{
			$filename = str_replace ("./skins/", "", $filename);
			$skins .= '<option value="' . $filename . '">' . $filename . '</option>';
		}
		echo '<script>document.getElementById("skins").innerHTML = \'' . $skins . '\';</script>';
	}


	/**
	 */
	public static function update ($lang)
	{
		// TODO: Hacer esta funcion cuando sea necesaria
		// if (isset ($_POST['dbname']))
		{
			$outputSkin = Installer::loadOutputSkin ($lang);

			$outputSkin = str_replace ('@@Operation@@', 'Actualizando de versión ' . $GLOBALS ['Version'] . ' a versión ' . VERSION, $outputSkin);
			$outputSkin = str_replace ('@@Body@@', Installer::updateProcess ($lang), $outputSkin);
		}
	}
}


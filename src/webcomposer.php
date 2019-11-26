<?php
include_once ('crud.model.php');
include_once ('plg.php');
class WebComposer
{


	private static function getMenuOpcs ($mysqli, $userId, &$arrayMnuOpc, $idNodoMenuPadre = 0, $nivel = 0)
	{
		$retVal = array ();

		$permissionList = RBAC::getPermissionList ();

		if ($permissionList == "")
		{
			$permissionList = 0;
		}

		$sql = 'SELECT distinct {nodosMenu}.idNodoMenu,{nodosMenu}.idNodoMenuPadre,{nodosMenu}.nombre,{nodosMenu}.descripcion, {nodosMenu}.classCss, {nodosMenu}.target,{nodosMenu}.menuIcon,
				plgClass,plgMenuName,plgDescription,plgFile,isMenu,isMenuAdmin,isSkinnable,{plugins}.idPlugin ';
		$sql .= 'FROM {nodosMenu} left outer join {plugins} on {plugins}.idPlugin = {nodosMenu}.idPlugin, {usuarios} ';
		$sql .= 'WHERE
				{nodosMenu}.idNodoMenuPadre=' . $idNodoMenuPadre . ' AND
				({plugins}.idPlugin is null OR {plugins}.isMenuAdmin =0) AND
                {nodosMenu}.idNodoMenu IN (' . $permissionList . ') AND
				{usuarios}.idUsuario=' . $userId . ' ORDER BY {nodosMenu}.orden asc';
		// echo '<br/>' . prefixQuery ( $sql );

		if (! $resultado = $mysqli->query (prefixQuery ($sql)))
		{
			echo "Error: La ejecución de la consulta falló debido a: \n";
			echo "Query: " . $sql . "\n";
			echo "Errno: " . $mysqli->errno . "\n";
			echo "Error: " . $mysqli->error . "\n";
			exit ();
		}

		while ($actual = $resultado->fetch_assoc ())
		{
			$mnuOpc = array ();
			$mnuOpc ['idPlugin'] = $actual ['idPlugin'];
			$mnuOpc ['nivel'] = $nivel;
			$mnuOpc ['nombre'] = $actual ['nombre'];
			$mnuOpc ['desc'] = $actual ['descripcion'];
			$mnuOpc ['classCss'] = $actual ['classCss'];
			$mnuOpc ['target'] = $actual ['target'];
			$mnuOpc ['menuIcon'] = $actual ['menuIcon'];
			$mnuOpc ['class'] = $actual ['plgClass'];
			$mnuOpc ['plgName'] = $actual ['plgMenuName'];
			$mnuOpc ['plgDesc'] = $actual ['plgDescription'];
			$mnuOpc ['file'] = $actual ['plgFile'];
			$mnuOpc ['isMnu'] = $actual ['isMenu'];
			$mnuOpc ['isMnuAdmin'] = $actual ['isMenuAdmin'];
			$mnuOpc ['isSkinnable'] = $actual ['isSkinnable'];

			$arrayMnuOpc [$actual ['idNodoMenu']] = $mnuOpc;

			if ($actual ['idNodoMenuPadre'] != - 1 || $nivel == 0)
			{
				WebComposer::getMenuOpcs ($mysqli, $userId, $arrayMnuOpc, $actual ['idNodoMenu'], $nivel + 1);
			}
		}
		$resultado->free ();

		if ($nivel == 0 && $_SESSION ['isAdmin'] == 1)
		{
			$mnuOpc = array ();
			$mnuOpc ['idPlugin'] = 0;
			$mnuOpc ['nivel'] = $nivel;
			$mnuOpc ['nombre'] = 'Configuraci&oacute;n';
			$mnuOpc ['desc'] = '';
			$mnuOpc ['classCss'] = 'settings';
			$mnuOpc ['target'] = 0;
			$mnuOpc ['menuIcon'] = '<i class="fas fa-cog"></i>';
			$mnuOpc ['class'] = NULL;
			$mnuOpc ['plgName'] = NULL;
			$mnuOpc ['plgDesc'] = NULL;
			$mnuOpc ['file'] = NULL;
			$mnuOpc ['isMnu'] = NULL;
			$mnuOpc ['isMnuAdmin'] = NULL;
			$mnuOpc ['isSkinnable'] = NULL;

			$arrayMnuOpc [0] = $mnuOpc;

			$sql = 'SELECT idPlugin,plgClass,plgMenuName,plgDescription,plgFile,isMenu,isMenuAdmin,isSkinnable FROM {plugins} WHERE isMenuAdmin=1;';
			if (! $resultado = $mysqli->query (prefixQuery ($sql)))
			{
				echo "Error: La ejecución de la consulta falló debido a: \n";
				echo "Query: " . $sql . "\n";
				echo "Errno: " . $mysqli->errno . "\n";
				echo "Error: " . $mysqli->error . "\n";
				exit ();
			}

			while ($actual = $resultado->fetch_assoc ())
			{
				$mnuOpc = array ();
				$mnuOpc ['idPlugin'] = $actual ['idPlugin'];
				$mnuOpc ['nivel'] = 1;
				$mnuOpc ['nombre'] = $actual ['plgMenuName'];
				$mnuOpc ['desc'] = '';
				$mnuOpc ['classCss'] = '';
				$mnuOpc ['target'] = 0;

				$mnuOpc ['class'] = $actual ['plgClass'];
				$mnuOpc ['plgName'] = $actual ['plgMenuName'];
				$mnuOpc ['plgDesc'] = $actual ['plgDescription'];
				$mnuOpc ['file'] = $actual ['plgFile'];
				$mnuOpc ['isMnu'] = $actual ['isMenu'];
				$mnuOpc ['isMnuAdmin'] = $actual ['isMenuAdmin'];
				$mnuOpc ['isSkinnable'] = $actual ['isSkinnable'];

				$arrayMnuOpc [$actual ['plgClass']] = $mnuOpc;
			}
			$resultado->free ();
		}
	}


	public static function setUserInfo (&$mysqli, $skin, &$output)
	{
		$output = str_replace ('@@userName@@', htmlspecialchars_decode ($_SESSION ['nombreUsuario']), $output);
	}


	/**
	 * Cargamos desde el skin, si no esta en el skin la página, de la configuracion por defecto
	 *
	 * @param string $skin
	 * @param string $page
	 * @return string
	 */
	private static function loadHtmlFromSkin ($skin, $page)
	{
		$outputSkin = "./skins/$skin/html/$page";
		if (! file_exists ($outputSkin))
		{
			$outputSkin = "./rsc/html/$page";
		}

		// Modificamos el skin con los cambios de rutas para skins/plgs
		$outputSkinTxt = str_replace ("@@skin@@", $skin, file_get_contents ($outputSkin));

		return $outputSkinTxt;
	}


	private static function setJsCall ($skin, $jsCall, &$output)
	{
		$isAnyJsCall = FALSE;
		if ($jsCall != '') // evitamos que detecte los directorios como ficheros
		{
			$jsCallFile = "./skins/$skin/js/$jsCall";
			if (file_exists ($jsCallFile))
			{
				$jsCallContents = '<script type="text/javascript">';
				$jsCallContents .= file_get_contents ($jsCallFile);
				$jsCallContents .= '</script>';
				$isAnyJsCall = TRUE;
			}
			else
			{
				$jsCallContents = '';
			}
		}
		else
		{
			$jsCallContents = '';
		}

		if ($isAnyJsCall)
		{
			if (strpos ($output, '@@jsLastCall@@') !== false)
			{
				$output = str_replace ('@@jsLastCall@@', $jsCallContents, $output);
			}
			else
			{
				$output = str_replace ('</body>', $jsCallContents . '</body>', $output);
			}
		}
		else
		{
			$output = str_replace ('@@jsLastCall@@', '', $output);
		}
	}


	private static function setJsHeader ($skin, $jsFiles, &$output)
	{
		$jsHeader = '';
		foreach ($jsFiles as $jsFile)
		{
			if (strpos ($jsFile, '://') !== false)
			{
				$jsPath = $jsFile;
			}
			else if (strpos ($jsFile, 'vendor') !== false)
			{
				$jsPath = "./$jsFile";
			}
			else
			{
				$jsPath = "./skins/$skin/js/$jsFile";
			}

			$jsHeader .= '<script type="text/javascript" src="' . $jsPath . '"></script>' . PHP_EOL;
		}

		if (strpos ($output, '@@externalJs@@') !== false)
		{
			$output = str_replace ('@@externalJs@@', $jsHeader, $output);
		}
		else
		{
			$output = str_replace ('</head>', $jsHeader . '</head>', $output);
		}
	}


	private static function setCssHeader ($skin, $cssFiles, &$output)
	{
		$cssHeader = '';
		foreach ($cssFiles as $cssFile)
		{
			if (strpos ($cssFile, '://') !== false)
			{
				$cssPath = $cssFile;
				$cssType = '';
			}
			else
			{
				$cssPath = "./skins/$skin/css/$cssFile";
				$cssType = 'type="text/css"';
			}

			// $cssPath = "./skins/$skin/css/$cssFile";
			$cssHeader .= '<link rel="stylesheet" ' . $cssType . ' href="' . $cssPath . '">' . PHP_EOL;
		}

		if (strpos ($output, '@@cssFiles@@') !== false)
		{
			$output = str_replace ('@@cssFiles@@', $cssHeader, $output);
		}
		else
		{
			$output = str_replace ('</head>', $cssHeader . '</head>', $output);
		}
	}


	private static function setMenu ($skin, $menuOpcs, &$output, $selectedOpc)
	{
		$lastNivel0Name = '';
		$count = 0;
		$isDropDown = false;
		// TODO: En un futuro cargar del skin las opciones
		$menuHtml = '<ul class="nav navbar-nav">';
		$nivel = - 1;
		foreach ($menuOpcs as $mnuId => $mnuOpc)
		{
			if ($mnuOpc ['idPlugin'] != NULL)
			{
				$permissionId = 10000 + $mnuOpc ['idPlugin'];
				if (! RBAC::hasPermission ($permissionId) && ($mnuOpc ['isMnuAdmin'] && $_SESSION ['isAdmin'] != 1))
				{
					continue;
				}
			}

			$selected = '';
			if ($mnuId == $selectedOpc)
			{
				$output = str_replace ('@@pageTitle@@', $mnuOpc ['nombre'], $output);
				if (! $mnuOpc ['target'])
				{
					$selected = 'selected';
				}
			}

			$nivel = $mnuOpc ['nivel'];

			if ($nivel == 0)
			{
				$lastNivel0Name = substr (str_replace (" ", "", $mnuOpc ['nombre']), 0, 5);

				if ($isDropDown)
				{
					$menuHtml .= '</ul></li>';
				}

				$isDropDown = true;
				$menuHtml .= '<li id="' . $lastNivel0Name . '" onclick="toggleMenu(\'li-' . $count . '\')" class="dropdown">';
				$menuHtml .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">' . $mnuOpc ['menuIcon'] . ' ' . $mnuOpc ['nombre'] . '<span class="caret"></span></a>';
				$menuHtml .= '<ul class="dropdown-menu li-' . $count . '" id="li-' . $count . '" style="display:none;">';
				++ $count;
			}
			else
			{
				$href = '#';
				if (isset ($mnuOpc ['class']))
				{
					$href = '?opc=' . $mnuOpc ['class'] . '&mn=' . $mnuId;
				}

				if ($selected != '')
				{
					$menuHtml .= '<script>$("#' . $lastNivel0Name . '").attr("class", "dropdown mainSelected");</script>';
				}

				$menuHtml .= '<a href="' . $href . '" class="' . $selected . '" ' . ($mnuOpc ['target'] ? 'target="_blank"' : '') . '>';
				$menuHtml .= '<li class="">' . $mnuOpc ['nombre'] . '</li>';
				$menuHtml .= '</a>';
			}
		}
		$menuHtml .= '</li></ul>';
		$menuHtml .= '</ul>';

		$output = str_replace ('@@menu@@', $menuHtml, $output);
	}


	private static function showPlgContents ($mysqli, $skin, $menuOpcs, $mnu)
	{
		$opc = $menuOpcs [$mnu];
		$isSkinnable = $opc ['isSkinnable'];
		$class = $opc ['class'];
		
		if ($isSkinnable == 1)
		{

			// Cargamos los datos a mostrar
			$skinFile = call_user_func ($class . '::getSkin');
			$cssFiles = call_user_func ($class . '::getExternalCss');
			$jsFiles = call_user_func ($class . '::getExternalJs');
			$jsCall = call_user_func ($class . '::getJsCall');
			$plgBody = call_user_func ($class . '::main', $mysqli, $mnu);

			// Cargamos el skin
			$output = WebComposer::loadHtmlFromSkin ($skin, $skinFile);

			// Componemos las distintas partes de la página
			// TODO: Revisar todas las entradas de skin y hacerlas skin ready
			WebComposer::setUserInfo ($mysqli, $skin, $output);
			WebComposer::setMenu ($skin, $menuOpcs, $output, $mnu);
			WebComposer::setCssHeader ($skin, $cssFiles, $output);
			WebComposer::setJsHeader ($skin, $jsFiles, $output);
			WebComposer::setJsCall ($skin, $jsCall, $output);
			$output = str_replace ('@@skinPath@@', $GLOBALS ['skinUriPath'], $output);
			$output = str_replace ('@@content@@', $plgBody, $output);
			$output = str_replace ('@@pageTitle@@', "GVI", $output);

			header ('Content-Type: text/html; charset=utf-8');
			echo $output;
		}
		else
		{
			echo call_user_func ($class . '::main', $mysqli, $mnu);
		}
	}


	private static function showEmptyBody($mysqli, $skin, $skinFile, $menuOpcs, $mnu)
	{
		// Cargamos el skin
		$output = WebComposer::loadHtmlFromSkin($skin, $skinFile);

		// Componemos las distintas partes de la página
		// TODO: Revisar todas las entradas de skin y hacerlas skin ready
		WebComposer::setUserInfo($mysqli, $skin, $output);
		WebComposer::setMenu($skin, $menuOpcs, $output, $mnu);
		$output = str_replace('@@cssFiles@@', '', $output);
		$output = str_replace('@@externalJs@@', '', $output);
		$output = str_replace('@@jsLastCall@@', '', $output);
		$output = str_replace('@@skinPath@@', $GLOBALS['skinUriPath'], $output);
		$output = str_replace('@@content@@', '', $output);
		$output = str_replace('@@pageTitle@@', "GVI", $output);

		header('Content-Type: text/html; charset=utf-8');
		echo $output;
	}



	/**
	 *
	 * @param string $skin
	 * @param integer $userId
	 */
	public static function main ($skin, $userId)
	{

		// ---- Obtenemos la conexión Mysql ----
		$mysqli = new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);
		$mysqli->set_charset ("utf8");

		if ($mysqli->connect_errno)
		{
			print ('No se puede acceder a la base de datos. Revisar configuracion');
			print ('Errno: ' . $mysqli->connect_errno . '<br />');
			print ('Error: ' . $mysqli->connect_error . '<br />');

			// Finalizamos la ejecucion si no hay conexión a la base de datos
			exit ();
		}

		// Obtenemos las opciones existentes en el menu
		$menuOpcs = array ();
		WebComposer::getMenuOpcs ($mysqli, $userId, $menuOpcs);

		// Comprobamos si hemos introducido una opción que podamos mostrar
		if (isset ($_GET ['mn']))
		{
			if ($_GET ['mn'] == "logout")
			{
				return Auth::logout (mysqli);
			}

			if (array_key_exists ($_GET ['mn'], $menuOpcs))
			{
				$opc = $menuOpcs [$_GET ['mn']];
				include_once ($opc ['file']);

				if (isset ($_GET ['ajax']))
				{
					echo call_user_func ($opc ['class'] . '::main', $mysqli, $_GET ['mn']);
					exit ();
				}
				else
				{
					WebComposer::showPlgContents ($mysqli, $skin, $menuOpcs, $_GET ['mn']);
				}
			}
			else
			{
				// TODO: Decidir que hacer si se ha especificado un módulo que no existe
				include ("fake404.php");
			}
		}
		else if (isset ($_GET ['opc']))
		{
			$plgSoportadoMnu = FALSE;
			foreach ($menuOpcs as $mnuId => $opc)
			{
				if ($_GET ['opc'] == $opc ['class'])
				{

					include_once ($opc ['file']);

					if (isset ($_GET ['ajax']))
					{
						echo call_user_func ($opc ['class'] . '::main', $mysqli, $mnuId);
						exit ();
					}
					else
					{
						WebComposer::showPlgContents ($mysqli, $skin, $menuOpcs, $mnuId);
					}
					$plgSoportadoMnu = TRUE;
					break;
				}
			}

			if (! $plgSoportadoMnu)
			{
				// TODO: Decidir que hacer si se ha especificado un módulo que no existe
				include ("fake404.php");
			}
		}
		else // Nothing selected
		{
			//TODO: have a -1 option in the nodes for the Frontpage module
			// Cargamos el skin
			$output = WebComposer::loadHtmlFromSkin ($skin, 'skel.htm');

			// Y mostramos la portada
			WebComposer::showEmptyBody ($mysqli, $skin,Plugin::getSkin(), $menuOpcs, 'Portada');
		}
	}
}

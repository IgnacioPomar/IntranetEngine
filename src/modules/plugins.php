<?php
/**
 * Se encarga delmantenimiento de los plugins
 */
class GestorPlugins extends Plugin
{


	private function grabaPermiosPlugin ($idPlg)
	{
		if ((isset ( $_POST ['idPlg'] )) && (is_numeric ( $idPlg )))
		{

			$usrGrps = array ();
			$prefijo = 'grp_';
			$prefLen = strlen ( $prefijo );

			$sqlInsercion = 'INSERT INTO {plugins_grupos} (idPlugin,idGrupo) VALUES ';
			$sep = '';
			$hayGruposQueInsertar = FALSE;
			foreach ( $_POST as $arg => $value )
			{
				if (substr ( $arg, 0, $prefLen ) === $prefijo)
				{
					$sqlInsercion .= $sep . '(' . $idPlg . ',' . intval ( substr ( $arg, $prefLen ) ) . ')';
					$sep = ',';
					$hayGruposQueInsertar = TRUE;
				}
			}
			$sqlInsercion .= ';';

			$sql = 'DELETE FROM {plugins_grupos} WHERE idPlugin=' . $idPlg . ';';

			$mysqli = $this->mysqli;

			if (CrudModel::executeQuery ( $this->mysqli, $sql ))
			{
				$retVal = 'Antiguos grupos eliminados Correctamente <br />';
				if (CrudModel::executeQuery ( $this->mysqli, $sqlInsercion ))
				{
					$retVal .= 'Incorporados nuevos grupos';
				}
			}

			return $retVal;
		}
	}


	private function ajustaPermiosPlugin ($idPlg)
	{
		$retVal = '<form method="post" action="?opc=GestorPlugins&action=grabaPermisos&plg=' . $idPlg . '">';
		$retVal .= ' <input type="hidden" name="idPlg" value="' . $idPlg . '">';
		$sql = 'SELECT idGrupo, descripcion, (SELECT COUNT(*) FROM {plugins_grupos} WHERE idPLugin=' . $idPlg . ' AND {grupos}.idGrupo = {plugins_grupos}.idGrupo) as activo FROM {grupos};';

		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
		while ( $fila = $resultado->fetch_assoc () )
		{
			if ($fila ['activo'] > 0)
			{
				$check = ' checked="true" ';
			}
			else
				$check = '';

			$retVal .= ' <label for="grp_' . $fila ['idGrupo'] . '"><input type="checkbox" ' . $check . ' id="grp_' . $fila ['idGrupo'] . '" name="grp_' . $fila ['idGrupo'] . '" />' . $fila ['descripcion'] . '</label><br />';
		}

		$retVal .= '<div class="field"><label></label><button class="btn" type="submit" value="Grabar">Grabar</button></div>';
		$retVal .= '</form>';

		return $retVal;
	}


	private function reinstalaPlugins ()
	{
		include_once ('src/installer.php');

		$outputMessage = '';

		Installer::updateRegisteredPlugins ( $this->mysqli, $outputMessage );
		Installer::updatePluginsTables ( $this->mysqli, $outputMessage );

		return $outputMessage;
	}


	private function getParams ($idPlg)
	{
		include_once ('src/installer.php');
		Installer::registerPluginParamsConf ( $this->mysqli, $idPlg );

		$sql = 'SELECT idParam, paramName, paramValor, {nodosMenu}.idNodoMenu as idNodoMenu, {nodosMenu}.nombre as nombreMenu, {plugins}.plgClass, {plugins}.plgFile ';
		$sql .= 'FROM {plugins_params} LEFT OUTER JOIN {nodosMenu} ON {nodosMenu}.idNodoMenu = {plugins_params}.idNodoMenu LEFT OUTER JOIN {plugins} ON {plugins_params}.idPlugin = {plugins}.idPlugin ';
		$sql .= 'WHERE {plugins_params}.idPlugin = ' . $idPlg . ' ORDER BY {plugins_params}.idNodoMenu, idParam;';
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

		if ($resultado->num_rows > 0)
		{

			$retVal = '<form class="paramsConfPlg" method="post" action="' . $this->getPlgLink () . '&action=grabaParams&plg=' . $idPlg . '">';
			$retVal .= ' <input type="hidden" name="idPlg" value="' . $idPlg . '">';

			$idNodoMenuAnt = 0;
			while ( $fila = $resultado->fetch_assoc () )
			{
				if ($idNodoMenuAnt != $fila ['idNodoMenu'])
				{
					$idNodoMenuAnt = $fila ['idNodoMenu'];
					$retVal .= '<h2>' . $fila ['nombreMenu'] . '</h2>';
				}
				$paramsConf = call_user_func ( $fila ['plgClass'] . '::getParamsConf' );
				$paramInfo = $paramsConf [$fila ['paramName']];
				$retVal .= CrudModel::generateField ( $this->mysqli, $paramInfo ['tipo'], $fila ['paramName'] . '_' . $fila ['idParam'], $paramInfo ['title'], $fila ['paramValor'] );
			}

			$retVal .= '<div class="field"><label></label><button class="btn" type="submit" value="Grabar">Grabar</button></div>';
			$retVal .= '</form>';
		}
		else
		{
			$retVal = 'Este plugin no tiene par&aacute;metros de configuración.';
		}

		return $retVal;
	}


	private function grabaParamsPlugin ($idPlg)
	{
		if ((isset ( $_POST ['idPlg'] )) && (is_numeric ( $idPlg )))
		{

			$retVal = 0;
			foreach ( $_POST as $arg => $value )
			{
				if ($arg != 'idPlg')
				{
					$sql = 'UPDATE {plugins_params} SET paramValor=' . $value . ' WHERE idParam = ' . explode ( "_", $arg ) [1] . ' AND ParamName = "' . explode ( "_", $arg ) [0] . '" AND idPlugin = ' . $idPlg . ';';
					if (CrudModel::executeQuery ( $this->mysqli, $sql ))
					{
						$retVal ++;
					}
				}
			}
			if ($retVal == 1)
				$retVal = "Se ha guardado correctamente el par&aacute;metro.";
			else
				$retVal = "Se han guardado correctamente los " . $retVal . " par&aacute;metros.";

			return $retVal;
		}
	}


	private function getPlugins ()
	{
		$mysqli = $this->mysqli;
		$retVal = '<h1>Administraci&oacute;n de los plugins</h1>';

		$retVal .= '<div class="marco" style="position:relative">';
		$retVal .= '<div class="controles"><a class="btn reinstalaPlgs" href="' . $this->getPlgLink () . '&action=reinstala"><i class="material-icons">create</i> Reinstalar</a></div>';
		$retVal .= '<table class="table-border">';
		$retVal .= '<tbody>';

		$sql = 'SELECT idPlugin,plgMenuName,plgDescription FROM {plugins} WHERE isMenuAdmin=0 ORDER BY plgMenuName;';

		$resultado = CrudModel::executeQuery ( $mysqli, $sql );
		while ( $plg = $resultado->fetch_assoc () )
		{
			$retVal .= '<tr class="clickable-row" data-href="' . $this->getPlgLink () . '&action=params&plg=' . $plg ['idPlugin'] . '" data-ajax="ajax">';
			$retVal .= '<td class="text clickable" title="' . $plg ['plgDescription'] . '">' . $plg ['plgMenuName'] . '</td>';
			$retVal .= '</tr>';
		}

		if ($resultado->num_rows == 0)
		{
			$retVal .= '<tr>';
			$retVal .= '<td class="text">No hay ning&uacute;n plugin</td>';
			$retVal .= '</tr>';
		}
		$retVal .= '</tbody></table>';
		$retVal .= '</div>';

		return $retVal;
	}


	// -----------------------------------------------------------------------
	// ------------------ Funciones básicas de un plugin --------------------
	// -----------------------------------------------------------------------
	public static function getExternalCss ()
	{
		$css = array ();
		$css [] = 'terceros.css';
		$css [] = 'multiselect.css';
		$css [] = 'menuadmin.css';

		return $css;
	}


	public static function getExternalJs ()
	{
		$js = array ();
		$js [] = 'vendor/tinymce/tinymce.min.js';
		$js [] = 'loadTinymce.js';
		$js [] = 'ajaxBtn.js';
		$js [] = 'multiselectGrupos.js'; // Necesita ajaxBtn
		$js [] = 'plugin.js';

		return $js;
	}


	public static function getPlgInfo ()
	{
		$plgInfo = array ();
		$plgInfo ['plgName'] = 'Administrar plugins';
		$plgInfo ['plgDescription'] = 'Nos permite administrar los plugins activos de la web';
		$plgInfo ['isMenu'] = 0;
		$plgInfo ['isMenuAdmin'] = 1;
		$plgInfo ['isSkinable'] = 1;

		return $plgInfo;
	}


	/**
	 *
	 * @param mysqli $mysqli
	 */
	public static function main ($mysqli, $opcion)
	{
		$gestorPlugins = new GestorPlugins ( $mysqli, $opcion );
		if (! isset ( $_GET ['action'] ))
		{
			$_GET ['action'] = '';
		}

		if (! isset ( $_GET ['plg'] ))
		{
			$_GET ['plg'] = 0;
		}

		switch ($_GET ['action'])
		{
			case 'permisos':
				if (isset ( $_GET ['plg'] ))
				{
					return $gestorPlugins->ajustaPermiosPlugin ( $_GET ['plg'] );
				}
				break;
			case 'grabaParams':
				if (isset ( $_GET ['plg'] ))
				{
					return $gestorPlugins->grabaParamsPlugin ( $_GET ['plg'] );
				}
				break;
			case 'params':
				return $gestorPlugins->getParams ( $_GET ['plg'] );
				break;
			case 'reinstala':
				return $gestorPlugins->reinstalaPlugins ();
				break;
		}
		return $gestorPlugins->getPlugins ();
	}
}
class PluginParams implements ComboData
{


	public function getComboData ($mysqli)
	{
		$sql = 'SELECT idPlugin,plgMenuName,plgDescription FROM {plugins} WHERE isMenuAdmin = 0;';

		$resultado = CrudModel::executeQuery ( $mysqli, $sql );
		$arrayPlugins = array ();
		$arrayPlugins [] = "";
		while ( $plg = $resultado->fetch_assoc () )
		{
			$arrayPlugins [$plg ['idPlugin']] = $plg ['plgMenuName'] . ' (' . $plg ['plgDescription'] . ')';
		}

		return $arrayPlugins;
	}
}

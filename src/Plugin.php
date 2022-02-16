<?php

abstract class Plugin
{
	var $context;
	var $uriPrefix;


	// IdNodoMenu
	public function __construct (Context &$context)
	{
		$this->context = &$context;
		$this->uriPrefix = $_SERVER ['SCRIPT_NAME'] . $context->subPath . '?';
	}


	abstract public function main ();


	abstract public static function getPlgInfo (): array;


	/*
	 * EXAMPLE
	 * public static function getPlgInfo ()
	 * {
	 * $plgInfo = array ();
	 * $plgInfo ['plgDescription'] = "Plugin small description";
	 * $plgInfo ['isMenu'] = 1;
	 * $plgInfo ['perms'] = '["SeeAll","ExecuteInfo"]';
	 * $plgInfo ['params'] = '[{"name":"backgroundColor","type":"color","defaultValue":"#ffaacc"}]';
	 *
	 * //Persmissions can't be nameless: that will be te node itself
	 *
	 * return $plgInfo;
	 * }
	 */

	// -----------------------------------------------------------------------
	// ------------------ BAsic Functions --------------------
	// -----------------------------------------------------------------------
	public function checkParams ()
	{
		// TODO: Check the params
		/*
		 * $params = array ();
		 * $sql = 'SELECT pp.idParam, pp.nombre, pp.tipo, IFNULL(np.valor, pp.defaultVal) AS valor FROM grn_plugins_params pp
		 * INNER JOIN grn_nodosMenu nm ON nm.idPlugin = pp.idPlugin
		 * LEFT JOIN grn_nodos_params np ON np.idNodo = nm.idNodoMenu AND pp.idParam = np.idParam
		 * WHERE nm.idNodoMenu = ' . $this->idNodoMenu . ';';
		 *
		 * $resultado = CrudModel::executeQuery ($this->mysqli, $sql);
		 * while ( $actual = $resultado->fetch_assoc () )
		 * {
		 * $retVal [$actual ['idParam']] = ['nombre' => $actual ['nombre'], 'tipo' => $actual ['tipo'], 'valor' => $actual ['valor']];
		 * }
		 *
		 * return $retVal;
		 */
	}


	public function checkPermisos ()
	{
		// TODO: Check the Permmisions
		/*
		 * $sql = 'SELECT * FROM (
		 * SELECT idPermiso, MIN(status) minSt FROM (
		 * SELECT * FROM grn_permisos_grupos WHERE idGrupo IN (SELECT idGrupo FROM grn_usuarios_grupos WHERE idusuario= ' . $_SESSION ['userId'] . ')
		 * UNION ALL SELECT * FROM grn_permisos_usuarios WHERE idUsuario= ' . $_SESSION ['userId'] . ') a
		 * WHERE a.idNodo =' . $this->idNodoMenu . ' AND a.idPermiso<>0
		 * GROUP BY idPermiso) b WHERE b.minSt > 0;';
		 *
		 * $resultado = CrudModel::executeQuery ($this->mysqli, $sql);
		 *
		 * $retVal = [];
		 * while ($permiso = $resultado->fetch_assoc())
		 * {
		 * $retVal [] = $permiso ['idPermiso'];
		 * }
		 *
		 * return $retVal;
		 */
	}


	public function getExternalCss ()
	{
		$css = array ();
		return $css;
	}


	public function getJsCall ()
	{
		return '';
	}


	public function getExternalJs ()
	{
		$js = array ();
		return $js;
	}
}

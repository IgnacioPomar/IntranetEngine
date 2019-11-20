<?php
class NodoMenu extends Entity implements ComboData
{
	public $idNodoMenu;
	public $idNodoMenuPadre;
	public $orden;
	public $nombre;
	public $idPlugin;
	public $classCss;
	public $target;


	public static function getTable ()
	{
		return '{nodosMenu}';
	}
	const DEFAULT_FIELD_LIST = array (
			'idNodoMenu',
			'idNodoMenuPadre',
			'orden',
			'nombre',
			'idPlugin',
			'target'
	);

	//@formatter:off
	const FORM_FIELD_LIST = array (
			'idNodoMenu'    	=> array ('hidden',   '',   0, 'idNodoMenu'),
			'orden'   		 	=> array ('hidden',   '',   0, 'orden'),
			'idNodoMenuPadre'   => array ('combo@nodoMenu',   '',   0, 'Men&uacute; Padre'),
			'nombre'      		=> array ('text',     '',  50, 'Nombre'),
			'idPlugin'        	=> array ('combo@pluginMenu',    '',  50, 'Plugin'),
			'classCss'     		=> array ('text', '', 100, 'classCss'),
			'target'      		=> array ('checkbox', '',   0, 'Contenido en otra pestaña')
	);
	// @formatter:on

	// @formatter:on
	public function getComboData ($mysqli)
	{
		$arrayNodosMenu = array ();
		$arrayNodosMenu [0] = 'No, es nodo ra&iacute;z';
		$this->getNodosMenuOrdenados ( $mysqli, $arrayNodosMenu );

		return $arrayNodosMenu;
	}


	public function getNodosMenuOrdenados ($mysqli, &$arrayNodosMenu, $idNodoMenuPadre = 0, $nivel = 0)
	{
		// $sql = 'select * from ' . getTable () . ' where idNodoMenuPadre=' . $idNodoMenuPadre;
		$nodoMenu = new NodoMenu ();
		$nodoMenu->addOrder ( 'orden', 'ASC' );
		$nodoMenu->addCondition ( 'idNodoMenuPadre', '=', $idNodoMenuPadre );
		$sql = CrudModel::getListSql ( $nodoMenu );
		$resultado = CrudModel::executeQuery ( $mysqli, $sql );

		while ( $nodo = $resultado->fetch_assoc () )
		{
			$indentacion = '';
			for($i = $nivel; $i > 0; $i --)
			{
				$indentacion .= '&nbsp;&nbsp;&nbsp;';
			}
			$arrayNodosMenu [$nodo ['idNodoMenu']] = $indentacion . $nodo ['nombre'];
			$this->getNodosMenuOrdenados ( $mysqli, $arrayNodosMenu, $nodo ['idNodoMenu'], $nivel + 1 );
		}
		$resultado->free ();
		return;
	}


	public function setFormFieldlist ()
	{
		$this->formFieldList = self::FORM_FIELD_LIST;
	}


	public function setDefaultFieldlist ()
	{
		$this->fieldList = self::DEFAULT_FIELD_LIST;
	}


	public function setCompleteFieldList ()
	{
		// $this->fieldList = self::DEFAULT_FIELD_LIST;
		$this->fieldList = array_keys ( self::FORM_FIELD_LIST );
	}


	public function getCompleteFieldList ()
	{
		return array_keys ( self::FORM_FIELD_LIST );
	}


	public static function getKeylist ()
	{
		return array (
				'idNodoMenu'
		);
	}


	public function __GET ($k)
	{
		return $this->$k;
	}


	public function __SET ($k, $v)
	{
		return $this->$k = $v;
	}
}
class PluginMenu implements ComboData
{


	public function getComboData ($mysqli)
	{
		$sql = 'SELECT idPlugin,plgMenuName,plgDescription FROM {plugins} WHERE isMenu = 1 ORDER BY plgMenuName;';

		$resultado = CrudModel::executeQuery ( $mysqli, $sql );
		$arrayPlugins = array ();
		$arrayPlugins [] = "Sin plugin asociado";
		while ( $plg = $resultado->fetch_assoc () )
		{
			$arrayPlugins [$plg ['idPlugin']] = $plg ['plgMenuName'] . ' (' . $plg ['plgDescription'] . ')';
		}

		return $arrayPlugins;
	}
}

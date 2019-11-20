<?php
class Plugin
{
	var $mysqli;
	var $idNodoMenu;
	var $plgParams;
	var $uriPrefix;
	
	
	// IdNodoMenu
	public function __construct ($mysqli)
	{
		// FIXME: pendinete de eliminar paramero Opc
		if (! isset ( $_GET ['mn'] ))
		{
			$_GET ['mn'] = "";
		}
		
		$this->mysqli = $mysqli;
		$this->idNodoMenu = $_GET ['mn'];
		$this->uriPrefix = '?mn=' . $this->idNodoMenu . '&';
		$this->plgParams = $this->loadPlgParams ();
	}
	
	
	// -----------------------------------------------------------------------
	// ------------------ Funciones básicas de un plugin --------------------
	// -----------------------------------------------------------------------
	public function setPlgOpcion ($opcion)
	{
		$this->idNodoMenu = $opcion;
	}
	
	
	public function getPlgOpcion ()
	{
		return $this->idNodoMenu;
	}
	
	
	public function loadPlgParams ()
	{
		$plgParams = array ();
		if (is_numeric ( $this->idNodoMenu ))
		{
			$sql = 'SELECT * FROM {plugins_params} WHERE idNodoMenu=' . $this->idNodoMenu;
			$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
			
			while ( $actual = $resultado->fetch_assoc () )
			{
				$plgParams [$actual ['paramName']] = $actual ['paramValor'];
			}
		}
		return $plgParams;
	}
	
	
	public function getPlgParams ($name)
	{
		if (array_key_exists ( $name, $this->plgParams ))
			return $this->plgParams [$name];
			else
			{
				$paramsConf = static::getParamsConf ();
				if (array_key_exists ( $name, $paramsConf ))
					return $paramsConf [$name] ['defaultValue'];
					else
						return FALSE;
			}
	}
	
	
	public function getPlgLink ()
	{
		return "?opc=" . static::class . '&mn=' . $this->getPlgOpcion ();
	}
	
	
	public static function getSkin ()
	{
		return 'skel.htm';
	}
	
	
	public static function getExternalCss ()
	{
		$css = array ();
		return $css;
	}
	
	
	public static function getJsCall ()
	{
		return '';
	}
	
	
	public static function getExternalJs ()
	{
		$js = array ();
		return $js;
	}
	
	
	public static function getRBACPermissions ()
	{
		$perms = array ();
		return $perms;
	}
	
	
	public static function getParamsConf ()
	{
		$conf = array ();
		// $conf ['id'] = ['title'=>"", 'tipo'=>'' , 'defaultValue' =>'1'];
		return $conf;
	}
	
	
	public static function getPlgInfo ()
	{
		$plgInfo = array ();
		$plgInfo ['plgName'] = "Nombre del Plugin";
		$plgInfo ['plgDescription'] = "Descipci&oacute; del Plugin";
		$plgInfo ['isMenu'] = 1;
		$plgInfo ['isMenuAdmin'] = 0;
		$plgInfo ['isSkinable'] = 1;
		
		return $plgInfo;
	}
}

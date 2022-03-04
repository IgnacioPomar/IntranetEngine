<?php

abstract class Plugin
{
	protected $params = array ();
	protected $perms = array ();
	protected $context;
	protected $uriPrefix;


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
		// First round: get The database values
		$sql = 'SELECT paramValues FROM wePlgParams WHERE mnuNode = "' . $this->context->subPath . '" AND plgName="' . get_class () . '";';

		if ($resultado = $this->context->mysqli->query ($sql))
		{
			if ($row = $resultado->fetch_assoc ())
			{
				$rawParams = json_decode ($row ['paramValues'], true);
				foreach ($rawParams as $rawParam)
				{
					$this->params [$rawParam ['name']] = $rawParam ['value'];
				}
			}
		}

		// Second round: set the default values
		$info = $this->getPlgInfo ();
		$infoP = json_decode ($info ['params'], true);
		foreach ($infoP as $cfgParam)
		{
			if (! array_key_exists ($cfgParam ['name'], $this->params))
			{
				$this->params [$cfgParam ['name']] = $cfgParam ['defaultValue'];
			}
		}
	}


	public function checkPerms ()
	{
		// In the database we can have 1 for allowed, and -1 for disallowed.
		// IMPORTANT: We dont have 0 in the database, those records should be erased

		// YAGNI: Create union table with group and user perms
		$sql = 'SELECT permName, MIN(permValue) val FROM ';
		$sql .= '((SELECT permName, permValue FROM wePermissionsUsers WHERE idUser=1) UNION ALL ';
		$sql .= '(SELECT permName, permValue FROM wePermissionsGroup WHERE idGrp IN (SELECT idGrp FROM weUsersGroups WHERE idUser=1))) z ';
		$sql .= 'GROUP BY permName;';

		if ($resultado = $this->context->mysqli->query ($sql))
		{
			while ($row = $resultado->fetch_assoc ())
			{
				$this->perms [$row ['permName']] = ($row ['val'] > 0);
			}
		}

		// Second round: set as false the unset perms
		$info = $this->getPlgInfo ();
		$infoP = json_decode ($info ['perms'], true);
		foreach ($infoP as $cfgPerm)
		{
			if (! array_key_exists ($cfgPerm, $this->perms))
			{
				$this->perms [$cfgPerm] = false;
			}
		}
	}


	// -----------------------------------------------------------------------
	// ------------------ Functions to be overriden --------------------
	// -----------------------------------------------------------------------
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

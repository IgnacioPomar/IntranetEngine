<?php

class EditPermissions extends Plugin
{
	const PERM_VALUES = array (- 1 => 'DENIED', 0 => 'NOT SET', 1 => 'ALLOWED');
	private array $plgssWithPerms;
	private array $currentPerms;


	private static function addPermCombo ($id, $defVal)
	{
		$retval = '<select name="' . $id . '">';
		foreach (self::PERM_VALUES as $idc => $val)
		{
			$sel = '';
			if ($idc == $defVal) $sel = 'selected=""';

			$retval .= "<option value=\"$idc\" $sel>$val</option>";
		}
		$retval .= "</select>";

		return $retval;
	}


	private static function inputIdEncode ($node, $plg, $perm)
	{
		$id = rtrim (strtr (base64_encode ($node), '+/', '-_'), '=');
		$id .= '@' . rtrim (strtr (base64_encode ($plg), '+/', '-_'), '=');
		$id .= '@' . rtrim (strtr (base64_encode ($perm), '+/', '-_'), '=');

		return 'b64@' . $id;
	}


	private static function inputIdDecode ($id)
	{
		$parts = explode ("@", $id);
		$retVal = array ();
		$retVal ['node'] = base64_decode (strtr ($parts [1], '-_', '+/'));
		$retVal ['plg'] = base64_decode (strtr ($parts [2], '-_', '+/'));
		$retVal ['perm'] = base64_decode (strtr ($parts [3], '-_', '+/'));

		return $retVal;
	}


	private function saveGrpPerms ()
	{
		$sep = '';
		$sql = 'INSERT INTO wePermissionsGroup (mnuNode,plgName,idGrp,permName, permValue) VALUES';
		foreach ($_POST as $key => $val)
		{
			if (substr ($key, 0, 4) === 'b64@')
			{

				$id = self::inputIdDecode ($key);

				$sql .= $sep . '(';
				$sql .= '"' . $this->context->mysqli->real_escape_string ($id ['node']) . '",';
				$sql .= '"' . $this->context->mysqli->real_escape_string ($id ['plg']) . '",';
				$sql .= $_POST ['idGrp'] . ',';
				$sql .= '"' . $this->context->mysqli->real_escape_string ($id ['perm']) . '",';
				$sql .= $val . ')';

				$sep = ',';
			}
		}
		if ($sep == ',')
		{
			$sql .= 'ON DUPLICATE KEY UPDATE permValue=VALUES(permValue)';

			$retVal = "<h1>Saving Permissions</h1>";
			if (! $this->context->mysqli->query ($sql))
			{
				$retVal .= '<p class="error">Failed saving permissions.</p>';
			}
			else
			{
				$retVal .= '<p class="success">Permissions saved.</p>';
			}

			return $retVal . $this->showSelector ();
		}

		return '<p class="warning">There was no permmisions to save.</p>';
	}


	private function getStoredValue ($node, $plugin, $perm)
	{
		/*
		 * if (! isset ($this->currentPerms [$node])) return 0;
		 * $nodeGrp = &$this->currentPerms [$node];
		 *
		 * if (! isset ($nodeGrp [$plugin])) return 0;
		 * $plgGrp = &$nodeGrp [$plugin];
		 *
		 * if (! isset ($plgGrp [$perm]))
		 * return 0;
		 * else
		 * return $plgGrp [$perm];
		 */
		return $this->currentPerms [$node] [$plugin] [$perm] ?? 0;
	}


	/**
	 * Show the menu level, with Parameters if the plugin has them
	 *
	 * @param int $parentID
	 * @param array $mnu
	 * @return string
	 */
	private function getMenuLevel (int $level, array &$mnu)
	{
		$ident = str_repeat ('<span class="w-25"></span>', $level);
		$retVal = '';

		foreach ($mnu as $opc)
		{
			$retVal .= '<div class="menuNode"><span class="nodeName">' . $ident . $opc ['name'] . '</span>';

			if ($this->isEditable)
			{
				if (isset ($opc ['opc']))
				{
					$defVal = $this->getStoredValue ($opc ['opc'], $opc ['plg'], '');
					$id = self::inputIdEncode ($opc ['opc'], $opc ['plg'], '');
					$retVal .= self::addPermCombo ($id, $defVal);
				}
			}
			else
			{
				$retVal .= '<span class="NoEditable">No Editable</span>';
			}
			// Aqui el combo selector
			$retVal .= '</div>';

			if (isset ($opc ['opc']) && isset ($this->plgssWithPerms [$opc ['plg']]))
			{
				$extraPerms = json_decode ($this->plgssWithPerms [$opc ['plg']], true);
				foreach ($extraPerms as $perm)
				{
					$retVal .= '<div class="pluginNode"><span class="nodeName">' . $ident . '<span class="pluginPerm">' . $perm . '</span></span>';

					$defVal = $this->getStoredValue ($opc ['opc'], $opc ['plg'], $perm);
					$id = self::inputIdEncode ($opc ['opc'], $opc ['plg'], $perm);
					$retVal .= self::addPermCombo ($id, $defVal);
					$retVal .= '</div>';
				}
			}

			if (isset ($opc ['subOpcs']))
			{
				$retVal .= $this->getMenuLevel ($level + 1, $opc ['subOpcs']);
			}
		}

		return $retVal;
	}


	/**
	 * Show the full Menu
	 *
	 * @return string
	 */
	private function getMainMenu ($desc, $hiddenInput)
	{
		// Load the menu params
		$this->isEditable = $this->context->mnu->isEditable;

		// Finally, show the menu
		$retVal = "<h1>Permissions Management for $desc</h1>";
		if (! $this->isEditable)
		{
			$retVal .= '<p class="info">This is a fixed menu, however it is possible to adjust the parameters.</p>';
		}

		$retVal .= '<form action="' . $this->uriPrefix . '" method="post" autocomplete="off">' . PHP_EOL;
		$retVal .= $hiddenInput . PHP_EOL;

		// YAGNI add the Menu editor HEader
		$retVal .= '<div id="mnuEditor">' . $this->getMenuLevel (0, $this->context->mnu->arrOpcs) . '</div>';
		$retVal .= '<button class="btn" type="submit" value="Save">Save</button>';
		$retVal .= '</form>';

		return $retVal;
	}


	/**
	 * Loads the Parameters in the database
	 */
	private function loadPlgsWithPerms ()
	{
		$this->plgssWithPerms = array ();
		$sql = 'SELECT plgName, plgPerms FROM wePlugins WHERE LENGTH(plgPerms) >2;';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			while ($row = $resultado->fetch_assoc ())
			{
				$this->plgssWithPerms [$row ['plgName']] = $row ['plgPerms'];
			}
		}
	}


	/**
	 * Loads the Parameters in the database
	 */
	private function loadCurrentGroupPerms ($idGrp)
	{
		$this->currentPerms = array ();
		$sql = 'SELECT * FROM wePermissionsGroup WHERE idGrp=' . $_GET ['idGrp'] . ';';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			while ($row = $resultado->fetch_assoc ())
			{
				$this->currentPerms [$row ['mnuNode']] [$row ['plgName']] [$row ['permName']] = $row ['permValue'];
			}
		}
	}


	private function showGrpForm ()
	{
		$this->loadPlgsWithPerms ();
		$this->loadCurrentGroupPerms ($_GET ['idGrp']);

		// Load group name
		$grpName = '';
		$sql = 'SELECT grpName FROM weGroups WHERE idGrp=' . $_GET ['idGrp'] . ';';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			if ($row = $resultado->fetch_assoc ())
			{
				$grpName = $row ['grpName'];
			}
		}

		$hiddenInput = '<input type="hidden" name="idGrp" value="' . $_GET ['idGrp'] . '" />';

		return $this->getMainMenu ('group ' . $grpName, $hiddenInput);
	}


	/**
	 * Show all the groups/users to allow edit permissions
	 *
	 * @return string
	 */
	private function showSelector ()
	{
		$retVal = '<h1>Permissions Management</h1><div class="adminFrame">';

		$retVal .= '<span class="parmissionGroup"><h2>Groups</h2>';
		$sql = 'SELECT idGrp, grpName FROM weGroups ORDER BY grpName;';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			while ($row = $resultado->fetch_assoc ())
			{
				$retVal .= '<a href="' . $this->uriPrefix . 'idGrp=' . $row ['idGrp'] . '"><div>' . $row ['grpName'] . '</div></a>' . PHP_EOL;
			}
		}
		$retVal .= '</span>';

		$retVal .= '<span class="parmissionGroup"><h2>Users</h2>';
		$sql = 'SELECT u.idUser,u.name,g.groups  FROM weUsers u ';
		$sql .= ' LEFT JOIN  (SELECT GROUP_CONCAT(grpName  SEPARATOR ", ") AS groups, rel.idUser FROM weGroups g INNER JOIN weUsersGroups rel ON g.idGrp = rel.idGrp GROUP BY rel.idUser) g';
		$sql .= ' ON u.idUser=g.idUser';

		if ($resultado = $this->context->mysqli->query ($sql))
		{
			while ($row = $resultado->fetch_assoc ())
			{
				$retVal .= '<a href="' . $this->uriPrefix . 'idUsr=' . $row ['idUser'] . '"><div>' . $row ['name'] . ' <span class="groups">(' . $row ['groups'] . ')</span></div></a>' . PHP_EOL;
			}
		}
		$retVal .= '</span>';

		return $retVal . '</div>';
	}


	/**
	 *
	 * @param mysqli $mysqli
	 */
	public function __construct (Context $context)
	{
		parent::__construct ($context);
	}


	public static function getPlgInfo (): array
	{
	}


	/**
	 *
	 * @param mysqli $mysqli
	 * @return string
	 */
	public function main ()
	{
		if (isset ($_GET ['idGrp']))
		{
			return $this->showGrpForm ();
		}
		else if (isset ($_GET ['idUsr']))
		{
		}
		else if (isset ($_POST ['idGrp']))
		{
			return $this->saveGrpPerms ();
		}
		else if (isset ($_POST ['idUsr']))
		{
		}
		else
		{
			return $this->showSelector ();
		}
		return "";
	}
}

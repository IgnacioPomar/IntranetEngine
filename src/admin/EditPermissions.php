<?php

class EditPermissions extends Plugin
{
	const PERM_VALUES = array (- 1 => 'DENIED', 0 => 'NOT SET', 1 => 'ALLOWED');
	private array $plgssWithPerms;
	private array $currentPerms;


	private static function addPermCombo ($id, $defVal)
	{
		$retval = '<select id="' . $id . '">';
		foreach (self::PERM_VALUES as $idc => $val)
		{
			$sel = '';
			if ($idc == $defVal) $sel = 'selected=""';

			$retval .= "<option value=\"$idc\" $sel>$val</option>";
		}
		$retval .= "</select>";

		return $retval;
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
			$retVal .= '<div><span class="nodeName">' . $ident . $opc ['name'] . '</span>';

			if ($this->isEditable)
			{
				if (isset ($opc ['opc']))
				{
					$defVal = $this->currentPerms [$mnu ['node']] [$mnu ['plg']] [''] ?? 0;
					$id = $opc ['opc'] . '^' . $opc ['plg'] . '^';
					$retVal .= self::addPermCombo ($id, $defVal);
				}
			}
			else
			{
				$retVal .= '<span class="NoEditable">No Editable</span>';
			}
			// Aqui el combo selector
			$retVal .= '</div>';

			if (isset ($opc ['opc']) && isset ($this->plgssWithPerms [$opc [plg]]))
			{
				$extraPerms = json_decode ($this->plgssWithPerms [$opc [plg]], true);
				foreach ($extraPerms as $perm)
				{
					$retVal .= '<div><span class="nodeName">' . $ident . '<span class="pluginPerm">' . $perm . '</span></span>';

					$defVal = $this->currentPerms [$mnu ['node']] [$mnu ['plg']] [$perm] ?? 0;
					$id = $opc ['opc'] . '^' . $opc ['plg'] . '^' . $perm;
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
	private function getMainMenu ($desc)
	{
		// Load the menu params
		$this->isEditable = $this->context->mnu->isEditable;

		// Finally, show the menu
		$retVal = "<h1>Permissions Management for $desc</h1>";
		if (! $this->isEditable)
		{
			$retVal .= '<p class"warning"> This is a fixed Menu. Is possible adjust params.</p>';
		}

		$retVal .= '<div id="mnuEditor">' . $this->getMenuLevel (0, $this->context->mnu->arrOpcs) . '</div>';

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

		return $this->getMainMenu ('group ' . $grpName);
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

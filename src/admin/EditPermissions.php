<?php
require_once $GLOBALS ['basePath'] . 'src/ColumnFormatter.php';
require_once $GLOBALS ['basePath'] . 'src/AutoForm.php';

class EditPermissions extends Plugin
{
	private $jsonFile;


	/**
	 *
	 * @param mysqli $mysqli
	 */
	public function __construct (Context $context)
	{
		parent::__construct ($context);
		$this->jsonFile = $GLOBALS ['basePath'] . 'src/tables/permissionsGroup.jsonTable';
	}
	const COLS_TABLE_MENU = array ('name' => array ('w-400', 'Menu name', 'Name displayed in the menu'));


	/**
	 *
	 * @return string
	 */
	private function loadMenu ($idGrp)
	{
		require_once ($GLOBALS ['moduleMenu']);

		$menuLoader = basename ($GLOBALS ['moduleMenu'], '.php');
		$menuLoaderClass = new $menuLoader ();
		$menuArray = $menuLoaderClass->getArrayMenu ($this->context->mysqli);

		$autoForm = new AutoForm ($this->jsonFile);
		$autoForm->set = array ();

		$retVal = '';

		if (! empty ($_POST ['idGrp']))
		{
			$retVal .= $this->updatePermissionsGroupTable ($idGrp);
		}
		else
		{
			// YAGNI: Add group name to title
			$retVal = '<h1>Edit group perm</h1>';
			$autoForm->setHidden ('idGrp', $idGrp);
			$this->setHiddenMnuNode ($autoForm, $menuArray);
		}

		$this->addPermValues ($menuArray);

		$formatter = new ColumnFormatter (self::COLS_TABLE_MENU);

		$retVal .= '<div class="head">';
		$retVal .= $formatter->getHeaderCols ();
		$retVal .= '<div class="w-400">Show</div></div>';

		$autoForm->externalFooterHTML = $this->showMenuItems ($formatter, $menuArray, ! empty ($_POST ['idGrp']));
		$retVal .= $autoForm->generateForm ([ ]);

		$retVal .= '</div>';

		return $retVal;
	}


	private function setHiddenMnuNode (&$autoForm, $menuArray)
	{
		foreach ($menuArray as $itemMenu)
		{
			$autoForm->setHidden ("mnuNode{$itemMenu['plg']}", $itemMenu ['opc']);
			if (isset ($itemMenu ['subOpcs']))
			{
				$this->setHiddenMnuNode ($autoForm, $itemMenu ['subOpcs']);
			}
		}
	}


	private function updatePermissionsGroupTable ($idGrp)
	{
		$query = "DELETE FROM wePermissionsGroup WHERE idGrp = {$idGrp}";
		$this->context->mysqli->query ($query);

		$query = 'INSERT INTO wePermissionsGroup (mnuNode, plgName, idGrp, permName, permValue) VALUES (?, ?, ?, ?, ?)';
		$stmt = $this->context->mysqli->prepare ($query);

		foreach ($_POST as $plgName => $value)
		{
			if (is_numeric ($value))
			{
				if ($stmt->bind_param ("ssisi", $_POST ["mnuNode{$plgName}"], $plgName, $idGrp, $plgName, $value))
				{
					$stmt->execute ();
				}
			}
		}

		$retVal = "<p>Registro Actualizado Correctamente</p>";
		$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-counterclockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466z"/></svg>';
		$retVal .= '<div class="container"><a class="btn rigth" href="' . strtok ($this->uriPrefix, '?') . '">' . $icon . 'Volver</a></div>';

		return $retVal;
	}


	/**
	 *
	 * @param array $menu
	 */
	private function addPermValues (&$menu)
	{
		$query = "SELECT * FROM wePermissionsGroup WHERE idGrp = {$_GET['idGrp']}";
		$resParams = $this->context->mysqli->query ($query);

		$groupPerms = array ();
		while ($row = $resParams->fetch_assoc ())
		{
			$groupPerms [$row ['plgName']] = $row ['permValue'];
		}

		$this->mergePermsFromDB ($menu, $groupPerms);
	}


	/**
	 *
	 * @param array $menu
	 * @param array $plgParams
	 */
	private function mergePermsFromDB (&$menu, $plgParams)
	{
		foreach ($menu as &$itemMenu)
		{
			$itemMenu ['permValue'] = $plgParams [$itemMenu ['plg']] ?? 0;

			if (isset ($itemMenu ['subOpcs']))
			{
				$this->mergePermsFromDB ($itemMenu ['subOpcs'], $plgParams);
			}
		}
	}


	/**
	 *
	 * @param object $formatter
	 * @param array $items
	 * @param number $ident
	 * @return string
	 */
	private function showMenuItems ($formatter, $items, $isDisabled, $ident = 0)
	{
		$retVal = '';
		foreach ($items as $itemMenu)
		{
			// We instantiate the class to format the name for have the correct indentation for each menu item
			$formatter->stylers ['name'] = new FormatterNameColumn ($ident);

			$retVal .= '<div class="line">';
			$retVal .= $formatter->getStyledBodyCols ($itemMenu);

			$disabled = '';
			$checkedYes = '';
			$checkedNo = '';

			if ($isDisabled)
			{
				$disabled = 'disabled';
			}

			if ($itemMenu ['permValue'] == 1)
			{
				$checkedYes = 'checked';
			}
			if ($itemMenu ['permValue'] == - 1)
			{
				$checkedNo = 'checked';
			}

			$retVal .= '<div class="w-400">';
			$retVal .= "<input type='radio' id='yes' name='{$itemMenu['plg']}' value='1' $disabled $checkedYes><label for='{$itemMenu['plg']}'>Yes</label>";
			$retVal .= "<input type='radio' id='no' name='{$itemMenu['plg']}' value='-1' $disabled $checkedNo><label for='{$itemMenu['plg']}'>No</label>";
			$retVal .= '</div>';

			$retVal .= "</div>";

			if (isset ($itemMenu ['subOpcs']))
			{
				$retVal .= $this->showMenuItems ($formatter, $itemMenu ['subOpcs'], $isDisabled, $ident + 1);
			}
		}

		return $retVal;
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
		// TODO: Check if menu is from db
		if (! empty ($_GET ['idGrp']))
		{
			$retVal = $this->loadMenu ($_GET ['idGrp']);
		}
		else
		{
			require_once 'MaintenanceGroups.php';
			$groups = new MaintenanceGroups ($this->context);
			$retVal = $groups->main ($this->context->mysqli);
		}

		return $retVal;
	}
}

class FormatterNameColumn
{
	private $ident;


	/**
	 *
	 * @param int $ident
	 */
	public function __construct ($ident)
	{
		$this->ident = $ident;
	}


	/**
	 *
	 * @param mixed $val
	 * @param string $class
	 * @return string
	 */
	public function getSpan ($val, $class)
	{
		$retVal = "<div class='$class d-flex-inline'>";
		while (0 < $this->ident --)
		{
			$retVal .= '<div class="w-25"></div>';
		}
		$retVal .= "<div>$val</div>";
		$retVal .= '</div>';
		return $retVal;
	}
}


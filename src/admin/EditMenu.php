<?php
require_once $GLOBALS ['basePath'] . 'src/ColumnFormatter.php';
require_once $GLOBALS ['basePath'] . 'src/AutoForm.php';

class EditMenu extends Plugin
{
	// @formatter:off
	const COLS_TABLE_MENU = array (
			'name'			=> array ('w-300', 'Name', 'Menu node name.'),
			'uri'			=> array ('w-200', 'uri', 'uri in the server'),
			'plg' 			=> array ('w-300', 'Plugin', 'Plugin'),
			'tmplt'			=> array ('w-200', 'Template', 'Platilla utilizada por el plugin'),
			''				=> array ('',  'Activo', 'Indica si es visible para los usuarios'),
	);
	// @formatter:on
	private $jsonFile;
	private bool $isEditable;


	public function __construct (Context &$context)
	{
		parent::__construct ($context);
		$this->jsonFile = $GLOBALS ['basePath'] . 'src/tables/mainMenu.jsonTable';
	}
	const sentido_VALUES = array (0 => 'buy', 1 => 'sell');


	/**
	 *
	 * @return string
	 */
	private function showMenuFromDB ()
	{
		$query = "SELECT * FROM weMenu ORDER BY idNodoParent, menuOrder";
		$menuDB = $this->mysqli->query ($query)->fetch_all (MYSQLI_ASSOC);
		$orderMenuDb = array ();

		foreach ($menuDB as &$parentItem)
		{
			// If the menu contains a parent we stop the loop since it should be assigned.
			// if ($parentItem ['idNodoParent'] != NULL) break;

			$orderMenuDb [$parentItem ['idNodo']] = $parentItem;
			// We delete the first element, since it will be the one we just saved in our new array
			array_shift ($menuDB);

			$childs = $this->addChildsMenuItems ($menuDB, $parentItem ['idNodo']);
			if (! empty ($childs [$parentItem ['idNodo']]))
			{
				$orderMenuDb [$parentItem ['idNodo']] ['subOpcs'] = $childs [$parentItem ['idNodo']];
			}
		}

		$formatter = new ColumnFormatter (self::COLS_TABLE_MENU);

		$retVal = "<div class='head'>{$formatter->getHeaderCols ()}</div>";

		$retVal .= $this->showMenuItems ($formatter, $orderMenuDb);

		return $retVal;
	}


	/**
	 *
	 * @param array $menuDB
	 * @param int $parentId
	 * @return array $childsOfMenu
	 */
	private function addChildsMenuItems (&$menuDB, $parentId)
	{
		$childsOfMenu = array ();
		foreach ($menuDB as $index => $childItem)
		{
			if ($childItem ['idNodoParent'] == $parentId)
			{
				$childsOfMenu [$parentId] [$childItem ['idNodo']] = $childItem;
				unset ($menuDB [$index]);

				$childs = $this->addChildsMenuItems ($menuDB, $childItem ['idNodo']);
				if (! empty ($childs [$childItem ['idNodo']]))
				{
					$childsOfMenu [$parentId] [$childItem ['idNodo']] ['subOpcs'] = $childs [$childItem ['idNodo']];
				}
			}
		}
		if (! empty ($childsOfMenu)) return $childsOfMenu;
	}


	/**
	 *
	 * @param object $formatter
	 * @param array $items
	 * @return string
	 */
	private function showMenuItems ($formatter, $items, $ident = 0)
	{
		$retVal = '';
		foreach ($items as $itemMenu)
		{
			$retVal .= '<div class="d-flex">';
			$retVal .= '<div class="line" style="transform: translateX(' . $ident . 'px);">';
			$retVal .= $formatter->getStyledBodyCols ($itemMenu);
			$retVal .= "</div>";

			$retVal .= '<input type="checkbox" ' . ($itemMenu ['isEnable'] ? 'checked' : '') . ' disabled>';

			$retVal .= '<span class="w-100"></span>';
			$link = "{$_SERVER ["REQUEST_URI"]}&idItemMenu={$itemMenu['idNodo']}";
			$retVal .= "<a href='$link'><span class='w-50'><svg xmlns='http: // www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-fill' viewBox='0 0 16 16'><path d='M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z'/></svg></span></a>";
			$retVal .= "</div>";

			if (isset ($itemMenu ['subOpcs']))
			{
				$retVal .= $this->showMenuItems ($formatter, $itemMenu ['subOpcs'], $ident + 15);
			}
		}

		return $retVal;
	}


	private function showEditView ($idItem)
	{
		$retVal = '';

		$fields = array ('plgName' => '');

		$autoForm = new AutoForm ($this->jsonFile);

		if (isset ($_POST ['idNodo']))
		{
			$_POST ['isEnable'] = $_POST ['isEnable'] ?? 0;

			$autoForm->mysqli = $this->mysqli;
			$sql = $autoForm->getUpdateSql ([ ], [ 'idNodo']);

			$this->mysqli->query ($sql);
			$retVal = "<p>Registro Actualizado Correctamente</p>";
			$retVal .= '<div class="container">';
			$retVal .= '<a  class="btn rigth" href=' . strtok ($_SERVER ['REQUEST_URI'], '?') . '?a=mnu><i class="material-icons">replay</i>Volver</a>';
			$retVal .= '</div>';
			return $retVal;
		}

		$fields ['idNodo'] = $idItem;

		$sql = "SELECT * FROM weMenu WHERE idNodo = $idItem";
		if ($res = $this->mysqli->query ($sql))
		{
			$itemMenu = $res->fetch_assoc ();
			$fields = array_merge ($fields, $itemMenu);

			$autoForm->setHidden ('idNodo', $fields ['idNodo']);
			unset ($fields ['idNodo']);
			unset ($fields ['menuOrder']);
			if (empty ($fields ['idNodoParent'])) unset ($fields ['idNodoParent']);

			$autoForm->set = array_keys ($fields);
			$retVal .= $autoForm->generateForm ($fields);
		}

		return $retVal;
	}

	// -----------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------
	private array $plgssWithParams;
	private array $configuredNodes;


	private function loadPlgsWithParams ()
	{
		$this->plgssWithParams = array ();
		$sql = 'SELECT plgName, plgParams FROM wePlugins WHERE LENGTH(plgParams) >2;';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			if ($row = $resultado->fetch_assoc ())
			{
				$this->plgssWithParams [$row ['plgName']] = $row ['plgParams'];
			}
		}

		$this->configuredNodes = array ();
		$sql = 'SELECT * FROM wePlgParams;';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			if ($row = $resultado->fetch_assoc ())
			{
				$this->nodesWithOpts [$row ['mnuNode']] [$row ['plgName']] = $row ['paramValues'];
			}
		}
	}


	private function showCurrentNodeParams ($paramValues, $paramDefinition)
	{
		// YAGNI: USe the definition to bbetr show the results
		$paramValues = json_decode ($paramValues);
		$retVal = '';
		foreach ($paramValues as $paramName => $paramValue)
		{
			$retVal .= '<b>' . $paramName . '</b>: ' . $paramValue . '<br >';
		}
		return $retVal;
	}


	/**
	 * Show the menu level, with Parameters if the plugin has them
	 *
	 * @param int $parentID
	 * @param array $mnu
	 * @return string
	 */
	private function getMenuLevel (int $parentID, array &$mnu)
	{
		$retVal = '<ul>';

		if ($this->isEditable)
		{
			$retVal .= '<li><a href="' . $this->uriPrefix . 'acc=newNodo&parent=' . $parentID . '&pos=ini">Add node to beginning</a></li>' . PHP_EOL;
		}

		foreach ($mnu as $opc)
		{
			$retVal .= '<li>' . $opc ['name'];

			// TODO: if ($this->isEditable) {//Add Move or delete options}

			if (isset ($opc ['opc']) && isset ($opc ['plg']) && isset ($this->plgssWithParams [$opc ['plg']]))
			{
				$base64Nde = rtrim (strtr (base64_encode ($opc ['opc']), '+/', '-_'), '=');
				$retVal .= '<a href="' . $this->uriPrefix . 'acc=editParams&node=' . $base64Nde . '">Edit Params <span class="Params">';
				if (isset ($this->nodesWithOpts [$opc ['opc']] [$opc ['plg']]))
				{
					$retVal .= $this->showCurrentNodeParams ($this->nodesWithOpts [$opc ['opc']] [$opc ['plg']], $this->plgssWithParams [$opc ['plg']]);
				}
				else
				{
					$retVal .= 'Using Default Params';
				}
				$retVal .= '</span></a>' . PHP_EOL;
			}

			$retVal .= '</li>' . PHP_EOL;

			if (isset ($opc ['subOpcs']))
			{
				// Only DB has $opc ['idNodo']
				$idNodo = (isset ($opc ['idNodo'])) ? $opc ['idNodo'] : 0;
				$retVal .= $this->getMenuLevel ($idNodo, $opc ['subOpcs']);
			}
		}

		if ($this->isEditable && count ($mnu) > 0)
		{
			$retVal .= '<li><a href="' . $this->uriPrefix . 'acc=newNodo&parent=' . $parentID . '&pos=end">Add node at end</a></li>' . PHP_EOL;
		}

		return $retVal . '</ul>';
	}


	/**
	 * Show the full Menu
	 *
	 * @return string
	 */
	private function getMainMenu ()
	{
		// Load the menu params
		$this->loadPlgsWithParams ();
		$this->isEditable = $this->context->mnu->isEditable;

		// Finally, show the menu
		$retVal = '<h1>Menu maintenance</h1>';
		if (! $this->isEditable)
		{
			$retVal .= '<p class"warning"> This is a fixed Menu. Is possible adjust params.</p>';
		}

		$retVal .= '<div id="mnuEditor">' . $this->getMenuLevel (0, $this->context->mnu->arrOpcs) . '</div>';

		return $retVal;
	}


	public function main ()
	{

		// $editMenu = new EditMenu ($context);
		/*
		 *
		 * if (! empty ($_GET ['idItemMenu']))
		 * {
		 * $retVal .= $this->showEditView ($_GET ['idItemMenu']);
		 * }
		 * else
		 * {
		 * // TODO Consider whether to call the menuLoaderDB file to use its selection and array formatting functions
		 * $retVal .= $this->showMenuFromDB ();
		 * }
		 *
		 * $retVal = str_replace ('@@content@@', $retVal, file_get_contents ($GLOBALS ['basePath'] . 'src/rsc/html/editViews.htm'));
		 */
		if (isset ($_GET ['acc']))
		{
			switch ($_GET ['acc'])
			{
			}
		}
		else
		{
			return $this->getMainMenu ();
		}
	}


	public static function getPlgInfo (): array
	{
	}
}
<?php
require_once $GLOBALS ['basePath'] . 'src/ColumnFormatter.php';
require_once $GLOBALS ['basePath'] . 'src/AutoForm.php';

class EditMenu extends Plugin
{
	private static array $pluginList;
	private static array $templateList;
	private array $plgssWithParams;
	private array $configuredNodes;
	private bool $isEditable;


	public function __construct (Context &$context)
	{
		parent::__construct ($context);
	}


	// -----------------------------------------------------------------------------------------------------
	// ------------------------------------------- NODE UTILS ----------------------------------------------
	// -----------------------------------------------------------------------------------------------------
	private function disableNode ()
	{
		$retVal = "<h1>Disabbling node</h1>";
		$sql = 'UPDATE weMenu SET isEnable=0 WHERE idNodo=' . intval ($_GET ['nodeId']);
		if (! $this->context->mysqli->query ($sql))
		{
			$retVal .= '<p class="error">Failed disabling node.</p>';
		}
		else
		{
			$retVal .= '<p class="success">Node disabled.</p>';
		}

		// Reload the menu
		$menuLoader = basename ($GLOBALS ['moduleMenu'], '.php');
		$menuLoader::load ($this->context, $this->context->mnu);

		return $retVal;
	}


	private function enableNode ()
	{
		$retVal = "<h1>Enabling node</h1>";
		$sql = 'UPDATE weMenu SET isEnable=1 WHERE idNodo=' . intval ($_GET ['nodeId']);
		if (! $this->context->mysqli->query ($sql))
		{
			$retVal .= '<p class="error">Failed enabling node.</p>';
		}
		else
		{
			$retVal .= '<p class="success">Node enabled.</p>';
		}

		// Reload the menu
		$menuLoader = basename ($GLOBALS ['moduleMenu'], '.php');
		$menuLoader::load ($this->context, $this->context->mnu);

		return $retVal;
	}


	private function changeNodeOrder ()
	{
		$retVal = "<h1>Change Node Order</h1>";
		$dir = (0 == intval ($_GET ['d'])) ? '-1' : '+1';
		$sql = 'UPDATE weMenu SET menuOrder=menuOrder' . $dir . ' WHERE idNodo=' . intval ($_GET ['nodeId']);
		if (! $this->context->mysqli->query ($sql))
		{
			$retVal .= '<p class="error">Failed enabling node.</p>';
		}
		else
		{
			$retVal .= '<p class="success">Node enabled.</p>';
		}

		// Reload the menu
		$menuLoader = basename ($GLOBALS ['moduleMenu'], '.php');
		$menuLoader::load ($this->context, $this->context->mnu);

		return $retVal;
	}


	// -----------------------------------------------------------------------------------------------------
	// -------------------------------------- ADD NEW NODE TO MENU -----------------------------------------
	// -----------------------------------------------------------------------------------------------------
	public static function getPlugins ()
	{
		return self::$pluginList;
	}


	public static function getTemplates ()
	{
		return self::$templateList;
	}


	private function loadStaticData ()
	{
		// Load existing plugins
		$plgs = array ();
		$sql = 'SELECT plgName, plgDescrip FROM wePlugins;';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			while ($row = $resultado->fetch_assoc ())
			{
				$plgs [$row ['plgName']] = $row ['plgDescrip'];
			}
		}
		self::$pluginList = $plgs;

		// Load existing templates
		$tmplts = array ();
		$files = glob ($GLOBALS ['templatePath'] . '*.htm');
		foreach ($files as $filePath)
		{
			$fileName = basename ($filePath);
			$tmplts [$fileName] = $fileName;
		}

		self::$templateList = $tmplts;
	}


	private function addNewNode ()
	{
		$mnuSchema = $GLOBALS ['basePath'] . 'src/tables/mainMenu.jsonTable';
		$retVal = '<h1>New Data Form</h1>';

		// We can select the order and wich fields we want to use to create
		if (isset ($_POST ['uri']))
		{
			// Extra values
			$_POST ['isEnable'] = true;

			$autoForm = new AutoForm ($mnuSchema);
			$autoForm->mysqli = $this->context->mysqli;
			$sql = $autoForm->getInsertSql ($_POST);

			if (! $this->context->mysqli->query ($sql))
			{
				$retVal .= '<p class="error">Failed to add node.</p>';
			}
			else
			{
				$retVal .= '<p class="success">Node added.</p>';
			}

			// Reload the menu
			$menuLoader = basename ($GLOBALS ['moduleMenu'], '.php');
			$menuLoader::load ($this->context, $this->context->mnu);

			$retVal .= $this->getMainMenu ();
		}
		else
		{
			$this->loadStaticData ();
			$autoForm = new AutoForm ($mnuSchema);
			$autoForm->set = array ('uri', 'plg', 'name', 'tmplt', 'isVisible');

			$autoForm->setHidden ('idNodoParent', $_GET ['parent']);
			$autoForm->setHidden ('isEnable', 1);
			$autoForm->setHidden ('menuOrder', 0);

			// Set default Values
			$defVals = array ();
			$defVals ['isVisible'] = 1;
			$defVals ['tmplt'] = 'skel.htm';
			$defVals ['uri'] = '/';
			$retVal .= $autoForm->generateForm ($defVals);
		}

		return $retVal;
	}


	// -----------------------------------------------------------------------------------------------------
	// ---------------------------------------- EDIT PARAMETERS --------------------------------------------
	// -----------------------------------------------------------------------------------------------------
	/**
	 * Menu for node params editing
	 *
	 * @return string
	 */
	private function editNodeParams ()
	{
		if (isset ($_POST ['node']))
		{
			return $this->editNodeParamsSave () . $this->getMainMenu ();
		}
		else
		{
			return $this->editNodeParamsShowForm ();
		}
	}


	/**
	 * Save the results in the database
	 *
	 * @return string
	 */
	private function editNodeParamsSave ()
	{
		$node = base64_decode (strtr ($_POST ['node'], '-_', '+/'));
		$nodeVals = $this->getNodeParamsValues ($node);

		$paramDef = json_decode ($nodeVals ['definition'], true);

		$paramValues = array ();
		foreach ($paramDef as $param)
		{
			$paramValues [$param ['name']] = $_POST [$param ['name']];
		}

		$paramValuesStr = $this->context->mysqli->real_escape_string (json_encode ($paramValues));
		$plg = $nodeVals ['opc'] ['plg'];

		$sql = 'INSERT INTO wePlgParams (mnuNode,plgName,paramValues)';
		$sql .= " VALUES ('$node','$plg','$paramValuesStr')";
		$sql .= "ON DUPLICATE KEY UPDATE paramValues='$paramValuesStr'";

		$retVal = "<h1>Saving node parameters [$node]</h1>";
		if (! $this->context->mysqli->query ($sql))
		{
			$retVal .= '<p class="error">Failed to save new parameters.</p>';
		}
		else
		{
			$retVal .= '<p class="success">Parameters changed.</p>';
		}

		// Reload the menu
		$menuLoader = basename ($GLOBALS ['moduleMenu'], '.php');
		$menuLoader::load ($this->context, $this->context->mnu);

		$retVal .= $this->getMainMenu ();

		return $retVal;
	}


	/**
	 * Loads the data from the database
	 *
	 * @param string $node
	 * @return array
	 */
	private function getNodeParamsValues ($node)
	{
		$opc = $this->context->mnu->getOpcFromMenu ($node);

		// Load current Values
		$vals = '[]';
		$sql = 'SELECT paramValues FROM wePlgParams WHERE plgName="' . $opc ['plg'] . '" AND mnuNode="' . $node . '";';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			if ($row = $resultado->fetch_assoc ())
			{
				$vals = $row ['paramValues'];
			}
		}

		// Load Parameters definition
		$definition = '[]';
		$sql = 'SELECT plgParams FROM wePlugins WHERE plgName="' . $opc ['plg'] . '";';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			if ($row = $resultado->fetch_assoc ())
			{
				$definition = $row ['plgParams'];
			}
		}

		return array ('vals' => $vals, 'definition' => $definition, 'opc' => $opc);
	}


	/**
	 * Show the for to edit the fields
	 *
	 * @return string
	 */
	private function editNodeParamsShowForm ()
	{
		$node = base64_decode (strtr ($_GET ['node'], '-_', '+/'));
		$nodeVals = $this->getNodeParamsValues ($node);
		$paramVals = json_decode ($nodeVals ['vals'], true);
		$paramDef = json_decode ($nodeVals ['definition'], true);

		// Finally, we return the values
		$retVal = '<h1>Edit node Parameters [' . $node . ']</h1>';
		$retVal .= '<form action="' . $this->uriPrefix . 'acc=editParams' . '" method="post" autocomplete="off">' . PHP_EOL;
		$retVal .= '<input type="hidden" name="node" value="' . $_GET ['node'] . '" />' . PHP_EOL;

		foreach ($paramDef as $param)
		{
			$currVal = isset ($paramVals [$param ['name']]) ? $paramVals [$param ['name']] : $param ['defaultValue'];
			$fieldInfo = array ();
			$fieldInfo ['type'] = $param ['type'];

			$retVal .= Autoform::getFormField ($param ['name'], $fieldInfo, $currVal, false);
		}

		$retVal .= '<button class="btn" type="submit" value="Grabar">Grabar</button>';
		$retVal .= '</form>';

		return $retVal;
	}


	// -----------------------------------------------------------------------------------------------------
	// -------------------------------------- SHOW THE MENU EDITOR -----------------------------------------
	// -----------------------------------------------------------------------------------------------------

	/**
	 * Loads the Parameters in the database
	 */
	private function loadPlgsWithParams ()
	{
		$this->plgssWithParams = array ();
		$sql = 'SELECT plgName, plgParams FROM wePlugins WHERE LENGTH(plgParams) >2;';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			while ($row = $resultado->fetch_assoc ())
			{
				$this->plgssWithParams [$row ['plgName']] = $row ['plgParams'];
			}
		}

		$this->configuredNodes = array ();
		$sql = 'SELECT * FROM wePlgParams;';
		if ($resultado = $this->context->mysqli->query ($sql))
		{
			while ($row = $resultado->fetch_assoc ())
			{
				$this->nodesWithOpts [$row ['mnuNode']] [$row ['plgName']] = $row ['paramValues'];
			}
		}
	}


	/**
	 *
	 * @param string $paramValues
	 *        	Values in JSOn format
	 * @param string $paramDefinition
	 *        	Parameters definition in JSOn Format
	 * @return string
	 */
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
	private function getMenuLevel (int $level, int $parentID, array &$mnu)
	{
		$ident = str_repeat ('<span class="w-25"></span>', $level);
		$retVal = '';

		if ($this->isEditable)
		{
			$text = ($level == 0) ? 'Add menu root node' : 'Add menu child node';
			$link = $this->uriPrefix . 'acc=newNodo&parent=' . $parentID . '&pos=ini';
			$retVal .= '<div class="newNodeLink"><span class="nodeId"></span><span class="nodeName">';
			$retVal .= "$ident<a href=\"$link\">$text</a></span></div>";
		}

		foreach ($mnu as $opc)
		{
			$isEnabled = (isset ($opc [isEnable])) ? (1 == $opc [isEnable]) : true;
			$extraClass = '';
			$txtNodeId = $opc ['opc'] ?? '';

			if (! $isEnabled)
			{
				$extraClass = 'disabled';
				$txtNodeId .= ' (disabled)';
			}

			$fullInfo = '<b>NodeId</b>:' . $opc ['opc'] . '<br />';
			$fullInfo .= '<b>Plugin</b>:' . $opc ['plg'] . '<br />';
			$fullInfo .= '<b>Show In tree</b>:' . (($opc ['show'] == 1) ? 'true' : 'false') . '<br />';
			$fullInfo .= '<b>title</b>:' . $opc ['name'] . '<br />';
			$fullInfo .= '<b>template</b>:' . $opc ['tmplt'] . '<br />';

			$retVal .= '<div class="menuNode ' . $extraClass . '"><span class="nodeId">' . $txtNodeId . '</span>';
			$retVal .= '<span class="nodeName">' . $ident . $opc ['name'] . '<span class="PopupInfo">' . $fullInfo . '</span></span>';

			if (! $isEnabled)
			{
				// We may reenable, but nothing else
				$retVal .= '<span class="nodeOpc">';
				$retVal .= '<a href="' . $this->uriPrefix . 'acc=enable&nodeId=' . $opc ['idNodo'] . '">Enable </a>';
				$retVal .= '</span>';
			}
			else
			{
				// Menu options
				if ($this->isEditable)
				{
					// YAGNI: convert in FORMs with post send
					// links for Delete/Move/Create nodes
					$retVal .= '<span class="nodeOpc">';
					$retVal .= '<a href="' . $this->uriPrefix . 'acc=edit&nodeId=' . $opc ['idNodo'] . '">Edit</a>';
					$retVal .= '<a href="' . $this->uriPrefix . 'acc=disable&nodeId=' . $opc ['idNodo'] . '">Disable</a>';
					$retVal .= '<a href="' . $this->uriPrefix . 'acc=orderCgh&d=0&nodeId=' . $opc ['idNodo'] . '">Up</a>';
					$retVal .= '<a href="' . $this->uriPrefix . 'acc=orderCgh&d=1&nodeId=' . $opc ['idNodo'] . '">Down</a>';
					$retVal .= '<a href="' . $this->uriPrefix . 'acc=delete&nodeId=' . $opc ['idNodo'] . '">Delete</a>';
					$retVal .= '</span>';
				}

				// Params editor
				if (isset ($opc ['opc']) && isset ($opc ['plg']) && isset ($this->plgssWithParams [$opc ['plg']]))
				{
					$base64Nde = rtrim (strtr (base64_encode ($opc ['opc']), '+/', '-_'), '=');
					$retVal .= '<a href="' . $this->uriPrefix . 'acc=editParams&node=' . $base64Nde . '">Edit Params <span class="PopupInfo">';
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
			}

			$retVal .= '</div>' . PHP_EOL;

			// Only DB has $opc ['idNodo']
			$idNodo = (isset ($opc ['idNodo'])) ? $opc ['idNodo'] : 0;

			if (isset ($opc ['subOpcs']))
			{
				$retVal .= $this->getMenuLevel ($level + 1, $idNodo, $opc ['subOpcs']);
			}
			else if ($this->isEditable && $isEnabled)
			{
				$fakeSubOpc = array ();
				$retVal .= $this->getMenuLevel ($level + 1, $idNodo, $fakeSubOpc);
			}
		}

		return $retVal; // . '</ul>';
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
			$retVal .= '<p class="info">This is a fixed menu, however it is possible to adjust the parameters.</p>';
			// $retVal .= '<p class"warning"> This is a fixed Menu. Is possible adjust params.</p>';
		}

		$retVal .= '<div id="mnuEditor">' . $this->getMenuLevel (0, 0, $this->context->mnu->arrOpcs) . '</div>';

		return $retVal;
	}


	// -----------------------------------------------------------------------------------------------------
	// ----------------------------------------- MAIN FUNCTION --------------------------------------------
	// -----------------------------------------------------------------------------------------------------
	public function main ()
	{
		if (isset ($_GET ['acc']))
		{
			switch ($_GET ['acc'])
			{
				case 'editParams':
					return $this->editNodeParams ();
					break;
				case 'newNodo':
					return $this->addNewNode ();
					break;
				case 'disable':
					return $this->disableNode () . $this->getMainMenu ();
					break;
				case 'enable':
					return $this->enableNode () . $this->getMainMenu ();
					break;
				case 'orderCgh':
					return $this->changeNodeOrder () . $this->getMainMenu ();
					break;
				case 'delete':
					return $this->addNewNode ();
					break;
				case 'edit':
					return $this->addNewNode ();
					break;
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
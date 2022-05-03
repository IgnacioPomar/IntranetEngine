<?php
require_once $GLOBALS ['basePath'] . 'src/ColumnFormatter.php';
require_once $GLOBALS ['basePath'] . 'src/AutoForm.php';

class EditMenu extends Plugin
{
	private array $plgssWithParams;
	private array $configuredNodes;
	private bool $isEditable;


	public function __construct (Context &$context)
	{
		parent::__construct ($context);
		// $this->jsonFile = $GLOBALS ['basePath'] . 'src/tables/mainMenu.jsonTable';
	}
	const sentido_VALUES = array (0 => 'buy', 1 => 'sell');


	// -----------------------------------------------------------------------------------------------------
	// ------------------------------------------- EDIT MENU -----------------------------------------------
	// -----------------------------------------------------------------------------------------------------

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
			$retVal .= 'Fail!!';
		}
		else
		{
			$retVal .= 'Success';
		}

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

			// TODO: Add a span with all the actions, so we can float righ them
			// TODO: if ($this->isEditable) {//Add links for Delete/Move/Create nodes}

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
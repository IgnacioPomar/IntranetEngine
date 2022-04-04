<?php
require_once 'ColumnFormatter.php';
require_once 'AutoForm.php';

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

class EditOptions
{
	private $mysqli;
	private $jsonFile;


	private function __construct ($mysqli)
	{
		$this->mysqli = $mysqli;
		$this->jsonFile = $GLOBALS ['basePath'] . 'src/tables/plgParams.jsonTable';
	}


	/**
	 *
	 * @param mysqli $mysqli
	 * @return mixed
	 */
	public static function main ($mysqli)
	{
		$editMenu = new EditOptions ($mysqli);

		$retVal = '<h1>Edit params ' . ($_GET ['plgName'] ?? '') . '</h1>';

		if (! empty ($_GET ['plgName']))
		{
			$retVal .= $editMenu->showEditView ($_GET ['plgName']);
		}
		else
		{
			// TODO Consider whether to call the menuLoaderDB file to use its selection and array formatting functions
			$retVal .= $editMenu->showMenu ();
		}

		$retVal = str_replace ('@@content@@', $retVal, file_get_contents ($GLOBALS ['basePath'] . 'src/rsc/html/editViews.htm'));

		return $retVal;
	}

	// @formatter:off
	const COLS_TABLE_MENU = array (
			'name'			=> array ('w-400', 'Menu name', 'Name displayed in the menu'),
	);
	// @formatter:on
	const sentido_VALUES = array (0 => 'buy', 1 => 'sell');


	/**
	 *
	 * @return string
	 */
	private function showMenu ()
	{
		require_once ($GLOBALS ['moduleMenu']);

		$menuLoader = basename ($GLOBALS ['moduleMenu'], '.php');
		$menuLoaderClass = new $menuLoader ();
		$menuArray = $menuLoaderClass->getArrayMenu ($this->mysqli);

		// TODO Obtener datos de los plugins para saber si tienen parametros registrados en la tabla de la base de datos
		$this->addParamValues ($menuArray);

		$formatter = new ColumnFormatter (self::COLS_TABLE_MENU);

		$retVal = "<div class='head'>{$formatter->getHeaderCols ()}</div>";
		$retVal .= $this->showMenuItems ($formatter, $menuArray);

		return $retVal;
	}


	/**
	 *
	 * @param array $menu
	 */
	private function addParamValues (&$menu)
	{
		$plgNames = array ();
		$this->getPlgNames ($menu, $plgNames);

		$query = 'SELECT plgName, paramValues FROM wePlgParams WHERE plgName IN ("' . join ('","', $plgNames) . '")';
		$resParams = $this->mysqli->query ($query);

		$plgParams = array ();
		while ($row = $resParams->fetch_assoc ())
		{
			$plgParams [$row ['plgName']] = $row ['paramValues'];
		}

		$this->mergeParamsFromDB ($menu, $plgParams);
	}


	/**
	 *
	 * @param array $menu
	 * @param array $plgsName
	 */
	private function getPlgNames ($menu, &$plgsName)
	{
		foreach ($menu as $itemMenu)
		{
			if (! empty ($itemMenu ['plg']))
			{
				$plgsName [] = $itemMenu ['plg'];
			}

			if (isset ($itemMenu ['subOpcs']))
			{
				$this->getPlgNames ($itemMenu ['subOpcs'], $plgsName);
			}
		}
	}


	private function mergeParamsFromDB (&$menu, $plgParams)
	{
		foreach ($menu as &$itemMenu)
		{
			if (isset ($itemMenu ['plg']) && isset ($plgParams [$itemMenu ['plg']]))
			{
				$itemMenu ['paramValues'] = $plgParams [$itemMenu ['plg']];
			}

			if (isset ($itemMenu ['subOpcs']))
			{
				$this->mergeParamsFromDB ($itemMenu ['subOpcs'], $plgParams);
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
	private function showMenuItems ($formatter, $items, $ident = 0)
	{
		$retVal = '';
		foreach ($items as $itemMenu)
		{
			// We instantiate the class to format the name for have the correct indentation for each menu item
			$formatter->stylers ['name'] = new FormatterNameColumn ($ident);

			$retVal .= '<div class="line">';
			$retVal .= $formatter->getStyledBodyCols ($itemMenu);

			if (! empty ($itemMenu ['paramValues']))
			{
				$link = "{$_SERVER ["REQUEST_URI"]}&plgName={$itemMenu['plg']}";
				$retVal .= "<a href='$link'><span class='w-50'><svg xmlns='http: // www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-fill' viewBox='0 0 16 16'><path d='M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z'/></svg></span></a>";
			}
			$retVal .= "</div>";

			if (isset ($itemMenu ['subOpcs']))
			{
				$retVal .= $this->showMenuItems ($formatter, $itemMenu ['subOpcs'], $ident + 1);
			}
		}

		return $retVal;
	}


	/**
	 *
	 * @param string $plgName
	 * @return string
	 */
	private function showEditView ($plgName)
	{
		$retVal = '';

		$sql = 'SELECT paramValues, plgParams FROM wePlgParams ';
		$sql .= "LEFT JOIN wePlugins ON wePlugins.plgName = wePlgParams.plgName WHERE wePlgParams.plgName = '$plgName'";
		if ($res = $this->mysqli->query ($sql))
		{
			if ($plg = $res->fetch_assoc ())
			{
				$plg ['paramValues'] = json_decode ($plg ['paramValues'], true) ?? array ();
				$plg ['plgParams'] = json_decode ($plg ['plgParams'], true) ?? array ();
				$this->mergeArrayParams ($plg ['plgParams'], $plg ['paramValues']);

				$autoForm = new AutoForm ($this->jsonFile);
				// We add the fields of the JSONParams so that the autoform formats them
				foreach ($plg ['plgParams'] as $param)
				{
					$autoForm->addCustomField ($param ['name'], $param ['type']);
				}

				if (! empty ($_POST ['plgName']))
				{
					$retVal .= $this->updateParams ($autoForm, $plg);
				}

				$fields = array ();
				foreach ($plg ['paramValues'] as $name => $value)
				{
					$fields [$name] = $value;
				}

				$autoForm->setHidden ('plgName', $plgName);

				$autoForm->set = array_keys ($fields);
				$retVal .= $autoForm->generateForm ($fields, ! empty ($_POST ['plgName']));
			}
		}

		return $retVal;
	}


	/**
	 *
	 * @param array $params
	 * @param array $values
	 */
	private function mergeArrayParams (&$params, $values)
	{
		foreach ($params as &$param)
		{
			$param ['value'] = $values [$param ['name']];
		}
	}


	/**
	 *
	 * @param object $autoForm
	 * @param array $plg
	 * @return string
	 */
	private function updateParams ($autoForm, &$plg)
	{
		foreach ($plg ['paramValues'] as $name => &$dataParam)
		{
			$dataParam = $_POST [$name] ?? 0;
			unset ($_POST [$name]);
		}

		$_POST ['paramValues'] = json_encode ($plg ['paramValues']);

		$autoForm->mysqli = $this->mysqli;
		$sql = $autoForm->getUpdateSql ([ ], [ 'plgName']);

		$this->mysqli->query ($sql);

		$retVal = "<p>Registro Actualizado Correctamente</p>";
		$retVal .= '<div class="container">';
		$retVal .= '<a  class="btn rigth" href=' . strtok ($_SERVER ['REQUEST_URI'], '?') . '?a=options>';
		$retVal .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-counterclockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466z"/></svg>';
		$retVal .= 'Volver</a>';
		$retVal .= '</div>';

		return $retVal;
	}
}
<?php
require_once 'ColumnFormatter.php';
require_once 'AutoForm.php';

class _DeprecatedEditParams
{
	private $mysqli;
	private $jsonFile;

	// @formatter:off
	const COLS_TABLE = array (
			'plgName'		=> array ('w-300', 'Nombre', 'Nombre del plugin'),
			'paramValues'	=> array ('w-500', 'Parametros', 'Parametros configurados en el plugin'),
	);
	// @formatter:on

	/**
	 * Constructor
	 *
	 * @param mysqli $mysqli
	 */
	private function __construct ($mysqli)
	{
		$this->mysqli = $mysqli;
		$this->jsonFile = $GLOBALS ['basePath'] . 'src/tables/plgParams.jsonTable';
	}


	/**
	 *
	 * @return string
	 */
	private function showPlgs ()
	{
		$query = "SELECT * FROM wePlgParams";
		$res = $this->mysqli->query ($query);

		$formatter = new ColumnFormatter (self::COLS_TABLE);
		$formatter->stylers ['paramValues'] = new FormatterPlgParamsColumn ();

		$retVal = "<div class='head'>{$formatter->getHeaderCols ()}</div>";

		while ($row = $res->fetch_assoc ())
		{
			$retVal .= $this->showRowPlg ($formatter, $row);
		}

		return $retVal;
	}


	/**
	 *
	 * @param object $formatter
	 * @param array $plg
	 * @return string
	 */
	private function showRowPlg ($formatter, $plg)
	{
		$retVal = '<div class="line">';
		$retVal .= $formatter->getStyledBodyCols ($plg);

		$retVal .= '<span class="w-100"></span>';
		$link = "{$_SERVER ["REQUEST_URI"]}&plgName={$plg['plgName']}";
		$retVal .= "<a href='$link'><span class='w-50'><svg xmlns='http: // www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-pencil-fill' viewBox='0 0 16 16'><path d='M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z'/></svg></span></a>";
		$retVal .= "</div>";

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

		$sql = "SELECT paramValues FROM wePlgParams WHERE plgName = '$plgName'";
		if ($res = $this->mysqli->query ($sql))
		{
			if ($plg = $res->fetch_assoc ())
			{
				$autoForm = new AutoForm ($this->jsonFile);

				$plg ['paramValues'] = json_decode ($plg ['paramValues'], true) ?? array ();

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


	private function updateParams ($autoForm, &$plg)
	{
		foreach ($plg ['paramValues'] as $name => &$dataParam)
		{
			$dataParam = $_POST [$name];
			unset ($_POST [$name]);
		}

		$_POST ['paramValues'] = json_encode ($plg ['paramValues']);

		$autoForm->mysqli = $this->mysqli;
		$sql = $autoForm->getUpdateSql ([ ], [ 'plgName']);

		$this->mysqli->query ($sql);

		$retVal = "<p>Registro Actualizado Correctamente</p>";
		$retVal .= '<div class="container">';
		$retVal .= '<a  class="btn rigth" href=' . strtok ($_SERVER ['REQUEST_URI'], '?') . '?a=params>';
		$retVal .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-counterclockwise" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466z"/></svg>';
		$retVal .= 'Volver</a>';
		$retVal .= '</div>';

		return $retVal;
	}


	/**
	 *
	 * @param mysqli $mysqli
	 * @return mixed
	 */
	public static function main ($mysqli)
	{
		$editMenu = new _DeprecatedEditParams ($mysqli);

		$retVal = '<h1>Edit params ' . ($_GET ['plgName'] ?? '') . '</h1>';

		if (! empty ($_GET ['plgName']))
		{
			$retVal .= $editMenu->showEditView ($_GET ['plgName']);
		}
		else
		{
			// TODO Consider whether to call the menuLoaderDB file to use its selection and array formatting functions
			$retVal .= $editMenu->showPlgs ();
		}

		$retVal = str_replace ('@@content@@', $retVal, file_get_contents ($GLOBALS ['basePath'] . 'src/rsc/html/defaultView.htm'));

		return $retVal;
	}
}

class FormatterPlgParamsColumn
{


	/**
	 *
	 * @param mixed $val
	 * @param string $class
	 * @return string
	 */
	public function getSpan ($val, $class)
	{
		$params = empty ($val) ? array () : json_decode ($val, true);
		$retVal = "<div class='$class'>";
		if (! empty ($params))
		{
			$retVal .= '<div class="d-flex flex-column">';
			foreach ($params as $name => $value)
			{
				$retVal .= '<div class="">';
				$retVal .= "<span class='w-150'><b>Name:</b> $name</span>";
				$retVal .= "<span class='w-300'><b>Value:</b> $value</span>";
				$retVal .= '</div>';
			}
			$retVal .= '</div>';
		}
		$retVal .= "</div>";
		return $retVal;
	}
}

<?php
include_once ('dbSchema.php');

class AutoForm
{
	// YAGNI: Considerar separar el comportamiento de los formularios en un json aparte para no "ensuciar" la estructura bd ela tabhla
	private $fields;
	private $tableName;
	private $hiddenFields;
	public $set; // Set of fields the autoform is going to show
	public $mysqli;
	public $externalHeadHTML;
	public $externalFooterHTML;
	const STD_COMBO_PREFIX = 'combo@';
	const DB_COMBO_PREFIX = 'dbcombo@';
	const DB_MULTI_PREFIX = 'dbMulti@';


	public function __construct ($jsonFile)
	{
		if (is_null ($jsonFile))
		{
			$this->tableName = '';
			$this->fields = array ();
		}
		else
		{
			$tableInfo = json_decode (file_get_contents ($jsonFile), true);
			$this->tableName = DbSchema::getTableName ($tableInfo);
			$this->fields = $tableInfo ['fields'];
		}
		$this->hiddenFields = array ();
	}


	public static function newWithMysql ($jsonFile, $mysqli)
	{
		$obj = new AutoForm ($jsonFile);
		$obj->mysqli = $mysqli;
		return $obj;
	}


	private static function isType ($type, $defType)
	{
		if ((substr ($type, 0, strlen ($defType)) === $defType))
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	public function getFormField ($fieldName, $fieldInfo, $val, $inputDisabled)
	{
		$formType = (isset ($fieldInfo ['formType'])) ? $fieldInfo ['formType'] : $fieldInfo ['type'];
		$label = (isset ($fieldInfo ['label'])) ? $fieldInfo ['label'] : $fieldName;

		$prefix = '<div class="field"><label for="' . $fieldName . '">' . $label . '</label><span class="field-inner">';
		$sufix = '</span></div>' . PHP_EOL;

		$params = array ();
		if ($inputDisabled) $params [] = 'disabled';

		$type = $formType;
		switch ($formType)
		{
			// Casos diferentes (rompen el flujo)
			case 'hidden':
				return '<input type="hidden" name="' . $fieldName . '" value="' . $val . '" />' . PHP_EOL;
				break;
			case 'json':
			case 'text':
			case 'textarea':
				$retVal = '<textarea id="' . $fieldName . '" name="' . $fieldName . '" >' . $val . '</textarea>';
				return $prefix . $retVal . $sufix;
				break;

			// Inputs que se muestran por pantalla (al salir del switch se usan)
			case 'string':
				$type = 'text';
				if (! empty ($fieldInfo ['lenght'])) $params [] = 'maxlength="' . $fieldInfo ['lenght'] . '"';
				break;
			case 'float':
			case 'double':
				$params [] = 'step="0.01"';
			case 'int':
			case 'number':
				$type = 'number';
				break;
			case 'datetime':
				$type = 'date';
				break;
			case 'bool':
				$type = 'checkbox';
				if ($val) $params [] = 'checked';
				$val = 1;
				break;

			// Inputs without changes
			case 'date':
			case 'email':
			case 'color':
				break;

			// Caso especial: los combos incorporados
			default:
				if (self::isType ($type, self::STD_COMBO_PREFIX))
				{
					return $this->getStdCombo ($fieldName, $val, $inputDisabled, $type, $prefix, $sufix);
				}
				if (self::isType ($type, self::DB_COMBO_PREFIX))
				{
					return $this->getDbCombo ($fieldName, $val, $inputDisabled, $type, $prefix, $sufix);
				}
				if (self::isType ($type, self::DB_MULTI_PREFIX))
				{
					return $this->getDbMultiselect ($fieldName, $val, $inputDisabled, $type, $prefix, $sufix);
				}
				break;
		}

		$retVal = '<input id="' . $fieldName . '"  name="' . $fieldName . '"  type="' . $type . '"  value="' . $val . '" ' . join (' ', $params) . '>';
		return $prefix . $retVal . $sufix;
	}


	private function getStdCombo ($fieldName, $val, $inputDisabled, $type, $prefix, $sufix)
	{
		$arrNAme = substr ($type, strlen (self::STD_COMBO_PREFIX));
		if (is_callable ($arrNAme))
		{
			$arr = $arrNAme ();
		}
		else
		{
			$arr = constant ($arrNAme);
		}
		return $this->getCombo ($fieldName, $val, $inputDisabled, $arr, $prefix, $sufix);
	}


	private function getDbCombo ($fieldName, $val, $inputDisabled, $type, $prefix, $sufix)
	{
		$arr = $this->loadDataFromDb (substr ($type, strlen (self::DB_COMBO_PREFIX)));
		return $this->getCombo ($fieldName, $val, $inputDisabled, $arr, $prefix, $sufix);
	}


	private function getDbMultiselect ($fieldName, $val, $inputDisabled, $type, $prefix, $sufix)
	{
		$arr = $this->loadDataFromDb (substr ($type, strlen (self::DB_COMBO_PREFIX)));
		return $this->getMultiselect ($fieldName, $val, $inputDisabled, $arr, $prefix, $sufix);
	}


	/**
	 * Load data from the database to fill copmbo/select
	 *
	 * @param string $dbDescription
	 *        	has the format table:id:string
	 */
	private function loadDataFromDb ($dbDescription)
	{
		list ($table, $id, $showVal) = explode (":", $dbDescription);
		$sql = "SELECT $id,$showVal FROM $table ORDER BY $showVal;";

		$retVal = array ();
		if ($res = $this->mysqli->query ($sql))
		{
			while ($row = $res->fetch_assoc ())
			{
				$retVal [$row [$id]] = $row [$showVal];
			}
		}

		return $retVal;
	}


	private function getCombo ($fieldName, $val, $inputDisabled, $arr, $prefix, $sufix)
	{
		$retVal = '<select class="form-control" id="' . $fieldName . '" name="' . $fieldName . '">';
		foreach ($arr as $clave => $valorOp)
		{
			$selected = ($val == $clave) ? 'selected' : '';
			$retVal .= '<option value="' . $clave . '" ' . $selected . '>' . $valorOp . '</option>';
		}
		return $prefix . $retVal . '</select>' . $sufix;
	}


	private function getMultiselect ($fieldName, $val, $inputDisabled, $arr, $prefix, $sufix)
	{
		$retVal = '<select multiple class="form-control" id="' . $fieldName . '" name="' . $fieldName . '[]">';
		foreach ($arr as $clave => $valorOp)
		{
			$selected = ($val == $clave) ? 'selected' : '';
			$retVal .= '<option value="' . $clave . '" ' . $selected . '>' . $valorOp . '</option>';
		}
		return $prefix . $retVal . '</select>' . $sufix;
	}


	private function getExtraHiddenFields ()
	{
		$retVal = '';
		foreach ($this->hiddenFields as $key => $value)
		{
			$retVal .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . PHP_EOL;
		}

		return $retVal;
	}


	public function generateForm ($rowData, $isDisabled = FALSE)
	{
		$action = $_SERVER ['REQUEST_URI'];
		$retVal = '<form action="' . $action . '" method="post" autocomplete="off" class="autoform">';
		$retVal .= $this->getExtraHiddenFields ();

		$retVal .= $this->externalHeadHTML;

		foreach ($this->set as $fieldName)
		{
			$val = '';
			if (isset ($rowData [$fieldName]))
			{
				$val = $rowData [$fieldName];
			}
			else if (isset ($this->fields [$fieldName] ['defaultValue']))
			{
				$val = $this->fields [$fieldName] ['defaultValue'];
			}
			$retVal .= self::getFormField ($fieldName, $this->fields [$fieldName] ?? [ 'type' => 'text'], $val, $isDisabled);
		}

		$retVal .= $this->externalFooterHTML;

		if (! $isDisabled) $retVal .= '<button class="btn" type="submit" value="Grabar">Grabar</button>';
		$retVal .= '</form>';

		return $retVal;
	}


	private function validateDate ($date, $format = 'Y-m-d')
	{
		$d = DateTime::createFromFormat ($format, $date);
		// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
		return $d && $d->format ($format) === $date;
	}


	private function getSqlFormatted ($val, $fieldInfo)
	{
		if (isset ($fieldInfo ['formType']) && self::isType ($fieldInfo ['formType'], self::DB_MULTI_PREFIX))
		{
			$val = json_encode ($val, JSON_NUMERIC_CHECK);
		}

		switch ($fieldInfo ['type'])
		{
			case 'bool':
				if (is_bool ($val))
				{
					return ($val) ? 1 : 0;
				}
				else
				{
					return (is_numeric ($val)) ? $val : 'NULL';
				}
				break;
			case 'double':
			case 'auto':
			case 'int':
				return (is_numeric ($val)) ? $val : 'NULL';
				break;
			case 'datetime':
			case 'date': // YAGNI: verificar formato de la fecha
				if ($this->validateDate ($val))
				{
					return '"' . $val . '"';
				}
				else
				{
					return 'NULL';
				}
				break;
			case 'json':
			case 'string':
			case 'text':
			case 'textarea':
				return '"' . $this->mysqli->real_escape_string ($val) . '"';
				break;
		}
	}


	public function setHidden ($name, $value)
	{
		$this->hiddenFields [$name] = $value;
	}


	public function addCustomField ($name, $value, $label = '')
	{
		$this->fields [$name] = array ('type' => $value);
		if (! empty ($label))
		{
			$this->fields [$name] += array ('label' => $label);
		}
	}


	public function getInsertSql (array $data)
	{
		$retVal = 'INSERT INTO ' . $this->tableName . ' (';
		$values = ') VALUES (';

		$sep = '';

		foreach ($this->fields as $fieldName => $fieldInfo)
		{
			if (isset ($data [$fieldName]))
			{
				$retVal .= $sep . $fieldName;
				$values .= $sep . $this->getSqlFormatted ($_POST [$fieldName], $fieldInfo);

				$sep = ', ';
			}
		}
		return $retVal . $values . ');';
	}


	public function getUpdateSql (array $data, array $idxs)
	{
		$retVal = 'UPDATE ' . $this->tableName;
		$where = '';

		$fieldSep = ' SET ';
		$whereSep = ' WHERE ';
		foreach ($this->fields as $fieldName => $fieldInfo)
		{
			if (isset ($data [$fieldName]))
			{

				if (in_array ($fieldName, $idxs))
				{
					$where .= $whereSep . $fieldName . '=' . $this->getSqlFormatted ($_POST [$fieldName], $fieldInfo);
					$whereSep = ' AND ';
				}
				else
				{
					$retVal .= $fieldSep . $fieldName . '=' . $this->getSqlFormatted ($_POST [$fieldName], $fieldInfo);

					$fieldSep = ', ';
				}
			}
		}

		return $retVal . $where . ';';
	}


	public static function editJsonForm ($jsonFile, $rowData, $fieldSet)
	{
		$autoForm = new AutoForm ($jsonFile);
		$autoForm->set = $fieldSet;

		return $autoForm->generateForm ($rowData);
	}
}
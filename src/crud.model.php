<?php

// @formatter:off
interface iEntity
{
	public static function getTable();
	public static function getKeylist();
}

/**
 * Los objetos que hereden de esta clase podrán funcionar como combos
 */
interface ComboData
{
	public function getComboData ($mysqli);
}

/**
 * Los objetos que hereden de esta clase podrán funcionar como combos
 */
interface CustomComboData
{
	public function getComboData ($mysqli, $id);
}
// @formatter:on

/**
 * Los objetos que hereden de Entity, tendrán el crud automático
 */
abstract class Entity implements iEntity
{


	// @formatter:off
	abstract public function getCompleteFieldList ();
	abstract public function setDefaultFieldlist();
	abstract public function setFormFieldlist();
	abstract public function __GET($k);
	abstract public function __SET($k, $v);
	// @formatter:on
	function __construct ()
	{
		$this->setDefaultFieldlist ();
		$this->setFormFieldlist ();
	}

	// Y a partir de aqui funciones comunes a todas las entidades
	const DEFAULT_ROWS_PER_PAGE = 11;
	const DEFAULT_CONDITION_SEP = 'OR';
	const DEFAULT_CONDITIONGROUP_SEP = 'AND';
	const DEFAULT_PAGE = 0;
	private $orderList = array ();
	private $conditionList = array ();
	private $customWhere = null;
	private $customTables = array ();
	private $customSelectField = array ();
	private $conditionSep = self::DEFAULT_CONDITION_SEP;
	private $conditionGroupList = array ();
	private $conditionGroupSep = self::DEFAULT_CONDITIONGROUP_SEP;
	private $page = self::DEFAULT_PAGE;
	private $rowsPerPage = self::DEFAULT_ROWS_PER_PAGE;
	private $groupBy = null;
	protected $fieldList;
	protected $formFieldList;
	protected $formFieldListExclusiones = array ();
	protected $formFieldListExclusionesSoloLectura = array ();
	protected $formFieldListTraducciones = array ();


	public function getAdditionalIndexes ()
	{
		return array();
	}


	// TODO: Crear un tipo de campo aparte en vez de usar el tipo de campo en el formulario....
	// TOOD: No pasar el mysqli directamente: rompe el modelo
	public function __GetSqlFormated ($k, $mysqli)
	{
		$tipoCampo = $this->formFieldList [$k] [0];
		switch ($tipoCampo)
		{
			case 'number':
			case 'foreignIndex':
			case 'float':
			case 'hidden':
			case 'checkbox':
				if (is_numeric ($this->__GET ($k)))
				{
					return $this->__GET ($k);
				}
				else
				{
					return 'NULL';
				}
				break;
			case 'hiddenText':
				return '"' . $mysqli->real_escape_string ($this->__GET ($k)) . '"';
				break;
			case 'date':
			case 'datetime':
			case 'time':
				// TODO: quizás convenga estudiar un método menos permisivo, y con cambio de formato a ISO....
				if (false === strtotime ($this->__GET ($k)))
				{
					return 'NULL';
				}
				else
				{
					return '"' . $this->__GET ($k) . '"';
				}
				break;
			case 'password':
			case 'email':
			case 'text':
			case 'textarea':
			case 'color':
			case 'colorBackground':
				$valorCurrCampo = $this->__GET ($k);
				if ((empty ($valorCurrCampo)) && (! is_numeric ($valorCurrCampo)) && ($this->formFieldList [$k] [1] === NULL))
				{
					return 'NULL';
				}
				else
					return '"' . $mysqli->real_escape_string ($valorCurrCampo) . '"';
				break;
			default:
				if (CrudModel::checkIfSpecial ($tipoCampo, CrudModel::COMBO_PREFIX))
				{
					if (is_numeric ($this->__GET ($k)))
					{
						return $this->__GET ($k);
					}
					else
					{
						if (is_string ($this->__GET ($k)) && $this->__GET ($k) != '')
						{
							return '"' . $this->__GET ($k) . '"';
						}
						else
						{
							return 'NULL';
						}
					}
				}
				else if (CrudModel::checkIfSpecial ($tipoCampo, CrudModel::AUTO_PREFIX))
				{
					// Si lleva comillas ya se asignó al establecerlo
					return $this->__GET ($k);
				}
				break;
		}
	}


	/**
	 * Comprueba si un campo debe pasar por un update (a día de hoy solo comprueba los tipos password)
	 *
	 * @param string $k
	 * @return boolean
	 */
	public function isUpdatableField ($k)
	{
		if ((empty ($this->__GET ($k))) && ($this->formFieldList [$k] [0] == 'password'))
		{
			return false;
		}
		else
			return true;
	}


	public function getOrderList ()
	{
		return $this->orderList;
	}


	public function getCustomWhere ()
	{
		return $this->customWhere;
	}


	public function getCustomTables ()
	{
		return $this->customTables;
	}


	public function getGroupBy ()
	{
		return $this->groupBy;
	}


	/**
	 * Obtiene un array con las columnas añadidas de la tabla custom seleccionada.
	 */
	public function getCustomTableFields ()
	{
		return $this->customSelectField;
	}


	public function getConditionList ()
	{
		return $this->conditionList;
	}


	public function getConditionGroupList ()
	{
		return $this->conditionGroupList;
	}


	public function getConditionSepataror ()
	{
		return $this->conditionSep;
	}


	public function getConditionGroupSepataror ()
	{
		return $this->conditionGroupSep;
	}


	public function getLimit ()
	{
		return $this->rowsPerPage;
	}


	public function setLimit ($limit)
	{
		// Si 0 no hay límite de registros por página
		if (is_numeric ($limit))
		{
			$this->rowsPerPage = $limit;
		}
		else
		{
			$this->limit = self::DEFAULT_ROWS_PER_PAGE;
		}
	}


	public function setPage ($page)
	{
		if (is_numeric ($page))
		{
			$this->page = $page;
		}
		else
		{
			$this->page = self::DEFAULT_PAGE;
		}
	}


	public function addFormFieldExclusion ($exclusion)
	{
		$this->formFieldListExclusiones [$exclusion] = '';
	}


	public function addFormFieldExclusionSoloLectura ($exclusion)
	{
		$this->formFieldListExclusionesSoloLectura [$exclusion] = '';
	}


	public function addFormFieldTraduccion ($fieldkey, $traduccion)
	{
		$this->formFieldListExclusiones [$fieldkey] = $traduccion;
	}


	public function getFormFieldlist ($disabled = FALSE)
	{
		$arrayRetVal = array_diff_key ($this->formFieldList, $this->formFieldListExclusiones);
		if ($disabled)
		{
			$arrayRetVal = array_diff_key ($arrayRetVal, $this->formFieldListExclusionesSoloLectura);
		}
		/*
		//TODO: trabajar con traducciones aqui
		foreach ($this->formFieldListTraducciones as $key => $traduccion)
		{
			if (array_key_exists ($key, $arrayRetVal))
			{
				$arrayRetVal [$key] [0] = $traduccion;
			}
		}
		*/

		return $arrayRetVal;
	}


	public function addToFieldList ($field)
	{
		$this->fieldList [] = $field;
	}


	public function getFieldlist ()
	{
		if ($this->fieldList == null)
			return array ();
		else
			return $this->fieldList;
	}


	public function setFieldlist (array $fieldList)
	{
		$this->fieldList = $fieldList;
	}


	public function setFormSelectedFieldlist ()
	{
		$newFormFieldList = array ();

		foreach ($this->fieldList as $fieldName)
		{
			$newFormFieldList [$fieldName] = $this->formFieldList [$fieldName];
		}

		$this->formFieldList = $newFormFieldList;
	}


	public function getPage ()
	{
		return $this->page;
	}


	public function resetConditionLists ()
	{
		$this->conditionList = array ();
		$this->conditionGroupList = array ();
	}


	public function addCustomWhere ($customWhere)
	{
		$this->customWhere = $customWhere;
	}


	public function addCondition ($column, $operator, $value)
	{
		$condition = array ();
		$condition [0] = $column;
		$condition [1] = $operator;

		// TODO: si al valor hay que añadir comillas debería ser aqui
		$condition [2] = $value;

		$this->conditionList [] = $condition;
	}


	public function addConditionGroup ()
	{
		if (count ($this->conditionList) > 0)
		{
			$this->conditionGroupList [] = $this->conditionList;
			$this->conditionList = array ();
		}
	}


	/**
	 * Invierte el comportamiento por defecto entre ANDs y ORs en los grupo s de sentencias
	 */
	public function inverseConditionsGroups ()
	{
		$tmp = $this->conditionSep;
		$this->conditionSep = $this->conditionGroupSep;
		$this->conditionGroupSep = $tmp;
	}


	public function addOrder ($column, $sentido = 'ASC')
	{
		$this->orderList [$column] = $sentido;
	}


	public function addGroupBy ($column)
	{
		$this->groupBy = $column;
	}


	public function addCustomTable ($table)
	{
		$this->customTables [] = $table;
	}


	/**
	 * Añade un array con las columnas a seleccionar de las tablas custom que añadas con addCustomTable
	 */
	public function addCustomTableFields (array $field)
	{
		$this->customSelectField = $field;
	}


	public function fillFromArray (array $data)
	{
		$camposFormulario = $this->getFormFieldlist ();
		foreach ($this->fieldList as $campo)
		{
			if (isset ($data [$campo]))
			{
				$this->__SET ($campo, $data [$campo]);
			}
			else
			{
				// Comprobamos que si venimos de un POST y hay un checkBox, marcamos la opcion adecuada
				if ($camposFormulario [$campo] [0] == 'checkbox')
				{
					$this->__SET ($campo, 0);
				}
			}
		}
	}
}

/**
 * Esta clase se encarga de grabar en la base de datos y/o hacer las operaciones necesarias (por ejemplo, registrar las visitas de actividad)
 *
 * @see http://anexsoft.com/p/57/realizando-un-crud-listar-registrar-actualizar-eliminar-con-php
 * @see http://stackoverflow.com/questions/10740005/abstract-static-function-in-php-5-3
 *
 */
class CrudModel
{
	const CUSTOM_COMBO_PREFIX = 'cCombo@';
	const COMBO_PREFIX = 'combo@';
	const AUTO_PREFIX = 'auto@';


	/**
	 * Comprueba si el tipo que se pasa es de tipo combo
	 *
	 * @param string $str
	 * @return boolean
	 */
	public static function checkIfSpecial ($str, $prefix)
	{
		return (substr ($str, 0, strlen ($prefix)) === $prefix);
	}


	/**
	 * Obtiene la clase a la que pertenece el combo
	 *
	 * @param string $str
	 * @return string
	 */
	private static function getSpecialType ($str, $prefix)
	{
		return substr ($str, strlen ($prefix));
	}


	/**
	 * Agregamos al array los elementos que se insertan de forma automática
	 * NOTA: Si a la hora de insertar el valor requiere comillas, estas se meten en el propio valor de la entidad
	 *
	 * @param Entity $entidad
	 * @param mixed $fields
	 */
	private static function addAutoFields (Entity $entidad, &$fields)
	{
		$camposFormulario = $entidad->getFormFieldlist ();
		foreach ($camposFormulario as $campoTabla => $datosCampo)
		{
			if (self::checkIfSpecial ($datosCampo [0], self::AUTO_PREFIX))
			{
				switch (self::getSpecialType ($datosCampo [0], self::AUTO_PREFIX))
				{
					case 'codUsr':
						$entidad->__SET ($campoTabla, $_SESSION ['userId']);
						$fields [] = $campoTabla;
						break;
					case 'fecha':
						$entidad->__SET ($campoTabla, '"' . date ('Y-m-d') . '"');
						$fields [] = $campoTabla;
						break;
				}
			}
		}
	}


	/**
	 * Funcion de seguridad: indica si el tipoo de campo que se especifica es válido para trabajar automaticamente con un formulario
	 *
	 * @param Entity $entidad
	 */
	public static function setEntidadFieldListFromSupportedFields (Entity $entidad)
	{
		$camposFormulario = $entidad->getFormFieldlist ();
		$datosVisbles = array ();
		foreach ($camposFormulario as $campoTabla => $datosCampo)
		{
			switch ($datosCampo [0])
			{
				case 'password':
				case 'email':
				case 'number':
				case 'float':
				case 'hidden':
				case 'hiddenText':
				case 'text':
				case 'checkbox':
				case 'date':
				case 'datetime':
				case 'time':
				case 'color':
				case 'colorBackground':
				case 'textarea':
					$datosVisbles [] = $campoTabla;
					break;
				default:
					if (CrudModel::checkIfSpecial ($datosCampo [0], self::COMBO_PREFIX))
					{
						$datosVisbles [] = $campoTabla;
					}
					if (CrudModel::checkIfSpecial ($datosCampo [0], self::CUSTOM_COMBO_PREFIX))
					{
						$datosVisbles [] = $campoTabla;
					}
					break;
			}
		}

		$entidad->setFieldlist ($datosVisbles);
	}


	public static function generateField ($mysqli, $entidad, $tipo, $name, $title, $valor, $maxlength = 100, $disabled = false)
	{
		$retVal = '';

		if ($disabled)
		{
			$disabledTxt = ' disabled="disabled" ';
		}
		else
		{
			$disabledTxt = '';
		}

		switch ($tipo)
		{
			case 'hidden':
			case 'hiddenText':
				$retVal .= '<input type="hidden" name="' . $name . '" value="' . $valor . '" />' . PHP_EOL;
				break;
			case 'text':
				if ($name == "descripcion")
				{
					$retVal .= '<div class="field"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
					$retVal .= '<input class="form-control" onkeyup="searchCoincidences(\'' . $name . '\')" type="text" id="' . $name . '"  name="' . $name . '" value="' . $valor . '" maxlength="' . $maxlength . '"' . $disabledTxt . ' />';
					$retVal .= '</span></div>' . PHP_EOL;
					$retVal .= '<div class="field"><div class="coincidences" id="' . $name . 'Matches"></div></div>';
				}
				else
				{
					$retVal .= '<div class="field"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
					$retVal .= '<input type="text" id="' . $name . '"  name="' . $name . '" value="' . $valor . '" maxlength="' . $maxlength . '"' . $disabledTxt . ' />';
					$retVal .= '</span></div>' . PHP_EOL;
				}
				break;
			case 'checkbox':
				if ($valor == 1)
					$checked = 'checked="true"';
				else
					$checked = '';

				$retVal .= '<div class="field-checkbox col-md-2"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
				$retVal .= '<input type="checkbox" id="' . $name . '" name="' . $name . '" value="1" ' . $checked . $disabledTxt . ' />';
				$retVal .= '</span></div>' . PHP_EOL;
				break;
			case 'password': // Sólo mostramos las passwords en caso de edición real
				if ($disabled == false)
				{
					$retVal .= '<div class="field"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
					$retVal .= '<input type="password" id="' . $name . '" name="' . $name . '" value=""  />';
					$retVal .= '</span></div>' . PHP_EOL;
				}
				break;
			case 'email':
				$retVal .= '<div class="field"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
				$retVal .= '<input type="email" id="' . $name . '" name="' . $name . '" value="' . $valor . '"' . $disabledTxt . '  />';
				$retVal .= '</span></div>' . PHP_EOL;
				break;
			case 'number':
				$retVal .= '<div class="field"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
				$retVal .= '<input type="number" id="' . $name . '" name="' . $name . '" value="' . $valor . '"' . $disabledTxt . '  />';
				$retVal .= '</span></div>' . PHP_EOL;
				break;
			case 'float':
				$retVal .= '<div class="field"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
				$retVal .= '<input type="number" id="' . $name . '" name="' . $name . '" value="' . $valor . '"' . $disabledTxt . ' step="0.01" />';
				$retVal .= '</span></div>' . PHP_EOL;
				break;
			case 'date':
				$retVal .= '<div class="col-md-2 field-select"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
				$retVal .= '<input class="form-control" type="date" id="' . $name . '" name="' . $name . '" value="' . $valor . '"' . $disabledTxt . '  />';
				$retVal .= '</span></div>' . PHP_EOL;
				break;
			case 'datetime':
				$retVal .= '<div class="col-md-2 field-select"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
				$retVal .= '<input class="form-control" type="datetime-local" id="' . $name . '" name="' . $name . '" value="' . $valor . '"' . $disabledTxt . '  />';
				$retVal .= '</span></div>' . PHP_EOL;
				break;
			case 'time':
				$retVal .= '<div class="col-md-2 field-select"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
				$retVal .= '<input class="form-control" type="time" id="' . $name . '" name="' . $name . '" value="' . $valor . '"' . $disabledTxt . '  />';
				$retVal .= '</span></div>' . PHP_EOL;
				break;
			case 'colorBackground':
				$color = $valor;
				if ($color == '') $color = '#ffffff';
			// Sin brake porque genero el campo en colorBackground
			case 'color':
				if (! isset ($color))
				{
					$color = $valor;
					if ($color == '') $color = '#000000';
				}
				$retVal .= '<div class="field"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
				$retVal .= '<input type="color" id="' . $name . '" name="' . $name . '" value="' . $color . '"' . $disabledTxt . '  />';
				$retVal .= '</span></div>' . PHP_EOL;
				break;
			case 'textarea':
				$retVal .= '<div class="field"><label for="' . $name . '">' . $title . '</label><span class="field-inner">';
				if ($disabled)
				{
					$retVal .= '<div class="textarea" name="' . $name . '"' . $disabledTxt . ' >' . $valor . '</div>';
				}
				else
				{
					$retVal .= '<textarea id="' . $name . '" name="' . $name . '"' . $disabledTxt . ' >' . $valor . '</textarea>';
				}
				$retVal .= '</span></div>' . PHP_EOL;
				break;
			default:
				if (self::checkIfSpecial ($tipo, self::COMBO_PREFIX))
				{
					$datosCombo = self::getSpecialType ($tipo, self::COMBO_PREFIX);
					if (class_exists ($datosCombo))
					{
						$cmb = new $datosCombo ();
						if ($cmb instanceof ComboData)
						{
							$valorOrig = $valor;
							$retVal .= '<div class="col-md-2 field-select"><label for="' . $name . '">' . $title . ' </label><span class="field-inner">';
							$retVal .= '<select class="form-control" id="' . $name . '" name="' . $name . '"' . $disabledTxt . ' >';
							foreach ($cmb->getComboData ($mysqli) as $clave => $valorOp)
							{
								if ($valorOrig == $clave)
									$retVal .= '<option value="' . $clave . '" selected>' . $valorOp . '</option>';
								else
									$retVal .= '<option value="' . $clave . '">' . $valorOp . '</option>';
							}
							$retVal .= '</select></span></div>' . PHP_EOL;
						}
					}
				}
				if (self::checkIfSpecial ($tipo, self::CUSTOM_COMBO_PREFIX))
				{
					$datosCombo = self::getSpecialType ($tipo, self::CUSTOM_COMBO_PREFIX);
					if (class_exists ($datosCombo))
					{
						$cmb = new $datosCombo ();
						if ($cmb instanceof CustomComboData)
						{
							$valorOrig = $valor;
							$retVal .= '<div class="col-md-2 field-select"><label for="' . $name . '">' . $title . ' </label><span class="field-inner">';
							$retVal .= '<select class="form-control" id="' . $name . '" name="' . $name . '"' . $disabledTxt . ' >';
							foreach ($cmb->getComboData ($mysqli, $entidad->__GET ('idCliente')) as $clave => $valorOp)
							{
								if ($valorOrig == $clave)
									$retVal .= '<option value="' . $clave . '" selected>' . $valorOp . '</option>';
								else
									$retVal .= '<option value="' . $clave . '">' . $valorOp . '</option>';
							}
							$retVal .= '</select></span></div>' . PHP_EOL;
						}
					}
				}
				break;
		}
		return $retVal;
	}


	public static function generateFormFromEntyty (Entity $entidad, $mysqli, $action, $disabled = false, $textAction = '', $verifAction = FALSE, $materialIcon = '', $jsOnClick = '')
	{
		$retVal = '<form id="myForm" action="' . $action . '" method="post" autocomplete="off">';
		$camposFormulario = $entidad->getFormFieldlist ($disabled);

		foreach ($camposFormulario as $campoTabla => $datosCampo)
		{
			$valorCurrCampo = $entidad->__GET ($campoTabla);
			if ((empty ($valorCurrCampo)) && ($datosCampo [1] !== NULL) && (! is_numeric ($valorCurrCampo)))
			{
				$valorCurrCampo = $datosCampo [1];
			}
			$retVal .= self::generateField ($mysqli, $entidad, $datosCampo [0], $campoTabla, $datosCampo [3], $valorCurrCampo, $datosCampo [2], $disabled);
		}
		$btn = FALSE;
		if ($materialIcon != '')
		{
			$btn = '<button class="btn-material-icon" onclick="' . $jsOnClick . '" type="submit" value="Grabar" title="Guardar"> <i class="material-icons">' . $materialIcon . '</i></button>';
		}
		else
		{
			if (! $disabled)
			{
				$btn = '<button class="btn" onclick="' . $jsOnClick . '" type="submit" value="Grabar">Grabar</button>';
			}
			else if ($action != '' && $textAction != '')
			{
				$btn = '<button class="btn warning" onclick="' . $jsOnClick . '" type="submit" value="Grabar">' . $textAction . '</button>';
			}
		}

		if ($btn !== false)
		{
			$retVal .= '<div class="field"><label></label>';
			if ($verifAction)
			{
				$retVal .= '<div class="verifAction">';
				$codVerif = self::generarCodigo (6);
				$retVal .= '<label for="codVerifAction"><b>' . $codVerif . '</b></label>';
				$retVal .= '<input type="hidden" name="codVerifActionReal" value="' . $codVerif . '"><input type="text" name="codVerifAction" value="" placeholder="Introduce el texto."/></div>';
			}
			$retVal .= $btn . '</div>';
		}

		$retVal .= '</form>';

		return $retVal;
	}


	/**
	 * Genera formulario para un array de entidades.
	 *
	 * @param array $entidades
	 * @param mysqli $mysqli
	 * @param string $action
	 * @param boolean $disabled
	 * @param string $textAction
	 * @param boolean $verifAction
	 * @param string $materialIcon
	 * @return string
	 */
	public static function generateFormFromEntities (array &$entidades, $mysqli, $action, $disabled = false, $textAction = '', $verifAction = FALSE, $materialIcon = '')
	{
		$count = 0;
		$retVal = '<form action="' . $action . '" method="post" autocomplete="off"><div class="row">';
		foreach ($entidades as $entidad)
		{
			$retVal .= '<div class="generatedForm col-md-6">';
			$camposFormulario = $entidad->getFormFieldlist ($disabled);

			foreach ($camposFormulario as $campoTabla => $datosCampo)
			{
				$valorCurrCampo = $entidad->__GET ($campoTabla);
				if ((empty ($valorCurrCampo)) && ($datosCampo [1] !== NULL) && (! is_numeric ($valorCurrCampo)))
				{
					$valorCurrCampo = $datosCampo [1];
				}
				$retVal .= self::generateField ($mysqli, $entidad, $datosCampo [0], $campoTabla, $datosCampo [3], $valorCurrCampo, $datosCampo [2], $disabled);
			}
			$btn = FALSE;
			if ($materialIcon != '')
			{
				$btn = '<button class="btn-material-icon" type="submit" value="Grabar" title="Guardar"> <i class="material-icons">' . $materialIcon . '</i></button>';
			}
			else
			{
				if (! $disabled)
				{
					$btn = '<button style="position:relative;" class="btn" type="submit" value="Grabar">Grabar</button>';
				}
				else if ($action != '' && $textAction != '')
				{
					$btn = '<button class="btn warning" type="submit" value="Grabar">' . $textAction . '</button>';
				}
			}
			if ($btn !== false)
			{
				$retVal .= '<div class="field"><label></label>';
				if ($verifAction)
				{
					$retVal .= '<div class="verifAction">';
					$codVerif = self::generarCodigo (6);
					$retVal .= '<label for="codVerifAction"><b>' . $codVerif . '</b></label>';
					$retVal .= '<input type="hidden" name="codVerifActionReal" value="' . $codVerif . '"><input type="text" name="codVerifAction" value="" placeholder="Introduce el texto."/></div>';
				}
			}
			$retVal .= '</div></div>';
			++ $count;
		}
		if ($count == count ($entidades))
		{
			$retVal .= '</div>' . $btn;
			$retVal .= '</div></form>';
		}

		return $retVal;
	}


	public static function generateDisabledFilledFormFromEntyty (Entity &$entidad, $mysqli)
	{
		return self::generateFilledFormFromEntyty ($entidad, $mysqli, '', true);
	}


	public static function fillEntytyFromDatabase (Entity &$entidad, mysqli &$mysqli)
	{
		$sql = self::getListSql ($entidad);
		$resultado = CrudModel::executeQuery ($mysqli, $sql);
		$entytyData = $resultado->fetch_assoc ();
		$entidad->fillFromArray ($entytyData);
	}


	public static function generateFilledFormFromEntyty (Entity &$entidad, $mysqli, $action, $disabled = false, $textAction = '', $verifAction = FALSE, $materialIcon = '')
	{
		self::setEntidadFieldListFromSupportedFields ($entidad);

		// ----- Cargamos en la entidad los valores a usar -----
		self::fillEntytyFromDatabase ($entidad, $mysqli);

		// ----- Mostramos el Formulario -----
		return self::generateFormFromEntyty ($entidad, $mysqli, $action, $disabled, $textAction, $verifAction, $materialIcon);
	}


	public static function generateFilledEditableFormFromEntities (array &$entidades, $mysqli, $action, $disabled = false, $textAction = '', $verifAction = FALSE, $materialIcon = '')
	{
		foreach ($entidades as $entidad)
		{
			self::setEntidadFieldListFromSupportedFields ($entidad);
			$sql = self::getListSql ($entidad);
			$resultado = CrudModel::executeQuery ($mysqli, $sql);
			$entytyData = $resultado->fetch_assoc ();
			$entidad->fillFromArray ($entytyData);
		}
		// ----- Mostramos el Formulario -----
		return self::generateFormFromEntities ($entidades, $mysqli, $action, $disabled, $textAction, $verifAction, $materialIcon);
	}


	public static function generateFilledFormFromEntities (array &$entidades, $mysqli, $action, $disabled = false, $textAction = '', $verifAction = FALSE, $materialIcon = '')
	{
		foreach ($entidades as $entidad)
		{
			self::setEntidadFieldListFromSupportedFields ($entidad);
		}
		// ----- Mostramos el Formulario -----
		return self::generateFormFromEntities ($entidades, $mysqli, $action, $disabled, $textAction, $verifAction, $materialIcon);
	}


	public static function isQuerySucees ($mysqli, $sql)
	{
		if (! $resultado = $mysqli->query (prefixQuery ($sql)))
		{
			return false;
		}
		else
		{
			$resultado->close ();
			return true;
		}
	}


	public static function executeQuery ($mysqli, $sql)
	{
		// echo '<br>' . prefixQuery ( $sql );
		if (! $resultado = $mysqli->query (prefixQuery ($sql)))
		{
			echo "Error: La ejecución de la consulta falló debido a: \n";
			echo "Query: " . prefixQuery ($sql) . "\n";
			echo "Errno: " . $mysqli->errno . "\n";
			echo "Error: " . $mysqli->error . "\n";
			exit ();
		}
		else
		{
			return $resultado;
		}
	}


	/**
	 *
	 * @see http://www.pontikis.net/blog/dynamically-bind_param-array-mysqli
	 * @see http://stackoverflow.com/questions/16236395/bind-param-with-array-of-parameters
	 * @param Entity $entidad
	 * @param mixed $mysqli
	 * @return string
	 */
	public static function update (Entity $entidad, $mysqli)
	{
		$sql = 'UPDATE ' . $entidad->getTable () . ' SET ';
		$fields = $entidad->getFieldlist ();
		$keys = $entidad->getKeylist ();
		$fields = array_diff ($fields, $keys);
		self::addAutoFields ($entidad, $fields);

		$sep = '';
		foreach ($fields as $campo)
		{
			if ($entidad->isUpdatableField ($campo))
			{
				$sql .= $sep . $campo . '=' . $entidad->__GetSqlFormated ($campo, $mysqli);
				$sep = ', ';
			}
		}
		$sql .= ' WHERE ';

		$sep = '';
		foreach ($keys as $campo)
		{
			$sql .= $sep . $campo . '=' . $entidad->__GetSqlFormated ($campo, $mysqli);
			$sep = ' AND ';
		}
		if (self::executeQuery ($mysqli, $sql) === TRUE)
		{
			return "Registro Actualizado Correctamente";
		}
	}


	public static function insert (Entity $entidad, $mysqli, $returNnumeric = false, $withKeys = false)
	{
		$sql = 'INSERT INTO ' . $entidad->getTable () . ' (';
		$fields = $entidad->getFieldlist ();
		$keys = $entidad->getKeylist ();
		if (! $withKeys) $fields = array_diff ($fields, $keys);
		self::addAutoFields ($entidad, $fields);

		// Recorremos los campos
		$sep = '';
		foreach ($fields as $campo)
		{
			$sql .= $sep . $campo;
			$sep = ', ';
		}
		$sql .= ') VALUES (';
		// Y ahora establecemos los valores
		$sep = '';
		foreach ($fields as $campo)
		{
			$sql .= $sep . $entidad->__GetSqlFormated ($campo, $mysqli);
			$sep = ', ';
		}
		$sql .= ');';

		if (self::executeQuery ($mysqli, $sql) === TRUE)
		{
			if ($returNnumeric)
			{
				return $mysqli->insert_id;
			}
			else
			{
				return "Registro Insertado Correctamente";
			}
		}
	}


	protected static function initWhere (Entity $entidad)
	{
		if ($entidad->getCustomWhere () != null)
		{
			return ' WHERE ' . $entidad->getCustomWhere () . ' ';
		}
		else
		{
			return '';
		}
	}


	protected static function whereCondicionList ($conditionList, $conditionSeparador, $inicio = ' WHERE ')
	{
		$sqlWhere = '';
		if ((is_array ($conditionList)) && (count ($conditionList) > 0))
		{
			$sqlWhere .= $inicio;
			$separador = '';
			foreach ($conditionList as $condicion)
			{
				if (is_array ($condicion [2]))
				{
					if (count ($condicion [2]) == 0)
					{
						// TODO: Considerar en un futuro "eliminar el WHERE si no hay más condiciones
						// Tenemos que tener al menos una condicion... que debe ser falsa
						$sqlWhere .= '1 = 0';
					}
					else
					{
						$inSep = '';
						$sqlWhere .= $separador . ' ' . $condicion [0] . ' ' . $condicion [1] . ' (';
						foreach ($condicion [2] as $value)
						{
							$sqlWhere .= $inSep . $value;
							$inSep = ', ';
						}
						$sqlWhere .= ')';
					}
				}
				else
				{
					$sqlWhere .= $separador . ' ' . $condicion [0] . ' ' . $condicion [1] . ' ' . $condicion [2];
				}
				$separador = ' ' . $conditionSeparador;
			}
			$sqlWhere .= ' ';
		}
		return $sqlWhere;
	}


	protected static function whereCondicionGroupList (Entity $entidad, $customWhere = '')
	{
		// Construimos la condicion
		if (empty ($customWhere))
		{
			$sqlWhere = self::whereCondicionList ($entidad->getConditionList (), $entidad->getConditionSepataror ());
		}
		else
		{
			$sqlWhere = $customWhere;
			$sqlWhere .= self::whereCondicionList ($entidad->getConditionList (), $entidad->getConditionSepataror (), '');
		}

		$conditionGroupList = $entidad->getConditionGroupList ();
		if ((is_array ($conditionGroupList)) && (count ($conditionGroupList) > 0))
		{
			if ($sqlWhere == '')
			{
				$sqlWhere .= ' WHERE ';
				$separador = '';
			}
			else
			{
				$separador = ' ' . $entidad->getConditionGroupSepataror ();
			}

			foreach ($conditionGroupList as $condicionList)
			{
				$sqlWhere .= $separador . ' (' . self::whereCondicionList ($condicionList, $entidad->getConditionSepataror (), '') . ') ';
				$separador = ' ' . $entidad->getConditionGroupSepataror ();
			}
		}

		return $sqlWhere;
	}


	private static function generarCodigo ($longitud)
	{
		$key = '';
		$pattern = strtoupper ('1234567890abcdefghijklmnopqrstuvwxyz');
		$max = strlen ($pattern) - 1;
		for($i = 0; $i < $longitud; $i ++)
			$key .= $pattern {mt_rand (0, $max)};
		return $key;
	}


	public static function deleteCondicionList (Entity $entidad, $mysqli, $verifAction = FALSE, $returBoolean = false)
	{
		if ($verifAction && (! isset ($_POST ['codVerifActionReal']) || ! isset ($_POST ['codVerifAction']) || $_POST ['codVerifAction'] == ""))
		{
			return "Para verificar la eliminación del registro es necesario completar el campo habilitado para ello.";
		}
		else if ($verifAction && $_POST ['codVerifAction'] !== $_POST ['codVerifActionReal'])
		{
			return "No coincide el código de verificación.";
		}
		else
		{
			$sqlDelete = 'DELETE FROM ' . $entidad->getTable () . ' ';
			// Construimos la condicion
			$sqlDelete .= self::whereCondicionGroupList ($entidad);

			// El siguiente código se usará para registrar los que se elimina
			/*
			 * $sql = 'INSERT INTO {gestora_accionesBBDD} (fecha,idUsuario,ip,accion,tabla,`where`) VALUES
			 * (DATE(NOW()), ' . $_SESSION ['userId'] . ', \'' . $_SERVER ['REMOTE_ADDR'] . '\', \'' . $sqlDelete . '\', \'' . $entidad->getTable () . '\', \'' . self::whereCondicionGroupList ( $entidad ) . '\');';
			 * if (self::executeQuery ( $mysqli, $sql ) === TRUE)
			 * {
			 *
			 * }
			 */

			if (self::executeQuery ($mysqli, $sqlDelete) === TRUE)
			{
				if ($returBoolean)
				{
					return TRUE;
				}
				else
				{
					return "Registro Eliminado Correctamente";
				}
			}
		}
		return FALSE;
	}


	/**
	 * Función para determinar el cotejamiento de las columnas en la base de datos
	 *
	 * @param array $campo
	 *        	array con las columnas que añadiremos/alteraremos
	 * @param string $nombre
	 *        	nombre de la columna
	 * @param array $keys
	 *        	array para determinar si es primario o no
	 * @return string
	 */
	private static function getNameAndColumnType (array $campo, $nombre, array $keys = [])
	{
		$sql = "";
		switch ($campo [0])
		{
			case 'hidden':
				if ($keys != [ ])
				{
					$sql .= $nombre . ' int ';
					if (in_array ($nombre, $keys))
					{
						if (count ($keys) == 1)
						{
							$sql .= 'NOT NULL AUTO_INCREMENT';
						}
						else
						{
							$sql .= 'NOT NULL';
						}
					}
					else
					{
						$sql .= 'DEFAULT NULL';
					}
				}
				else
				{
					$sql .= $nombre . ' int DEFAULT NULL';
				}
				break;
			case 'textarea':
				$sql .= $nombre . ' TEXT DEFAULT NULL';
				break;
			case 'text':
				$sql .= $nombre . ' varchar(' . $campo [2] . ') DEFAULT NULL';
				break;
			case 'float':
				$sql .= $nombre . ' DOUBLE DEFAULT NULL';
				break;
			case 'decimal':
				$sql .= $nombre . ' DECIMAL(10, 2) DEFAULT NULL';
				break;
			case 'foreignIndex':
			case 'number':
				$sql .= $nombre . ' int DEFAULT NULL';
				break;
			case 'date':
				$sql .= $nombre . ' DATE DEFAULT NULL';
				break;
			case 'datetime':
				$sql .= $nombre . ' DATETIME DEFAULT NULL';
				break;
			case 'time':
				$sql .= $nombre . ' TIME DEFAULT NULL';
				break;
			case 'checkbox':
				$sql .= $nombre . ' BOOL DEFAULT FALSE';
				break;
			case 'bool':
				$sql .= $nombre . ' BOOL DEFAULT FALSE';
				break;
			default:
				if (CrudModel::checkIfSpecial ($campo [0], CrudModel::COMBO_PREFIX))
				{
					$sql .= $nombre . ' int DEFAULT NULL';
				}
				else if (CrudModel::checkIfSpecial ($campo [0], CrudModel::AUTO_PREFIX))
				{
					$sql .= $nombre . ' DATETIME DEFAULT NULL';
				}
				else
				{
					$sql .= $nombre . ' FALTA_TIPO';
				}
			// comprobar combo
		}

		return $sql;
	}


	/**
	 * Actualiza los campos de una tabla
	 *
	 * @param Entity $entidad
	 *        	Entidad de la que hay que crear la tabla
	 * @param mysqli $mysqli
	 *        	conexión a la base de datos
	 */
	private static function alterTable (Entity $entidad, $mysqli)
	{
		// TODO: Considerar en un futuro también editar los tipos

		// TODO: No se elimuinan columnas antiguas: al reinstalar plugins se eliminarían las dinámicas
		// quizás estudiar la posibilidad de una propiedad si elliminar o no
		$yaExisten = array ();
		$sql = 'SHOW COLUMNS FROM ' . $entidad->getTable ();
		$resultado = CrudModel::executeQuery ($mysqli, $sql);
		while ($linea = $resultado->fetch_assoc ())
		{
			$yaExisten [] = $linea ['Field'];
		}
		// $resultado->free ();

		$nuevasColumnas = array_diff ($entidad->getCompleteFieldList (), $yaExisten);

		if (count ($nuevasColumnas) > 0)
		{
			$sql = 'ALTER TABLE ' . $entidad->getTable () . ' ';

			$sep = '';
			$fields = $entidad->getFormFieldlist ();
			foreach ($nuevasColumnas as $nombre)
			{
				$sql .= $sep . 'ADD COLUMN ';

				$campo = $fields [$nombre];

				$sql .= CrudModel::getNameAndColumnType ($campo, $nombre, [ ]);

				$sep = ', ';
			} // foreach

			// Devolvemos mensaje de consecución
			if (self::executeQuery ($mysqli, $sql) === TRUE)
			{
				return $entidad->getTable ();
			}
			else
			{
				return 'FALLO (modificando ' . $entidad->getTable () . ')';
			}
		}
		else
		{
			// return 'NO HAY CAMBIOS (tabla ' . $entidad->getTable () . ')';
			return 'SC [' . $entidad->getTable () . ']';
		}
	}


	/**
	 * Crea la tabla
	 *
	 * @param Entity $entidad
	 *        	Entidad de la que hay que crear la tabla
	 * @param mysqli $mysqli
	 *        	conexión a la base de datos
	 */
	private static function createTable (Entity $entidad, $mysqli)
	{
		// Creamos la sentencia sql con los campos de la entidad
		$keys = $entidad->getKeylist ();

		$sql = 'CREATE TABLE ' . $entidad->getTable () . '(';
		$sperador = '';
		$fields = $entidad->getFormFieldlist ();
		foreach ($fields as $nombre => $campo)
		{
			$sql .= $sperador;

			$sql .= CrudModel::getNameAndColumnType ($campo, $nombre, $keys);

			$sperador = ', ';
		}

		if (count ($keys) > 1)
		{
			$sql .= ', PRIMARY KEY (';
			$sep = '';
			foreach ($keys as $currKey)
			{
				$sql .= $sep . $currKey;
				$sep = ',';

			}
			$sql .=')';
		}

		$moreIdx = $entidad->getAdditionalIndexes();
		$i = 0;
		foreach ($moreIdx as $currIdx)
		{
			$i++;
			$sql .=', INDEX i' . $i . ' (';
			$sep = '';
			foreach ($currIdx as $fld)
			{
				$sql .= $sep . $fld . ' ASC';
				$sep = ',';
			}
			$sql .= ')';
		}

		$sql .= ');';

		// Devolvemos mensaje de consecución
		if (self::executeQuery ($mysqli, $sql) === TRUE)
		{
			return $entidad->getTable ();
		}
		else
		{
			return 'FALLO (creando ' . $entidad->getTable () . ')';
		}
	}


	/**
	 * Crea la tabla si no existe, o actualiza los campos de existor
	 *
	 * @param Entity $entidad
	 *        	Entidad de la que hay que crear la tabla
	 * @param mysqli $mysqli
	 *        	conexión a la base de datos
	 */
	public static function createOrUpdateTable (Entity $entidad, $mysqli)
	{
		$sql = 'SELECT 1 FROM ' . $entidad->getTable () . ' LIMIT 1;';

		if (CrudModel::isQuerySucees ($mysqli, $sql))
		{
			return self::alterTable ($entidad, $mysqli);
		}
		else
		{
			return self::createTable ($entidad, $mysqli);
		}
	}


	/**
	 * Carga el contenido desde esta entidad desde la base de datos.
	 * Tiene que estar la condición lista para ser el primer resultado.
	 *
	 * @param Entity $entidad
	 * @param mysqli $mysqli
	 */
	public static function loadFromDatabase (Entity $entidad, $mysqli)
	{
		$sql = self::getListSql ($entidad);
		$resultado = self::executeQuery ($mysqli, $sql);
		if ($resultado->num_rows > 0)
		{
			$linea = $resultado->fetch_assoc ();
			$entidad->fillFromArray ($linea);
		}
	}


	public static function getListSql (Entity $entidad)
	{
		$extraTables = $entidad->getCustomTables ();
		if ((is_array ($extraTables)) && (count ($extraTables) > 0))
		{
			$isMultiTable = true;
		}
		else
		{
			$isMultiTable = false;
		}

		$retval = 'SELECT ';
		$fields = $entidad->getFieldlist ();
		$sep = '';

		foreach ($fields as $campo)
		{
			if ($isMultiTable)
			{
				$retval .= $sep . $entidad->getTable () . '.' . $campo;
			}
			else
			{
				$retval .= $sep . $campo;
			}
			$sep = ',';
		}

		// Si existen tablas custom podremos añadir columnas de dichas tablas, se añadirán en este ámbito
		if (count ($extraTables) > 0)
		{
			$customFields = $entidad->getCustomTableFields ();
			foreach ($customFields as $campo)
			{
				$retval .= $sep . $campo;
			}
			$sep = ',';
		}

		$retval .= ' FROM ' . $entidad->getTable ();

		if ($isMultiTable)
		{
			foreach ($extraTables as $table)
			{
				$retval .= ', ' . $table;
			}
		}

		$customWhere = self::initWhere ($entidad);
		$retval .= self::whereCondicionGroupList ($entidad, $customWhere);

		if ($entidad->getGroupBy () != '')
		{
			$retval .= ' GROUP BY ' . $entidad->getGroupBy ();
		}

		$orderList = $entidad->getOrderList ();
		if ((is_array ($orderList)) && (count ($orderList) > 0))
		{
			$retval .= ' ORDER BY ';
			$seprador = '';
			foreach ($orderList as $columna => $sentido)
			{
				$retval .= $seprador . $columna . ' ' . $sentido;
				$seprador = ', ';
			}
		}

		$rowsPerPage = $entidad->getLimit ();
		if ($rowsPerPage != 0)
		{
			$retval .= ' LIMIT ' . $rowsPerPage;
			$offset = $rowsPerPage * $entidad->getPage ();
			$retval .= ' OFFSET ' . $offset;
		}

		$retval .= ';';

		return $retval;
	}
}


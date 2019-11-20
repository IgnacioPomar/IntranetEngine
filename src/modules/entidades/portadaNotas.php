<?php
class PortadaNotas extends Entity
{
	// --- campos con los que opera esta clase ---
	public $id;
	public $userId;
	public $para;
	public $fecha;
	public $titulo;
	public $mensaje;
	public $color;
	public $templateId;


	public static function getTable ()
	{
		return '{portada_notas}';
	}
	const DEFAULT_FIELD_LIST = array (
			'id',
			'userId',
			'para',
			'fecha',
			'titulo',
			'mensaje',
			'color',
			'templateId'
	);
	//@formatter:off
	const FORM_FIELD_LIST = array (
			'id'        				=> array ('hidden',   '',    0,   'ID'),
			'userId'        		    => array ('number',   '',    0,   'ID Usuario'),
			'para'        		        => array ('text',     '',    50,  'Para usuario'),
			'fecha'                     => array ('datetime', '',    0,   'Fecha'),
			'titulo'                    => array ('text',     '',    50,  'Titulo'),
			'mensaje'                   => array ('text',     '',    150, 'Mensaje'),
			'color'                     => array ('text',     '',    10,  'Color'),
			'templateId'        	    => array ('number',   '',    0,   'ID Template')
	);

	// @formatter:on
	public function setFormFieldlist ()
	{
		$this->formFieldList = self::FORM_FIELD_LIST;
	}


	public function setDefaultFieldlist ()
	{
		$this->fieldList = self::DEFAULT_FIELD_LIST;
	}


	public function setCompleteFieldList ()
	{
		// $this->fieldList = self::DEFAULT_FIELD_LIST;
		$this->fieldList = array_keys ( self::FORM_FIELD_LIST );
	}


	public function getCompleteFieldList ()
	{
		return array_keys ( self::FORM_FIELD_LIST );
	}


	public static function getKeylist ()
	{
		return array (
				'id'
		);
	}


	public function __GET ($k)
	{
		return $this->$k;
	}


	public function __SET ($k, $v)
	{
		return $this->$k = $v;
	}
}

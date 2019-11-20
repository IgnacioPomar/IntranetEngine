<?php
class RoleBasedAccessControl extends Entity
{
	// --- campos con los que opera esta clase ---
	public $idUsuario;
	public $permissions;
	public $templates;


	public static function getTable ()
	{
		return '{rbac}';
	}
	const DEFAULT_FIELD_LIST = array (
			'idUsuario',
			'permissions',
			'templates'
	);
	//@formatter:off
	const FORM_FIELD_LIST = array (
			'idUsuario'        			=> array ('number',   '',    0,   'ID Usuario'),
			'permissions'               => array ('text',     '',    255, 'Permissions'),
			'templates'                 => array ('text',     '',    255, 'Plantillas')
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

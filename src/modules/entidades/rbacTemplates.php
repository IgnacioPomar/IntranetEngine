<?php
class RBACTemplates extends Entity
{
	// --- campos con los que opera esta clase ---
	public $id;
	public $rbacName;
	public $permissions;


	public static function getTable ()
	{
		return '{rbac_templates}';
	}
	const DEFAULT_FIELD_LIST = array (
			'id',
			'rbacName',
			'permissions'
	);
	//@formatter:off
	const FORM_FIELD_LIST = array (
			'id'        				=> array ('number',   '',    0,   'ID Usuario'),
			'rbacName'                  => array ('text',     '',    50,  'Nombre'),
			'permissions'               => array ('text',     '',    255, 'Permissions')
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

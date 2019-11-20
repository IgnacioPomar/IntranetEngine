<?php
class Usuario extends Entity implements ComboData
{
	public $idUsuario;
	public $nombre;
	public $email;
	public $password;
	public $departamento;
	public $isAdmin;
	public $isActive;

	//@formatter:off
	public static $GRN_DEPARTAMENTO_TODOS 				= 100;
	public static $GRN_DEPARTAMENTO_DIRECCION 			= 101;
	public static $GRN_DEPARTAMENTO_ADMINISTRACION 		= 102;
	public static $GRN_DEPARTAMENTO_COMERCIAL 			= 103;
	public static $GRN_DEPARTAMENTO_GESTION 			= 104;
	public static $GRN_DEPARTAMENTO_INFORMATICA 		= 105;
	// @formatter:on
	public static function getTable ()
	{
		return '{usuarios}';
	}
	const DEFAULT_FIELD_LIST = array (
			'idUsuario',
			'nombre',
			'email',
			'password',
			'departamento',
			'isAdmin',
			'isActive'
	);

	//@formatter:off
	const FORM_FIELD_LIST = array (
			'idUsuario'    => array ('hidden',   '',   0, 'idUsuario'),
			'nombre'       => array ('text',     '',  50, 'Nombre'),
			'email'        => array ('email',    '',  50, 'email'),
			'password'     => array ('password', '', 255, 'Password'),
			'departamento' => array ('combo@departamento',   '',   0, 'Departamento'),
			'isAdmin'      => array ('checkbox', '',   0, 'Es administrador'),
			'isActive'     => array ('checkbox', '',   0, 'Esta activo')
	);
	// @formatter:on
	public function getComboData ($mysqli)
	{
		$this->addOrder ( 'nombre' );
		$this->addCondition ( 'isActive', '=', 1 );
		$sql = CrudModel::getListSql ( $this );
		$resultado = CrudModel::executeQuery ( $mysqli, $sql );

		$usuarios = array ();
		$usuarios [0] = 'Ninguno';
		while ( $usuario = $resultado->fetch_assoc () )
		{
			$usuarios [$usuario ['idUsuario']] = $usuario ['nombre'] . ' (' . $usuario ['email'] . ')';
		}

		return $usuarios;
	}


	public function getDepartment ($mysqli)
	{
		$this->addCondition ( 'idUsuario', '=', $_SESSION ['userId'] );
		$sql = CrudModel::getListSql ( $this );
		$resultado = CrudModel::executeQuery ( $mysqli, $sql );

		return $resultado->fetch_assoc () ['departamento'];
	}


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
				'idUsuario'
		);
	}


	public function __GET ($k)
	{
		return $this->$k;
	}


	public function __SET ($k, $v)
	{
		if ($k == 'password')
		{
			$this->$k = password_hash ( $v, PASSWORD_DEFAULT );
		}
		else
		{
			return $this->$k = $v;
		}
	}
}
class Departamento implements ComboData
{


	public function getComboData ($mysqli)
	{
		$departamentos = array ();
		$departamentos [Usuario::$GRN_DEPARTAMENTO_TODOS] = 'Todos';
		$departamentos [Usuario::$GRN_DEPARTAMENTO_DIRECCION] = 'Dirección';
		$departamentos [Usuario::$GRN_DEPARTAMENTO_ADMINISTRACION] = 'Administración';
		$departamentos [Usuario::$GRN_DEPARTAMENTO_COMERCIAL] = 'Comercial';
		$departamentos [Usuario::$GRN_DEPARTAMENTO_GESTION] = 'Gestión';
		$departamentos [Usuario::$GRN_DEPARTAMENTO_INFORMATICA] = 'Informática';

		return $departamentos;
	}
}
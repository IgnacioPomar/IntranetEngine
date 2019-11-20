<?php
include_once ('./src/crud.model.php');
include_once ('./src/plg.php');
include_once ('entidades/rbacEntity.php');
include_once ('entidades/rbacPermissions.php');
include_once ('entidades/rbacTemplates.php');
include_once ('entidades/usuario.php');
class RBAC extends Plugin
{
	var $templates;
	var $tabs = array (
			array (
					'Crear plantilla',
					'<i class="material-icons">control_point</i>'
			),
			array (
					'Editar plantilla',
					'<i class="material-icons">edit</i>'
			),
			array (
					'Nuevo permiso',
					'<i class="material-icons">plus_one</i>'
			),
			array (
					'Permisos de usuarios',
					'<i class="material-icons">build</i>'
			)
	);

	// @formatter:off
	// Defines de las plantillas por defecto
	public static $RBAC_DEFAULT_TEMPLATE 			= 1;
	public static $RBAC_DIRECCION_TEMPLATE          = 2;
	public static $RBAC_ADMINISTRACION_TEMPLATE     = 3;
	public static $RBAC_COMERCIAL_TEMPLATE          = 4;
	public static $RBAC_GESTION_TEMPLATE            = 5;
	public static $RBAC_INFORMATICA_TEMPLATE        = 6;

    // Defines de los permisos
	public static $RBAC_ADD_CONTACT 				= 1;
	public static $RBAC_COMPLETE_EDIT_CONTACT 		= 2;
	public static $RBAC_DEL_CONTACT 				= 3;
	public static $RBAC_ADD_RELATIONSHIP   			= 4;
	public static $RBAC_COMPLETE_EDIT_RELATIONSHIP  = 5;
	public static $RBAC_DEL_RELATIONSHIP   			= 6;
	public static $RBAC_ADD_ACCOUNT        			= 7;
	public static $RBAC_COMPLETE_EDIT_ACCOUNT       = 8;
	public static $RBAC_DEL_ACCOUNT        			= 9;
	public static $RBAC_VIEW_ROL_CLIENT        		= 10;
	public static $RBAC_VIEW_ROL_PROVIDER      		= 11;
	public static $RBAC_VIEW_ROL_STAFF         		= 12;
	public static $RBAC_ADMIN_CLIENT_ROL   			= 13;
	public static $RBAC_ADMIN_PROVIDER_ROL 			= 14;
	public static $RBAC_ADMIN_STAFF_ROL    			= 15;
	public static $RBAC_ADD_ADDRESS        			= 16;
	public static $RBAC_COMPLETE_EDIT_ADDRESS       = 17;
	public static $RBAC_DEL_ADDRESS        			= 18;
	public static $RBAC_PARTIAL_EDIT_ADDRESS 		= 19;
	public static $RBAC_PARTIAL_EDIT_CONTACT        = 20;
	public static $RBAC_PARTIAL_EDIT_ACCOUNT        = 21;
	public static $RBAC_WRITE_COVER_PAGE_NOTE       = 22;
	public static $RBAC_COOKIEPASS                  = 23;
	public static $RBAC_WORLDCHECK                  = 24;
	public static $RBAC_WAKEONLAN_ADMINISTRATOR     = 25;
	// @formatter:on

	/**
	 * Comprueba si tiene los permisos necesarios o no
	 *
	 * @param integer $permissionId
	 * @return boolean
	 */
	public static function hasPermission ($permissionId)
	{
		if (in_array ( $permissionId, $_SESSION ['permissions'] ))
		{
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * Obtiene las opciones del menú habilitadas por sus permisos
	 */
	public static function getOpcsEnabled ()
	{
		$mysqli = @new mysqli ( $GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport'] );

		$sql = 'SELECT * FROM {rbac} WHERE idUsuario = ' . $_SESSION ['userId'];
		$resultado = CrudModel::executeQuery ( $mysqli, $sql );

		$permissions = array();
		
		while ( $permission = $resultado->fetch_assoc () )
		{
			$permissions = explode ( ',', $permission ['permissions'] );
		}

		foreach ( $permissions as $perm )
		{
			$_SESSION ['permissions'] [] = $perm;
		}
	}


	/**
	 * Obtiene los permisos del usuario
	 *
	 * @param integer $mysqli
	 */
	public static function getPermissions ($mysqli)
	{
		$sql = 'SELECT * FROM {rbac_permissions} WHERE idUsuario = ' . $_SESSION ['userId'];
		$resultado = CrudModel::executeQuery ( $mysqli, $sql );

		while ( $permission = $resultado->fetch_assoc () )
		{
			$permissions = explode ( ',', $permission ['permissions'] );
		}

		foreach ( $permissions as $perm )
		{
			$_SESSION ['permissions'] [] = $perm;
		}
	}


	/**
	 * Obtiene la lista completa de permisos existentes
	 *
	 * @return string
	 */
	public static function getPermissionList ()
	{
		$permissionList = '';
		foreach ( $_SESSION ['permissions'] as $perm )
		{
			if ($perm > 10000)
			{
				$permissionList .= ($perm - 10000) . ',';
			}
		}

		if (substr ( $permissionList, strlen ( $permissionList ) - 1, 1 ) == ",")
		{
			$permissionList = substr ( $permissionList, 0, strlen ( $permissionList ) - 1 );
		}

		return $permissionList;
	}


	/**
	 * Elimina los permisos de los plugins
	 *
	 * @param mysqli $mysqli
	 */
	public static function deletePluginPermissions ($mysqli)
	{
		$sql = 'DELETE FROM {rbac_permissions} WHERE id >= 10000';
		CrudModel::executeQuery ( $mysqli, $sql );
	}


	/**
	 * Inserta los permisos de los plugins
	 *
	 * @param integer $idNodoMenu
	 * @param string $plgName
	 * @param mysqli $mysqli
	 */
	public static function registerPluginPermissions ($idNodoMenu, $plgName, $mysqli)
	{
		$sql = 'INSERT INTO {rbac_permissions} (id, permission) VALUES (' . (10000 + $idNodoMenu) . ', "' . $plgName . '")';
		CrudModel::executeQuery ( $mysqli, $sql );
	}


	/**
	 * Muestra html a razón de si el permiso está habilitado o no
	 *
	 * @param integer $permissionId
	 * @param string $htmlIfEnabled
	 * @param string $htmlIfDisabled
	 * @return string
	 */
	public static function setHTMLIfEnabled ($permissionId, $htmlIfEnabled, $htmlIfDisabled = "")
	{
		if (self::hasPermission ( $permissionId ))
		{
			return $htmlIfEnabled;
		}
		else
		{
			return $htmlIfDisabled;
		}
	}


	/**
	 * Obtiene la lista de usuarios
	 *
	 * @return string
	 */
	private function getUserList ()
	{
		$users = new Usuario ();
		$users->addCondition ( 'isActive', '=', 1 );
		$users->setLimit ( 0 );
		$sql = CrudModel::getListSql ( $users );
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

		$retVal = '<select class="form-control" name="selectUser" id="selectUser" onchange="selectorChange()">';
		$retVal .= '<option value="0">Seleccione un usuario</option>';
		while ( $user = $resultado->fetch_assoc () )
		{
			$retVal .= '<option value="' . $user ['idUsuario'] . '">' . $user ['nombre'] . '</option>';
		}
		$retVal .= '</select>';

		return $retVal;
	}


	/**
	 * Muestra las pestañas
	 *
	 * @return string
	 */
	private function showTabs ()
	{
		$retVal = '';

		$opChecked = 'Crearplantilla';

		if (isset ( $_GET ['tab'] ))
		{
			$opChecked = $_GET ['tab'];
		}

		foreach ( $this->tabs as $tab )
		{
			if ($opChecked == str_replace ( " ", "", $tab [0] ))
			{
				$retVal .= '<div class="tab activeTab" id="' . str_replace ( " ", "", $tab [0] ) . '" onclick="switchTabs(\'' . $this->uriPrefix . '\', \'' . str_replace ( " ", "", $tab [0] ) . '\')">' . $tab [1] . ' ' . $tab [0] . '</div>';
			}
			else
			{
				$retVal .= '<div class="tab" id="' . str_replace ( " ", "", $tab [0] ) . '" onclick="switchTabs(\'' . $this->uriPrefix . '\', \'' . str_replace ( " ", "", $tab [0] ) . '\')">' . $tab [1] . ' ' . $tab [0] . '</div>';
			}
		}

		return $retVal;
	}


	/**
	 * Muestra el contenedor principal
	 *
	 * @return string
	 */
	private function mainContainer ()
	{
		$retVal = '<div class="container marco">';
		$retVal .= '<div class="tabs">' . $this->showTabs () . '</div>';
		$retVal .= '<div class="contenedor">';
		$retVal .= '<h1>Role-Based Access Control</h1>';
		$retVal .= '<br />';
		$display = 'style="display:none;"';
		if (isset ( $_GET ['tab'] ) && $_GET ['tab'] == "Crearplantilla")
		{
			$display = '';
		}
		else if (! isset ( $_GET ['tab'] ))
		{
			$display = '';
		}
		$retVal .= '<div id="CrearplantillaDiv" ' . $display . '>' . $this->showAddTemplate () . '</div>';
		$display = 'style="display:none;"';
		if (isset ( $_GET ['tab'] ) && $_GET ['tab'] == "Editarplantilla")
		{
			$display = '';
		}
		$retVal .= '<div id="EditarplantillaDiv" ' . $display . '>' . $this->showTemplateSelector () . '</div>';
		$display = 'style="display:none;"';
		if (isset ( $_GET ['tab'] ) && $_GET ['tab'] == "Nuevopermiso")
		{
			$display = '';
		}
		$retVal .= '<div id="NuevopermisoDiv" ' . $display . '>' . $this->showAddPermission () . '</div>';
		$display = 'style="display:none;"';
		if (isset ( $_GET ['tab'] ) && $_GET ['tab'] == "Permisosdeusuarios")
		{
			$display = '';
		}
		$retVal .= '<div id="PermisosdeusuariosDiv" ' . $display . '>' . $this->showUserSelector () . '</div>';
		$retVal .= '</div>';
		$retVal .= '</div>';

		return $retVal;
	}


	/**
	 * Muestra la selección de usuario para posteriormente editar sus permisos.
	 *
	 * @return string
	 */
	private function showUserSelector ()
	{
		$retVal = '<div class="col-md-4">';
		$retVal .= $this->getUserList ();
		$retVal .= '</div>';
		$retVal .= '<div class="col-md-4" id="userTemplate">';
		$retVal .= '</div>';
		$retVal .= '<div class="col-md-4" id="userName">';
		$retVal .= '</div><br />';
		$retVal .= '<div class="col-md-12" id="loadPermissions" style="margin-top:20px;">';
		$retVal .= '</div>';

		return $retVal;
	}


	/**
	 * Muestra el nombre del usuario
	 *
	 * @param integer $userId
	 * @return string
	 */
	private function getUserName ($userId)
	{
		$user = new Usuario ();
		$user->addCondition ( 'idUsuario', '=', $userId );
		$sql = CrudModel::getListSql ( $user );
		$result = CrudModel::executeQuery ( $this->mysqli, $sql );

		return $result->fetch_assoc () ['nombre'];
	}


	private function getTemplateName ($templateId)
	{
		if (! $templateId)
		{
			return;
		}

		$rbacDefPerms = new RBACTemplates ();
		$rbacDefPerms->addCondition ( 'id', '=', $templateId );
		$rbacDefPerms->setLimit ( 0 );
		$sql = CrudModel::getListSql ( $rbacDefPerms );
		$result = CrudModel::executeQuery ( $this->mysqli, $sql );
		return $result->fetch_assoc () ['rbacName'];
	}


	/**
	 * Muestra el panel de edición de permisos de usuario
	 *
	 * @param integer $userId
	 * @return string
	 */
	private function showPermissionsEdit ($userId, $templateId)
	{
		$retVal = '';
		if (isset ( $_POST ['saveEdit'] ))
		{
			$rbac = new RoleBasedAccessControl ();
			$rbac->addCondition ( 'idUsuario', '=', $userId );
			CrudModel::deleteCondicionList ( $rbac, $this->mysqli );

			$permissions = '';

			foreach ( $_POST as $post )
			{
				if (is_numeric ( $post ))
				{
					$permissions .= $post . ',';
				}
			}

			$permissions = substr ( $permissions, 0, strlen ( $permissions ) - 1 );

			$rbac->__SET ( 'idUsuario', $userId );
			$rbac->__SET ( 'permissions', $permissions );
			// $rbac->__SET ( 'templates', $_POST ['saveEdit'] );

			CrudModel::insert ( $rbac, $this->mysqli );

			$permissions = explode ( ',', $permissions );

			foreach ( $permissions as $perm )
			{
				$_SESSION ['permissions'] [] = $perm;
			}

			return $this->mainContainer ();
		}
		else
		{
			$permissions = '';
			$templates = '';
			if ($userId)
			{
				$rbacUserPerms = new RoleBasedAccessControl ();
				$rbacUserPerms->addCondition ( 'idUsuario', '=', $userId );
				$rbacUserPerms->setLimit ( 0 );
				$sql = CrudModel::getListSql ( $rbacUserPerms );
				$result = CrudModel::executeQuery ( $this->mysqli, $sql );

				while ( $perm = $result->fetch_assoc () )
				{
					$permissions = $perm ['permissions'];
					// $templates = $perm ['templates'];
					// $_GET ['selectedTemplates'] = $templates;
				}
			}
			else
			{
				return '<script>$("#userName").html("");</script>';
			}

			$temArr = array ();
			if (isset ( $_GET ['selectedTemplates'] ))
			{
				$templates = $_GET ['selectedTemplates'];

				if (! in_array ( $templateId, explode ( ",", $templates ) ))
				{
					$templates .= $templateId . ',';
				}

				foreach ( explode ( ",", $templates ) as $template )
				{
					if ($template == "" || in_array ( $template, $temArr ))
					{
						continue;
					}

					$temArr [] = $template;
					$retVal .= '<div class="col-md-12 usedTemplates" id="' . str_replace ( " ", "", $this->getTemplateName ( $templateId ) ) . '"><div class="col-md-3"><input type="text" class="form-control" disabled value="' . $this->getTemplateName ( $template ) . '" /></div> </div><br />';
				}
				// $retVal .= '<i class="material-icons btn btn-danger">remove_circle</i>';
				$retVal .= '<button class="btn btn-primary" onclick="clearTemplates()">Limpiar</button>';

				if (substr ( $templates, strlen ( $templates ) - 1, strlen ( $templates ) ) == ",")
				{
					$templates = substr ( $templates, 0, strlen ( $templates ) - 1 );
				}
			}

			if ($templates)
			{
				$rbacDefPerms = new RBACTemplates ();
				$rbacDefPerms->addCondition ( 'id', 'IN', '(' . $templates . ')' );
				$rbacDefPerms->setLimit ( 0 );
				$sql = CrudModel::getListSql ( $rbacDefPerms );
				$result = CrudModel::executeQuery ( $this->mysqli, $sql );
				while ( $perm = $result->fetch_assoc () )
				{
					$permissions .= $perm ['permissions'] . ',';
				}

				$permissions = substr ( $permissions, 0, strlen ( $permissions ) - 1 );
			}

			$rbacPerms = new RBACPermissions ();
			$rbacPerms->setLimit ( 0 );
			$sql = CrudModel::getListSql ( $rbacPerms );
			$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

			$retVal .= '<br /><br /><form method="POST" action="' . $this->uriPrefix . 'action=userPermissions&userId=' . $userId . '&templateId=' . $templateId . '&selectedTemplates=' . $templates . '&permissions=' . $permissions . '"><script>$("#userName").html("' . $this->getUserName ( $userId ) . '"); $("#userTemplate").html(\'' . $this->getDefaultTemplatesForUser ( $userId, $templateId ) . '\');</script>';

			$retVal .= '<input type="hidden" id="saveEdit" name="saveEdit" value="' . $templates . '" />';

			while ( $perms = $resultado->fetch_assoc () )
			{
				$checked = '';
				foreach ( explode ( ",", $permissions ) as $permission )
				{
					if ($permission == $perms ['id'])
					{
						$checked = 'checked="checked"';
					}
				}

				$retVal .= '<div class="col-md-4 permissionRow">';
				$retVal .= '    <div class="col-md-10">';
				$retVal .= $perms ['permission'];
				$retVal .= '    </div>';
				$retVal .= '    <div class="col-md-2">';
				$retVal .= '        <label class="switch">';
				$retVal .= '            <input type="checkbox" ' . $checked . ' name="perm-' . $perms ['id'] . '" id="perm-' . $perms ['id'] . '" value="' . $perms ['id'] . '" />';
				$retVal .= '            <span class="slider round"></span>';
				$retVal .= '        </label>';
				$retVal .= '    </div>';
				$retVal .= '</div>';
			}

			$retVal .= '<button class="btn btn-default">Guardar cambios</button></form>';
		}
		return $retVal;
	}


	/**
	 * Muestra el panel de creación de plantillas
	 *
	 * @return string
	 */
	private function showAddTemplate ()
	{
		$templateId = '';
		$rbacPerms = new RBACPermissions ();
		$rbacPerms->setLimit ( 0 );
		$sql = CrudModel::getListSql ( $rbacPerms );
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

		$retVal = '<form id="createTemplate" method="POST" action="' . $this->uriPrefix . 'action=add"><div class="col-md-4">';
		$retVal .= '<input type="text" class="form-control" name="templateTitle" id="templateTitle" placeholder="Escriba un nombre para su plantilla..." />';
		$retVal .= '</div>';
		$retVal .= '<div class="col-md-6">';
		$retVal .= '</div>';
		$retVal .= '<div class="col-md-12" style="margin-top:20px;">';
		while ( $perms = $resultado->fetch_assoc () )
		{
			$checked = '';
			if ($templateId)
			{
				foreach ( explode ( ",", $permissions ) as $permission )
				{
					if ($permission == $perms ['id'])
					{
						$checked = 'checked="checked"';
					}
				}
			}
			$retVal .= '<div class="col-md-5 permissionRow">';
			$retVal .= '    <div class="col-md-8">';
			$retVal .= $perms ['permission'];
			$retVal .= '    </div>';
			$retVal .= '    <div class="col-md-4">';
			$retVal .= '        <label class="switch">';
			$retVal .= '            <input type="checkbox" name="perm-' . $perms ['id'] . '" id="perm-' . $perms ['id'] . '" />';
			$retVal .= '            <span class="slider round"></span>';
			$retVal .= '        </label>';
			$retVal .= '    </div>';
			$retVal .= '</div>';
		}
		$retVal .= '<button class="btn btn-default">Crear plantilla</button>';
		$retVal .= '</div></form>';

		return $retVal;
	}


	/**
	 * Obtiene de base de datos las plantillas añadidas
	 *
	 * @return string
	 */
	private function getDefaultTemplates ()
	{
		$rbacPerms = new RBACTemplates ();
		$rbacPerms->setLimit ( 0 );
		$sql = CrudModel::getListSql ( $rbacPerms );
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

		$retVal = '<select class="form-control" name="selectTemplate" id="selectTemplate" onchange="selectTemplate()">';
		$retVal .= '<option value="0">Seleccione una plantilla</option>';
		while ( $template = $resultado->fetch_assoc () )
		{
			$retVal .= '<option value="' . $template ['id'] . '">' . $template ['rbacName'] . '</option>';
		}
		$retVal .= '</select>';

		return $retVal;
	}


	/**
	 * Obtiene de base de datos las plantillas añadidas
	 *
	 * @return string
	 */
	private function getDefaultTemplatesForUser ($userId, $templateId)
	{
		$rbacPerms = new RBACTemplates ();
		$rbacPerms->setLimit ( 0 );
		$sql = CrudModel::getListSql ( $rbacPerms );
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

		$retVal = '<select class="form-control" name="selectUserTemplate" id="selectUserTemplate" onchange="selectUserTemplate(' . $userId . ')">';
		$retVal .= '<option value="0">Seleccione una plantilla</option>';
		while ( $template = $resultado->fetch_assoc () )
		{
			$retVal .= '<option value="' . $template ['id'] . '">' . $template ['rbacName'] . '</option>';
		}
		$retVal .= '</select>';

		return $retVal;
	}


	/**
	 * Muestra la selección de plantillas para su posterior edición
	 *
	 * @return string
	 */
	private function showTemplateSelector ()
	{
		$retVal = '<div class="col-md-4">';
		$retVal .= $this->getDefaultTemplates ();
		$retVal .= '</div>';
		$retVal .= '<div class="col-md-12" id="loadTemplate" style="margin-top:20px;">';
		$retVal .= '</div>';

		return $retVal;
	}


	/**
	 * Muestra el panel de edición de plantillas
	 *
	 * @param integer $templateId
	 * @return void|string
	 */
	private function showEditTemplate ($templateId)
	{
		$retVal = '';
		if (isset ( $_POST ['rbacName'] ))
		{
			$permissions = '';
			foreach ( $_POST as $post )
			{
				if (is_numeric ( $post ))
				{
					$permissions .= $post . ',';
				}
			}

			$permissions = substr ( $permissions, 0, strlen ( $permissions ) - 1 );

			$sql = 'UPDATE {rbac_templates} SET rbacName = "' . $_POST ['rbacName'] . '", permissions = "' . $permissions . '" WHERE id =' . $templateId;
			CrudModel::executeQuery ( $this->mysqli, $sql );

			unset ( $_POST );

			return $this->mainContainer ();
		}
		else
		{
			$permissions = '';
			$rbacName = '';
			if ($templateId)
			{
				$rbacDefPerms = new RBACTemplates ();
				$rbacDefPerms->addCondition ( 'id', '=', $templateId );
				$rbacDefPerms->setLimit ( 0 );
				$sql = CrudModel::getListSql ( $rbacDefPerms );
				$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

				while ( $rbac = $resultado->fetch_assoc () )
				{
					$permissions = $rbac ['permissions'];
					$rbacName = $rbac ['rbacName'];
				}
			}
			else
			{
				return;
			}

			$rbacPerms = new RBACPermissions ();
			$rbacPerms->setLimit ( 0 );
			$sql = CrudModel::getListSql ( $rbacPerms );
			$result = CrudModel::executeQuery ( $this->mysqli, $sql );

			$retVal .= '<form method="POST" action="' . $this->uriPrefix . 'action=edit&templateId=' . $templateId . '"><div class="col-md-12" style="margin-top:20px;">';
			$retVal .= '<div class="col-md-4" style="height:80px;"><label for="rbacName">Título de plantilla: </label><input type="text" class="form-control" id="rbacName" name="rbacName" value="' . $rbacName . '" /></div><div class="col-md-8" style="height:80px;"></div><br /><br /><br />';
			while ( $perms = $result->fetch_assoc () )
			{
				$checked = '';
				foreach ( explode ( ",", $permissions ) as $permission )
				{
					if ($permission == $perms ['id'])
					{
						$checked = 'checked="checked"';
					}
				}

				$retVal .= '<div class="col-md-12 permissionRow">';
				$retVal .= '    <div class="col-md-8">';
				$retVal .= $perms ['permission'];
				$retVal .= '    </div>';
				$retVal .= '    <div class="col-md-4">';
				$retVal .= '        <label class="switch">';
				$retVal .= '            <input type="checkbox" ' . $checked . ' name="perm-' . $perms ['id'] . '" id="perm-' . $perms ['id'] . '" value="' . $perms ['id'] . '" />';
				$retVal .= '            <span class="slider round"></span>';
				$retVal .= '        </label>';
				$retVal .= '    </div>';
				$retVal .= '</div>';
			}
			$retVal .= '</div>';

			$retVal .= '<button class="btn btn-default right">Guardar cambios</button></form>';
			$retVal .= '<form method="POST" action="' . $this->uriPrefix . 'action=remove"><input type="hidden" name="template" id="template" value="' . $templateId . '" /><button class="btn btn-danger left">Eliminar plantilla</button></form>';
		}
		return $retVal;
	}


	private function showAddPermission ()
	{
		$retVal = '';
		if (isset ( $_POST ['permission'] ))
		{
			$sql = 'INSERT INTO {rbac_permissions} (id, permission) VALUES (' . $this->getNewPermissionID () . ', "' . $_POST ['permission'] . '")';
			CrudModel::executeQuery ( $this->mysqli, $sql );

			return 'Permiso añadido correctamente <a class="btn btn-primary" href="' . $this->uriPrefix . 'tab=Nuevopermiso">Añadir más permisos</a>';
		}
		else
		{
			$retVal .= '<form method="POST">';
			$retVal .= '<div class="col-md-4"><input type="text" class="form-control" name="permission" id="permission" placeholder="Nombre del permiso" style="width:400px;" /></div>';
			$retVal .= '<div class="col-md-2"><button class="btn btn-default right">Guardar cambios</button></div>';
			$retVal .= '</form>';
		}

		return $retVal;
	}


	private function getNewPermissionID ()
	{
		$sql = 'SELECT id FROM {rbac_permissions} WHERE id < 10000 ORDER BY id DESC LIMIT 1';
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

		return ++ $resultado->fetch_assoc () ['id'];
	}


	/**
	 * Inserta una nueva plantilla en la base de datos
	 *
	 * @return void|string
	 */
	private function addNewTemplate ()
	{
		if (isset ( $_POST ['templateTitle'] ))
		{
			if ($_POST ['templateTitle'] == "")
			{
				echo "El título de la plantilla no puede estar vacío.";
				return;
			}

			$rbacPerms = new RBACPermissions ();
			$rbacPerms->setLimit ( 0 );
			$sql = CrudModel::getListSql ( $rbacPerms );
			$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

			$permissions = '';
			while ( $perms = $resultado->fetch_assoc () )
			{
				if (isset ( $_POST ['perm-' . $perms ['id']] ))
				{
					if ($_POST ['perm-' . $perms ['id']] == "on")
					{
						$permissions .= $perms ['id'] . ',';
					}
				}
			}

			if (substr ( $permissions, strlen ( $permissions ) - 1, 1 ) == ",")
			{
				$permissions = substr ( $permissions, 0, strlen ( $permissions ) - 1 );
			}

			$sql = 'INSERT INTO {rbac_templates} (rbacName, permissions) VALUES ("' . $_POST ['templateTitle'] . '", "' . $permissions . '");';
			CrudModel::executeQuery ( $this->mysqli, $sql );

			return $this->mainContainer ();
		}
	}


	private function removeTemplate ()
	{
		if (isset ( $_POST ['template'] ))
		{
			$rbacTemplate = new RBACTemplates ();
			$rbacTemplate->addCondition ( 'id', '=', $_POST ['template'] );
			CrudModel::deleteCondicionList ( $rbacTemplate, $this->mysqli );

			return $this->mainContainer ();
		}
	}


	public static function getUserTemplates ($mysqli)
	{
		$rbacUserPerms = new RoleBasedAccessControl ();
		$rbacUserPerms->addCondition ( 'idUsuario', '=', $_SESSION ['userId'] );
		$rbacUserPerms->setLimit ( 0 );
		$sql = CrudModel::getListSql ( $rbacUserPerms );
		$result = CrudModel::executeQuery ( $mysqli, $sql );

		return $result->fetch_assoc () ['templates'];
	}


	// -----------------------------------------------------------------------
	// ------------------ Funciones básicas de un plugin --------------------
	// -----------------------------------------------------------------------
	public static function getSkin ()
	{
		return 'skel.htm';
	}


	public static function getExternalCss ()
	{
		$css = array ();
		$css [] = 'bootstrap.min.css';
		$css [] = 'rbac.css';

		return $css;
	}


	public static function getExternalJs ()
	{
		$js = array ();
		$js [] = 'vendor/bootstrap/js/bootstrap.min.js';
		$js [] = 'rbac.js';

		return $js;
	}


	public static function getPlgInfo ()
	{
		$plgInfo = array ();
		$plgInfo ['plgName'] = 'RBAC';
		$plgInfo ['plgDescription'] = 'RBAC';
		$plgInfo ['isMenu'] = 1;
		$plgInfo ['isMenuAdmin'] = 1;
		$plgInfo ['isSkinable'] = 1;

		return $plgInfo;
	}


	/**
	 *
	 * Llamamos a CrudModel para crear o actualizar las tablas desde las entidades
	 *
	 * @param mysqli $mysqli
	 *        	Conexión al servidor mysql
	 */
	public static function updateTables ($mysqli)
	{
		$rbacDefaultPerms = new RBACTemplates ();
		$msg = CrudModel::createOrUpdateTable ( $rbacDefaultPerms, $mysqli );
		$rbac = new RoleBasedAccessControl ();
		$msg .= CrudModel::createOrUpdateTable ( $rbac, $mysqli );
		$rbacPerms = new RBACPermissions ();
		$msg .= CrudModel::createOrUpdateTable ( $rbacPerms, $mysqli );

		return $msg;
	}


	public static function main ($mysqli)
	{
		$rbac = new RBAC ( $mysqli );

		if (isset ( $_GET ['action'] ))
		{
			switch ($_GET ['action'])
			{
				case 'userPermissions':
					$templateId = 0;
					if (isset ( $_GET ['templateId'] ))
					{
						$templateId = $_GET ['templateId'];
					}
					return $rbac->showPermissionsEdit ( $_GET ['userId'], $templateId );
					break;
				case 'edit':
					return $rbac->showEditTemplate ( $_GET ['templateId'] );
					break;
				case 'add':
					return $rbac->addNewTemplate ();
					break;
				case 'remove':
					return $rbac->removeTemplate ();
					break;
			}
		}
		else
		{
			return $rbac->mainContainer ();
		}
	}
}

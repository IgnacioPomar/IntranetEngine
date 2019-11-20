<?php
include_once 'entidades/usuario.php';
class GestorUsuarios extends Plugin
{


	/**
	 * Esta funcion crea un nuevo grupo y devuelve su id.
	 */
	private function nuevoGrupo ()
	{
		if (isset ( $_POST ['grupo'] ))
		{
			$mysqli = $this->mysqli;
			$sql = 'SELECT * FROM {grupos} WHERE descripcion = "' . $mysqli->real_escape_string ( $_POST ['grupo'] ) . '";';
			$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
			if ($resultado->num_rows == 0)
			{

				$sql = 'INSERT INTO {grupos} (descripcion) VALUES("' . $mysqli->real_escape_string ( $_POST ['grupo'] ) . '");';
				if ($mysqli->query ( prefixQuery ( $sql ) ) === TRUE)
				{
					$idGrupo = $mysqli->insert_id;
					if (isset ( $_GET ['usr'] ) && is_numeric ( $_GET ['usr'] ))
					{
						$this->grabaUsuarioGrupo ( $_GET ['usr'], $idGrupo, 'on' );
					}
					return $idGrupo;
				}
				else
				{
					echo "Error: La ejecución de la consulta falló debido a: \n";
					echo "Query: " . $sql . "\n";
					echo "Errno: " . $mysqli->errno . "\n";
					echo "Error: " . $mysqli->error . "\n";
				}
			}
			else
			{
				return 'El grupo \'' . $_POST ['grupo'] . '\' ya existe.';
			}
			$resultado->free ();
		}
	}


	private function listadoGrupos ($idUsuario)
	{
		$retVal = '<h1 style="clear: both;">Grupos a los que pertecence este usuario</h1>';
		$retVal .= '<form method="post" action="?opc=GestorUsuarios&action=grabaGrpUsr&usr=' . $idUsuario . '">';
		$retVal .= ' <input type="hidden" name="usr" value="' . $idUsuario . '">';
		$sql = 'SELECT idGrupo, descripcion, (SELECT COUNT(*) FROM {usuarios_grupos} WHERE idUsuario=' . $idUsuario . ' ANd {grupos}.idGrupo = {usuarios_grupos}.idGrupo) as activo FROM {grupos};';

		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
		while ( $fila = $resultado->fetch_assoc () )
		{
			if ($fila ['activo'] > 0)
			{
				$check = ' checked="true" ';
			}
			else
				$check = '';

			$retVal .= ' <label for="grp_' . $fila ['idGrupo'] . '"><input type="checkbox" ' . $check . ' id="grp_' . $fila ['idGrupo'] . '" name="grp_' . $fila ['idGrupo'] . '" />' . $fila ['descripcion'] . '</label><br />';
		}

		$retVal .= '<div class="field"><label></label><button class="btn" type="submit" value="Grabar">Grabar</button></div>';
		$retVal .= '</form>';

		$retVal .= '<form method="post" action="?opc=GestorUsuarios&action=nuevoGrupo">
				<input type="text" name="grupo" placeholder="Nuevo Grupo" required></form>';

		return $retVal;
	}


	private function grabaUsuarioGrupo ($idUsuario, $idGrupo, $modo)
	{
		$mysqli = $this->mysqli;
		$sql = 'SELECT * FROM {usuarios_grupos} WHERE idUsuario=' . $idUsuario . ' AND idGrupo=' . $idGrupo . ';';
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
		if ($resultado->num_rows == 0 && $modo == 'on')
		{
			$sql = 'INSERT INTO {usuarios_grupos} (idUsuario,idGrupo) VALUES (' . $idUsuario . ', ' . $idGrupo . ');';
		}
		else
		{
			$sql = 'DELETE FROM {usuarios_grupos} WHERE idUsuario=' . $idUsuario . ' AND idGrupo=' . $idGrupo . ';';
		}

		if ($mysqli->query ( prefixQuery ( $sql ) ) === TRUE)
		{
			return 'Grupo actualizado correctamente';
		}
		else
		{
			echo "Error: La ejecución de la consulta falló debido a: \n";
			echo "Query: " . $sql . "\n";
			echo "Errno: " . $mysqli->errno . "\n";
			echo "Error: " . $mysqli->error . "\n";
		}
	}


	private function nuevoUsuario ()
	{
		$usuario = new Usuario ();
		if (isset ( $_POST ['idUsuario'] ))
		{
			CrudModel::setEntidadFieldListFromSupportedFields ( $usuario );
			$usuario->fillFromArray ( $_POST );
			return CrudModel::insert ( $usuario, $this->mysqli );
		}
		else
		{
			$retVal = '<h1>Crear un nuevo usuario</h1>';
			$link = '?opc=GestorUsuarios&action=nuevoUsr';
			$retVal .= CrudModel::generateFormFromEntyty ( $usuario, $this->mysqli, $link );
		}

		return $retVal;
	}


	private function editaUsuario ($idUsuario)
	{
		$usuario = new Usuario ();

		if (isset ( $_POST ['idUsuario'] ))
		{

			$usuario->addCondition ( 'idUsuario', '=', $idUsuario );
			CrudModel::setEntidadFieldListFromSupportedFields ( $usuario );
			$usuario->fillFromArray ( $_POST );

			return CrudModel::update ( $usuario, $this->mysqli );
		}
		else
		{
			$retVal = '<h1>Editar datos del usuario</h1>';
			$link = '?opc=GestorUsuarios&action=editaUsr&usr=' . $idUsuario;
			$usuario->addCondition ( 'idUsuario', '=', $idUsuario );
			$retVal .= CrudModel::generateFilledFormFromEntyty ( $usuario, $this->mysqli, $link );
		}

		return $retVal;
	}


	private function eliminaUsuario ($idUsuario)
	{
		if (isset ( $_POST ['idUsuario'] ))
		{
			// ----- Eliminamos los grupos del usuario -----
			$sql = 'DELETE FROM {usuarios_grupos} WHERE idUsuario = ' . $idUsuario . ';';
			$mysqli = $this->mysqli;
			if ($mysqli->query ( prefixQuery ( $sql ) ) === TRUE)
			{
				$retVal = 'Grupos eliminados correctamente';
			}
			else
			{
				echo "Error: La ejecución de la consulta falló debido a: \n";
				echo "Query: " . $sql . "\n";
				echo "Errno: " . $mysqli->errno . "\n";
				echo "Error: " . $mysqli->error . "\n";
			}

			// ----- Eliminamos el Usuario
			$usuario = new Usuario ();
			$usuario->addCondition ( 'idUsuario', '=', $idUsuario );
			return $retVal . '<br/>' . CrudModel::deleteCondicionList ( $usuario, $this->mysqli );
		}
		else
		{
			$retVal = '<h1>¿Est&aacute;s seguro de eliminar este usuario?</h1>';

			$link = '?opc=GestorUsuarios&action=eliminaUsr&usr=' . $idUsuario;
			$usuario = new Usuario ();
			$usuario->addCondition ( 'idUsuario', '=', $idUsuario );
			$retVal .= CrudModel::generateFilledFormFromEntyty ( $usuario, $this->mysqli, $link, true, 'Eliminar' );
		}
		return $retVal;
	}


	private function detalleUsuario ($idUsuario)
	{
		$usuario = new Usuario ();
		$usuario->addCondition ( 'idUsuario', '=', $idUsuario );
		$usuario->setCompleteFieldList ();

		$sql = CrudModel::getListSql ( $usuario );
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
		$campos = $resultado->fetch_assoc ();
		$resultado->free ();

		$usuario->fillFromArray ( $campos );
		$retVal = '<h1>Detalle de un usuario</h1>';
		$retVal .= CrudModel::generateFormFromEntyty ( $usuario, $this->mysqli, '', true );

		// Ahora los botones con las acciones
		$retVal .= '<div class="right">';
		$retVal .= '<a class="btn" href="?opc=GestorUsuarios&action=editaUsr&usr=' . $idUsuario . '"><i class="material-icons">create</i>Editar</a>';
		$retVal .= '<a class="btn" href="?opc=GestorUsuarios&action=eliminaUsr&usr=' . $idUsuario . '"><i class="material-icons">delete</i>Eliminar</a><br />';
		$retVal .= '</div>';

		return $retVal;
	}


	private function muestraUsuario ($usuario)
	{
		$retVal = '<tr>';
		// Indice
		$retVal .= '<input type="hidden" class="idUsuario" name="idUsuario" value="' . $usuario ['idUsuario'] . '" />' . PHP_EOL;
		// Nombre y correo
		$retVal .= '<td class="">' . $usuario ['nombre'] . '</td>';
		$retVal .= '<td class="">' . $usuario ['email'] . '</td>';

		// Grupos
		$retVal .= '<td class="text grupos">' . $this->muestraUsuarioGrupos ( $usuario ['idUsuario'] ) . '</td>';

		// Es administrador
		$retVal .= '<td class="text" style="text-align:center;">';
		$retVal .= '<input type="checkbox" id="' . $usuario ['idUsuario'] . '-isAdmin" name="isAdmin" class="checkbox" ' . (($usuario ['isAdmin'] == 1) ? 'checked= true' : '') . ' disabled="disabled">';
		$retVal .= '<label for="' . $usuario ['idUsuario'] . '-esAdmin"></label>';
		$retVal .= '</td>';
		// $retVal .= '<input type="checkbox" name="target" value="1" ' . (($usuario ['target'] == 1) ? 'checked= true' : '') . ' disabled="disabled"/></td>' . PHP_EOL;

		// Está activo
		$retVal .= '<td class="text" style="text-align:center;">';
		$retVal .= '<input type="checkbox" id="' . $usuario ['idUsuario'] . '-isActive" name="isActive" class="checkbox" ' . (($usuario ['isActive'] == 1) ? 'checked= true' : '') . ' disabled="disabled">';
		$retVal .= '<label for="' . $usuario ['idUsuario'] . '-isActive"></label>';
		$retVal .= '</td>';
		// $retVal .= '<input type="checkbox" name="target" value="1" ' . (($usuario ['target'] == 1) ? 'checked= true' : '') . ' disabled="disabled"/></td>' . PHP_EOL;

		// Editar, borrar, ...
		$retVal .= '<td><div class="btn-group-container right cto-btn">';
		$retVal .= '<div class="btn-group">';
		$retVal .= '<a href="?opc=GestorUsuarios&action=detalleUsr&usr=' . $usuario ['idUsuario'] . '"><i class="material-icons">visibility</i>Ver usuario</a>';
		$retVal .= '<br><a href="?opc=GestorUsuarios&action=editaUsr&usr=' . $usuario ['idUsuario'] . '"><i class="material-icons">create</i>Editar usuario</a>';
		$retVal .= '<br><a href="?opc=GestorUsuarios&action=eliminaUsr&usr=' . $usuario ['idUsuario'] . '"><i class="material-icons">delete</i>Eliminar usuario</a>';
		$retVal .= '</div>';
		$retVal .= '<i class="material-icons">more_vert</i>';
		$retVal .= '</div></td>';

		$retVal .= '</tr>';

		return $retVal;
	}


	private function muestraUsuarioGrupos ($idUsuario)
	{
		$sql = 'SELECT idGrupo, descripcion, (SELECT COUNT(*) FROM {usuarios_grupos} WHERE idUsuario=' . $idUsuario . ' ANd {grupos}.idGrupo = {usuarios_grupos}.idGrupo) as activo FROM {grupos};';
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

		$multiselectOptions = '<ul class="multiselectoptions down">';
		$multiselectOptionsSelected = '';
		$separador = '';
		while ( $fila = $resultado->fetch_assoc () )
		{
			$checked = '';
			if ($fila ['activo'] > 0)
			{
				$checked = 'checked';
				$multiselectOptionsSelected .= $separador . $fila ['descripcion'];
				$separador = ', ';
			}
			$multiselectOptions .= '<li class="' . $checked . '" data-action="?opc=GestorUsuarios&action=grabaGrpUsr&usr=' . $idUsuario . '&grupo=' . $fila ['idGrupo'] . '">';
			$multiselectOptions .= '<input type="checkbox" id="ms' . $idUsuario . '-option-' . $fila ['idGrupo'] . '" class="checkbox" ' . $checked . '>';
			$multiselectOptions .= '<label for="ms' . $idUsuario . '-option-' . $fila ['idGrupo'] . '" title="' . $fila ['descripcion'] . '">' . $fila ['descripcion'] . '</label>';
			$multiselectOptions .= '</li>';
		}
		$multiselectOptions .= '<li class="creator" title="Añadir grupo" data-action="?opc=GestorUsuarios&action=nuevoGrupo&usr=' . $idUsuario . '">+ Añadir grupo</li></ul>';

		// TODO: cambiar la clase down a up según convenga, javascript
		$class = '';
		if ($multiselectOptionsSelected == '')
		{
			$multiselectOptionsSelected = 'sin grupo';
			$class = 'singrupo';
		}
		$retVal = '<div class="multiselect down" style="min-width: 100px;"><span class="' . $class . '">' . $multiselectOptionsSelected . '</span><span class="icon-drop-down"></span>';
		$retVal .= $multiselectOptions;
		$retVal .= '</div>';

		return $retVal;
	}


	private function muestraListaUsusarios ($search, $pagina)
	{
		$numRegistros = 0;
		$usuario = new Usuario ();

		$usuario->addOrder ( 'isActive', 'DESC' );
		$usuario->addOrder ( 'nombre' );
		$usuario->setPage ( $pagina );
		$usuario->setLimit ( 40 );

		// TODO: Evaluar formulas dentro del search, o si se trata de un código
		if ($search != '')
		{
			$usuario->addCondition ( 'nombre', 'like', '"%' . $search . '%"' );
		}

		$sql = CrudModel::getListSql ( $usuario );
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );

		// $retVal = GestorTerceros::muestraCajaBusqueda ();
		$retVal = '<h1>Usuarios en la aplicaci&oacute;n</h1>';

		$retVal .= '<div class="marco" style="position:relative">';
		$retVal .= '<div class="controles"><a class="btn" href="?opc=GestorUsuarios&action=nuevoUsr"><i class="material-icons">create</i> Nuevo</a></div>';
		$retVal .= '<table>';
		$retVal .= '<thead><tr><th>Nombre</th><th>Correo</th><th>Grupos</th><th style="text-align: center;">Administrador</th><th style="text-align: center;">Activo</th><th></th></tr></thead>';
		$retVal .= '<tbody>';
		while ( $actual = $resultado->fetch_assoc () )
		{
			$numRegistros ++;
			$retVal .= GestorUsuarios::muestraUsuario ( $actual );
			// $retVal .= '<tr>';
			// $retVal .= '<td class="text clickable"><a href="?opc=GestorUsuarios&action=detalleUsr&usr=' . $actual ['idUsuario'] . '">' . $actual ['nombre'] . '</a></td>';
			// $retVal .= '</tr>';
		}
		if ($numRegistros == 0)
		{
			$retVal .= '<tr>';
			$retVal .= '<td class="text">No se han encontrado resultados</td>';
			$retVal .= '</tr>';
		}
		$retVal .= '</tbody></table>';

		if (! is_numeric ( $pagina ))
		{
			$pagina = 0;
		}

		$retVal .= '<div class="nav-page">';
		$enlace = '?opc=GestorUsuarios&action=search&search=' . $search . '&pag=';
		if ($pagina > 0)
		{
			$prevPag = $pagina - 1;
			$retVal .= '<a class="prev btn" href="' . $enlace . $prevPag . '">< Anterior</a>';
		}

		if ($numRegistros == $usuario->getLimit ())
		{
			$nextPag = $pagina + 1;
			$retVal .= '<a class="next btn" href="' . $enlace . $nextPag . '">Siguiente ></a>';
		}

		$retVal .= '</div></div>';

		$resultado->free ();
		return $retVal;
	}


	// -----------------------------------------------------------------------
	// ------------------ Funciones básicas de un plugin --------------------
	// -----------------------------------------------------------------------
	public static function getExternalCss ()
	{
		$css = array ();

		$css [] = 'terceros.css';
		$css [] = 'multiselect.css';
		$css [] = 'menuadmin.css';

		return $css;
	}


	public static function getExternalJs ()
	{
		$js = array ();
		$js [] = 'vendor/tinymce/tinymce.min.js';
		$js [] = 'loadTinymce.js';
		$js [] = 'ajaxBtn.js';
		$js [] = 'multiselectGrupos.js'; // Necesita ajaxBtn
		$js [] = 'nodosMenu.js';

		return $js;
	}


	public static function getPlgInfo ()
	{
		$plgInfo = array ();
		$plgInfo ['plgName'] = "Administrar Usuarios";
		$plgInfo ['plgDescription'] = "Administramos los usuarios existentes, y los permisos que tienen";
		$plgInfo ['isMenu'] = 0;
		$plgInfo ['isMenuAdmin'] = 1;
		$plgInfo ['isSkinable'] = 1;

		return $plgInfo;
	}


	/**
	 *
	 * @param mysqli $mysqli
	 */
	public static function main ($mysqli, $opcion)
	{
		$gestorUsuarios = new GestorUsuarios ( $mysqli, $opcion );

		if (isset ( $_GET ['action'] ))
		{
			switch ($_GET ['action'])
			{
				case 'detalleUsr':
					return $gestorUsuarios->detalleUsuario ( $_GET ['usr'] );
					break;
				case 'editaUsr':
					return $gestorUsuarios->editaUsuario ( $_GET ['usr'] );
					break;
				case 'nuevoUsr':
					return $gestorUsuarios->nuevoUsuario ();
					break;
				case 'eliminaUsr':
					return $gestorUsuarios->eliminaUsuario ( $_GET ['usr'] );
					break;
				case 'grabaGrpUsr':
					if (isset ( $_GET ['checked'] ) && isset ( $_GET ['usr'] ) && is_numeric ( $_GET ['usr'] ) && isset ( $_GET ['grupo'] ) && is_numeric ( $_GET ['grupo'] ))
					{
						return $gestorUsuarios->grabaUsuarioGrupo ( $_GET ['usr'], $_GET ['grupo'], $_GET ['checked'] );
					}
					break;
				case 'nuevoGrupo':
					return $gestorUsuarios->nuevoGrupo ();
					break;
				case 'cargaGrupos':
					return $gestorUsuarios->muestraUsuarioGrupos ( $_GET ['usr'] );
				// case 'search':
				default:
					// $gestorTerceros = new GestorTerceros ( $mysqli );
					if (isset ( $_GET ['search'] ))
						$search = $_GET ['search'];
					else
						$search = '';
					if (isset ( $_GET ['pag'] ))
						$pagina = $_GET ['pag'];
					else
						$pagina = 0;

					return $gestorUsuarios->muestraListaUsusarios ( $search, $pagina );
					break;
			}
		}

		return $gestorUsuarios->muestraListaUsusarios ( '', 0 );
	}
}


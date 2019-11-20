<?php
include_once 'entidades/nodoMenu.php';
class GestorNodosMenu extends Plugin
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
					if (isset ( $_GET ['menu'] ) && is_numeric ( $_GET ['menu'] ))
					{
						$this->grabaNodoMenuGrupo ( $_GET ['menu'], $idGrupo, 'on' );
					}
					return $idGrupo;
				}
				else
				{
					echo "Error: La ejecuci�n de la consulta fall� debido a: \n";
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
	
	
	private function grabaNodoMenuGrupo ($idNodoMenu, $idGrupo, $modo)
	{
		$mysqli = $this->mysqli;
		$sql = 'SELECT * FROM {nodosMenu_grupos} WHERE idNodoMenu=' . $idNodoMenu . ' AND idGrupo=' . $idGrupo . ';';
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
		if ($resultado->num_rows == 0 && $modo == 'on')
		{
			$sql = 'INSERT INTO {nodosMenu_grupos} (idNodoMenu,idGrupo) VALUES (' . $idNodoMenu . ', ' . $idGrupo . ');';
		}
		else
		{
			$sql = 'DELETE FROM {nodosMenu_grupos} WHERE idNodoMenu=' . $idNodoMenu . ' AND idGrupo=' . $idGrupo . ';';
		}
		
		if ($mysqli->query ( prefixQuery ( $sql ) ) === TRUE)
		{
			return 'Grupo actualizado correctamente';
		}
		else
		{
			echo "Error: La ejecuci�n de la consulta fall� debido a: \n";
			echo "Query: " . $sql . "\n";
			echo "Errno: " . $mysqli->errno . "\n";
			echo "Error: " . $mysqli->error . "\n";
		}
	}
	
	
	private function grabaNodoMenuPlugin ()
	{
		// Comprobamos que se haya enviado el formulario por post
		if (isset ( $_POST ['idNodoMenu'] ) && is_numeric ( $_POST ['idNodoMenu'] ) && isset ( $_POST ['idPlugin'] ) && is_numeric ( $_POST ['idPlugin'] ))
		{
			$sql = 'UPDATE {nodosMenu} SET idPlugin = ' . $_POST ['idPlugin'] . ' WHERE idNodoMenu = ' . $_POST ['idNodoMenu'] . ';';
			$mysqli = $this->mysqli;
			
			if ($mysqli->query ( prefixQuery ( $sql ) ) === TRUE)
			{
				$retVal = 'Plugin actualizado correctamente.';
			}
			else
			{
				echo "Error: La ejecuci�n de la consulta fall� debido a: \n";
				echo "Query: " . $sql . "\n";
				echo "Errno: " . $mysqli->errno . "\n";
				echo "Error: " . $mysqli->error . "\n";
			}
			return $retVal;
		}
	}
	
	
	private function nuevoNodoMenu ()
	{
		$nodoMenu = new NodoMenu ();
		if (isset ( $_POST ['idNodoMenu'] ))
		{
			CrudModel::setEntidadFieldListFromSupportedFields ( $nodoMenu );
			$nodoMenu->fillFromArray ( $_POST );
			
			$idNuevoNodo = CrudModel::insert ( $nodoMenu, $this->mysqli , true);
			RBAC::registerPluginPermissions ($idNuevoNodo, $_POST ['nombre'], $this->mysqli);
			
			return "Opcion creada correctamente";
		}
		else
		{
			$retVal = '<h1>Crear una nueva opci&oacute;n del men&uacute;</h1>';
			$link = '?opc=GestorNodosMenu&action=nuevoMenu';
			$retVal .= CrudModel::generateFormFromEntyty ( $nodoMenu, $this->mysqli, $link );
		}
		
		return $retVal;
	}
	
	
	private function editaNodoMenu ($idNodoMenu)
	{
		$nodoMenu = new NodoMenu ();
		
		if (isset ( $_POST ['idNodoMenu'] ))
		{
			
			$nodoMenu->addCondition ( 'idNodoMenu', '=', $idNodoMenu );
			CrudModel::setEntidadFieldListFromSupportedFields ( $nodoMenu );
			$nodoMenu->fillFromArray ( $_POST );
			
			return CrudModel::update ( $nodoMenu, $this->mysqli );
		}
		else
		{
			$retVal = '<h1>Editar la opci&oacute;n del men&uacute;</h1>';
			$link = '?opc=GestorNodosMenu&action=editaMenu&menu=' . $idNodoMenu;
			$nodoMenu->addCondition ( 'idNodoMenu', '=', $idNodoMenu );
			$retVal .= CrudModel::generateFilledFormFromEntyty ( $nodoMenu, $this->mysqli, $link );
		}
		
		return $retVal;
	}
	
	
	private function setOrdenNodoMenu ($idNodoMenu, $inc)
	{
		$orden = 0;
		if (isset ( $_GET ['orden'] ) && is_numeric ( $_GET ['orden'] ))
		{
			$orden = $_GET ['orden'];
		}
		
		$mysqli = $this->mysqli;
		$sql = 'UPDATE {nodosMenu} SET orden = ' . $orden . ' WHERE orden = ' . $orden . $inc . ' AND idNodoMenuPadre IN (SELECT idNodoMenuPadre FROM (SELECT * FROM {nodosMenu}) AS n WHERE idNodoMenu=' . $idNodoMenu . ');';
		if ($mysqli->query ( prefixQuery ( $sql ) ) === TRUE)
		{
			$sql = 'UPDATE {nodosMenu} SET orden = ' . $orden . $inc . ' WHERE idNodoMenu = ' . $idNodoMenu . ';';
			
			if ($mysqli->query ( prefixQuery ( $sql ) ) === TRUE)
			{
				$retVal = 'Opci&oacute;n del men&uacute; actualizado correctamente.';
			}
			else
			{
				echo "Error: La ejecuci�n de la consulta fall� debido a: \n";
				echo "Query: " . $sql . "\n";
				echo "Errno: " . $mysqli->errno . "\n";
				echo "Error: " . $mysqli->error . "\n";
			}
		}
		else
		{
			echo "Error: La ejecuci�n de la consulta fall� debido a: \n";
			echo "Query: " . $sql . "\n";
			echo "Errno: " . $mysqli->errno . "\n";
			echo "Error: " . $mysqli->error . "\n";
		}
		return $retVal;
	}
	
	
	private function eliminaNodoMenu ($idNodoMenu)
	{
		$arrayNodosHijos = array ();
		if (isset ( $_POST ['idNodoMenu'] ))
		{
			// ----- lista con los id para eliminar, el nodo y todos sus hijos, si los tubiera -----
			$listaIds = $idNodoMenu . $this->getNodosMenuOrdenados ( $arrayNodosHijos, 'listaIdNodosMenu', $idNodoMenu );
			$retVal = '';
			
			// ----- Eliminamos los grupos del nodo y sus hijos -----
			$sql = 'DELETE FROM {nodosMenu_grupos} WHERE idNodoMenu in (' . $listaIds . ');';
			if (CrudModel::executeQuery ( $this->mysqli, $sql ))
			{
				$retVal .= 'Grupos eliminados correctamente.';
			}
			
			// ----- Eliminamos los par�metros del nodo y sus hijos -----
			$sql = 'DELETE FROM {plugins_params} WHERE idNodoMenu in (' . $listaIds . ');';
			if (CrudModel::executeQuery ( $this->mysqli, $sql ))
			{
				$retVal .= '<br/>Par�metros de los plugins eliminados correctamente.';
			}
			
			// ----- Eliminamos el nodo y sus hijos -----
			$nodoMenu = new NodoMenu ();
			$nodoMenu->addCondition ( 'idNodoMenu', 'in', '(' . $listaIds . ')' );
			return $retVal . '<br/>' . CrudModel::deleteCondicionList ( $nodoMenu, $this->mysqli );
		}
		else
		{
			$nodoMenu = new NodoMenu ();
			$nodoMenu->addCondition ( 'idNodoMenuPadre', '=', $idNodoMenu );
			$sql = CrudModel::getListSql ( $nodoMenu );
			$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
			
			if ($resultado->num_rows > 0)
			{
				$retVal = '<h1>�Est&aacute;s seguro de eliminar este nodo del men&uacute; y ';
				switch ($resultado->num_rows)
				{
					case 1:
						$retVal .= 'su nodo hijo';
						break;
					default:
						$retVal .= 'sus ' . $resultado->num_rows . ' nodos hijos';
						break;
				}
				$retVal .= '?</h1>Opciones del men&uacute; relacionadas:<div class="simple-box">' . substr ( $this->getNodosMenuOrdenados ( $arrayNodosHijos, 'listaNodosMenu', $idNodoMenu ), strlen ( '<br/>' ) ) . '</div>';
			}
			else
			{
				$retVal = '<h1>�Est&aacute;s seguro de eliminar este nodo del men&uacute;?</h1>';
			}
			$resultado->free ();
			
			$link = '?opc=GestorNodosMenu&action=eliminaMenu&menu=' . $idNodoMenu;
			$nodoMenu = new NodoMenu ();
			$nodoMenu->addCondition ( 'idNodoMenu', '=', $idNodoMenu );
			$retVal .= CrudModel::generateFilledFormFromEntyty ( $nodoMenu, $this->mysqli, $link, true, 'Eliminar' );
		}
		return $retVal;
	}
	
	
	private function listaIdNodosMenu ($nodo)
	{
		return ', ' . $nodo ['idNodoMenu'];
	}
	
	
	private function listaNodosMenu ($nodo)
	{
		$indentacion = '';
		for($i = $nodo ['nivel']; $i > 0; $i --)
		{
			$indentacion .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		return '<br/>' . $indentacion . $nodo ['nombre'];
	}
	
	
	private function detalleNodoMenu ($idNodoMenu)
	{
		$nodoMenu = new NodoMenu ();
		$nodoMenu->addCondition ( 'idNodoMenu', '=', $idNodoMenu );
		$nodoMenu->setCompleteFieldList ();
		
		$sql = CrudModel::getListSql ( $nodoMenu );
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
		$campos = $resultado->fetch_assoc ();
		$resultado->free ();
		
		$nodoMenu->fillFromArray ( $campos );
		$retVal = '<h1>Detalle del men&uacute;</h1>';
		$retVal .= CrudModel::generateFormFromEntyty ( $nodoMenu, $this->mysqli, '', true );
		
		// Ahora los botones con las acciones
		$retVal .= '<div class="right">';
		$retVal .= '<a class="btn" href="?opc=GestorNodosMenu&action=editaMenu&menu=' . $idNodoMenu . '"><i class="material-icons">create</i>Editar</a>';
		$retVal .= '<a class="btn" href="?opc=GestorNodosMenu&action=eliminaMenu&menu=' . $idNodoMenu . '"><i class="material-icons">delete</i>Eliminar</a><br />';
		$retVal .= '</div>';
		
		return $retVal;
	}
	
	
	private function getNodosMenuOrdenados (&$arrayNodosMenu, $funcNodoReturn = '', $idNodoMenuPadre = 0, $nivel = 0)
	{
		if ($funcNodoReturn != '')
		{
			$retVal = '';
		}
		$nodoMenu = new NodoMenu ();
		$nodoMenu->addCondition ( 'idNodoMenuPadre', '=', $idNodoMenuPadre );
		$nodoMenu->addOrder ( 'orden', 'ASC' );
		$nodoMenu->addOrder ( 'idNodoMenu', 'ASC' );
		$nodoMenu->setCompleteFieldList ();
		$sql = CrudModel::getListSql ( $nodoMenu );
		$resultado = CrudModel::executeQuery ( $this->mysqli, $sql );
		
		$orden = 0;
		while ( $nodo = $resultado->fetch_assoc () )
		{
			// Actualizamos el nodo con el nivel
			$nodo ['nivel'] = $nivel;
			// Actualizamos el nodo con el orden
			if (! isset ( $nodo ['orden'] ) || $nodo ['orden'] != $orden)
			{
				// Modificamos la bbdd para que el orden sea coerente
				$sql = 'UPDATE {nodosMenu} SET orden = ' . $orden . ' WHERE idNodoMenu = ' . $nodo ['idNodoMenu'] . ';';
				$mysqli = $this->mysqli;
				
				if ($mysqli->query ( prefixQuery ( $sql ) ) !== TRUE)
				{
					echo "Error: La ejecuci�n de la consulta fall� debido a: \n";
					echo "Query: " . $sql . "\n";
					echo "Errno: " . $mysqli->errno . "\n";
					echo "Error: " . $mysqli->error . "\n";
				}
			}
			$orden ++;
			$nodo ['esUlt'] = FALSE;
			if ($orden == $resultado->num_rows)
			{
				$nodo ['esUlt'] = TRUE;
			}
			
			$arrayNodosMenu [$nodo ['idNodoMenu']] = $nodo;
			$tmp = $this->getNodosMenuOrdenados ( $arrayNodosMenu, $funcNodoReturn, $nodo ['idNodoMenu'], $nivel + 1 );
			if ($funcNodoReturn != '')
			{
				$retVal .= call_user_func ( 'GestorNodosMenu::' . $funcNodoReturn, $nodo );
				$retVal .= $tmp;
			}
		}
		
		$resultado->free ();
		if ($funcNodoReturn != '')
		{
			return $retVal;
		}
		return;
	}
	
	
	private function muestraNodoMenu ($nodo)
	{
		$retVal = '<tr>';
		// Padre
		$retVal .= '<input type="hidden" class="idNodoMenu" name="idNodoMenu" value="' . $nodo ['idNodoMenu'] . '" />' . PHP_EOL;
		$retVal .= '<input type="hidden" name="idNodoMenuPadre" value="' . $nodo ['idNodoMenuPadre'] . '" />' . PHP_EOL;
		$retVal .= '<input type="hidden" name="orden" value="' . $nodo ['orden'] . '" />' . PHP_EOL;
		// Nombre
		$indentacion = '';
		for($i = $nodo ['nivel']; $i > 0; $i --)
		{
			$indentacion .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		if ($nodo ['classCss'] != '')
		{
			$indentacion .= '<i class="material-icons" style="color:#dcb600">' . $nodo ['classCss'] . '</i> ';
		}
		$retVal .= '<td class="">' . $indentacion . $nodo ['nombre'] . '</td>';
		// Target
		$retVal .= '<td class="text" style="text-align:center;">';
		$retVal .= '<input type="checkbox" id="' . $nodo ['idNodoMenu'] . '-target" name="target" class="checkbox" ' . (($nodo ['target'] == 1) ? 'checked= true' : '') . ' disabled="disabled">';
		$retVal .= '<label for="' . $nodo ['idNodoMenu'] . '-target" title="Muestra el contenido en una pesta�a nueva"></label>';
		$retVal .= '</td>';
		
		// $retVal .= '<input type="checkbox" name="target" value="1" ' . (($nodo ['target'] == 1) ? 'checked= true' : '') . ' disabled="disabled"/></td>' . PHP_EOL;
		// Plugin
		$retVal .= '<td class="text">';
		$cmb = new PluginMenu ();
		if ($cmb instanceof ComboData)
		{
			$valorOrig = $nodo ['idPlugin'];
			$retVal .= '<select name="idPlugin" class="plugin stylemultiselect" >';
			foreach ( $cmb->getComboData ( $this->mysqli ) as $clave => $valor )
			{
				$retVal .= '<option value="' . $clave . '" ' . (($valorOrig == $clave) ? 'selected' : '') . '>' . $valor . '</option>';
			}
			$retVal .= '</select>' . PHP_EOL;
		}
		$retVal .= '</td>';
		// Grupos
		$retVal .= '<td class="text grupos">' . $this->muestraNodoMenuGrupos ( $nodo ['idNodoMenu'] ) . '</td>';
		// Cambiar el orden
		$retVal .= '<td class="">';
		if ($nodo ['orden'] > 0)
		{
			$retVal .= '<a class="setOrden" href="?opc=GestorNodosMenu&action=upMenu&menu=' . $nodo ['idNodoMenu'] . '&orden=' . $nodo ['orden'] . '" title="Adelartar un lugar"><i class="material-icons">arrow_upward</i></a>';
		}
		$retVal .= '</td>';
		$retVal .= '<td class="">';
		if (! $nodo ['esUlt'])
		{
			$retVal .= '<a class="setOrden" href="?opc=GestorNodosMenu&action=downMenu&menu=' . $nodo ['idNodoMenu'] . '&orden=' . $nodo ['orden'] . '" title="Retrasar un lugar"><i class="material-icons">arrow_downward</i></a>';
		}
		$retVal .= '</td>';
		
		// Editar, borrar, ...
		$retVal .= '<td><div class="btn-group-container right cto-btn">';
		$retVal .= '<div class="btn-group">';
		$retVal .= '<a href="?opc=GestorNodosMenu&action=detalleMenu&menu=' . $nodo ['idNodoMenu'] . '"><i class="material-icons">visibility</i>Ver opci&oacute;n men&uacute;</a>';
		$retVal .= '<br><a href="?opc=GestorNodosMenu&action=editaMenu&menu=' . $nodo ['idNodoMenu'] . '"><i class="material-icons">create</i>Editar opci&oacute;n men&uacute;</a>';
		$retVal .= '<br><a href="?opc=GestorNodosMenu&action=eliminaMenu&menu=' . $nodo ['idNodoMenu'] . '"><i class="material-icons">delete</i>Eliminar opci&oacute;n</a>';
		$retVal .= '</div>';
		$retVal .= '<i class="material-icons">more_vert</i>';
		$retVal .= '</div></td>';
		
		$retVal .= '</tr>';
		
		return $retVal;
	}
	
	
	private function muestraNodoMenuGrupos ($idNodoMenu)
	{
		$sql = 'SELECT idGrupo, descripcion, (SELECT COUNT(*) FROM {nodosMenu_grupos} WHERE idNodoMenu=' . $idNodoMenu . ' ANd {grupos}.idGrupo = {nodosMenu_grupos}.idGrupo) as activo FROM {grupos};';
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
			$multiselectOptions .= '<li class="' . $checked . '" data-action="?opc=GestorNodosMenu&action=grabaGrpMenu&menu=' . $idNodoMenu . '&grupo=' . $fila ['idGrupo'] . '">';
			$multiselectOptions .= '<input type="checkbox" id="ms' . $idNodoMenu . '-option-' . $fila ['idGrupo'] . '" class="checkbox" ' . $checked . '>';
			$multiselectOptions .= '<label for="ms' . $idNodoMenu . '-option-' . $fila ['idGrupo'] . '" title="' . $fila ['descripcion'] . '">' . $fila ['descripcion'] . '</label>';
			$multiselectOptions .= '</li>';
		}
		$multiselectOptions .= '<li class="creator" title="A�adir grupo" data-action="?opc=GestorNodosMenu&action=nuevoGrupo&menu=' . $idNodoMenu . '">+ A�adir grupo</li></ul>';
		
		// TODO: cambiar la clase down a up seg�n convenga, javascript
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
	
	
	private function muestraListaNodosMenu ()
	{
		$numRegistros = 0;
		
		$retVal = '<h1>Administraci&oacute;n de las opciones del men&uacute;</h1>';
		
		$retVal .= '<div class="marco" style="position:relative">';
		$retVal .= '<div class="controles"><a class="btn" href="?opc=GestorNodosMenu&action=nuevoMenu"><i class="material-icons">create</i> Nuevo</a></div>';
		$retVal .= '<table class="">';
		$retVal .= '<thead><tr><th>Opci&oacute;n Men&uacute;</th><th>Nueva<br>pesta&ntilde;a</th><th>Plugin</th><th>Grupos</th><th></th><th></th><th></th></tr></thead>';
		$retVal .= '<tbody>';
		$arrayNodosMenu = array ();
		$retVal .= $this->getNodosMenuOrdenados ( $arrayNodosMenu, 'muestraNodoMenu' );
		
		if (count ( $arrayNodosMenu ) == 0)
		{
			$retVal .= '<tr>';
			$retVal .= '<td class="text">No se han encontrado resultados</td>';
			$retVal .= '</tr>';
		}
		$retVal .= '</tbody></table>';
		$retVal .= '</div>';
		
		return $retVal;
	}
	
	
	// -----------------------------------------------------------------------
	// ------------------ Funciones b�sicas de un plugin --------------------
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
		$plgInfo ['plgName'] = "Administrar Opciones del Men&uacute;";
		$plgInfo ['plgDescription'] = "Administramos las opciones del men&uacute;, su contenido y los permisos que tienen.";
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
		$gestorNodosMenu = new GestorNodosMenu ( $mysqli, $opcion );
		
		if (isset ( $_GET ['action'] ))
		{
			switch ($_GET ['action'])
			{
				case 'editaMenu':
					return $gestorNodosMenu->editaNodoMenu ( $_GET ['menu'] );
					break;
				case 'detalleMenu':
					return $gestorNodosMenu->detalleNodoMenu ( $_GET ['menu'] );
					break;
				case 'nuevoMenu':
					return $gestorNodosMenu->nuevoNodoMenu ();
					break;
				case 'eliminaMenu':
					return $gestorNodosMenu->eliminaNodoMenu ( $_GET ['menu'] );
					break;
				case 'grabaPlugin':
					return $gestorNodosMenu->grabaNodoMenuPlugin ();
				case 'grabaGrpMenu':
					if (isset ( $_GET ['checked'] ) && isset ( $_GET ['menu'] ) && is_numeric ( $_GET ['menu'] ) && isset ( $_GET ['grupo'] ) && is_numeric ( $_GET ['grupo'] ))
					{
						return $gestorNodosMenu->grabaNodoMenuGrupo ( $_GET ['menu'], $_GET ['grupo'], $_GET ['checked'] );
					}
					break;
				case 'downMenu':
					return $gestorNodosMenu->setOrdenNodoMenu ( $_GET ['menu'], '+1' );
					break;
				case 'upMenu':
					return $gestorNodosMenu->setOrdenNodoMenu ( $_GET ['menu'], '-1' );
					break;
				case 'nuevoGrupo':
					return $gestorNodosMenu->nuevoGrupo ();
					break;
				case 'cargaGrupos':
					return $gestorNodosMenu->muestraNodoMenuGrupos ( $_GET ['menu'] );
					break;
				default:
					return $gestorNodosMenu->muestraListaNodosMenu ();
					break;
			}
		}
		
		return $gestorNodosMenu->muestraListaNodosMenu ();
	}
}

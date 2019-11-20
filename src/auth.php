<?php
include_once ('lib/password.php');
include_once ('modules/rbac.php');
class Auth
{


	public static function login ($skin = 'defaut')
	{
		$userId = NULL;
		$errorInfo = '';

		// 0.- Comprobar si ya estamos dentro de una sesión php
		if (isset ( $_SESSION ['userId'] ))
		{
			RBAC::getOpcsEnabled ();
			return $_SESSION ['userId'];
		}

		// 1.- Comprobar si venimos de otras sesiones
		if (Auth::checkCookieslogin ( $userId )) return $userId;

		// 2.- Comprobar si hay login con cuenta de google o Facebook
		// 3.- Comprobar si se ha introducido algo en el formulario de login
		if (Auth::checkLocallogin ( $userId, $errorInfo )) return $userId;

		// 4.- Comprobamos si es una llamada interna de la página
		if (Auth::checkInternalCall ( $userId )) return $userId;

		// ---------- No tenemos id de usuario que devolver ----------
		// 5.- Mostrar el formulario de login
		Auth::showLoginForm ( $skin, $errorInfo );
		return NULL;
	}


	/**
	 * Rellenamos los datos que mantendremos a lo largo de la sesión referente al usuario
	 *
	 * @param mysqli $mysqli
	 * @param integer $userId
	 * @return boolean
	 */
	private static function fillSessionData ($mysqli, $userId)
	{
		$retVal = FALSE;
		$mysqli->query ( "SET NAMES 'UTF8'" );
		$consulta = "SELECT isAdmin, nombre FROM {usuarios} WHERE idUsuario = $userId;";
		$consulta = prefixQuery ( $consulta );

		if ($resultado = $mysqli->query ( $consulta ))
		{
			if ($resultado->num_rows > 0)
			{
				$tupla = $resultado->fetch_object ();
				$_SESSION ['isAdmin'] = $tupla->isAdmin;
				$_SESSION ['nombreUsuario'] = $tupla->nombre;
				$_SESSION ['userId'] = $userId;

				RBAC::getOpcsEnabled ();
			}

			$resultado->free_result ();
		}
	}


	/**
	 * Comprobamos si el usuario en cuestión esta activo
	 *
	 * @param mysqli $mysqli
	 * @param integer $userId
	 * @return boolean
	 */
	private static function checkIfUserIsActive ($mysqli, $userId)
	{
		$retVal = FALSE;
		$consulta = "SELECT isActive FROM {usuarios} WHERE idUsuario = $userId;";
		$consulta = prefixQuery ( $consulta );

		if ($resultado = $mysqli->query ( $consulta ))
		{
			if ($resultado->num_rows > 0)
			{
				$tupla = $resultado->fetch_object ();

				if ($tupla->isActive == 1)
				{
					$retVal = TRUE;
				}
			}

			$resultado->free_result ();
		}

		return $retVal;
	}


	/**
	 * Intentamos hacer la autentificacion mediante cookie
	 *
	 * @param integer $userId
	 *        	parametro de salida: Identificador del usuarioq ue hace login
	 * @return boolean TRUE en caso de que la autentificaci´n sea correcta
	 */
	private static function checkCookieslogin (&$userId)
	{
		$retVal = FALSE;

		if (isset ( $_COOKIE ['SecurityCookie'] ))
		{
			// Comprobamos si la cookie tiene el formato deseado
			if (strpos ( $_COOKIE ['SecurityCookie'], '@' ) !== false)
			{

				$mysqli = @new mysqli ( $GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport'] );

				// Primero eliminamos las cookies caducadas
				$consulta = 'DELETE FROM {sesioncookie}  WHERE expires < NOW();';
				$consulta = prefixQuery ( $consulta );
				$mysqli->query ( $consulta );

				// Comprobamos si la cookie es una cookie de sesion real
				list ( $cookieId, $cookiePass ) = explode ( '@', $_COOKIE ['SecurityCookie'] );
				$cookieId = $mysqli->real_escape_string ( $cookieId );

				$consulta = "SELECT cookiePass, realUserId FROM {sesioncookie} WHERE cookieId ='$cookieId'";
				$consulta = prefixQuery ( $consulta );

				// TODO: Considerar usar la información de browser INFo para comprobar si es una sesión válida

				if ($resultado = $mysqli->query ( $consulta ))
				{
					if ($resultado->num_rows > 0)
					{
						$tupla = $resultado->fetch_object ();

						if (($tupla->cookiePass === $cookiePass) && (Auth::checkIfUserIsActive ( $mysqli, $tupla->realUserId )))
						{
							self::fillSessionData ( $mysqli, $tupla->realUserId );

							// Datos de retorno
							$userId = $tupla->realUserId;
							$retVal = TRUE;

							// renovamos la cookie
							$consulta = "UPDATE {sesioncookie} SET expires= NOW() + INTERVAL 30 DAY WHERE cookieId = '$cookieId';";
							$consulta = prefixQuery ( $consulta );
							$mysqli->query ( $consulta );
							setcookie ( "SecurityCookie", $cookieId . '@' . $cookiePass, strtotime ( '+30 days' ) );
						}
					}

					$resultado->free ();
				}

				$mysqli->close ();
			}
		}

		return $retVal;
	}


	/**
	 * Obtenemos un parametro get para usar autentificación internamente.
	 * IMPORTANTE: No mostrar jamas esto de cara al usuario, nos basamos en secureCookie
	 *
	 * @return string parametro a añadir a la secuencia GET
	 */
	public static function getInternalCallParam ()
	{
		return 'localhostId=' . $_COOKIE ['SecurityCookie'];
	}


	/**
	 * Comprobamos si es una llamada interna desde el propio motor
	 * parametro de salida: Identificador del usuario que hace login
	 *
	 * @return boolean TRUE en caso de que la autentificación sea correcta
	 */
	private static function checkInternalCall (&$userId)
	{
		$retVal = FALSE;

		// TODO: comprobar si conviene codificar en base 64
		// TODO: Comprobar además que sólo se ejecuta desde localhost

		/*
		 * $whitelist = array (
		 * '127.0.0.1',
		 * '::1'
		 * );
		 * if (! in_array ( $_SERVER ['REMOTE_ADDR'], $whitelist ))
		 * {
		 * // not valid
		 * }
		 */

		if (isset ( $_GET ['localhostId'] ))
		{
			$secureCookie = $_GET ['localhostId'];

			if (strpos ( $secureCookie, '@' ) !== false)
			{
				$mysqli = @new mysqli ( $GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport'] );

				list ( $cookieId, $cookiePass ) = explode ( '@', $secureCookie );
				$cookieId = $mysqli->real_escape_string ( $cookieId );

				$consulta = "SELECT cookiePass, realUserId FROM {sesioncookie} WHERE cookieId ='$cookieId'";
				$consulta = prefixQuery ( $consulta );

				// TODO: Considerar usar la información de browser INFo para comprobar si es una sesión válida

				if ($resultado = $mysqli->query ( $consulta ))
				{
					if ($resultado->num_rows > 0)
					{
						$tupla = $resultado->fetch_object ();

						if (($tupla->cookiePass === $cookiePass) && (Auth::checkIfUserIsActive ( $mysqli, $tupla->realUserId )))
						{
							self::fillSessionData ( $mysqli, $tupla->realUserId );

							// Datos de retorno
							$userId = $tupla->realUserId;
							$retVal = TRUE;
						}
					}
					$resultado->free ();
				}
				$mysqli->close ();
			}
		}
		return $retVal;
	}


	/**
	 * Guardamos en la base de datos un identificador de la sesión, y mandamos una cookie al usuario
	 *
	 * @param integer $userId
	 *        	Identificador real del usuario
	 */
	private static function saveCookieSession ($mysqli, $userId)
	{
		// Primero obtenemos los valores unicos para esta sesion
		$cookieId = uniqid ( '', true ); // menos de 30 caracteres
		$cookiePass = mt_rand ( 100000000, 999999999 );
		$browserId = $mysqli->real_escape_string ( $_SERVER ['HTTP_USER_AGENT'] );
		$browserId = $_SERVER ['HTTP_USER_AGENT'];

		$consulta = "INSERT INTO {sesioncookie} (cookieId,cookiePass,realUserId,firstAccess,expires,browserInfo)
		VALUES (\"$cookieId\",\"$cookiePass\",$userId,NOW(),NOW() + INTERVAL 30 DAY,\"$browserId\");
		";
		$consulta = prefixQuery ( $consulta );

		if ($mysqli->query ( $consulta ))
		{
			setcookie ( "SecurityCookie", $cookieId . '@' . $cookiePass, strtotime ( '+30 days' ) );
		}
	}


	/**
	 *
	 * @param integer $userId
	 * @param string $errorInfo
	 * @return boolean
	 */
	public static function checkLocallogin (&$userId, &$errorInfo)
	{
		$retVal = FALSE;
		if (isset ( $_POST ['user'] ))
		{
			$mysqli = @new mysqli ( $GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport'] );

			$usuario = $_POST ['user'];
			$usuario = $mysqli->real_escape_string ( $_POST ['user'] );
			$consulta = 'SELECT idUsuario, password, isActive FROM {usuarios} WHERE email="' . $usuario . '"';
			$consulta = prefixQuery ( $consulta );

			if ($mysqli->connect_error)
			{
				$errorInfo = 'Base de datos inaccesible: ' . $mysqli->connect_error;
				return FALSE;
			}

			if ($resultado = $mysqli->query ( $consulta ))
			{
				if ($resultado->num_rows > 0)
				{
					$tupla = $resultado->fetch_object ();

					if (password_verify ( $_POST ['password'], $tupla->password ))
					{
						if ($tupla->isActive == 1)
						{
							$userId = $tupla->idUsuario;
							$_SESSION ['userId'] = $tupla->idUsuario;
							Auth::saveCookieSession ( $mysqli, $userId );
							$retVal = TRUE;

							self::fillSessionData ( $mysqli, $tupla->idUsuario );
						}
						else
						{
							$errorInfo = 'Cuenta caducada';
						}
					}
					else
					{
						$errorInfo = 'Usuario o contraseña incorrectos';
					}
				}
				else
				{
					$errorInfo = 'Usuario o contraseña incorrectos';
				}
				$resultado->free ();
			}
			$mysqli->close ();
		}
		return $retVal;
	}


	/**
	 * Mostramos el formulario para poder hacer login
	 *
	 * @param string $lang
	 */
	public static function showLoginForm ($skin, $errorInfo = '')
	{
	    $loginForm = file_get_contents ( $GLOBALS ['skinPath'] . "html/loginForm.htm" );
	    $loginForm = str_replace ('@@skin@@', $skin, $loginForm);
		$loginForm = str_replace ( '@@errorInfo@@', $errorInfo, $loginForm );

		header ( 'Content-Type: text/html; charset=utf-8' );
		print ($loginForm) ;
	}


	public static function logout ($mysqli)
	{
		if (isset ( $_COOKIE ['SecurityCookie'] ))
		{
			$mysqli = @new mysqli ( $GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport'] );

			$cookie = explode ( "@", $_COOKIE ['SecurityCookie'] );
			// Primero eliminamos nuestra cookie actual
			$consulta = 'DELETE FROM {sesioncookie} WHERE cookieId = "' . $cookie [0] . '" AND cookiePass = "' . $cookie [1] . '";';
			$consulta = prefixQuery ( $consulta );
			$mysqli->query ( $consulta );
		}

		session_start ();
		session_unset ();
		session_destroy ();

		header ( "location:index.php" );

		exit ();
	}
}

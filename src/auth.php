<?php
class Auth
{
	/**
	 * A login for the admin space. 
	 * It wont use the skin at all
	 */
	public static function setupLogin ()
	{
		$userId = NULL;
		$errorInfo = '';

		if (isset ($_SESSION ['userId']))
		{
			return $_SESSION ['userId'];
		}

		if (self::checkLocallogin ($userId, $errorInfo)) return $userId;

		self::showSetupLoginForm ( $errorInfo);

	}


	public static function login ($skin = 'defaut')
	{
		$userId = NULL;
		$errorInfo = '';

		// 0.- Comprobar si ya estamos dentro de una sesión php
		if (isset ($_SESSION ['userId']))
		{
			return $_SESSION ['userId'];
		}

		// 1.- Comprobar si venimos de otras sesiones
		if (self::checkCookieslogin ($userId)) return $userId;

		// 2.- Comprobar si hay login con cuenta de google o Facebook
		// 3.- Comprobar si se ha introducido algo en el formulario de login
		if (self::checkLocallogin ($userId, $errorInfo)) return $userId;

		// 4.- Comprobamos si es una llamada interna de la página
		if (self::checkInternalCall ($userId)) return $userId;

		// 5.- Comprobamos si se trata de una llamada desde una aplicación
		if (self::checkAppCall ($userId)) return $userId;

		// TODO: opcionalmente añadir un método de identificación usando certificados instalados en la máqina
		// https://webauthn.guide/#webauthn-api

		// ---------- No tenemos id de usuario que devolver ----------
		// 5.- Mostrar el formulario de login
		Auth::showLoginForm ($skin, $errorInfo);
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
		$mysqli->query ("SET NAMES 'UTF8'");
		$consulta = "SELECT isAdmin, nombre FROM {usuarios} WHERE idUsuario = $userId;";
		$consulta = prefixQuery ($consulta);

		if ($resultado = $mysqli->query ($consulta))
		{
			if ($resultado->num_rows > 0)
			{
				$tupla = $resultado->fetch_object ();
				$_SESSION ['isAdmin'] = $tupla->isAdmin;
				$_SESSION ['nombreUsuario'] = $tupla->nombre;
				$_SESSION ['userId'] = $userId;
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
		$consulta = prefixQuery ($consulta);

		if ($resultado = $mysqli->query ($consulta))
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
	 * Nos aseguramos de que la base de datos admita un mayor tiempo de vida a esta identificación
	 *
	 *
	 * @param mysqli $mysqli
	 * @param integer $userId
	 * @return boolean
	 */
	private static function extendCoockieLife (&$mysqli, $cookieId)
	{
		$sql = "UPDATE {sesioncookie} SET expires= NOW() + INTERVAL 30 DAY WHERE cookieId = '$cookieId';";
		$sql = prefixQuery ($sql);
		$mysqli->query ($sql);
	}


	/**
	 * Nos aseguramos de que la base de datos admita un mayor tiempo de vida a esta identificación
	 *
	 *
	 * @param mysqli $mysqli
	 * @param integer $userId
	 * @return boolean
	 */
	private static function deleteOldCookies (&$mysqli)
	{
		$sql = 'DELETE FROM {sesioncookie}  WHERE expires < NOW();';
		$sql = prefixQuery ($sql);
		$mysqli->query ($sql);
	}


	/**
	 * Nos aseguramos de que la base de datos admita un mayor tiempo de vida a esta identificación
	 *
	 *
	 * @param mysqli $mysqli
	 * @param integer $userId
	 * @return boolean
	 */
	private static function checkStaticPass (&$mysqli, $staticPass, &$userId, $extendLifeTime, $useCookie)
	{
		list ($cookieId, $cookiePass) = explode ('@', $staticPass);
		$cookieId = $mysqli->real_escape_string ($cookieId);

		$consulta = "SELECT cookiePass, realUserId FROM {sesioncookie} WHERE cookieId ='$cookieId'";
		$consulta = prefixQuery ($consulta);

		// TODO: Considerar usar la información de browser INFo para comprobar si es una sesión válida
		$retVal = false;
		if ($resultado = $mysqli->query ($consulta))
		{
			if ($resultado->num_rows > 0)
			{
				$tupla = $resultado->fetch_object ();

				if (($tupla->cookiePass === $cookiePass) && (Auth::checkIfUserIsActive ($mysqli, $tupla->realUserId)))
				{
					self::fillSessionData ($mysqli, $tupla->realUserId);

					// Datos de retorno
					$userId = $tupla->realUserId;
					$retVal = true;

					if ($extendLifeTime)
					{
						self::extendCoockieLife ($mysqli, $cookieId);
						if ($useCookie)
						{
							setcookie ("SecurityCookie", $cookieId . '@' . $cookiePass, strtotime ('+30 days'));
						}
					}
				}
			}

			$resultado->free ();
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
	 * Intentamos hacer la autentificacion mediante cookie
	 *
	 * @param integer $userId
	 *        	parametro de salida: Identificador del usuarioq ue hace login
	 * @return boolean TRUE en caso de que la autentificaci´n sea correcta
	 */
	private static function checkCookieslogin (&$userId)
	{
		$retVal = FALSE;

		if (isset ($_COOKIE ['SecurityCookie']))
		{
			// Comprobamos si la cookie tiene el formato deseado
			if (strpos ($_COOKIE ['SecurityCookie'], '@') !== false)
			{

				$mysqli = @new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);

				// Primero eliminamos las cookies caducadas
				self::deleteOldCookies ($mysqli);

				$retVal = self::checkStaticPass ($mysqli, $_COOKIE ['SecurityCookie'], $userId, true, true);

				$mysqli->close ();
			}
		}

		return $retVal;
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

		if (isset ($_GET ['localhostId']))
		{
			$secureCookie = $_GET ['localhostId'];

			if (strpos ($secureCookie, '@') !== false)
			{
				$mysqli = @new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);
				$retVal = self::checkStaticPass ($mysqli, $secureCookie, $userId, false, false);

				$mysqli->close ();
			}
		}
		return $retVal;
	}


	/**
	 * Sistema de auth pensado para apps externas (excel, metatrader, etc...)
	 * Consideramos que estas aplicaciones carecen de cookies, y por ende, de sesión
	 *
	 * @return boolean TRUE en caso de que la autentificación sea correcta
	 */
	private static function checkAppCall (&$userId)
	{
		if (isset ($_POST ['AppIdAction']))
		{
			// Nos aseguramos de que se trate como AJAX, siempre
			$_GET ['ajax'] = 1;

			if ($_POST ['AppIdAction'] == 'login')
			{
				$loginRetval = 'KO'; // . json_encode ($_POST);
				if ((isset ($_POST ['appIdUser'])) && (isset ($_POST ['appIdPass'])))
				{
					// Estamos realizando un login
					// Rompe el flujo y responde directamente
					$mysqli = @new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);

					$sql = 'SELECT idUsuario, password FROM {usuarios} WHERE isActive=1 AND email="' . $_POST ['appIdUser'] . '"';
					if ($res = $mysqli->query (prefixQuery ($sql)))
					{
						if ($row = $res->fetch_assoc ())
						{
							if (password_verify ($_POST ['appIdPass'], $row ['password']))
							{
								$userId = $row ['idUsuario'];
								$loginRetval = 'OK:';
								$loginRetval .= Auth::saveCookieSession ($mysqli, $userId, false);
							}
						}
					}

					$mysqli->close ();
				}
				exit ($loginRetval);
			}
			else
			{
				$retval = false;
				$keyToken = $_POST ['AppIdToken'];
				if (strpos ($keyToken, '@') !== false)
				{
					$mysqli = @new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);
					if (self::checkStaticPass ($mysqli, $keyToken, $userId, true, false))
					{
						$retval = true;
					}
					$mysqli->close ();
				}

				if ($_POST ['AppIdAction'] == 'check')
				{
					// Estamos comprobando la auth (en cuyo caso extendemos la vida)
					// Rompe el flujo y responde directamente
					if ($retval)
					{
						exit ('OK');
					}
					else
					{
						exit ('KO');
					}
				}
				else if ($_POST ['AppIdAction'] == 'std')
				{
					// Uso normal: comprobamos el id de usuario y seguimos
					// Esta debe seguir el flujo en caso de que haya ido ok
					if ($retval)
					{
						return $retval;
					}
					else
					{
						exit ('KO: auth fail');
					}
				}

				// TODO: Esto es delicado, por loq ue habría que comprobar que un usuario no cede sus credenciales a un tercero
				/*
				 * Por ello, lo que haremos será crear una aplicación que se ejecutará y registrará en el dominio, y esta, a su vez,
				 * generará un token de seguridad exclusivo que se almacenará en una ubicación accesible por excel (en el registro).
				 *
				 * Pasaríamos este token como una cabecera más de HTTP (lo leeríamos con la función de PHP get_headers)
				 */
			}
		}
	}


	/**
	 * Guardamos en la base de datos un identificador de la sesión, y mandamos una cookie al usuario
	 *
	 * @param integer $userId
	 *        	Identificador real del usuario
	 */
	private static function saveCookieSession ($mysqli, $userId, $setCookie)
	{
		// Primero obtenemos los valores unicos para esta sesion
		$cookieId = uniqid ('', true); // menos de 30 caracteres
		$cookiePass = mt_rand (100000000, 999999999);
		$browserId = $mysqli->real_escape_string ($_SERVER ['HTTP_USER_AGENT']);
		$browserId = $_SERVER ['HTTP_USER_AGENT'];

		$consulta = "INSERT INTO {sesioncookie} (cookieId,cookiePass,realUserId,firstAccess,expires,browserInfo)
		VALUES (\"$cookieId\",\"$cookiePass\",$userId,NOW(),NOW() + INTERVAL 30 DAY,\"$browserId\");
		";
		$consulta = prefixQuery ($consulta);

		if ($mysqli->query ($consulta))
		{
			if ($setCookie)
			{
				setcookie ("SecurityCookie", $cookieId . '@' . $cookiePass, strtotime ('+30 days'));
			}

			return $cookieId . '@' . $cookiePass;
		}
		return '';
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
		if (isset ($_POST ['user']))
		{
			$mysqli = @new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);

			$usuario = $_POST ['user'];
			$usuario = $mysqli->real_escape_string ($_POST ['user']);
			$consulta = 'SELECT idUsuario, password, isActive FROM {usuarios} WHERE email="' . $usuario . '"';
			$consulta = prefixQuery ($consulta);

			if ($mysqli->connect_error)
			{
				$errorInfo = 'Base de datos inaccesible: ' . $mysqli->connect_error;
				return FALSE;
			}

			if ($resultado = $mysqli->query ($consulta))
			{
				if ($resultado->num_rows > 0)
				{
					$tupla = $resultado->fetch_object ();

					if (password_verify ($_POST ['password'], $tupla->password))
					{
						if ($tupla->isActive == 1)
						{
							$userId = $tupla->idUsuario;
							$_SESSION ['userId'] = $tupla->idUsuario;
							Auth::saveCookieSession ($mysqli, $userId, true);
							$retVal = TRUE;

							self::fillSessionData ($mysqli, $tupla->idUsuario);
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
	private static function showFinalLoginForm ($file, $skin, $errorInfo = '')
	{
		$loginForm = file_get_contents ($file);
		$loginForm = str_replace ('@@skin@@', $skin, $loginForm);
		$loginForm = str_replace ('@@errorInfo@@', $errorInfo, $loginForm);

		header ('Content-Type: text/html; charset=utf-8');
		print ($loginForm);
	}

		/**
	 * Mostramos el formulario para poder hacer login
	 *
	 * @param string $lang
	 */
	public static function showLoginForm ($skin, $errorInfo = '')
	{
		self::showFinalLoginForm ($GLOBALS ['skinPath'] . 'html/loginForm.htm', $skin, $errorInfo);
	}

	public static function showSetupLoginForm ($errorInfo)
	{
		self::showFinalLoginForm ('./src/rsc/html/setupLoginForm.htm', '', $errorInfo);
	}


	public static function logout ($mysqli)
	{
		if (isset ($_COOKIE ['SecurityCookie']))
		{
			$mysqli = @new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);

			$cookie = explode ("@", $_COOKIE ['SecurityCookie']);
			// Primero eliminamos nuestra cookie actual
			$consulta = 'DELETE FROM {sesioncookie} WHERE cookieId = "' . $cookie [0] . '" AND cookiePass = "' . $cookie [1] . '";';
			$consulta = prefixQuery ($consulta);
			$mysqli->query ($consulta);
		}


		if (!isset ($_SESSION)) session_start ();
		unset($_SESSION);
		session_unset ();
		session_destroy ();

		header ("location:index.php");

		exit ();
	}
}

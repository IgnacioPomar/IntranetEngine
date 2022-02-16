<?php
// TODO: Adapt to the new tables
// Allos online registration of the users, and passeord automatic recovery (using emails)
include_once ('authMailer.php');

class Auth
{
	private $userId;
	private $mysqli;
	private $errorInfo;
	private $errorCode;


	public static function login ($mysqli)
	{
		// 0.- Si ya estamos dentro de una sesión php, no seguimos
		if (isset ($_SESSION ['userId']))
		{
			return $_SESSION ['userId'];
		}

		$auth = new Auth ();
		$auth->userId = NULL;
		$auth->mysqli = $mysqli;

		checkEmailRecoverLink ();
		if (isset ($_POST ['recoverPass']))
		{
			return $auth->recoverPass ();
		}

		// 1.- Comprobar si venimos de otras sesiones
		if ($auth->checkCookieslogin ()) return $auth->userId;

		// 2.- Comprobar si hay login con cuenta de google o Facebook
		// 3.- Comprobar si se ha introducido algo en el formulario de login
		if ($auth->checkLocallogin ()) return $auth->userId;

		// 4.- Comprobamos si se esta recuperando una contraseña

		// ---------- No tenemos id de usuario que devolver ----------
		// 5.- Mostrar el formulario de login
		$auth->showLoginForm ();
		return NULL;
	}


	/**
	 * Rellenamos los datos que mantendremos a lo largo de la sesi�n referente al usuario
	 *
	 * @param mysqli $mysqli
	 * @param integer $userId
	 * @return boolean
	 */
	private function fillSessionData ($userId)
	{
		$consulta = "SELECT nombre FROM almLogins WHERE idUsuario = $userId;";

		if ($resultado = $this->mysqli->query ($consulta))
		{
			if ($resultado->num_rows > 0)
			{
				$tupla = $resultado->fetch_object ();
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
	private function checkIfUserIsActive ($userId)
	{
		$retVal = FALSE;
		$consulta = "SELECT isActive FROM almLogins WHERE idUsuario = $userId;";

		if ($resultado = $this->mysqli->query ($consulta))
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
	 * Nos aseguramos de que la base de datos admita un mayor tiempo de vida a esta identificaci�n
	 *
	 *
	 * @param mysqli $mysqli
	 * @param integer $userId
	 * @return boolean
	 */
	private function extendCoockieLife ($cookieId)
	{
		$sql = "UPDATE almSessCookie SET expires= NOW() + INTERVAL 5 DAY WHERE cookieId = '$cookieId';";
		$this->mysqli->query ($sql);
	}


	/**
	 * Actualiza la última conexión
	 *
	 * @param integer $idUsuario
	 */
	private function setLastConnDate ($idUsuario)
	{
		$sql = "UPDATE almLogins SET lastlogin= NOW()  WHERE idUsuario= $idUsuario;";
		$this->mysqli->query ($sql);
	}


	/**
	 * Nos aseguramos de que la base de datos admita un mayor tiempo de vida a esta identificaci�n
	 *
	 *
	 * @param mysqli $mysqli
	 * @param integer $userId
	 * @return boolean
	 */
	private function deleteOldCookies ()
	{
		$sql = 'DELETE FROM almSessCookie  WHERE expires < NOW();';
		$this->mysqli->query ($sql);
	}


	/**
	 * Nos aseguramos de que la base de datos admita un mayor tiempo de vida a esta identificaci�n
	 *
	 *
	 * @param mysqli $mysqli
	 * @param integer $userId
	 * @return boolean
	 */
	private function checkStaticPass ($staticPass, $extendLifeTime, $useCookie)
	{
		list ($cookieId, $cookiePass) = explode ('@', $staticPass);
		$cookieId = $this->mysqli->real_escape_string ($cookieId);

		$consulta = "SELECT cookiePass, realUserId FROM almSessCookie WHERE cookieId ='$cookieId'";

		// TODO: Considerar usar la información de browser INFo para comprobar si es una sesión válida
		$retVal = false;
		if ($resultado = $this->mysqli->query ($consulta))
		{
			if ($resultado->num_rows > 0)
			{
				$tupla = $resultado->fetch_object ();

				if (($tupla->cookiePass === $cookiePass) && ($this->checkIfUserIsActive ($tupla->realUserId)))
				{
					$this->fillSessionData ($tupla->realUserId);

					// Datos de retorno
					$this->userId = $tupla->realUserId;
					$retVal = true;

					if ($extendLifeTime)
					{
						$this->extendCoockieLife ($cookieId);
						if ($useCookie)
						{
							setcookie ("SecurityCookie", $cookieId . '@' . $cookiePass, strtotime ('+30 days'));
						}
					}

					$this->setLastConnDate ($this->userId);
				}
			}

			$resultado->free ();
		}
		return $retVal;
	}


	/**
	 * Intentamos hacer la autentificacion mediante cookie
	 *
	 * @return boolean TRUE en caso de que la autentificación sea correcta
	 */
	private function checkCookieslogin ()
	{
		$retVal = FALSE;

		if (isset ($_COOKIE ['SecurityCookie']))
		{
			// Comprobamos si la cookie tiene el formato deseado
			if (strpos ($_COOKIE ['SecurityCookie'], '@') !== false)
			{
				// Primero eliminamos las cookies caducadas
				$this->deleteOldCookies ();
				$retVal = $this->checkStaticPass ($_COOKIE ['SecurityCookie'], true, true);
			}
		}

		return $retVal;
	}


	/**
	 * Guardamos en la base de datos un identificador de la sesi�n, y mandamos una cookie al usuario
	 *
	 * @param integer $userId
	 *        	Identificador real del usuario
	 */
	private function saveCookieSession ($userId, $setCookie)
	{
		// Primero obtenemos los valores unicos para esta sesion
		$cookieId = uniqid ('', true); // menos de 30 caracteres
		$cookiePass = mt_rand (100000000, 999999999);
		$browserId = $this->mysqli->real_escape_string ($_SERVER ['HTTP_USER_AGENT']);
		$browserId = $_SERVER ['HTTP_USER_AGENT'];

		$consulta = "INSERT INTO almSessCookie (cookieId,cookiePass,realUserId,firstAccess,expires,browserInfo)
		VALUES (\"$cookieId\",\"$cookiePass\",$userId,NOW(),NOW() + INTERVAL 5 DAY,\"$browserId\");";

		if ($this->mysqli->query ($consulta))
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
	public function checkLocallogin ()
	{
		$retVal = FALSE;
		$this->errorCode = 0;

		if (isset ($_POST ['user']))
		{
			$usuario = $_POST ['user'];
			$usuario = $this->mysqli->real_escape_string ($_POST ['user']);
			$consulta = 'SELECT idUsuario, password, isActive FROM almLogins WHERE email="' . $usuario . '"';

			if ($resultado = $this->mysqli->query ($consulta))
			{
				if ($resultado->num_rows > 0)
				{
					$tupla = $resultado->fetch_object ();

					if (password_verify ($_POST ['password'], $tupla->password))
					{
						if ($tupla->isActive == 1)
						{
							$this->userId = $tupla->idUsuario;
							$_SESSION ['userId'] = $tupla->idUsuario;
							$this->saveCookieSession ($this->userId, true);
							$retVal = TRUE;

							$this->fillSessionData ($tupla->idUsuario);
							$this->setLastConnDate ($this->userId);
						}
						else
						{
							$this->errorInfo = 'Cuenta caducada';
							$this->errorCode = 1;
						}
					}
					else
					{
						$this->errorInfo = 'Invalid user or password';
						$this->errorCode = 2;
					}
				}
				else
				{
					$this->errorInfo = 'Invalid user or password';
					$this->errorCode = 3;
				}
				$resultado->free ();
			}
		}
		return $retVal;
	}


	/**
	 * Mostramos el formulario para poder hacer login
	 *
	 * @param string $lang
	 */
	public function showLoginForm ()
	{
		$formFile = '';
		switch ($this->errorCode)
		{
			case 1: // Cta caducada
				$formFile = 'html/ctaCaducada.htm';
				break;
			case 2: // password incorrecta
			case 3: // cuenta incorrecta
				$formFile = 'html/loginFormWrongPass.htm';
				break;
			default:
				$formFile = 'html/loginForm.htm';
				break;
		}

		$loginForm = file_get_contents ($GLOBALS ['skinPath'] . $formFile);

		header ('Content-Type: text/html; charset=utf-8');
		print ($loginForm);
	}


	/**
	 * Gestiona la recuperación de contraseñas
	 *
	 * @param string $email
	 */
	private function launchRecoverEmail ($email)
	{
		// Buscar si existe y si se ha mandado un correo ya
		if (! filter_var ($email, FILTER_VALIDATE_EMAIL))
		{
			// YAGNI: Mirar si hay que usar FILTER_FLAG_EMAIL_UNICODE
			echo "Incorrect email address.";
			die ();
		}

		// Comprobamos si existe un código en la BBDD
		$pass = '';
		$accountExists = false;
		$mustSendEmail = true;
		$mustGenerateNewRecoverPass = true;
		$usuario = $this->mysqli->real_escape_string ($email);
		$consulta = 'SELECT recoveryPass, recoveryDate FROM almLogins WHERE email="' . $usuario . '"';

		if ($resultado = $this->mysqli->query ($consulta))
		{
			if ($resultado->num_rows > 0)
			{
				$accountExists = true;

				$tupla = $resultado->fetch_object ();

				if (is_null ($tupla->recoveryDate)) $elapsed = 999999;

				$elapsed = time () - strtotime ($tupla->recoveryDate);

				if ($elapsed < 3600) // Menos de una hora
				{
					$mustSendEmail = false;
					$mustGenerateNewRecoverPass = false;
					$pass = $tupla->recoveryPass;
				}
				else if ($elapsed < 86400) // Al cabo de un día caduca
				{
					$mustGenerateNewRecoverPass = false;
					$pass = $tupla->recoveryPass;
				}
			}
		}

		// Generamos el código de recuperación
		if ($mustGenerateNewRecoverPass)
		{
			$pass = generateRecoverPass ();
		}

		// Mandar por email (Sí corresponde)
		if ($mustSendEmail)
		{
			sendRecoverEmail ($email, $pass, ! $accountExists);
		}

		if (! $accountExists)
		{
			// YAGNI: Hacer Configurable si admitir nuevos registros... o no
			// No existe es un insert
			$sql = 'INSERT INTO almLogins (email,recoveryPass,recoveryDate,isActive) ';
			$sql .= 'VALUES ("' . $usuario . '","' . $pass . '",NOW(),true);';

			$this->mysqli->query ($sql);
		}
		else if ($mustGenerateNewRecoverPass)
		{
			// Hacer un update de la cuenta
			$sql = 'UPDATE almLogins SET recoveryPass="' . $pass . '", recoveryDate= NOW() WHERE email = "' . $usuario . '";';
			$this->mysqli->query ($sql);
		}
	}


	/**
	 * Nos han proporcionado una nueva contraseña, comprobamos el código de REcuperación y guardamos
	 */
	private function checkAndStoreNewPass ()
	{
		// Primero comprobamos el código de recuperación
		$email = $this->mysqli->real_escape_string ($_POST ['recoverEmail']);
		$sql = 'SELECT idUsuario,recoveryPass FROM almLogins WHERE email="' . $email . '"';

		$idUsr = NULL;
		$isValid = false;
		if ($resultado = $this->mysqli->query ($sql))
		{
			if ($resultado->num_rows > 0)
			{
				$tupla = $resultado->fetch_object ();

				if ($tupla->recoveryPass == $_POST ['codRec'])
				{
					$isValid = true;
					$idUsr = $tupla->idUsuario;
					$_SESSION ['userId'] = $idUsr;
				}
			}
		}

		if (! $isValid)
		{
			// TODO: Mostrar un error si no coincide
			return NULL;
		}
		else
		{
			$dbPass = $this->mysqli->real_escape_string (password_hash ($_POST ['newPass'], 1));

			$sql = 'UPDATE almLogins SET password="' . $dbPass . '", recoveryPass="", isActive=true WHERE email = "' . $email . '";';
			$this->mysqli->query ($sql);
			return $idUsr;
		}
	}


	/**
	 * GEstiona las distintas opciones de recuperra contarseña
	 */
	private function recoverPass ()
	{
		if (isset ($_POST ['recoverPass']) && isset ($_POST ['recoverEmail']))
		{

			if (isset ($_POST ['codRec']))
			{
				// TODO: Comprobar que las passwords cumplen los criterios marcados (a día de hoy sólo se hace desde javascript)
				return $this->checkAndStoreNewPass ();
			}
			else
			{
				$loginEmailForm = file_get_contents ($GLOBALS ['skinPath'] . 'html/recoverFormPass.htm');
				$loginEmailForm = str_replace ('@@email@@', $_POST ['recoverEmail'], $loginEmailForm);

				if (isset ($_GET ['codRec']))
				{
					// Viene desde el enlace enviado en el email
					$loginEmailForm = str_replace ('@@codRec@@', $_GET ['codRec'], $loginEmailForm);
				}
				else
				{
					// Venimos de la forma "estandar" desde el formulario
					$this->launchRecoverEmail ($_POST ['recoverEmail']);
					$loginEmailForm = str_replace ('@@codRec@@', '', $loginEmailForm);
				}

				header ('Content-Type: text/html; charset=utf-8');
				print ($loginEmailForm);
			}
		}
		else
		{
			$loginEmailForm = file_get_contents ($GLOBALS ['skinPath'] . 'html/recoverFormEmail.htm');
			// $loginForm = str_replace ('@@skin@@', '.', $loginForm);

			header ('Content-Type: text/html; charset=utf-8');
			print ($loginEmailForm);
		}

		return NULL;
	}


	public static function logout ($mysqli)
	{
		if (isset ($_COOKIE ['SecurityCookie']))
		{

			$cookie = explode ("@", $_COOKIE ['SecurityCookie']);
			// Primero eliminamos nuestra cookie actual
			$consulta = 'DELETE FROM almSessCookie WHERE cookieId = "' . $cookie [0] . '" AND cookiePass = "' . $cookie [1] . '";';
			$mysqli->query ($consulta);
		}

		if (! isset ($_SESSION)) session_start ();
		unset ($_SESSION);
		session_unset ();
		session_destroy ();

		$logout = file_get_contents ($GLOBALS ['skinPath'] . 'html/logout.htm');
		// $loginForm = str_replace ('@@skin@@', '.', $loginForm);

		header ('Content-Type: text/html; charset=utf-8');
		print ($logout);
		die ();
	}
}

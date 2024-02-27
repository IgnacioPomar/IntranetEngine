<?php

namespace PHPSiteEngine;

class Auth
{
	private $userId;
	private $mysqli;
	private $errorInfo = '';
	private $errorCode;


	/**
	 * A login for the admin space.
	 * It wont use the skin at all
	 */
	public static function setupLogin ($mysqli): ?string
	{
		if (isset ($_SESSION ['userId']))
		{
			return $_SESSION ['userId'];
		}

		$auth = new Auth ();
		$auth->userId = NULL;
		$auth->mysqli = $mysqli;

		if ($auth->checkLocallogin ()) return $auth->userId;

		$auth->showSetupLoginForm ();

		return $auth->userId;
	}


	/**
	 * Check if the user is logged Or is logging
	 *
	 * @param integer $userId
	 * @return boolean
	 */
	public static function login ($mysqli): ?string
	{
		// 0.- WE are already in session
		if (isset ($_SESSION ['userId']))
		{
			return $_SESSION ['userId'];
		}

		$auth = new Auth ();
		$auth->userId = NULL;
		$auth->mysqli = $mysqli;

		// 1.- Check if there is a cookie with a valid session
		if ($auth->checkCookieslogin ()) return $auth->userId;

		// 2.- TODO: check if there is a valid google or facebook or Office365 login
		// 3.- Check if the form has been submitted
		if ($auth->checkLocallogin ()) return $auth->userId;

		// 4.- Check if it is an internal call from inside the site
		if ($auth->checkInternalCall ()) return $auth->userId;

		// 5.- Check if it is a direct app call
		if ($auth->checkAppCall ()) return $auth->userId;

		// TODO: Check if there is a valid certificate in the machine
		// https://webauthn.guide/#webauthn-api

		// ---------- We dont have a valid user ----------
		// Show the login form
		$auth->showLoginForm ();
		return NULL;
	}


	/**
	 * Check if the user is an admin
	 *
	 * @param integer $userId
	 * @return boolean
	 */
	public static function isAdmin ($mysqli, $userId): bool
	{
		$retVal = false;
		$consulta = "SELECT isAdmin FROM weUsers WHERE idUser = '$userId';";

		if ($res = $mysqli->query ($consulta))
		{
			if ($row = $res->fetch_assoc ())
			{
				$retVal = ($row ['isAdmin'] == 1);
			}
			$res->free_result ();
		}
		return $retVal;
	}


	/**
	 * Check if the user is still active
	 *
	 * @param integer $userId
	 * @return boolean
	 */
	private function checkIfUserIsActive ($userId): bool
	{
		$retVal = FALSE;
		$consulta = "SELECT isActive, name FROM weUsers WHERE idUser = '$userId';";

		if ($resultado = $this->mysqli->query ($consulta))
		{
			if ($resultado->num_rows > 0)
			{
				$tupla = $resultado->fetch_object ();

				if ($tupla->isActive == 1)
				{
					$retVal = TRUE;

					$this->userId = $_SESSION ['userId'] = $userId;
					$_SESSION ['userName'] = $tupla->name;
				}
			}

			$resultado->free_result ();
		}

		return $retVal;
	}


	/**
	 * Extend the coookie another 30 days
	 *
	 *
	 * @param integer $userId
	 * @return boolean
	 */
	private function extendCoockieLife ($cookieId)
	{
		$sql = "UPDATE weSessCookie SET expires= NOW() + INTERVAL 30 DAY WHERE cookieId = '$cookieId';";
		$this->mysqli->query ($sql);
	}


	/**
	 * DElete old coockies
	 */
	private function deleteOldCookies ()
	{
		$sql = 'DELETE FROM weSessCookie WHERE expires < NOW();';
		$this->mysqli->query ($sql);
	}


	/**
	 * Nos aseguramos de que la base de datos admita un mayor tiempo de vida a esta identificación
	 *
	 *
	 * @param integer $userId
	 * @return boolean
	 */
	private function checkStaticPass ($staticPass, $extendLifeTime, $useCookie)
	{
		list ($cookieId, $cookiePass) = explode ('@', $staticPass);
		$cookieId = $this->mysqli->real_escape_string ($cookieId);

		$consulta = "SELECT cookiePass, realUserId FROM weSessCookie WHERE cookieId ='$cookieId'";

		// TODO: Considerar usar la información de browser INFo para comprobar si es una sesión válida
		$retVal = false;
		if ($resultado = $this->mysqli->query ($consulta))
		{
			if ($resultado->num_rows > 0)
			{
				$tupla = $resultado->fetch_object ();

				if (($tupla->cookiePass === $cookiePass) && ($this->checkIfUserIsActive ($tupla->realUserId)))
				{

					$retVal = true;

					if ($extendLifeTime)
					{
						$this->extendCoockieLife ($cookieId);
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
	 * @return boolean TRUE en caso de que la autentificaci´n sea correcta
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
	 * Comprobamos si es una llamada interna desde el propio motor
	 * parametro de salida: Identificador del usuario que hace login
	 *
	 * @return boolean TRUE en caso de que la autentificación sea correcta
	 */
	private function checkInternalCall ()
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
				$retVal = $this->checkStaticPass ($secureCookie, false, false);
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
	private function checkAppCall ()
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

					$sql = 'SELECT idUser, password FROM weUsers WHERE isActive=1 AND email="' . $_POST ['appIdUser'] . '"';
					if ($res = $this->mysqli->query ($sql))
					{
						if ($row = $res->fetch_assoc ())
						{
							if (password_verify ($_POST ['appIdPass'], $row ['password']))
							{
								$userId = $row ['idUser'];
								$loginRetval = 'OK:';
								$loginRetval .= $this->saveCookieSession ($this->mysqli, $userId);
							}
						}
					}
				}
				exit ($loginRetval);
			}
			else
			{
				$retval = false;
				$keyToken = $_POST ['AppIdToken'];
				if (strpos ($keyToken, '@') !== false)
				{
					if ($this->checkStaticPass ($keyToken, true, false))
					{
						$retval = true;
					}
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
	private function saveCookieSession ($setCookie, $userId = NULL)
	{
		$userId = $userId ?? $this->userId;

		// Primero obtenemos los valores unicos para esta sesion
		$cookieId = uniqid ('', true); // menos de 30 caracteres
		$cookiePass = mt_rand (100000000, 999999999);
		$browserId = $this->mysqli->real_escape_string ($_SERVER ['HTTP_USER_AGENT']);
		$browserId = $_SERVER ['HTTP_USER_AGENT'];

		$consulta = "INSERT INTO weSessCookie (cookieId,cookiePass,realUserId,firstAccess,expires,browserInfo)
		VALUES (\"$cookieId\",\"$cookiePass\",'$userId',NOW(),NOW() + INTERVAL 30 DAY,\"$browserId\");
		";

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
	 * @return boolean
	 */
	public function checkLocallogin ()
	{
		$retVal = FALSE;
		if (isset ($_POST ['user']))
		{
			$usuario = $this->mysqli->real_escape_string ($_POST ['user']);
			$consulta = 'SELECT idUser, name, password, isActive FROM weUsers WHERE email="' . $usuario . '"';

			if ($resultado = $this->mysqli->query ($consulta))
			{
				if ($resultado->num_rows > 0)
				{
					$tupla = $resultado->fetch_object ();

					if (password_verify ($_POST ['password'], $tupla->password))
					{
						if ($tupla->isActive == 1)
						{
							$this->userId = $_SESSION ['userId'] = $tupla->idUser;
							$_SESSION ['userName'] = $tupla->name;

							$this->saveCookieSession (true);
							$retVal = TRUE;
						}
						else
						{
							$this->errorInfo = 'Cuenta caducada';
						}
					}
					else
					{
						$this->errorInfo = 'Usuario o contraseña incorrectos';
					}
				}
				else
				{
					$this->errorInfo = 'Usuario o contraseña incorrectos';
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
	private function showFinalLoginForm ($file)
	{
		$loginForm = file_get_contents ($file);
		$loginForm = str_replace ('@@uriPath@@', Site::$uriPath, $loginForm);
		$loginForm = str_replace ('@@skinPath@@', Site::$uriSkinPath, $loginForm);
		$loginForm = str_replace ('@@rscUriPath@@', Site::$rscUriPath, $loginForm);
		$loginForm = str_replace ('@@errorInfo@@', $this->errorInfo, $loginForm);

		header ('Content-Type: text/html; charset=utf-8');
		print ($loginForm);
	}


	/**
	 * Mostramos el formulario para poder hacer login
	 *
	 * @param string $lang
	 */
	public function showLoginForm ()
	{
		if (file_exists (Site::$skinPath . 'tmplt/loginForm.htm'))
		{
			$this->showFinalLoginForm (Site::$skinPath . 'tmplt/loginForm.htm');
		}
		else
		{
			$this->showFinalLoginForm (Site::$rscPath . 'skinTmplt/loginForm.htm');
		}
	}


	public function showSetupLoginForm ()
	{
		$this->showFinalLoginForm (Site::$rscPath . 'html/setupLoginForm.htm', '');
	}


	public function logout ()
	{
		if (isset ($_COOKIE ['SecurityCookie']))
		{

			$cookie = explode ("@", $_COOKIE ['SecurityCookie']);
			// Primero eliminamos nuestra cookie actual
			$consulta = 'DELETE FROM weSessCookie WHERE cookieId = "' . $cookie [0] . '" AND cookiePass = "' . $cookie [1] . '";';
			$this->mysqli->query ($consulta);
		}

		if (! isset ($_SESSION)) session_start ();
		unset ($_SESSION);
		session_unset ();
		session_destroy ();

		header ("location:index.php");

		exit ();
	}
}

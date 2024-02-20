<?php

namespace PHPSiteEngine\PlgsStd;

use PHPSiteEngine\Plugin;

class Logout extends Plugin
{


	public function main ()
	{
		if (isset ($_COOKIE ['SecurityCookie']))
		{

			$cookie = explode ("@", $_COOKIE ['SecurityCookie']);
			// DElete from Database
			$consulta = 'DELETE FROM weSessCookie WHERE cookieId = "' . $cookie [0] . '" AND cookiePass = "' . $cookie [1] . '";';
			$this->context->mysqli->query ($consulta);

			// Delete in the browser
			unset ($_COOKIE ['SecurityCookie']);
			setcookie ('SecurityCookie', '', - 1, '/');
		}

		if (! isset ($_SESSION)) session_start ();
		unset ($_SESSION);
		session_unset ();
		session_destroy ();
		$_SESSION ['userName'] = '';

		return '<h2 class="warning">Logout sucess</h2>';
	}


	public function getExternalCss ()
	{
		$css = array ();
		$css [] = 'gioMain.css';
		$css [] = 'groupList.css';
		return $css;
	}


	public static function getPlgInfo (): array
	{
		$plgInfo = array ();
		$plgInfo ['plgDescription'] = "Muestra el día de hoy";
		$plgInfo ['isMenu'] = 1;
		$plgInfo ['perms'] = '[]';
		$plgInfo ['params'] = '[]';

		return $plgInfo;
	}
}


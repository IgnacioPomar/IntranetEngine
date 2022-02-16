<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'libphp-phpmailer/autoload.php';


function smtpSend ($email, $subject, $body, $bodyTxt)
{
	$mail = new PHPMailer (true);

	try
	{
		// Server settings
		$mail->SMTPDebug = 0;
		$mail->isSMTP ();
		$mail->Host = 'smtp.gmail.com';
		$mail->SMTPAuth = true;
		$mail->Username = $GLOBALS ['smtpUsername'];
		$mail->Password = $GLOBALS ['smtpPass'];
		$mail->SMTPSecure = 'tls';
		$mail->Port = 587;

		$mail->setFrom ($GLOBALS ['smtpUsername'], 'Zona alumnos - GolfInone');

		// //YAGNI: recinbir el nombre de la cuneta
		$mail->addAddress ($email, $email);

		// Attachments
		// $mail->addAttachment ($realFile, $fileName);

		// Content

		$mail->isHTML (true);
		$mail->Body = $body;
		$mail->AltBody = $bodyTxt;
		$mail->Subject = $subject;

		$mail->send ();
	}
	catch (Exception $e)
	{
		// openlog ("gtsMailer", LOG_PID | LOG_PERROR, LOG_SYSLOG);
		syslog (LOG_ERR, "Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
		// closelog ();
	}
}


function base64url_encode ($data)
{
	return rtrim (strtr (base64_encode ($data), '+/', '-_'), '=');
}


function base64url_decode ($data)
{
	return base64_decode (strtr ($data, '-_', '+/'));
}


/**
 * Comprueba si venimos de un email de recuperación de contraseña, y establece las variables del sistema en consecuencia
 */
function checkEmailRecoverLink ()
{
	if (isset ($_GET ['recover']))
	{
		$prms = array ();
		parse_str (base64_decode (strtr ($_GET ['recover'], '-_', '+/')), $prms);
		if (isset ($prms ['rP']) && isset ($prms ['rE']) && isset ($prms ['CR']))
		{
			$_POST ['recoverPass'] = $prms ['rP'];
			$_POST ['recoverEmail'] = $prms ['rE'];
			$_GET ['codRec'] = strtr ($prms ['CR'], '+/', '-_');
		}
	}
}


/**
 * Función ecargada de mandar por SMTRP un email
 *
 * @param string $email
 * @param string $pass
 */
function sendRecoverEmail ($email, $pass, $isNewAccount)
{
	$plainLink = 'rP&rE=' . $email . '&CR=' . strtr ($pass, '-_', '+/');
	$server = $_SERVER ['REQUEST_SCHEME'] . '://' . $_SERVER ['SERVER_NAME'];
	$link = $server . $GLOBALS ['uriPath'] . '?recover=' . base64url_encode ($plainLink);

	if ($isNewAccount)
	{
		$subject = 'Para crear cuenta en GolfinOne';
		$skinFile = 'html/msgNewAccount.htm';
		$skinTextFile = 'html/msgNewAccount.txt';
	}
	else
	{
		$subject = 'Recuperar credenciales en GolfinOne';
		$skinFile = 'html/msgRecoverPass.htm';
		$skinTextFile = 'html/msgRecoverPass.txt';
	}

	$msg = file_get_contents ($GLOBALS ['skinPath'] . $skinFile);
	$msg = str_replace ('@@recoverCode@@', $pass, $msg);
	$msg = str_replace ('@@recoverLink@@', $link, $msg);

	$msgtxt = file_get_contents ($GLOBALS ['skinPath'] . $skinTextFile);
	$msgtxt = str_replace ('@@recoverCode@@', $pass, $msg);
	$msgtxt = str_replace ('@@recoverLink@@', $link, $msg);

	smtpSend ($email, $subject, $msg, $msgtxt);
}


/**
 * Genera una password aleatoria válida para el código de recuperación
 *
 * @return string
 */
function generateRecoverPass ()
{
	return rtrim (base64_encode (random_bytes (14)), '=');
}

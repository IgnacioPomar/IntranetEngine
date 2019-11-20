<?php
include_once ('src/defines.php');

session_start ();

$_SESSION ['permissions'] = array ();

// ---------------------- Mostramos la web ----------------------

// 0 ---> Check user languaje
$lang = substr ($_SERVER ['HTTP_ACCEPT_LANGUAGE'], 0, 2);

// 1 ---> Check if is a clean install
if (! file_exists ($GLOBALS ['fileCfg']))
{
	include_once $GLOBALS ['srcPath'] . 'installer.php';
	Installer::install ($lang);
	return (0);
}

include_once $GLOBALS ['fileCfg'];

// 1.5 ---> Get skin name
$skin = $GLOBALS ['skin'];
$GLOBALS ['skinPath'] = $GLOBALS ['basePath'] . 'skins' . DIRECTORY_SEPARATOR . $skin . DIRECTORY_SEPARATOR;
$GLOBALS ['plgsPath'] = $GLOBALS ['basePath'] . 'plgs' . DIRECTORY_SEPARATOR . $GLOBALS ['plgs'] . DIRECTORY_SEPARATOR;
$GLOBALS ['skinUriPath'] = $GLOBALS ['uriPath'] . 'skins' . DIRECTORY_SEPARATOR . $skin . DIRECTORY_SEPARATOR;

// 2 ---> Check if we need to update the database
if ($GLOBALS ['Version'] != VERSION)
{
	include_once $GLOBALS ['srcPath'] . 'installer.php';
	Installer::update ($lang);
	return (0);
}

// 3 ---> Check AuthData
include_once $GLOBALS ['srcPath'] . 'auth.php';
$userId = Auth::login ($skin);

// 4 ---> (lastly) We show the real Page
if ($userId !== NULL)
{
	$GLOBALS ['relativePath'] = getRelativeSitePath ();
	include_once $GLOBALS ['srcPath'] . 'webcomposer.php';
	WebComposer::main ($skin, $userId);
}
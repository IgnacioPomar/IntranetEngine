<?php
/**
 * @name	defines.php
 * Variables comunes a toda la p�gina web
 */

// ---------------------- definiciones comunes ----------------------
define ('VERSION', '0.1');



/**
 * Obtenemos la ruta relativa del sitio.
 * S�lo debe invocarse desde el index.php raiz
 *
 * @return string ruta reativa
 */
function getRelativeSitePath ()
{
    $rutaRelativa = $_SERVER ['SCRIPT_NAME'];
    $rutaRelativa = substr ($rutaRelativa, 0, - 9); // Quitamos index.php
    
    return $rutaRelativa;
}


// Rutas necesarias para poder moevr de directorio de forma transparente

$GLOBALS ['basePath'] = getcwd () . DIRECTORY_SEPARATOR;
$GLOBALS ['cfgPath'] = $GLOBALS ['basePath'] . 'cfg' . DIRECTORY_SEPARATOR;
$GLOBALS ['fileCfg'] = $GLOBALS ['cfgPath'] . 'site_cfg.php';
$GLOBALS ['uriPath'] = getRelativeSitePath ();


function setCfgGlobals ()
{
	$GLOBALS ['skinPath'] = $GLOBALS ['basePath'] . 'skins' . DIRECTORY_SEPARATOR . $GLOBALS['skin'] .  DIRECTORY_SEPARATOR ;
	$GLOBALS ['templatePath'] = $GLOBALS ['skinPath'] . 'tmplt' . DIRECTORY_SEPARATOR;
}
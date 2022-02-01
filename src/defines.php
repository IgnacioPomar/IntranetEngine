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
$GLOBALS ['fileCfg'] = $GLOBALS ['basePath'] . 'cfg' . DIRECTORY_SEPARATOR . 'site_cfg.php';
//$GLOBALS ['srcPath'] = $GLOBALS ['basePath'] . 'src' . DIRECTORY_SEPARATOR;
$GLOBALS ['uriPath'] = getRelativeSitePath ();


$GLOBALS ['skinPath'] = $GLOBALS ['basePath'] . 'skin' . DIRECTORY_SEPARATOR;
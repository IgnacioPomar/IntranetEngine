<?php
/**
 * @name	defines.php
 * Variables comunes a toda la página web
 */

// ---------------------- definiciones comunes ----------------------
// Muestra la versión de la web acttual
define ('VERSION', '0.1');
define ('PROJECT_NAME', 'GVI-Intranet');

// Prefijos de tablas
define ('DB_PREFIX', 'grn_');


/**
 * Obtenemos la ruta relativa del sitio.
 * Sólo debe invocarse desde el index.php raiz
 *
 * @return string ruta reativa
 */
function getRelativeSitePath ()
{
	$rutaRelativa = $_SERVER ['SCRIPT_NAME'];
	$rutaRelativa = substr ($rutaRelativa, 0, - 9); // Quitamos index.php

	return $rutaRelativa;
}


/**
 * Añadimos el prefijo a la consulta especificada
 *
 * @param
 *        	string Consulta sin prefijos
 * @return string La consulta con prefijo incluido
 */
function prefixQuery ($query)
{
	return str_replace ('}', '', str_replace ('{', DB_PREFIX, $query));
}

// Rutas necesarias para poder moevr de directorio de forma transparente

$GLOBALS ['basePath'] = getcwd () . DIRECTORY_SEPARATOR;
$GLOBALS ['fileCfg'] = $GLOBALS ['basePath'] . 'cfg' . DIRECTORY_SEPARATOR . 'site_cfg.php';
$GLOBALS ['srcPath'] = $GLOBALS ['basePath'] . 'src' . DIRECTORY_SEPARATOR;
$GLOBALS ['uriPath'] = getRelativeSitePath ();

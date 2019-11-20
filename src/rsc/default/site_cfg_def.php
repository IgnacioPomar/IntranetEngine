<?php

/**
 * Datos de configuracion
 */

// Versión del sitio
// Versión del sitio
$GLOBALS['Version'] = '0.1';

// Habilitar o deshabilitar Role-based access control
$GLOBALS['enableRBAC']= TRUE;

// Configuración de la base de datos
$GLOBALS['dbserver'] = '@@dbserver@@';
$GLOBALS['dbport'] ='@@dbport@@';
$GLOBALS['dbuser'] ='@@dbuser@@';
$GLOBALS['dbpass'] = '@@dbpass@@';
$GLOBALS['dbname'] = '@@dbname@@';


$GLOBALS['plgs'] = '@@plgs@@';
$GLOBALS['skin'] = '@@skins@@';

// Encoding del servidor
define ( 'SERVER_ENCODING', 'UTF-8' );

// Configuración local
date_default_timezone_set ( 'Europe/Madrid' );
setlocale ( LC_ALL, 'es_ES.UTF8' );

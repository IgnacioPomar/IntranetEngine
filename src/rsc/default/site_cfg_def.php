<?php
/**
 * Site CFG
 */

// site Version
$GLOBALS ['Version'] = '0.1';

// Selected modules
$GLOBALS ['moduleAuth'] = 'src/auth_vanilla.php';
$GLOBALS ['moduleMenu'] = 'src/menu_json.php';

// ----------------------------------
// Database CFG
$GLOBALS ['dbserver'] = '@@dbserver@@';
$GLOBALS ['dbport'] = '@@dbport@@';
$GLOBALS ['dbuser'] = '@@dbuser@@';
$GLOBALS ['dbpass'] = '@@dbpass@@';
$GLOBALS ['dbname'] = '@@dbname@@';

// ----------------------------------

$GLOBALS ['plgs'] = '@@plgs@@';
$GLOBALS ['skin'] = '@@skins@@';

// ----------------------------------
define ('SERVER_ENCODING', 'UTF-8');
date_default_timezone_set ('Europe/Madrid');
setlocale (LC_ALL, 'es_ES.UTF8');

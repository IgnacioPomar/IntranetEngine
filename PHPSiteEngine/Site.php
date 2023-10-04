<?php

namespace PHPSiteEngine;

/**
 * Contains the Site Engine Version, and all the Paths Info
 */
class Site
{
	const VERSION = '0.2';

	// ----------- Universal Folders -----------
	// The browser PATH
	public static $uriPath;

	// The felesystempath to the
	public static $basePath;

	// The folder with configuration files
	public static $cfgPath;

	// Main configuration file
	public static $cfgFile;

	// ----------- Configurated Folders -----------
	// Folder containing the plugins
	public static $plgsPath;

	// Folder who stores the skin
	public static $skinPath;

	// the Browser path to the skin
	public static $uriSkinPath;

	// Folder who stores the templates
	public static $templatePath;


	// ----------- Methods -----------
	/**
	 * Initialization of universal Vars
	 */
	public static function init ()
	{
		self::$uriPath = dirname ($_SERVER ['SCRIPT_NAME']); // Remove index.php or siteAdmin.php
		self::$uriPath = rtrim (self::$uriPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR; // Force end with slash

		$reflector = new \ReflectionClass (self::class);
		self::$basePath = dirname (dirname ($reflector->getFileName ())) . DIRECTORY_SEPARATOR;

		self::$cfgPath = self::$basePath . 'cfg' . DIRECTORY_SEPARATOR;
		self::$cfgFile = self::$cfgPath . 'site_cfg.php';

		echo self::$basePath;
	}


	/**
	 * Initialization of config dependent Vars
	 */
	public static function initCfg ()
	{
		$GLOBALS ['plgsPath'] = $GLOBALS ['basePath'] . 'plgs' . DIRECTORY_SEPARATOR . $GLOBALS ['plgs'] . DIRECTORY_SEPARATOR;
		$GLOBALS ['skinPath'] = $GLOBALS ['basePath'] . 'skins' . DIRECTORY_SEPARATOR . $GLOBALS ['skin'] . DIRECTORY_SEPARATOR;
		$GLOBALS ['templatePath'] = $GLOBALS ['skinPath'] . 'tmplt' . DIRECTORY_SEPARATOR;
		$GLOBALS ['urlSkinPath'] = $GLOBALS ['uriPath'] . 'skins' . DIRECTORY_SEPARATOR . $GLOBALS ['skin'] . DIRECTORY_SEPARATOR;

		self::$plgsPath = self::$basePath . 'plgs' . DIRECTORY_SEPARATOR . $GLOBALS ['plgs'] . DIRECTORY_SEPARATOR;
		self::$skinPath = self::$basePath . 'skins' . DIRECTORY_SEPARATOR . $GLOBALS ['skin'] . DIRECTORY_SEPARATOR;
		self::$templatePath = self::$skinPath . 'tmplt' . DIRECTORY_SEPARATOR;
		self::$uriSkinPath = self::$uriPath . 'skins' . DIRECTORY_SEPARATOR . $GLOBALS ['skin'] . DIRECTORY_SEPARATOR;
	}
}

// We will initialize when we load the file
Site::init ();



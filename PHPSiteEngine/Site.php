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

	// The felesystempath to the Site root
	public static $rootPath;

	// The folder with configuration files
	public static $cfgPath;

	// Main configuration file
	public static $cfgFile;

	// The felesystempath to the namespace folder
	public static $nsPath;

	// The felesystempath to the resource folder
	public static $rscPath;

	// The browser path to the resource folder
	public static $rscUriPath;

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
		self::$nsPath = dirname ($reflector->getFileName ()) . DIRECTORY_SEPARATOR;
		self::$rootPath = dirname (self::$nsPath) . DIRECTORY_SEPARATOR;

		self::$cfgPath = self::$rootPath . 'cfg' . DIRECTORY_SEPARATOR;
		self::$cfgFile = self::$cfgPath . 'site_cfg.php';

		self::$rscPath = self::$nsPath . 'rsc' . DIRECTORY_SEPARATOR;
		self::$rscUriPath = self::$uriPath . $reflector->getNamespaceName () . DIRECTORY_SEPARATOR . 'rsc' . DIRECTORY_SEPARATOR;
	}


	/**
	 * Initialization of config dependent Vars
	 */
	public static function initCfg ()
	{
		self::$plgsPath = self::$rootPath . 'plgs' . DIRECTORY_SEPARATOR . $GLOBALS ['plgs'] . DIRECTORY_SEPARATOR;
		self::$skinPath = self::$rootPath . 'skins' . DIRECTORY_SEPARATOR . $GLOBALS ['skin'] . DIRECTORY_SEPARATOR;
		self::$templatePath = self::$skinPath . 'tmplt' . DIRECTORY_SEPARATOR;
		self::$uriSkinPath = self::$uriPath . 'skins' . DIRECTORY_SEPARATOR . $GLOBALS ['skin'] . DIRECTORY_SEPARATOR;
	}
}

// We will initialize when we load the file
Site::init ();



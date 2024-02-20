<?php

namespace PHPSiteEngine\PlgsAdm;

use PHPSiteEngine\Plugin;
use PHPSiteEngine\Installer;

class ReinstallPlugins extends Plugin
{


	public static function getPlgInfo (): array
	{
	}


	public function main ()
	{
		$installer = new Installer ($this->context->mysqli);

		$retVal = '<h1>Reinstalling Plugins</h1>';
		$retVal .= $installer->registerPlugins ();
		return $retVal;
	}
}
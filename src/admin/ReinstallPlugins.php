<?php

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
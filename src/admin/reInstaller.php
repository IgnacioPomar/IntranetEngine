<?php

class ReInstaller extends Plugin
{


	public static function getPlgInfo (): array
	{
	}


	public function main ()
	{
		$installer = new Installer ($this->context->mysqli);

		if ($_SERVER ['PATH_INFO'] == '/reinstallCore')
		{
			$retVal = '<h1>Reinstalling Core Tables</h1>';
			$retVal .= $installer->createCoreTables ();
			return $retVal;
		}

		if ($_SERVER ['PATH_INFO'] == '/reinstallPlugins')
		{
			$retVal = '<h1>Reinstalling Plugins</h1>';
			$retVal .= $installer->registerPlugins ();
			return $retVal;
		}
	}
}
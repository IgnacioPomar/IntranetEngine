<?php

class ReinstallCore extends Plugin
{


	public static function getPlgInfo (): array
	{
	}


	public function main ()
	{
		$installer = new Installer ($this->context->mysqli);

		$retVal = '<h1>Reinstalling Core Tables</h1>';
		$retVal .= $installer->createCoreTables ();
		return $retVal;
	}
}
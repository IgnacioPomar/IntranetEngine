<?php

/*
 * The Menu - JSon variant means all the users will see the same options
 * (it'll not hide options if we haver give permissions to it)
 *
 */
class MenuLoaderJson
{


	/**
	 *
	 * @param Context $context
	 * @return array
	 */
	public static function load (&$context)
	{
		$menuLoaderJson = new MenuLoaderJson ();
		$context->mnu = $menuLoaderJson->getArrayMenu ();

		return 0;
	}

	public function getArrayMenu ($mysqli = NULL, $userId = NULL)
	{
		$menuFile = (isset ($GLOBALS ['jsonMenu'])) ? $GLOBALS ['jsonMenu'] : 'mainMenu.json';

		$string = file_get_contents ($GLOBALS ['cfgPath'] . $menuFile);

		return json_decode ($string, true);
	}
}
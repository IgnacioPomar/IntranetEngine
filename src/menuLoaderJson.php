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
	public function load (&$context)
	{
		$menuFile = (isset ($GLOBALS ['jsonMenu'])) ? $GLOBALS ['jsonMenu'] : 'mainMenu.json';

		$string = file_get_contents ($GLOBALS ['cfgPath'] . $menuFile);

		$context->mnu = json_decode ($string, true);

		return 0;
	}
}
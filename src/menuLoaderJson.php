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
	public static function load (&$context, Menu &$menu)
	{
		$menuFileName = (isset ($GLOBALS ['jsonMenu'])) ? $GLOBALS ['jsonMenu'] : 'mainMenu.json';
		MenuLoaderJson::loadFromFile ($GLOBALS ['cfgPath'] . $menuFileName, $menu);
	}


	public static function loadFromFile ($menuFile, Menu &$menu)
	{
		$menu->setMenuOpc (json_decode (file_get_contents ($menuFile), true));
		$menu->isEditable = false;
	}
}



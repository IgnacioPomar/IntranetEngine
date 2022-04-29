<?php

class MenuLoaderDB
{
	const ONLY_SITE_ADMIN = - 1;


	/**
	 *
	 * @param Context $context
	 * @return array
	 */
	public static function load (&$context, Menu &$menu)
	{
		$menu->setMenuOpc (MenuLoaderDB::getArrayMenu ($context->mysqli, $context->userId));
		$menu->isEditable = true;
	}


	/**
	 *
	 * @param mysqli $mysqli
	 * @return array $orderMenu
	 */
	public static function getArrayMenu ($mysqli, $userId = self::ONLY_SITE_ADMIN)
	{
		$query = 'SELECT idNodo, idNodoParent, uri AS opc, plg, isEnable AS "show", name, tmplt FROM weMenu ';
		if ($userId != self::ONLY_SITE_ADMIN)
		{
			$query .= 'INNER JOIN (SELECT mnuNode, MIN(permValue) minPermValue FROM';
			$query .= "(SELECT permValue, mnuNode FROM wePermissionsUsers WHERE idUser = $userId ";
			$query .= 'UNION ALL SELECT permValue, mnuNode FROM wePermissionsGroup WHERE idGrp IN';
			$query .= "(SELECT idGrp FROM weUsersGroups WHERE idUser = $userId)) permission WHERE permValue <> 0 GROUP BY mnuNode) ";
			$query .= 'permission ON uri = mnuNode AND minPermValue <> -1 ';
		}
		$query .= 'WHERE isEnable = 1 ORDER BY idNodoParent, menuOrder';

		$menuDB = $mysqli->query ($query)->fetch_all (MYSQLI_ASSOC);

		$orderMenuDb = array ();
		foreach ($menuDB as &$parentItem)
		{
			// If the menu contains a parent we stop the loop since it should be assigned.
			// if ($parentItem ['idNodoParent'] != NULL) break;

			$orderMenuDb [$parentItem ['idNodo']] = $parentItem;
			// We delete the first element, since it will be the one we just saved in our new array
			array_shift ($menuDB);

			$childs = self::addChildsMenuItems ($menuDB, $parentItem ['idNodo']);
			if (! empty ($childs [$parentItem ['idNodo']]))
			{
				$orderMenuDb [$parentItem ['idNodo']] ['subOpcs'] = $childs [$parentItem ['idNodo']];
			}
		}

		return $orderMenuDb;
	}


	/**
	 *
	 * @param array $menuDB
	 * @param int $parentId
	 * @return array $childsOfMenu
	 */
	private static function addChildsMenuItems (&$menuDB, $parentId)
	{
		$childsOfMenu = array ();
		foreach ($menuDB as $index => $childItem)
		{
			if ($childItem ['idNodoParent'] == $parentId)
			{
				$childsOfMenu [$parentId] [$childItem ['idNodo']] = $childItem;
				unset ($menuDB [$index]);

				$childs = self::addChildsMenuItems ($menuDB, $childItem ['idNodo']);
				if (! empty ($childs [$childItem ['idNodo']]))
				{
					$childsOfMenu [$parentId] [$childItem ['idNodo']] ['subOpcs'] = $childs [$childItem ['idNodo']];
				}
			}
		}
		if (! empty ($childsOfMenu)) return $childsOfMenu;
	}
}
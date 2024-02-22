<?php

namespace PHPSiteEngine;

class Migrator
{
	private $context;


	/**
	 * Constructor
	 */
	public function __construct ($context)
	{
		$this->context = $context;
	}


	public function migrate02To03 ()
	{
		echo "TODO: Migrate 0.2 to 0.3";

		// --- In the table weUsers --
		// 1.- Add the user uuid field
		// 2.- Iterate each user creating the uuid
		// 3.- Create the migration table (weMigration_02To03)
		// 4.- Copy idUser and uuidUser to weMigration_02To03
		// 5.- Delete idUser from weUsers
		// 6.- Change the key

		// --- Make trhe process for the tables: weUsersGroups, wePermissionsUsers, weSessCookie, weUsersGroups
		// 1.- Add the user uuid field
		// 2.- copy the uuid from the table weMigration_02To03
		// 3.- change indexs and drop the old idUser field

		// --- finally, call the plugin migrators, if any
	}


	/**
	 * Migrate the site to the new version
	 */
	public static function migrate ($context)
	{
		$migrator = new Migrator ($context);

		echo 'Migrating...';
		switch ($GLOBALS ['Version'])
		{
			case '0.2':
				$migrator->migrate02To03 ();
				break;
		}
	}
}
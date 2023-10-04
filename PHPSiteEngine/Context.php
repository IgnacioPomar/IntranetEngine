<?php

namespace PHPSiteEngine;

require_once ('Menu.php');

class Context
{
	public \mysqli $mysqli;
	public int $userId;
	public Menu $mnu;
}
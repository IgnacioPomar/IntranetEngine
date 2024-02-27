<?php

namespace PHPSiteEngine;

require_once ('Menu.php');

class Context
{
	public \mysqli $mysqli;
	public ?string $userId;
	public Menu $mnu;
	public bool $isAjax;
}
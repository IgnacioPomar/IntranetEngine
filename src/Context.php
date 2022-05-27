<?php
require_once ('src/Menu.php');

class Context
{
	public mysqli $mysqli;
	public int $userId;
	public Menu $mnu;
}
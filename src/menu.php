<?php

/*
 *
 * As all the Menu modules, it'll return:
 * - From the selected option:
 * · The template
 * · The plugin name
 * - A formatted menu for the user
 * - The personal Info Menu (if any)
 */
class Menu
{
	private $currOpc;
	private $hasOpc;


	/**
	 * Sets the full menu info
	 *
	 *
	 * @param Context $context
	 */
	function __construct (&$context)
	{
		$this->context = $context;

		$this->context->subPath = (isset ($_SERVER ['PATH_INFO'])) ? $_SERVER ['PATH_INFO'] : "/";

		$this->hasOpc = FALSE;
		$this->setSelectedOpc ($this->context->mnu);
	}


	/**
	 * Main Menu.
	 *
	 * @return string
	 */
	public function getMenu ($mainMnu)
	{
		$retVal = '<span id="mainMenu">';
		$retVal .= $this->getMnuOpcs ($mainMnu, 0);
		return $retVal . '</span>';
	}


	/**
	 * Main Menu.
	 *
	 * @return string
	 */
	public function getBaseTemplate ()
	{
		return $this->currOpc ['tmplt'];
	}


	public function getTitle ()
	{
		return $this->currOpc ['name'];
	}


	public function getPlugin ()
	{
		return $this->currOpc ['plg'];
	}


	public function hasOpcSelected ()
	{
		return $this->hasOpc;
	}


	/**
	 * Searchs recursively for the selected option
	 *
	 * @return string
	 */
	private function setSelectedOpc (&$arrOpcs)
	{
		$retVal = false;
		foreach ($arrOpcs as &$opc)
		{
			if (isset ($opc ['opc']) && $opc ['opc'] == $this->context->subPath)
			{
				$opc ['isSelected'] = true;
				$this->currOpc = $opc;
				$retVal = true;
				$this->hasOpc = TRUE;

				if (isset ($opc ['subOpcs']))
				{
					$this->setSelectedOpc ($opc ['subOpcs']);
				}
			}
			else if (isset ($opc ['subOpcs']))
			{
				$opc ['isSelected'] = $this->setSelectedOpc ($opc ['subOpcs']);
			}
			else
			{
				$opc ['isSelected'] = false;
			}
		}
		return $retVal;
	}


	/**
	 * Main Menu.
	 * Iterate recursively on directories
	 *
	 * @return string
	 */
	private function getMnuOpcs ($arrOpcs, $lvl)
	{
		$retVal = '<ul class="mnuLvl' . $lvl . '">';
		foreach ($arrOpcs as $opc)
		{
			if ($opc ['show'])
			{
				$retVal .= '<li';
				if ($opc ['isSelected'])
				{
					$retVal .= ' class="selected"';
				}
				if (isset ($opc ['opc']))
				{
					$retVal .= '><a href="' . $_SERVER ['SCRIPT_NAME'] . $opc ['opc'] . '">' . $opc ['name'] . '</a>';
				}
				else
				{
					$retVal .= '>' . $opc ['name'];
				}

				if (isset ($opc ['subOpcs']))
				{
					$retVal .= $this->getMnuOpcs ($opc ['subOpcs'], $lvl + 1) . '<span class="caret"></span>';
				}
				$retVal .= '</li>' . PHP_EOL;
			}
		}
		return $retVal . '</ul>';
	}


	/**
	 * Menu for the user itself
	 *
	 * @return string
	 */
	public function getUserMnu ()
	{
		$retVal = '<a href="' . $GLOBALS ['uriPath'] . '?o=logout">Desconectar</a>';

		// $retVal = '<a href="'.$GLOBALS ['uriPath'].'?o=logout">Perfil</a>'; //Nombre, forma de pago, etc...

		return $retVal;
	}
}
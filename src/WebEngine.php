<?php


class WebEngine
{
	private $context;
	private $mnu;
	
	public static function launch (&$context, &$mnu, $isAjax)
	{
		$we = new WebEngine ();
		$we->context = & $context;
		$we->mnu = & $mnu;
		
		if ($isAjax)
		{
			$we->showAjaxBody();
		}
		else 
		{
			$we->compose();
		}
		
	}
	
	
	/**
	 * Gets from the Database the file of the plugin and includes it 
	 */
	private function loadPlugin ()
	{
		
	}
	
	
	private function compose ()
	{
		$layout = file_get_contents ($GLOBALS ['templatePath'] . $this->mnu->getBaseTemplate());
		$layout = str_replace ('@@Menu@@',  $this->mnu->getMenu (), $layout);
		$layout = str_replace ('@@pageTitle@@',   $this->mnu->getTitle (), $layout);
		
		
		//---- Session data ----
		$layout = str_replace ('@@userName@@', htmlspecialchars_decode ($_SESSION ['nombreUsuario']), $layout);
		
		//---- Plugin data ----
		$class = $this->mnu->getPlugin();
		$this->loadPlugin ($class);
		
		
		$skinFile = call_user_func ($class . '::getSkin');
		$cssFiles = call_user_func ($class . '::getExternalCss');
		$jsFiles = call_user_func ($class . '::getExternalJs');
		$jsCall = call_user_func ($class . '::getJsCall');
		$plgBody = call_user_func ($class . '::main', $this->context);
		
		
		$layout = str_replace ('@@body@@',  $plgBody, $layout);
		
		print ($layout);
	}
	
	
	private function getBody ()
	{
		$class = $this->mnu->getPlugin();
		$this->loadPlugin ($class);
		
		print (call_user_func ($class . '::main', $this->context));
	}
	
	
	
}
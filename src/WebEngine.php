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
	private function loadPlugin (): Plugin
	{
		if (!$this->mnu->hasOpcSelected())
		{
			require_once ('src/fake404.php');
			Fake404::main();
		}
		
		
		$class = $this->mnu->getPlugin();
		if ($resultado = $this->context->mysqli->query ("SELECT plgFile FROM wePlugins WHERE plgName='$class';"))
		{
			if ($row = $resultado->fetch_assoc ())
			{
				require_once ($row['plgFile']);
				return new $class ($this->context);
			}
		}
		
		//If we arrive here, means we have no plugin installed
		require_once ('src/fake404.php');
		Fake404::main();
		
	}
	
	
	private function compose ()
	{
		$layout = file_get_contents ($GLOBALS ['templatePath'] . $this->mnu->getBaseTemplate());
		
		
		
		//---- Session data ----
		$layout = str_replace ('@@userName@@', htmlspecialchars_decode ($_SESSION ['userName']), $layout);
		
		//---- Plugin data ----
		
		$plg = $this->loadPlugin ();
		
		/*
		$cssFiles = call_user_func ($class . '::getExternalCss');
		$jsFiles = call_user_func ($class . '::getExternalJs');
		$jsCall = call_user_func ($class . '::getJsCall');
		
		*/
		//---- Web Engine base components ----
		$layout = str_replace ('@@Menu@@',  $this->mnu->getMenu (), $layout);
		$layout = str_replace ('@@pageTitle@@',   $this->mnu->getTitle (), $layout);
		$layout = str_replace ('@@skinPath@@', $GLOBALS ['urlSkinPath'], $layout);
		
		
		//---- Finally, the body ----
		$plgBody = $plg->main();
		
		
		$layout = str_replace ('@@content@@',  $plgBody, $layout);
		
		print ($layout);
	}
	
	
	private function showAjaxBody ()
	{
		$plg = $this->loadPlugin ();
		print ( $plg->main());
	}
	
	
	
}
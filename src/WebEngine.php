<?php

class WebEngine
{
	private $context;
	private $mnu;
	
	/**
	 * @param Context $context
	 * @param Menu $mnu
	 * @param bool $isAjax
	 */
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
	 * Show the main body of the plugin and nothing else 
	 */
	private function showAjaxBody ()
	{
		$plg = $this->loadPlugin ();
		print ( $plg->main());
	}
	
	
	/**
	 * Compose the page  from its diufferent fragments
	 */
	private function compose ()
	{
		$plg = $this->loadPlugin ();
		
		$layout = file_get_contents ($GLOBALS ['templatePath'] . $this->mnu->getBaseTemplate());
		
		//---- Session data ----
		$layout = str_replace ('@@userName@@', htmlspecialchars_decode ($_SESSION ['userName']), $layout);
		
		//---- Plugin data ----
		
		$this->setJsCall($plg->getJsCall(), $layout);
		$this->setJsHeader($plg->getExternalJs(), $layout);
		$this->setCssHeader($plg->getExternalCss(), $layout);
		
		//---- Web Engine base components ----
		$layout = str_replace ('@@Menu@@',  $this->mnu->getMenu (), $layout);
		$layout = str_replace ('@@pageTitle@@',   $this->mnu->getTitle (), $layout);
		$layout = str_replace ('@@skinPath@@', $GLOBALS ['urlSkinPath'], $layout);
		
		//---- Finally, the body ----
		$plgBody = $plg->main();
		
		$layout = str_replace ('@@content@@',  $plgBody, $layout);
		
		print ($layout);
	}
	
	
	/**
	 * Returns the Plugin of the body.
	 * If there is no plugin, ends the execution
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
	
	/**
	 * Emplaces the content of the  javascript call at the end of the page 
	 * @param string $jsCall
	 * @param string $output
	 */
	private static function setJsCall ($jsCall, &$output)
	{
		$isAnyJsCall = FALSE;
		$jsCallContents = '';
		if ($jsCall != '') // avoid null files
		{
			$jsCallFile = $GLOBALS ['skinPath'] . 'js/' . $jsCall;
			if (file_exists ($jsCallFile))
			{
				$jsCallContents = '<script type="text/javascript">';
				$jsCallContents .= file_get_contents ($jsCallFile);
				$jsCallContents .= '</script>';
				$isAnyJsCall = TRUE;
			}
		}
		
		if ($isAnyJsCall)
		{
			$output = str_replace ('</body>', $jsCallContents . '</body>', $output);
		}
	}
	
	
	/**
	 * @param array $jsFiles
	 * @param string $output
	 */
	private static function setJsHeader ( $jsFiles, &$output)
	{
		$jsHeader = '';
		foreach ($jsFiles as $jsFile)
		{
			if (strpos ($jsFile, '://') !== false)
			{
				$jsPath = $jsFile;
			}
			else
			{
				$jsPath = $GLOBALS ['urlSkinPath']. "js/$jsFile";
			}
			
			$jsHeader .= '<script type="text/javascript" src="' . $jsPath . '"></script>' . PHP_EOL;
		}
		
		$output = str_replace ('</head>', $jsHeader . '</head>', $output);
	}
	
	
	/**
	 * @param array $cssFiles
	 * @param string $output
	 */
	private static function setCssHeader ( $cssFiles, &$output)
	{
		$cssHeader = '';
		foreach ($cssFiles as $cssFile)
		{
			if (strpos ($cssFile, '://') !== false)
			{
				$cssPath = $cssFile;
				$cssType = '';
			}
			else
			{
				$cssPath = $GLOBALS ['urlSkinPath']. "css/$cssFile";
				$cssType = 'type="text/css"';
			}
			
			// $cssPath = "./skins/$skin/css/$cssFile";
			$cssHeader .= '<link rel="stylesheet" ' . $cssType . ' href="' . $cssPath . '">' . PHP_EOL;
		}
		$output = str_replace ('</head>', $cssHeader . '</head>', $output);
	}
	
}
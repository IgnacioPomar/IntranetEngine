<?php

//De momento no se hace por falta de tiempo

class Installer
{
    public static function install ()
    {
        //YAGNI: Check if the filkke has been added manually (and thus, it lefts the database updates)
        if (isset ($_POST ['dbname']))
		{
            include_once ('dbSchema.php');
            readfile  ('./src/rsc/html/installResult.htm');
            $installer = new Installer ();
			print ($installer->installProcess ());
		}
		else
		{
			Installer::showInstallSetup ();
		}
    }

    public static function showInstallSetup ()
	{
		$options = '';
		foreach (glob ("./plgs/*", GLOB_ONLYDIR) as $filename)
		{
			$filename = str_replace ("./plgs/", "", $filename);
			$options .= '<option value="' . $filename . '">' . $filename . '</option>';
		}
		
		$skins = '';
		foreach (glob ("./skins/*", GLOB_ONLYDIR) as $filename)
		{
			$filename = str_replace ("./skins/", "", $filename);
			$skins .= '<option value="' . $filename . '">' . $filename . '</option>';
		}

        $layout = file_get_contents ('./src/rsc/html/installForm.htm');
        $layout = str_replace ('@@plgs@@',  $options, $layout);
        $layout = str_replace ('@@skins@@',  $skins, $layout);

        header ('Content-Type: text/html; charset=utf-8');
        print ($layout);
	}
    
    
    /**
	 *
	 * 
	 */
	public function installProcess ()
	{
		$this->mysqli = @new mysqli ($_POST ['dbserver'], $_POST ['dbuser'], $_POST ['dbpass'], $_POST ['dbname'], $_POST ['dbport']);

		// Pasos a realizar
		$outputMessage = '';

		// 0.- Check Params
		if (! $this->checkDbAccess ($outputMessage))
		{
			return $outputMessage;
		}

		// 1.- Guardamos el nuevo fichero de configuración
		if (! $this->saveNewCfgFile ($outputMessage))
		{
			return $outputMessage;
		}

		require_once (__DIR__ . '/../cfg/site_cfg.php');

		// Las rutas "finales" las obtenemos para lograr código "transparene"
		$GLOBALS ['skinPath'] = $GLOBALS ['basePath'] . 'skins' . DIRECTORY_SEPARATOR . $_POST ['skins'] . DIRECTORY_SEPARATOR;
		$GLOBALS ['plgsPath'] = $GLOBALS ['basePath'] . 'plgs' . DIRECTORY_SEPARATOR . $_POST ['plgs'] . DIRECTORY_SEPARATOR;

		// 2.- Creamos la estructura de la base de datos y metemos datos iniciales
		$this->createNewDBSchema ( $outputMessage);

        

        if (! $this->addInitialData ( $outputMessage))
		{
			return $outputMessage;
		}

        /*

		if (! Installer::registerPlugins ($mysqli, $outputMessage))
		{
			return $outputMessage;
		}

		

		// 3.- Actualizamos permisos y parámetros de plugins
		require_once ('modules/nodosMenu.php');
		$mysqli->select_db ($GLOBALS ['dbname']);
		// Actualizar permisos
		Permisos::updateTables ($mysqli);
		Permisos::actualizarOnInstall ($mysqli, $outputMessage);
		// Actualizar parámetros de configuración
		GestorNodosMenu::updateTables ($mysqli);
		Parametros::actualizarOnInstall ($mysqli, $outputMessage);

		// 4.- Creamos fichero HtAccess

        */

		return $outputMessage;
	}


    private function formatDBAction ($upd)
    {
        if ( $upd[1] == -1)
        {
            return '<div class="fail"><b>Error</b>: '.$upd[0].' <br /> ' . $this->mysqli->connect_error. '</div>';
        }
        else if ( $upd[1] == 1)
        {
            return '<div class="OK"><b>'.$upd[0].' </b>: Ok</div>';
        }
        else
        {
            return '<div class="none">'.$upd[0].' [No changes]</div>';
        }
    }

    
	/**
	 * Creamos el esquema de la base de datos
	 *
	 * @param string $outputMessage
	 */
	public function createNewDBSchema (&$outputMessage)
	{
        //relative to index... 
        $dir = new FilesystemIterator("./src/tables/");
        foreach ($dir as $fileinfo) 
        {
            if ($fileinfo->getExtension() == 'json')
            {
                //TODO: Elaborar más el informe de salida
                $upd = DbSchema::createOrUpdateTable ($this->mysqli, $fileinfo->getPathname());
                $outputMessage .= $this->formatDBAction ($upd);
            }
        }
    }

    /**
	 * Add the just created admin account
	 *
	 * @param boolean $outputMessage
	 */
	public function addInitialData (&$out)
	{
		$sqlCmd = 'INSERT INTO logins (name, email, password, isActive, isAdmin) VALUES (';
		$sqlCmd .= '"' . $_POST ['adminname'] . '",';
		$sqlCmd .= '"' . $_POST ['adminlogin'] . '",';
		$sqlCmd .= '"' . password_hash ($_POST ['adminpass1'], PASSWORD_DEFAULT) . '",';
		$sqlCmd .= '1,1);';

		// Finalmente, insertamos el nuevo registro
		if ($this->mysqli->query ($sqlCmd) === TRUE)
		{
			$out .= '<div class="ok">Creating admin credentials:  <b>OK</b></div>';
			return TRUE;
		}
		else
		{
			$out .= '<div class="fail"><b>Error</b>: Unablle to create admin user.<br />: '. $this->mysqli->error. '</div>';
			return FALSE;
		}
	}


    /**
	 * Check if the database info is correct
	 *
	 * @param string $outputMessage
	 *        	the output message in we will append the new report
	 */
	public  function checkDbAccess ( &$outputMessage)
	{
		if ($this->mysqli->connect_error)
		{
			$outputMessage .= '<div class="fail">';
			$outputMessage .= '<b>Error</b>: MySQL connection Failed.<br />';
			$outputMessage .= 'Codigo de error: ' . $this->mysqli->connect_errno . '.<br />';
			$outputMessage .= 'Descripci&oacute;n: ' . $this->mysqli->connect_error . '.<br />';
			$outputMessage .= '</div>';
			return FALSE;
		}
		else
		{
			$outputMessage .= '<div class="ok">Database Conection: <b>OK</b></div>';
			return TRUE;
		}
	}


    /**
	 *
	 * @param boolean $outputMessage
	 */
	public function saveNewCfgFile (&$outputMessage)
	{
		//TODO: Detect and apply the modules (auth....)

		$outputSkin = './src/rsc/default/site_cfg_def.php';
		$cfgFile = file_get_contents ($outputSkin);
		$cfgFile = str_replace ('@@dbserver@@', $_POST ['dbserver'], $cfgFile);
		$cfgFile = str_replace ('@@dbport@@', $_POST ['dbport'], $cfgFile);
		$cfgFile = str_replace ('@@dbuser@@', $_POST ['dbuser'], $cfgFile);
		$cfgFile = str_replace ('@@dbpass@@', $_POST ['dbpass'], $cfgFile);
		$cfgFile = str_replace ('@@dbname@@', $_POST ['dbname'], $cfgFile);
		$cfgFile = str_replace ('@@plgs@@', $_POST ['plgs'], $cfgFile);
		$cfgFile = str_replace ('@@skins@@', $_POST ['skins'], $cfgFile);

		if (! file_put_contents ($GLOBALS ['fileCfg'], $cfgFile))
		{
			$outputMessage .= '<div class="fail">';
			$outputMessage .= '<b>Error</b>: Unable to save the config file<br />';
			$outputMessage .= 'Workaround: save the following file<br /><pre>';
			$outputMessage .= $cfgFile;
			$outputMessage .= '</pre></div>';
			return FALSE;
		}
		else
		{
			$outputMessage .= '<div class="ok">Fichero de configuración: <b>OK</b></div>';
		}

		return TRUE;
	}
    
    
    public static function update ($mysqli)
    {
        include_once ('dbSchema.php');
        
        
        $retVal = '';
        
        //La ruta es relativa al index... 
        $dir = new FilesystemIterator("./src/tables/");
        foreach ($dir as $fileinfo) 
        {
            if ($fileinfo->getExtension() == 'json')
            {
                //TODO: Elaborar más el informe de salida
                $upd = DbSchema::createOrUpdateTable ($mysqli, $fileinfo->getPathname());

                $outputMessage .= '<div class="ok">Creando tablas: ' . $sqlTxtResult . '<br />Creacion de Tablas: <b>OK</b></div>';

                return TRUE;
            }
            else
            {
                $outputMessage .= '<div class="fail">';
                $outputMessage .= '<b>Error</b>: No se pudo crear la estructura de tablas.<br />Tablas ceradas: ';
                $outputMessage .= $sqlTxtResult;
                $outputMessage .= '</div>';

                $retVal .= $upd[0] . ': ' . $upd[1] . '<br />';
                
            }
        }
        
        return $retVal;
    }
    
}
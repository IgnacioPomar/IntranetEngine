<?php
include_once ('src/defines.php');


class WebEngineAdmin
{
    private $mysqli;
    private $userId;
    
    /**
     * Establish connection to database
     * @return boolean false if database connection failed
     */
    private function connectDb ()
    {
        $this->mysqli = new mysqli ($GLOBALS ['dbserver'], $GLOBALS ['dbuser'], $GLOBALS ['dbpass'], $GLOBALS ['dbname'], $GLOBALS ['dbport']);
        if ($this->mysqli->connect_errno)
        {
            print ('Database Connection Failed');
            print ('Errno: ' . $this->mysqli->connect_errno . '<br />');
            print ('Error: ' . $this->mysqli->connect_error . '<br />');
            
            return false;
        }
        
		//$mysqli->query ("SET NAMES 'UTF8'");
        $this->mysqli->set_charset ("utf8");
        
        
        return true;
        
    }
    
    /**
     * Check if this is a fresh installation
     * @return boolean false if we called the installer
     */
    private function checkInstallation ()
    {
        if (! file_exists ($GLOBALS ['fileCfg']))
        {
            include_once 'src/installer.php';
            Installer::install ();
            
            return false;
        }
		
        //Tenemos archivo de configuración
        include_once ($GLOBALS ['fileCfg']);
        
        return true;
    }
	
	/**
     * Check if we need to reinstall, and reinstalls if needed
     */
	private function updateIfOutdated ()
	{
		if ($GLOBALS ['Version'] != VERSION) 
		{
			//TODO: instead of force, let the user decide if wants to apply now
			include_once 'src/installer.php';
			$installer = new Installer ($this->mysqli);
			echo $installer->createCoreTables();
		}
		else
		{
			if (isset ($_GET['a']))
			{
				switch ($_GET['a'])
				{
					case 'reinstallCore':
						include_once 'src/installer.php';
						$installer = new Installer ($this->mysqli);
						echo $installer->createCoreTables();
						break;
				}
				
			}
			else 
			{
				echo 'Version engine matches. <br ><h2>Admin actions</h2>';
				$this->showAdminMnu ();
			}
		}
	}
    
	/**
	 * Show admin posibilities
	 */
	private function showAdminMnu ()
	{
		//YAGNI: Improve format
		//Only DBG
		echo '<a href="?a=reinstallCore">Reinstall core tables</a><br /><hr /><br />';
		
		//Std Optrions
		echo '<a href="?a=rePlugins">Reinstall Plugins</a><br />';
		
		//echo '<a href="?a=users">Admin site users</a><br />';
		//echo '<a href="?a=rbac">Edit permissions</a><br />';
		
		// is users menu by json or by database?
		//echo '<a href="?a=mnu">Edit Mnu</a><br />';
	}
	
    /**
     * Check user login
     *  @return boolean false if no user or user without admin role
     */
    private function checkAdminAuth ()
    {
        session_start ();
        
        // Comprobamos si estamos autenticados
        include_once  ($GLOBALS['moduleAuth']);
        $this->userId = Auth::setupLogin ($this->mysqli);
        
		//TODO; comprobar que tiene permisos de admin
		
        // Mostramos la página de verdad
        if ( $this->userId !== NULL)
        {
            if ($_SESSION ['isAdmin'] == 1)
            {
                return TRUE;
            }
            else
            {
                echo 'You need ADMIN privileges to enter here';
                return FALSE;
            }
        } 
        else
        {
            return FALSE;
        }
    }
    
  
    
    /**
     * Entry Point
     */
    public static function main ()
    {
        $adminModule = new WebEngineAdmin ();
        
        if ($adminModule->checkInstallation () && $adminModule->connectDb () && $adminModule->checkAdminAuth ())
        {
            $adminModule->updateIfOutdated ();
        }
        
    }

}

WebEngineAdmin::main ();


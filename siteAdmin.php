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
        include_once $GLOBALS ['fileCfg'];
        
        return true;
    }
	
	/**
     * Check if we need to reinstall, and reinstalls if needed
     */
	private function updateIfOutdated ()
	{
		if ($GLOBALS ['Version'] != VERSION) 
		{
			include_once 'src/installer.php';
            Installer::update ($this->mysqli);
		}
		else
		{
			echo 'Already updated';
		}
	}
    
    /**
     * Check user login
     *  @return boolean false if no user or user without admin role
     */
    private function checkAdminAuth ()
    {
        session_start ();
        
        // Comprobamos si estamos autenticados
        include_once  $GLOBALS['authModule'];
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


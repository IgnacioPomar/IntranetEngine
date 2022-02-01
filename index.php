<?php
include_once ('src/defines.php');


class WebEngine
{
    private $mysqli;
    private $userId;
    
    /**
     * Establish connection to database
     * @return boolean false if database connection failed
     */
    private function connectDb ()
    {
        $this->mysqli = new mysqli ($GLOBALS ['srv'], $GLOBALS ['user'], $GLOBALS ['password'], $GLOBALS ['name'], $GLOBALS ['port']);
        if ($this->mysqli->connect_errno)
        {
            print ('No se puede acceder a la base de datos. Revisar configuracion');
            print ('Errno: ' . $this->mysqli->connect_errno . '<br />');
            print ('Error: ' . $this->mysqli->connect_error . '<br />');
            
            return false;
        }
        
        $this->mysqli->set_charset ("utf8");
        
        //$mysqli->query ("SET NAMES 'UTF8'");
        
        return true;
        
    }
    
    /**
     * Realiza tareas de instalación
     * @return boolean true en caso de que no se interumpa por instalación la ejecución
     */
    private function checkInstallation ()
    {
        if (! file_exists ($GLOBALS ['fileCfg']))
        {
            echo 'Waiting installation';
            return false;
        }
        
        //Tenemos archivo de configuración
        include_once $GLOBALS ['fileCfg'];
        
        if ($GLOBALS ['Version'] != VERSION)
        {
            echo 'Maintenance in progress. Please, return later';
            return false;
        }
        return true;
    }
    
    /**
     * Comprueba que el usuario este logueado
     *  @return boolean false Si ha fallado la autenticación
     */
    private function checkAuth ()
    {
        session_start ();
        
        // Comprobamos si estamos autenticados
        include_once  'src/auth.php';
        $this->userId = Auth::login ($this->mysqli);
        
        // Mostramos la página de verdad
        return ( $this->userId !== NULL);
            
    }
    
    
    private function compose ()
    {
        $body = $this->getBody();
        
        $layout = file_get_contents ($GLOBALS ['skinPath'] . 'html/layout.htm');
        $layout = str_replace ('@@Mnu@@',  $this->getMenu(), $layout);
        $layout = str_replace ('@@topMnu@@',  $this->getUserMnu(), $layout);
        $layout = str_replace ('@@body@@',  $body, $layout);

        
        header ('Content-Type: text/html; charset=utf-8');
        print ($layout);
    }
    
    
    /**
     * Barra de menú de la izquierda: muestra las opciones para gestionar los usuarios
     * @return string
     */
    private function getMenu ()
    {
       $retVal = '<a href="'.$GLOBALS ['uriPath'].'?o=alumnos">Alumnos</a>';
       $retVal .= '<a href="'.$GLOBALS ['uriPath'].'?o=pago">Formas de Pago</a>';
       $retVal .= '<a href="'.$GLOBALS ['uriPath'].'?o=preins">Preinscripciones</a>';
       //$retVal .= '<a href="'.$GLOBALS ['uriPath'].'?o=clases">Clases recibidas</a>';
       
       
       //$retVal .= '<a href="'.$GLOBALS ['uriPath'].'?o=clases">Recuperar clases</a>';
       
       
       return $retVal;
    }
    
    
    /**
     * La barrea de arriba de menú esta reservada para el menu de usuario, a día de hoy sólo desconectar
     * @return string
     */
    private function getUserMnu ()
    {
        $retVal = '<a href="'.$GLOBALS ['uriPath'].'?o=logout">Desconectar</a>';
        
        //$retVal = '<a href="'.$GLOBALS ['uriPath'].'?o=logout">Perfil</a>'; //Nombre, forma de pago, etc...
        
        return $retVal;
    }
    

    
    /**
     * Punto de entrada a la aplicación 
     */
    public static function main ()
    {
        $webEngine = new WebEngine ();
        
        if ($webEngine->checkInstallation () && $webEngine->connectDb () && $webEngine->checkAuth ())
        {
            $webEngine->compose ( isset($_GET['ajax']) );   
        }
        
    }

}

WebEngine::main ();


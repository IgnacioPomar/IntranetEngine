<?php
include_once ('src/defines.php');


class WebEngine
{
    private $mysqli;
    private $userId;
    
    /**
     * Nos conectamos a la base de datos
     * @return boolean false en casod e que haya habido un error a la conexión en la base de datos
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
            include_once 'src/installer.php';
            Installer::install ();
            
            return false;
        }
        
        //Tenemos archivo de configuración
        include_once $GLOBALS ['fileCfg'];
        
        
        
        //if ($GLOBALS ['Version'] != VERSION) {//Que hacemos is cambia la versión?}
        
        
        
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
     * Pantalla de bienvenida para los alumnos (primera entrada en las aplicación)
     */
    private function bienvenida ()
    {
        //YAGNI: Hacer un  texto dinámico a introducir desde el área de "profesores"
        return file_get_contents ($GLOBALS ['skinPath'] . 'html/bienvenida.htm');
        
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
     * Hace de menú dentro de las opciones existentes
     * @return string
     */
    private function getBody ()
    {
        if (isset($_GET['o']))
        {
            switch ($_GET['o'])
            {
                case 'logout':
                    Auth::logout($this->mysqli); //Esta rompe la ejecución
                    break;
                case 'alumnos':
                    include_once ("src/Alumnos.php");
                    return Alumnos::main($this->mysqli, $this->userId);
                    break;
                case 'pago':
                    include_once ("src/Pagadores.php");
                    return Pagadores::main($this->mysqli, $this->userId);
                    break;
                case 'preins':
                	include_once ("src/Preinscripciones.php");
                	return Preinscripciones::main($this->mysqli, $this->userId);
                	break;
                default:
                    return $this->bienvenida();
                    break;
            }   
        }
        else
        {
            return $this->bienvenida();
        }
        
        return '';
    }
    
    /**
     * Punto de entrada a la aplicación 
     */
    public static function main ()
    {
        $webEngine = new WebEngine ();
        
        if ($webEngine->checkInstallation () && $webEngine->connectDb () && $webEngine->checkAuth ())
        {
            if (isset($_GET['ajax']))
            {
                return $webEngine->getBody ();
            }
            else
            {
                $webEngine->compose ();   
            }

        }
        
    }

}

WebEngine::main ();


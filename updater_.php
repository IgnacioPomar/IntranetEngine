<?php

include_once 'cfg/cfg.php';



//A continuación hacemos labores d emantenimiento "generiucos"

class UpdateTasks
{
    private $mysqli;
    
    public function execute ($sql)
    {
        // echo '<br>' . prefixQuery ( $sql );
        if (! $resultado = $this->mysqli->query ($sql))
        {
            echo "Error: La ejecución de la consulta falló debido a: \n";
            echo "Query: " . $sql . "\n";
            echo "Errno: " . $this->mysqli->errno . "\n";
            echo "Error: " . $this->mysqli->error . "\n";
            exit ();
        }
        else
        {
            return $resultado;
        }
    }
    
    
    /**
     * [SÓLO UNA VEZ] liga los clientes antiguos 
     */
    private function onlyOnce_linkExistingClients ()
    {
        echo "Creando alumnos de cada usuario<br />";
        
        $sqlTruncate = 'TRUNCATE alumnos;';
        $sql = 'INSERT INTO alumnos (idUsuario, nombre, apellidos,fechaNacimiento, noAbonado, nivel) 
SELECT idUsuario, name, surname,birthDate,LEFT(subNum , 29),level FROM almLogins l INNER JOIN alumns a ON l.email=TRIM(a.email) 
INNER JOIN preRegistrations p ON p.alumnId = a.id WHERE p.status<>-2';
        $this->execute ($sqlTruncate);
        $this->execute ($sql);
        
    }
    
    /**
     *  [SÓLO UNA VEZ] Crea los logins de los usuarios actualmente existentes 
     */
    private function onlyOnce_createLogins ()
    {
        echo "Creando nuevos usuarios<br />";
        
        $sqlTruncate = 'TRUNCATE almLogins;';
        $sql = 'INSERT INTO almLogins (email,isActive) SELECT DISTINCT(TRIM(email)) as email, 0 as isActive FROM alumns a
  INNER JOIN preRegistrations p ON p.alumnId = a.id WHERE p.status<>-2  ORDER BY email;';
        $this->execute ($sqlTruncate);
        $this->execute ($sql);

        //OJO: En la base de datos usada en desarrollo, aparte de duplicados hay un montón de emails erroneos
        //TAmbién hay un montón con espacios
        //  SELECT * FROM alumns WHERE TRIM(email) NOT REGEXP '^[a-zA-Z0-9][+a-zA-Z0-9._-]*@[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]*\\.[a-zA-Z]{2,4}$'
    }
    
    /**
     *  [SÓLO UNA VEZ] RElaciona las formas de pago
     */
    private function onlyOnce_createPagadores ()
    {
        echo "Creando formas de pago de cada usuario<br />";
        
        $sqlTruncate = 'TRUNCATE pagadores;';
        $sql = "INSERT INTO pagadores (idUsuario, titular, cuenta, email, porcentaje)
SELECT DISTINCTROW idUsuario, a.accountTit, REPLACE(a.account, ' ', '') as cta, l.email, 100
FROM almLogins l INNER JOIN alumns a ON l.email=TRIM(a.email) 
INNER JOIN preRegistrations p ON p.alumnId = a.id 
WHERE p.status<>-2 AND a.account is not null ORDER BY l.email, accountTit;
";
        //Para domicializaciones
        $sqlFormat = 'UPDATE pagadores SET formaPago=1, cuenta = concat(SUBSTRING(cuenta, 1, 4)," ",SUBSTRING(cuenta,5,4)," ",SUBSTRING(cuenta, 9, 4)," ",SUBSTRING(cuenta, 13, 2)," ",SUBSTRING(cuenta, 15, 10)) WHERE cuenta REGEXP "^ES[0-9]*$";';

        $this->execute ($sqlTruncate);
        $this->execute ($sql);
        $this->execute ($sqlFormat);
        

    }
    
    private static function addExcepion ()
    {
        $fecha  = date("Ymd");
        echo '<br /><hr /><br />';
        echo "Para evitar futuras ejecuciones, Incorpore a cfg.php<br />\$GLOBALS ['onlyOnceApplied']='$fecha'<br />";
    }
    
    public static function launch ()
    {
        session_start();
        
        $updTasks = new UpdateTasks ();
        
        $updTasks->mysqli =  new mysqli ($GLOBALS ['srv'],$GLOBALS ['user'],$GLOBALS ['password'],$GLOBALS ['name'],$GLOBALS ['port']);
        
        //A ejecutar una sóla vez
        if (isset($_SESSION["onlyOnceApplied"]))
        {
            if (!isset ($GLOBALS ['onlyOnceApplied']))
            {
                self::addExcepion ();
            }
        }
        else if (isset ($GLOBALS ['onlyOnceApplied']))
        {
            echo "Ya están aplicados los cambios de OnlyOnce<br />";
        }
        else
        {
            $updTasks->onlyOnce_createLogins();
            $updTasks->onlyOnce_linkExistingClients();
            $updTasks->onlyOnce_createPagadores ();
            
            $_SESSION["onlyOnceApplied"] = true;
            
            self::addExcepion ();
        }
        
        
        
    }
    
}

//----------------------- Lanzamos el proceso --------------------------------------


//Actualizamos las tablas en la base de datos
include_once 'src/installer.php';
echo Installer::update ();

echo '<br /><hr /><br />';

//Actualizamos los registros
UpdateTasks::launch ();
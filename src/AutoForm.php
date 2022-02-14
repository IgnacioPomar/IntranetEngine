<?php
class AutoForm
{
    //YAGNI: Considerar separar el comportamiento de los formularios en un json aparte para no "ensuciar" la estructura bd ela tabhla
    private $fields;
    private $tableName;
    private $hiddenFields;
    public $set;
    public $mysqli;
    
    const COMBO_PREFIX = 'combo@';
    
    
    public function __construct($jsonFile)
    {
        $tableSchema = json_decode(file_get_contents($jsonFile), true);
        
        $this->tableName = $tableSchema ['tablename'];
        $this->fields = $tableSchema ['fields'];
        $this->hiddenFields = array ();
        
    }
    
    
    
    private function isCombo ($type)
    {
        if ((substr ($type, 0, strlen (self::COMBO_PREFIX)) === self::COMBO_PREFIX))
        {
            return true;
        }
        else 
        {
            return false;
        }
    }
    
    private function getFormField ($fieldName, $fieldInfo, $val)
    {
        $formType = (isset($fieldInfo['formType']))?$fieldInfo['formType']:$fieldInfo['type'];
        $label= (isset($fieldInfo['label']))?$fieldInfo['label']:$fieldName;
        
        
        $prefix = '<div class="field"><label for="' . $fieldName . '">' . $label . '</label><span class="field-inner">';
        $sufix =  '</span></div>' . PHP_EOL;
        
        $params = '';
        $type=$formType;
        switch ($formType)
        {
            //Casos diferentes (rompen el flujo)
            case 'hidden':
                return '<input type="hidden" name="' . $fieldName . '" value="' . $val . '" />' . PHP_EOL;
                break;
            case 'json':
            case 'textarea':
                $retVal = '<textarea id="' . $fieldName . '" name="' . $fieldName . '" >' . $val . '</textarea>';
                return  $prefix. $retVal . $sufix;
                break;
                
                
                //Inputs que se muestran por pantalla (al salir del switch se usan)
            case 'string':
                $type='text';
                $params=  'maxlength="' . $fieldInfo['lenght'] . '"';
                break;
            case 'float':
                $params = ' step="0.01"';
            case 'int':
            case 'number':
                $type='number';
                break;
                
                //Inputs sin ningún tipo de cambio
            case 'date':
            case 'email':
            case 'color':
                break;
                
                
                //Caso especial: los combos incorporados
            default:
                if ($this->isCombo($type))
                {
                    $arrNAme =  substr ($type, strlen (self::COMBO_PREFIX));
                    $arr = constant ($arrNAme);
                    
                    $retVal = '<select class="form-control" id="' . $fieldName . '" name="' . $fieldName . '" >';
                    foreach ($arr as $clave => $valorOp)
                    {
                        $selected = ($val == $clave)? 'selected':'';
                        $retVal .= '<option value="' . $clave . '" '.$selected.'>' . $valorOp . '</option>';
                    }
                    return  $prefix. $retVal . '</select>' . $sufix;
                }
                break;
            
        }
        
       
        $retVal = '<input id="' . $fieldName . '"  name="' . $fieldName . '"  type="'.$type.'"  value="' . $val .  '" '.$params.'>';
        return  $prefix. $retVal . $sufix;
    }
    
    private function getExtraHiddenFields ()
    {
    	$retVal = '';
    	foreach ($this->hiddenFields as $key => $value)
    	{
    		$retVal .='<input type="hidden" name="'.$key.'" value="' . $value . '" />' . PHP_EOL;
    	}
    	
    	return $retVal;
    }
    
    
    public function generateForm ($rowData)
    {
        $action=$_SERVER['REQUEST_URI'];
        $retVal = '<form action="' . $action . '" method="post" autocomplete="off">';
        $retVal .= $this->getExtraHiddenFields();
        
        foreach ($this->set as $fieldName)
        {
            $val = '';
            if (isset($rowData[$fieldName]))
            {
            	$val = $rowData[$fieldName];
            }
            else if (isset($this->fields[$fieldName]['defaultValue']))
            {
            	$val = $this->fields[$fieldName]['defaultValue'];
            }
            $retVal .= $this->getFormField($fieldName, $this->fields[$fieldName], $val);
        }
        
        $retVal .= '<button class="btn" type="submit" value="Grabar">Grabar</button>';
        $retVal .= '</form>';
        
        return $retVal;
    }
    
    
    private function getSqlFormatted ($val, $fieldInfo)
    {
        switch ($fieldInfo['type'])
        {
            case 'auto':
            case 'int':
                return (is_numeric ($val))? $val: 'NULL';
                break;
            case 'date'://YAGNI: verificar formato de la fecha
                return '"' . $this->mysqli->real_escape_string ($val) . '"';
                break;
            case 'string':
                return '"' . $this->mysqli->real_escape_string ($val) . '"';
                break;
        }
        
    }
    
    public function setHidden ($name, $value)
    {
    	$this->hiddenFields[$name] = $value;
    }
    
    
    public function getInsertSql (array $data)
    {
        $retVal = 'INSERT INTO ' . $this->tableName . ' (';
        $values = ') VALUES (';
        
        $sep = '';
        
        foreach ($this->fields as $fieldName => $fieldInfo)
        {
            if (isset($_POST[$fieldName]))
            {
                $retVal .= $sep . $fieldName;
                $values .= $sep . $this->getSqlFormatted($_POST[$fieldName], $fieldInfo);
                
                $sep = ', ';
                
            }
        }
        return $retVal . $values . ');';
        
    }
    
    public function getUpdateSql (array $data, array $idxs)
    {
        $retVal = 'UPDATE ' . $this->tableName;
        $where = '';
        
        $fieldSep = ' SET ';
        $whereSep = ' WHERE ';
        foreach ($this->fields as $fieldName => $fieldInfo)
        {
            if (isset($_POST[$fieldName]))
            {
                
                if (in_array($fieldName, $idxs))
                {
                    $where .= $whereSep . $fieldName . '=' . $this->getSqlFormatted($_POST[$fieldName], $fieldInfo);
                    $whereSep = ' AND ';
                    
                }
                else
                {
                    $retVal .= $fieldSep. $fieldName . '=' . $this->getSqlFormatted($_POST[$fieldName], $fieldInfo);
                    
                    $fieldSep = ', ';
                }
            }   
        }
        
        return $retVal . $where . ';';
        
    }
    
    
    public static function editJsonForm ($jsonFile, $rowData, $fieldSet)
    {
        $autoForm = new AutoForm ($jsonFile);
        $autoForm->set = $fieldSet;
        
        return $autoForm->generateForm($rowData);
        
    }
    
}
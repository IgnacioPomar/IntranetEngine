<?php

class DbSchema
{


	private static function isQuerySucess ($mysqli, $sql)
	{
		if (! $resultado = $mysqli->query ($sql))
		{
			return false;
		}
		else
		{
			$resultado->close ();
			return true;
		}
	}


	private static function isExecutionSucess ($mysqli, $sql)
	{
		if (! $resultado = $mysqli->query ($sql))
		{
			echo "Error: La ejecución de la consulta falló debido a: \n";
			echo "Query: " . $sql . "\n";
			echo "Errno: " . $mysqli->errno . "\n";
			echo "Error: " . $mysqli->error . "\n";
			exit ();
		}
		else
		{
			return $resultado === TRUE;
		}
	}


	private static function getColumnType ($field)
	{
		switch ($field ['type'])
		{
			case 'auto':
				return ' int NOT NULL AUTO_INCREMENT';
				break;
			case 'bool':
				return ' bool DEFAULT FALSE';
				break;
			case 'int':
				return ' int DEFAULT NULL';
				break;
			case 'double':
				return ' double DEFAULT NULL';
				break;
			case 'decimal':
				return ' DECIMAL(10, 2) DEFAULT NULL';
				break;
			case 'date':
				return ' DATE DEFAULT NULL';
				break;
			case 'datetime':
				return ' DATETIME DEFAULT NULL';
				break;
			case 'text':
				return ' text DEFAULT NULL';
				break;
			case 'json':
				return ' json DEFAULT NULL';
				break;
			case 'string':
				if (isset ($field ['lenght']))
				{
					return ' varchar(' . $field ['lenght'] . ') DEFAULT NULL';
				}
				else
				{
					return ' text DEFAULT NULL';
				}
				break;
		}
	}


	private static function createTable ($mysqli, $tableInfo)
	{
		// TODO: Add the initial set of data from a JSON
		// TODO: Consider using DEFAULT NULL as other parameter
		$sql = 'CREATE TABLE ' . self::getTableName ($tableInfo) . '(';
		$sperador = '';
		foreach ($tableInfo ['fields'] as $fieldName => $field)
		{
			$sql .= $sperador;
			$sql .= $fieldName . self::getColumnType ($field);

			$sperador = ', ';
		}

		// Buscamos la clave primaria
		foreach ($tableInfo ['indexes'] as $index)
		{
			if (isset ($index ['primary']))
			{
				$sep = '';
				$sql .= ', PRIMARY KEY (';
				foreach ($index ['fields'] as $idxField)
				{
					$sql .= $sep . $idxField;
					$sep = ',';
				}
				$sql .= ')';
				break;
			}
		}

		// resto de las claves
		foreach ($tableInfo ['indexes'] as $idxNum => $index)
		{
			if (! isset ($index ['primary']))
			{
				$sep = '';
				$sql .= ', INDEX k_' . $idxNum . '(';
				foreach ($index ['fields'] as $idxField)
				{
					$sql .= $sep . $idxField;
					$sep = ',';
				}
				$sql .= ')';
			}
		}

		$sql .= ');';

		return self::isExecutionSucess ($mysqli, $sql);
	}


	private static function alterTable ($mysqli, $tableInfo)
	{
		// TODO: Maybe in the future, alse edit types
		// TODO: Maybe in teh future, change indexes
		// TODO: Decide what to do with the old fields (they are never removed)

		// TODO: Support table version for...
		// TODO: Support of "lambdas" to update the data in the tables
		$tablename = self::getTableName ($tableInfo);

		$sql = 'SHOW COLUMNS FROM ' . $tablename;
		$resultado = $mysqli->query ($sql);

		if (! $resultado) return - 1;

		$yaExisten = array ();
		while ($linea = $resultado->fetch_assoc ())
		{
			$yaExisten [] = $linea ['Field'];
		}
		$resultado->free ();

		$sql = '';
		$sep = 'ALTER TABLE ' . $tablename . ' ';

		$count = 0;

		foreach ($tableInfo ['fields'] as $fieldName => $field)
		{
			if (! in_array ($fieldName, $yaExisten))
			{
				$sql .= $sep . 'ADD COLUMN ' . $fieldName . self::getColumnType ($field);
				$sep = ', ';
				$count ++;
			}
		}

		if ($count > 0)
		{
			if (self::isExecutionSucess ($mysqli, $sql))
			{
				return 1;
			}
			else
			{
				return - 1;
			}
		}
		else
		{
			return 0;
		}
	}


	public static function getTableName (array $tableInfo)
	{
		if (isset ($tableInfo ['varSchema']))
		{
			// Defined in a global var
			return $GLOBALS [$tableInfo ['varSchema']] . '.' . $tableInfo ['tablename'];
		}
		else if (isset ($tableInfo ['fixedSchema']))
		{
			return $tableInfo ['fixedSchema'] . '.' . $tableInfo ['tablename'];
		}
		else
		{
			return $tableInfo ['tablename'];
		}
	}


	/**
	 *
	 * @param mysqli $mysqli
	 * @param array $fileInfo
	 * @return array: nombre tabla, y código de salida: 1, ok, 0, no accion, -1, error
	 *
	 */
	public static function createOrUpdateTable ($mysqli, $fileInfo)
	{
		$tableInfo = json_decode (file_get_contents ($fileInfo), true);

		$sql = 'SELECT 1 FROM ' . self::getTableName ($tableInfo) . ' LIMIT 1;';

		if (self::isQuerySucess ($mysqli, $sql))
		{
			$retVal = self::alterTable ($mysqli, $tableInfo);
		}
		else
		{
			$retVal = (self::createTable ($mysqli, $tableInfo)) ? 1 : - 1;
		}

		return array ($tableInfo ['tablename'], $retVal);
	}
}
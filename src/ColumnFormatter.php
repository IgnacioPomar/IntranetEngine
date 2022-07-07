<?php

/**
 * This class helps column order and format for reports
 */
class ColumnFormatter
{
	/*
	 * Must have the following format:
	 * colsDef = array (
	 * 'aaa' => array ('bbb', 'ccc', 'ddd'),
	 * );
	 *
	 * WHERE:
	 * 'aaa': index of the column array
	 * 'bbb': CSS class for the field
	 * 'ccc': Header name of the column
	 * 'ddd': Alt tag for the header
	 */
	private $colsDef;
	public $stylers;


	/**
	 * Constructor
	 *
	 * @param array $colsDef
	 *        	Column definition
	 */
	public function __construct (array $colsDef)
	{
		$this->colsDef = $colsDef;
		$this->stylers = array ();
	}


	public function getHeaderCols ()
	{
		$retVal = '';
		foreach ($this->colsDef as $col)
		{
			$retVal .= '<span class="' . $col [0] . '" title="' . $col [2] . '">' . $col [1] . '</span>';
		}
		return $retVal;
	}


	public function getHeaderColsWithOrdLink ($prelink)
	{
		$retVal = '';
		foreach ($this->colsDef as $idCol => $col)
		{
			$retVal .= '<a href="' . $prelink . $idCol . '"><span class="' . $col [0] . '" title="' . $col [2] . '">' . $col [1] . '</span></a>';
		}
		return $retVal;
	}


	public function getHeaderColsWithAutoOrder ($prelink, $orderTag = 'order', $dirTag = 'dir')
	{
		$currOrder = $_GET [$orderTag] ?? '';
		$currDir = $_GET [$dirTag] ?? 'ASC';
		$retVal = '';
		foreach ($this->colsDef as $idCol => $col)
		{
			$link = $prelink . $orderTag . '=' . $idCol;
			$class = '';
			if ($currOrder == $idCol)
			{
				$class = ' orderBy';
				$link .= '&' . $dirTag;
				if ($currDir == 'ASC')
				{
					$class .= ' ordAsc';
					$link .= '=DESC';
				}
				else
				{
					$class .= ' ordDesc';
					$link .= '=ASC';
				}
			}
			$retVal .= '<a href="' . $link . '"><span class="' . $col [0] . $class . '" title="' . $col [2] . '">' . $col [1] . '</span></a>';
		}
		return $retVal;
	}


	public function getBodyCols (array $row)
	{
		$retVal = '';
		foreach ($this->colsDef as $fld => $col)
		{
			$val = '';
			if (isset ($row [$fld]))
			{
				$val = $row [$fld];
			}

			$retVal .= '<span class="' . $col [0] . '">' . $val . '</span>';
		}
		return $retVal;
	}


	public function getStyledBodyCols (array $row)
	{
		$retVal = '';
		foreach ($this->colsDef as $fld => $col)
		{
			$val = '';
			if (isset ($row [$fld]))
			{
				$val = $row [$fld];
			}

			if (isset ($this->stylers [$fld]))
			{
				$sty = &$this->stylers [$fld];
				$retVal .= $sty->getSpan ($val, $col [0]);
			}
			else
			{
				$retVal .= '<span class="' . $col [0] . '">' . $val . '</span>';
			}
		}
		return $retVal;
	}
}

// ------------------------------------------------------------------
// --------------------- Common Stylers -----------------------------
// ------------------------------------------------------------------
/**
 * Plain formatter array: Use the value to find in the array to find its text value
 */
class FormatterPlainArr
{
	private $arr;


	public function __construct (array $values)
	{
		$this->arr = &$values;
	}


	public function getSpan ($val, $class)
	{
		$txt = $this->arr [$val] ?? '';
		return "<span class=\"$class\" >$txt</span>";
	}
}

class HelperColor
{


	/**
	 * Transform from RGB array to its hex form
	 *
	 * @param array $rgb
	 *        	RGB array
	 * @return string Color in Hex format
	 */
	private static function rgb2hex ($rgb)
	{
		$hex = "#";
		$hex .= str_pad (dechex ($rgb [0]), 2, "0", STR_PAD_LEFT);
		$hex .= str_pad (dechex ($rgb [1]), 2, "0", STR_PAD_LEFT);
		$hex .= str_pad (dechex ($rgb [2]), 2, "0", STR_PAD_LEFT);

		return $hex; // returns the hex value including the number sign (#)
	}


	/**
	 * Transformamos un color desde su notación hehadecimal a su notación rgb
	 *
	 * @param string $hex
	 *        	Color en formato string
	 * @return array color en notación rgb
	 */
	private static function hex2rgb ($hex)
	{
		$hex = str_replace ("#", "", $hex);

		if (strlen ($hex) == 3)
		{
			$r = hexdec (substr ($hex, 0, 1) . substr ($hex, 0, 1));
			$g = hexdec (substr ($hex, 1, 1) . substr ($hex, 1, 1));
			$b = hexdec (substr ($hex, 2, 1) . substr ($hex, 2, 1));
		}
		else
		{
			$r = hexdec (substr ($hex, 0, 2));
			$g = hexdec (substr ($hex, 2, 2));
			$b = hexdec (substr ($hex, 4, 2));
		}
		$rgb = array ($r, $g, $b);

		// return implode(",", $rgb); // returns the rgb values separated by commas
		return $rgb; // returns an array with the rgb values
	}


	/**
	 * Return a "half way" color
	 *
	 * @param string $firstColor
	 *        	The start color
	 * @param string $endcolor
	 *        	The last color
	 * @param int|float $minValue
	 *        	The min value
	 * @param int|float $maxValue
	 *        	The max Value
	 * @param int|float $currentValue
	 *        	The current value
	 * @return string "Half way" color in hex format
	 */
	public function getGradientColor ($firstColor, $endcolor, $minValue, $maxValue, $currentValue)
	{
		if ($currentValue >= $maxValue)
		{
			return $endcolor;
		}
		else if ($currentValue <= $minValue)
		{
			return $firstColor;
		}
		else
		{
			$colIni = self::hex2rgb ($firstColor);
			$colFin = self::hex2rgb ($endcolor);

			$porcentaje = ($currentValue - $minValue) / ($maxValue - $minValue);

			$retVal = array ();

			for($i = 0; $i <= 2; $i ++)
			{
				$retVal [] = (($colFin [$i] - $colIni [$i]) * $porcentaje) + $colIni [$i];
			}

			return self::rgb2hex ($retVal);
		}
	}
}

class FormatterBackgroundColor
{
	private $firstColor;
	private $endcolor;
	private $minValue;
	private $maxValue;


	public function __construct ($firstColor, $endcolor, $minValue, $maxValue)
	{
		$this->firstColor = $firstColor;
		$this->endcolor = $endcolor;
		$this->minValue = $minValue;
		$this->maxValue = $maxValue;
	}


	public function getSpan ($val, $class)
	{
		$bgColor = HelperColor::getGradientColor ($this->firstColor, $this->endcolor, $this->minValue, $this->maxValue, $val);
		return '<span class="' . $class . '" style="background-color: ' . $bgColor . ';">' . $val . '</span>';
	}
}

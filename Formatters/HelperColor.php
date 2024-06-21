<?php

namespace PHPSiteEngine\Formatters;

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
	public static function getGradientColor ($firstColor, $endcolor, $minValue, $maxValue, $currentValue)
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


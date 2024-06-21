<?php

namespace PHPSiteEngine\Formatters;

use IntlDateFormatter;

class FormatterTimestamp
{
	private $fmt;


	function __construct ($locale = 'es_ES.UTF-8')
	{
		$this->fmt = new \IntlDateFormatter ($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, null, IntlDateFormatter::GREGORIAN, null);
	}


	/**
	 *
	 * @param mixed $val
	 * @param string $class
	 * @return string
	 */
	public function getSpan ($val, $class)
	{
		return "<div class='$class'>" . $this->fmt->format ($val) . '</div>';
	}
}

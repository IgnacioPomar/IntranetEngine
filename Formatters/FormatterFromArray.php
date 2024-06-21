<?php

namespace PHPSiteEngine\Formatters;


class FormatterFromArray
{
	private $data;


	function __construct (array $vals)
	{
		$this->data = $vals;
	}


	/**
	 *
	 * @param mixed $val
	 * @param string $class
	 * @return string
	 */
	public function getSpan ($val, $class)
	{
		return "<div class='$class'>" . $this->data [$val] . '</div>';
	}
}


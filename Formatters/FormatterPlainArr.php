<?php

namespace PHPSiteEngine\Formatters;

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

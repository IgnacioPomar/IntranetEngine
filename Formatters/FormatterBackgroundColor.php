<?php

namespace PHPSiteEngine\Formatters;

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

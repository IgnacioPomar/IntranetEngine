<?php

namespace PHPSiteEngine;

interface SearchBox
{


	public function getTextVal ($mysqli, int $val): string;
}


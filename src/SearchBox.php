<?php

namespace src;

interface SearchBox
{


	public function getTextVal ($mysqli, int $val): string;
}


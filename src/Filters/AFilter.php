<?php
namespace Sellastica\TranslationExtractor\Filters;

abstract class AFilter
{
	/**
	 * Remove single or double quotes from begin and end of the string.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function stripQuotes($string)
	{
		$prime = substr($string, 0, 1);
		if ($prime === "'" || $prime === '"') {
			if (substr($string, -1, 1) === $prime) {
				$string = substr($string, 1, -1);
			}
		}
		return $string;
	}
}


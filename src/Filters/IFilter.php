<?php
namespace Sellastica\TranslationExtractor\Filters;

interface IFilter
{
	/**
	 * @param string $file
	 * @return array List<Map<KEY, string>>
	 */
	function extract(string $file): array;
}


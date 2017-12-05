<?php
namespace Sellastica\TranslationExtractor\Filters;

class PHP extends AFilter implements IFilter
{
	/**
	 * @param string $string
	 * @return array
	 */
	public function extract(string $string): array
	{
		$data = [];
		$matches = [];
		preg_match_all('~[\'\"][a-z0-9_]+\.[a-z0-9_]+\.[a-z0-9_]+(\.[a-z0-9_]+)?[\'\"]~', $string, $matches);

		if (isset($matches[0])) {
			foreach ($matches[0] as $match) {
				$data[] = $this->stripQuotes($match);
			}
		}

		return array_unique($data);
	}
}

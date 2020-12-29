<?php
namespace Sellastica\TranslationExtractor;

use Nette\Utils\Finder;
use Sellastica\TranslationExtractor\Filters\IFilter;

class Extractor
{
	/** @var IFilter[] */
	protected $filters = [];
	/** @var array */
	protected $data = [];
	/** @var array */
	private $dirs = [];
	/** @var array|null */
	private $lastScan;


	/**
	 * @param string $path
	 * @return $this
	 */
	public function addDir(string $path): Extractor
	{
		$this->dirs[] = $path;
		return $this;
	}

	/**
	 * Scans given files or directories and extracts gettext keys from the content
	 * @return self
	 */
	public function scan(): Extractor
	{
		$messagesCount = 0;

		$masks = [];
		foreach (array_keys($this->filters) as $extension) {
			$masks[] = '*.' . $extension;
		}

		//files
		$filesCount = 0;
		foreach ($this->dirs as $directory) {
			if (!is_dir($directory)) {
				continue;
			}

			foreach (Finder::findFiles($masks)->from($directory) as $file) {
				/** @var \SplFileInfo $file */
				$messagesCount += $this->extract($file->getExtension(), file_get_contents($file->getRealPath()));
				$filesCount++;
			}
		}

		$this->lastScan = [
			'files' => $filesCount,
			'messages' => $messagesCount,
		];
		return $this;
	}

	/**
	 * Add a filter object
	 *
	 * @param string $filterName
	 * @param \Sellastica\TranslationExtractor\Filters\IFilter $filter
	 * @return $this
	 */
	public function addFilter(string $filterName, \Sellastica\TranslationExtractor\Filters\IFilter $filter)
	{
		$this->filters[$filterName] = $filter;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}

	/**
	 * @return array|null
	 */
	public function getLastScan(): ?array
	{
		return $this->lastScan;
	}

	/**
	 * @param string $filterType
	 * @param string $string
	 * @return int
	 */
	private function extract(string $filterType, string $string)
	{
		if (isset($this->filters[$filterType])) {
			$filter = $this->filters[$filterType];
			$messages = $filter->extract($string);
			$this->data = array_unique(array_merge($messages, $this->data));

			return count($messages);
		}

		return 0;
	}
}

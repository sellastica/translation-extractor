<?php
namespace Sellastica\TranslationExtractor;

use Nette\Utils\Json;
use Sellastica\Utils\Arrays;

class CatalogueBuilder
{
	const FILE_MASK = '%s.%s.json';

	/** @var array */
	private $catalogueDirs = [];
	/** @var array */
	private $localizations = [];
	/** @var bool */
	private $backup = false;

	/**
	 * @param bool $backup
	 * @return $this
	 */
	public function setBackup(bool $backup)
	{
		$this->backup = $backup;
		return $this;
	}

	/**
	 * @param string $name File and domain name
	 * @param string $dir Catalogue directory
	 * @return $this
	 */
	public function addCatalogueDir(string $name, string $dir)
	{
		$this->catalogueDirs[$name] = $dir;
		return $this;
	}

	/**
	 * @param string $localization
	 * @return $this
	 */
	public function addLocalization(string $localization)
	{
		$this->localizations[] = $localization;
		return $this;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public function build(array $data)
	{
		$outputFiles = [];
		$messagesByDomain = [];
		foreach ($data as $messageData) {
			list($domain, $message) = explode('.', $messageData, 2);
			$messagesByDomain[$domain][] = $message;
		}

		foreach ($messagesByDomain as $domain => $messages) {
			$result = [];
			foreach ($messages as $message) {
				Arrays::set($result, $message, null);
			}

			if (isset($result)) {
				$outputFiles = array_merge($outputFiles, $this->buildLocalizationFiles($domain, $result));
			}
		}

		return $outputFiles;
	}

	/**
	 * @param string $domain
	 * @param array $jsonData
	 * @return array
	 */
	private function buildLocalizationFiles(string $domain, array $jsonData)
	{
		$outputFiles = [];
		if ($catalogueDir = $this->getCatalogueDirByDomain($domain)) {
			foreach ($this->localizations as $localization) {
				$fileName = sprintf(self::FILE_MASK, $domain, $localization);
				$file = $catalogueDir . '/' . $fileName;
				if ($this->backup === true) {
					$this->backupFile($file, $fileName);
				}

				$this->mergeFile($file, $jsonData);
				$outputFiles[] = $file;
			}
		}

		return $outputFiles;
	}

	/**
	 * @param string $domain
	 * @return mixed|null
	 */
	private function getCatalogueDirByDomain(string $domain)
	{
		return $this->catalogueDirs[$domain] ?? null;
	}

	/**
	 * @param string $file
	 * @param string $fileName
	 */
	private function backupFile(string $file, string $fileName)
	{
		if (is_file($file)) {
			$dir = TEMP_DIR . '/backup/' . (new \DateTime())->format('YmdHis');
			if (!is_dir($dir)) {
				mkdir($dir, 0775, true);
			}

			copy($file, $dir . '/' . $fileName);
		}
	}

	/**
	 * @param string $file
	 * @param array $jsonData
	 */
	private function mergeFile(string $file, array $jsonData)
	{
		if (file_exists($file)) {
			$contents = Json::decode(file_get_contents($file), Json::FORCE_ARRAY);
			$merged = $this->mergeTree($contents, $jsonData);
		} else {
			$merged = $jsonData;
		}

		file_put_contents($file, Json::encode($merged, Json::PRETTY));
		@chmod(dirname($file), 0755);
		@chmod($file, 0664);
	}

	/**
	 * Recursively appends elements of remaining keys from the second array to the first.
	 * @param array $arr1
	 * @param array $arr2
	 * @return array
	 */
	private function mergeTree(array $arr1, array $arr2)
	{
		$res = $arr1 + $arr2; //join old and new translations
		$res = array_intersect_key($res, $arr2); //remove old translations, if they are not used in the new one
		ksort($res);
		foreach (array_intersect_key($arr1, $arr2) as $k => $v) {
			if (is_array($v) && is_array($arr2[$k])) {
				$res[$k] = $this->mergeTree($v, $arr2[$k]);
			}
		}

		return $res;
	}

	/**
	 * @return CatalogueBuilder
	 */
	public static function create(): self
	{
		return new self();
	}
}

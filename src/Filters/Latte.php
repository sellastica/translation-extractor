<?php
namespace Sellastica\TranslationExtractor\Filters;

use Latte\MacroTokens;
use Latte\Parser;
use Latte\PhpWriter;
use Nette\Utils\Strings;

class Latte extends \Sellastica\TranslationExtractor\Filters\AFilter implements IFilter
{
	/** @var string */
	private $prefix;
	/** @var array */
	private $messages = [];


	/**
	 * @param string $prefix
	 * @return $this
	 */
	public function setPrefix(string $prefix)
	{
		$this->prefix = $prefix;
		return $this;
	}

	/**
	 * @param string $string
	 * @return array
	 */
	public function extract(string $string): array
	{
		$this->messages = [];
		$buffer = null;
		$parser = new Parser();
		foreach ($tokens = $parser->parse($string) as $token) {
			if ($token->type !== $token::MACRO_TAG || !in_array($token->name, ['_', '/_'], true)) {
				if ($buffer !== null) {
					$buffer .= $token->text;
				}

				continue;
			}

			if ($token->name === '/_') {
				$this->add(($this->prefix ? $this->prefix . '.' : '') . $buffer);
				$buffer = null;
			} elseif ($token->name === '_' && empty($token->value)) {
				$buffer = '';
			} else {
				$args = new MacroTokens($token->value);
				$writer = new PhpWriter($args, $token->modifiers);

				$message = $writer->write('%node.word');
				if (in_array(substr(trim($message), 0, 1), ['"', '\''], true)) {
					$message = substr(trim($message), 1, -1);
				}

				$this->add(($this->prefix ? $this->prefix . '.' : '') . $message);
			}
		}

		return array_unique($this->messages);
	}

	/**
	 * @param string $message
	 */
	private function add(string $message)
	{
		if (Strings::startsWith($message, '$')) {
			return;
		}

		$this->messages[] = $message;
	}
}

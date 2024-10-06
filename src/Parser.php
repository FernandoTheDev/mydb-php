<?php

namespace Fernando\MyDB;

use Fernando\MyDB\Cli\Color;
use Fernando\MyDB\Cli\Printer;
use Fernando\MyDB\Expressions\{
	Show,
	Select,
	Create,
	Delete,
	Update,
	Insert,
};

class Parser
{
	private array $keywords = [];

	public function __construct()
	{
		if (!is_dir(__DIR__ . '/../db/')) {
			mkdir(__DIR__ . '/../db/');
		}

		$this->keywords = [
			"SHOW" => new Show,
			"SELECT" => new Select,
			"CREATE" => new Create,
			"INSERT" => new Insert,
			"DELETE" => new Delete,
			"UPDATE" => new Update,
		];
	}

	public function parserAndInterpreter(string $expression): void
	{
		/**
		 * A Primeira expressão tem obrigatoriamente que ser um  Keyword ou um Comentário.
		 */
		$expression = explode("\n", $expression);
		$inPointer = false;
		$dataPointer = [];
		$openInit = false;

		foreach ($expression as $_ => $value) {
			if (str_contains($value, ";")) {
				Printer::getInstance()->Display(Color::Fg(196, "The system does not accept the use of semicolon '{$value}'."));
				exit;
			}

			$trimmedValue = trim($value);
			$tokens = explode(" ", $trimmedValue);
			$keyword = $tokens[0];
			$lastToken = end($tokens);

			/* Vamos ver se é um comentário */
			if (in_array($keyword, ["#", "--", '', "\n"])) {
				array_shift($expression);
				continue;
			}

			if ($lastToken === "(" || $lastToken[0] === "(") {
				$inPointer = true;
				$openInit = true;
				array_shift($expression);
			}

			if ($lastToken === ")" || $lastToken[0] === ")" && $openInit) {
				$inPointer = false;
				$openInit = false;
				$dataPointer[] = $value;

				$tokens = explode(" ", implode(" ", $dataPointer));
				$upperKeyword = strtoupper($tokens[0]);
				array_shift($tokens);

				$tokenize = implode(" ", $tokens);

				$this->keywords[$upperKeyword]->run($tokens);

				array_shift($expression);
				$dataPointer = [];
				continue;
			}

			if ($lastToken[strlen($lastToken) - 1] === ")" && $openInit) {
				$inPointer = false;
				$openInit = false;
				$dataPointer[] = $value;

				$tokens = explode(" ", implode(" ", $dataPointer));
				$upperKeyword = strtoupper($tokens[0]);
				array_shift($tokens);

				$tokenize = implode(" ", $tokens);
				$this->keywords[$upperKeyword]->run($tokens);

				array_shift($expression);
				$dataPointer = [];
				continue;
			}

			if ($inPointer) {
				$dataPointer[] = $value;
				array_shift($expression);
				continue;
			}

			/* Verificando se é um Keyword válido. */
			$upperKeyword = strtoupper($keyword);
			if (isset($this->keywords[$upperKeyword])) {
				array_shift($tokens);
				$tokenize = implode(" ", $tokens);
				$this->keywords[$upperKeyword]->run($tokens);
				array_shift($expression);
				continue;
			}

			/* Não identificado. */
			Printer::getInstance()->Display(Color::Fg(196, "Keyword '{$keyword}' not found."));
			exit;
			# array_shift($expression);
		}

		if (count($expression) > 0) {
			$this->parserAndInterpreter(implode("\n", $expression));
		}
	}
}

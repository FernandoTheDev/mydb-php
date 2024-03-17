<?php

namespace Fernando\MyDB;

use Fernando\MyDB\Expressions\Select;
use Fernando\MyDB\Expressions\Create;
use Fernando\MyDB\Expressions\Insert;
use Fernando\MyDB\Expressions\Show;
use Fernando\MyDB\Expressions\Delete;
use Fernando\MyDB\Expressions\Update;
use Fernando\MyDB\Utils\Logger;

final class Parser
{
	private array $keywords = [];

	public function __construct() {
		$this->keywords = [
			"SELECT" => new Select,
			"CREATE" => new Create,
			"INSERT" => new Insert,
			"SHOW" => new Show,
			"DELETE" => new Delete,
			"UPDATE" => new Update,
		];
	}

	public function parserAndInterpreter(string $expression): void
	{
		$logger = new Logger;
		/**
		* A Primeira expressão tem obrigatoriamente que ser um  Keyword ou um Comentário.
		*/
		$expression = explode("\n", $expression);
		$inPointer = false;
		$dataPointer = [];
		$openInit = false;

		foreach ($expression as $key => $value) {
			$trimmedValue = trim($value);
			$tokens = explode(" ", $trimmedValue);
			$keyword = $tokens[0];
			$lastToken = end($tokens);

			if (in_array($value, ['', " "])) {
				array_shift($expression);
				continue;
			}

			/* Vamos ver se é um comentário */
			if (in_array($keyword, ["#", "--", '', "\n"])) {
				array_shift($expression);
				continue;
			}

			if ($lastToken === "(" || $lastToken[0] === "(") {
				$inPointer = true;
				$openInit = true;
				$dataPointer[] = $value;
				array_shift($expression);
				continue;
			}

			if ($lastToken === ")" && $openInit) {
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
			$logger->warning("Invalid keyword '{$keyword}'.");
			array_shift($expression);
		}

		if (count($expression) > 0) {
			$this->parserAndInterpreter(implode("\n", $expression));
		}
	}
}
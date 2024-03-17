<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Utils\Logger;

final class Delete
{
	private string $dirBase = __DIR__ . '/../../db/';

	public function run(array $expression): void
	{
		$logger = new Logger;
		if (count($expression) < 1) {
			$logger->error("Invalid expression '{$expression}'.");
			return;
		}

		$command = strtoupper($expression[0]);

		switch ($command) {
			case 'DATABASES':
			case 'DATABASE':
				array_shift($expression);
				$this->database($expression);
				break;
			case 'TABLES':
			case 'TABLE':
				array_shift($expression);
				$this->table($expression);
				break;
			$logger->warning("Comando inválido: '{$command}'.");
			break;
		}
	}

	private function table(array $expression): void
	{
		$logger = new Logger;

		foreach ($expression as $db => $exp) {
			list($dbName, $tableName) = $this->getDatabaseAndTableName($exp);
			$dir = $this->dirBase . $dbName;
			$file = $dir . '/' . $tableName . '.json';

			if (!is_dir($dir)) {
				$logger->error("Database not found '{$dbName}'.");
				return;
			}

			if (!file_exists($file)) {
				$logger->error("Table not found '{$dbName}.{$tableName}'.");
				return;
			}

			unlink($file);
			$logger->success("Table excluded '{$dbName}.{$tableName}'.");
		}
	}

	private function database(array $expression): void
	{
		$logger = new Logger;

		foreach ($expression as $db => $exp) {
			$dir = $this->dirBase . $exp;

			if (!is_dir($dir)) {
				$logger->error("Database not found '{$exp}'.");
				return;
			}

			exec("rm -f -r " . $dir);
			$logger->success("Database excluded '{$exp}'.");
		}
	}

	private function getDatabaseAndTableName(string $expression): array
	{
		return explode(".", $expression);
	}

	private function parseTableData(array $expression): array
	{
		$data["columns"] = [];
		$data["data"] = [];

		for ($i = 0; $i < count($expression); $i++) {
			$part = trim(str_replace(",", "", $expression[$i]));

			if ($part === '' or $part === '(') {
				continue;
			}

			if ($part === ')') {
				break;
			}

			if ($part[-1] === ")") {
				$part = str_replace(")", "", $part);
				array_push($data["columns"], $part);
				break;
			}

			array_push($data["columns"], $part);
		}

		return $data;
	}
}
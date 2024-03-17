<?php

namespace Fernando\MyDB\Php\Expressions;

use Fernando\MyDB\Php\Utils\Logger;

final class Create
{
	private string $dirBase = __DIR__ . '/../../../db/';

	public function run(array $expression, ?string $databaseName = ""): array
	{
		$logger = new Logger;
		if (count($expression) < 2) {
			$logger->error("Invalid expression '{$expression}'.");
			return [];
		}

		$command = strtoupper($expression[0]);

		switch ($command) {
			case 'DATABASE':
				array_shift($expression);
				return $this->createDatabase($expression, $databaseName);
			case 'TABLE':
				array_shift($expression);
				return $this->createTable($expression, $databaseName);
			default:
				$logger->warning("Invalid command '{$command}'.");
				return [];
			}
		}

		private function createDatabase(array $expression, string $databaseName): array
		{
			$logger = new Logger;
			$dbName = ($databaseName !== "") ? $databaseName : $this->getDatabaseName($expression);

			if (is_dir($this->dirBase . $dbName)) {
				$logger->warning("Database already exists '{$dbName}'.");
				return [];
			}

			mkdir($this->dirBase . $dbName);
			return [];
		}

		private function createTable(array $expression, string $databaseName): array
		{
			$logger = new Logger;
			if ($databaseName == "") {
			list($dbName, $tableName) = $this->getDatabaseAndTableName($expression);
			} else {
				$dbName = $databaseName;
				$tableName = $expression[0];
				
			}

			if (!is_dir($this->dirBase . $dbName)) {
				$logger->error("Database not exists '{$dbName}'.");
				return [];
			}

			if (file_exists($this->dirBase . $dbName . '/' . $tableName . '.json')) {
				$logger->warning("The table already exists '{$tableName}.{$dbName}'.");
				return [];
			}

			$tableData = [];
			if ($expression[1] == "(") {
				array_shift($expression);
				$tableData = $this->parseTableData($expression);
				file_put_contents($this->dirBase . $dbName . '/' . $tableName . '.json', json_encode($tableData, JSON_PRETTY_PRINT));
	}

			if (str_contains($expression[1], "(")) {
				$expression[1] = str_replace("(", "", $expression[1]);
				array_shift($expression);
				$tableData = $this->parseTableData($expression);
				file_put_contents($this->dirBase . $dbName . '/' . $tableName . '.json', json_encode($tableData, JSON_PRETTY_PRINT));
}

			return [];
		}

		private function getDatabaseName(array $expression): string
		{
			return $expression[0];
		}

		private function getDatabaseAndTableName(array $expression): array
		{
			return explode(".", $expression[0]);
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
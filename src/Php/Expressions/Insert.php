<?php

namespace Fernando\MyDB\Php\Expressions;

use Fernando\MyDB\Php\Utils\Logger;

final class Insert
{
	private string $dirBase = __DIR__ . '/../../../db/';

	public function run(array $expression, ?string $databaseName = ""): array
	{
		$logger = new Logger;

		if (count($expression) < 3) {
			$logger->fatal("Invalid syntax '" . implode(" ", $expression) . "'.");
			return [];
		}

		if (strtoupper($expression[0]) !== "INTO") {
			$logger->error("Invalid expression '$expression[0]'.");
			return [];
		}

		if ($databaseName == "") {
			if (!str_contains($expression[1], '.')) {
				$logger->error("Invalid expression '$expression[1]'.");
				return [];
			}
		}

		if (strtoupper($expression[2]) !== "VALUES") {
			$logger->error("Invalid expression '$expression[2]'.");
			return [];
		}

		if (!$expression[3] == "(") {
			if ($expression[3][0] == "(") {
				$expression[3] = str_replace("(", "", $expression[2]);
			}
		}

		if ($databaseName == "") {
			list($dbName, $tableName) = $this->getDatabaseAndTableName($expression[1]);
		} else {
			$dbName = $databaseName;
			$tableName = $expression[1];
		}
		array_shift($expression);
		array_shift($expression);
		array_shift($expression);


		if (!is_dir($this->dirBase . $dbName)) {
			$logger->error("Database not exists '{$dbName}'.");
			return [];
		}

		$tableFile = $this->dirBase . $dbName . '/' . $tableName . '.json';

		if (!file_exists($tableFile)) {
			$logger->error("Table not exists '{$dbName}.{$tableName}'.");
			return [];
		}

		$tableData = json_decode(file_get_contents($tableFile), true);
		$columns = $tableData["columns"];
		$expression = array_filter($expression, function ($i) {
			return $i != "" and $i != "(";
		});
		$data = [];

		$newExpression = [];

		foreach ($expression as $index) {
			array_push($newExpression, $index);
		}

		for ($i = 0; $i < count($newExpression); $i++) {
			if ($newExpression[$i] === NULL) {
				continue;
			}

			$part = str_replace([",", "(", ")"], "", $newExpression[$i]);

			if ($part === '' or $part === '(') {
				continue;
			}

			if ($part === ')') {
				break;
			}

			if ($part[$i + 1] === ")") {
				$part = str_replace([")", ","], "", $part);
				$data[$columns[$i]] = $part;
				break;
			}

			$data[$columns[$i]] = $part;
		}

		if (count($data) !== count($columns)) {
			$logger->error("Amount of data is different from " . count($columns));
			$logger->error("You passed  " . count($data) . " parameters.");
			return [];
		}

		$tableData['data'][] = $data;
		file_put_contents($tableFile, json_encode($tableData, JSON_PRETTY_PRINT));
		return [];
	}

	private function getDatabaseAndTableName(string $expression): array
	{
		return explode(".", $expression);
	}
}
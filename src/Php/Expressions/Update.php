<?php

namespace Fernando\MyDB\Php\Expressions;

use Fernando\MyDB\Php\Utils\Logger;

final class Update
{
	private string $reservedWordSet = "SET";
	private string $reservedWordWhere = "WHERE";
	private string $dirBase = __DIR__ . '/../../../db/';

	public function run(array $expression, ?string $databaseName = ""): array
	{
		$logger = new Logger;
		if (empty($expression)) {
			$logger->fatal("Invalid expression '{$expression}'.");
			return [];
		}

		$setData = [];
		$whereData = [];
		$table = "";
		$currentIndex = 0;

		while ($currentIndex < count($expression)) {
			$keyword = $expression[$currentIndex];

			switch (strtoupper($keyword)) {
				case $this->reservedWordSet:
					$setData = $this->parseSetData($expression, $currentIndex);
					$currentIndex += count($setData) * 3 + 1;
					break;
				case $this->reservedWordWhere:
					$whereData = $this->parseWhereData($expression, $currentIndex);
					$currentIndex += count($whereData) * 4 + 1;
					break;
				default:
					if ($table === "") {
						$table = $keyword;
					}
					$currentIndex++;
					break;
			}
		}

		$this->processUpdate($table, $setData, $whereData, $databaseName);
		return [];

	}

	private function parseSetData(array $expression, int $setIndex): array
	{
		$setData = [];
		$currentIndex = $setIndex + 1;
		while ($currentIndex < count($expression) && strtoupper($expression[$currentIndex]) !== $this->reservedWordWhere) {
			$field = $expression[$currentIndex];
			$value = $expression[$currentIndex + 2];
			$setData[$field] = $value;
			$currentIndex += 3;
			if ($currentIndex < count($expression) && strtoupper($expression[$currentIndex]) === ',') {
				$currentIndex++;
			}
		}
		return $setData;
	}

	private function parseWhereData(array $expression, int $whereIndex): array
	{
		$whereData = [];
		$currentIndex = $whereIndex + 1;
		while ($currentIndex < count($expression)) {
			$field = $expression[$currentIndex];
			$operator = $expression[$currentIndex + 1];
			$value = $expression[$currentIndex + 2];
			$whereData[] = [$field,
				$operator,
				$value];
			$currentIndex += 4;
			if ($currentIndex < count($expression) && strtoupper($expression[$currentIndex]) === 'AND') {
				$currentIndex++;
			} else {
				break;
			}
		}
		return $whereData;
	}

	private function processUpdate(string $tableName, array $setData, array $whereData, ?string $databaseName = ""): array
	{
		$logger = new Logger;
		if ($databaseName == "") {
			list($dbName, $tableName) = $this->getDatabaseAndTableName($tableName);

			if (!is_dir($this->dirBase . $dbName)) {
				$logger->error("Database not exists '{$dbName}'.");
				return [];
			}
		} else {
			$dbName = $databaseName;

			if (!is_dir($this->dirBase . $dbName)) {
				$logger->error("Database not exists '{$dbName}'.");
				return [];
			}
		}

		$tableFile = $this->dirBase . $dbName . '/' . $tableName . '.json';

		if (!file_exists($tableFile)) {
			$logger->error("Table not exists '{$dbName}.{$tableName}'.");
			return [];
		}

		$tableData = json_decode(file_get_contents($tableFile), true);

		$this->performUpdate($tableData, $setData, $whereData, $tableFile);

		return $tableData;
	}

	private function performUpdate(array &$tableData, array $setData, array $whereData, string $tableFile): void
	{
		foreach ($tableData["data"] as &$row) {
			$updateRow = true;
			foreach ($whereData as $condition) {
				list($field, $operator, $value) = $condition;
				if (!$this->evaluateCondition($row[$field], $operator, $value)) {
					$updateRow = false;
					break;
				}
			}
			if ($updateRow) {
				foreach ($setData as $field => $value) {
					$row[$field] = $value;
				}
			}
		}

		file_put_contents($tableFile, json_encode($tableData, JSON_PRETTY_PRINT));
	}

	private function evaluateCondition(string $fieldValue, string $operator, string $value): bool
	{
		switch ($operator) {
			case "=":
				return $fieldValue == $value;
			case "<":
				return $fieldValue < $value;
			case ">":
				return $fieldValue > $value;
			case "<=":
				return $fieldValue <= $value;
			case ">=":
				return $fieldValue >= $value;
			default:
				return false;
		}
	}

	private function getDatabaseAndTableName(string $expression): array
	{
		return explode(".", $expression);
	}
}
<?php

namespace Fernando\MyDB\Php\Expressions;

use Fernando\MyDB\Php\Utils\Logger;

final class Select
{
	private string $reservedWord = "FROM";
	private string $dirBase = __DIR__ . '/../../../db/';

	public function run(array $expression, ?string $databaseName = ""): array
	{
		$logger = new Logger;
		if (empty($expression)) {
			$logger->fatal("Invalid expression '{$expression}'.");
			return [];
		}

		$fromIndex = array_search(strtoupper($this->reservedWord), array_map('strtoupper', $expression));
		if ($fromIndex === false) {
			$logger->fatal("'FROM' not found in expression '{$expression}'.");
			return [];
		}

		if (!isset($expression[$fromIndex + 1])) {
			$logger->error("The table was not passed '{$expression}'.");
			return [];
		}

		$table = $expression[$fromIndex + 1];
		$selects = $this->parseSelects($expression, $fromIndex);

		return $this->processSelect($selects, $table, $expression, $databaseName);
	}

	private function parseSelects(array $expression, int $fromIndex): array
	{
		$selects = [];
		for ($index = 0; $index < $fromIndex; $index++) {
			if ($expression[$index] !== ",") {
				$selects[] = str_replace(',', '', $expression[$index]);
			}
		}
		return $selects;
	}

	private function processSelect(array $selects, string $table, array $expression, ?string $databaseName = ""): array
	{
		$logger = new Logger;
		if (empty($selects)) {
			$logger->error("The selection type was not passed '" . implode(" ", $expression) . "'.");
			return [];
		}

		if ($databaseName == "") {
			list($dbName, $tableName) = $this->getDatabaseAndTableName($table);

			if (!is_dir($this->dirBase . $dbName)) {
				$logger->error("Database not exists '{$dbName}'.");
				return [];
			}
		} else {
			$tableName = $table;
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

		$whereIndex = array_search(strtoupper("WHERE"), array_map('strtoupper', $expression));
		if (count($selects) === 1 && $selects[0] === "*") {
			if ($whereIndex !== false && $this->isValidWhereClause($expression, $whereIndex)) {
				$whereField = $expression[$whereIndex + 1];
				$whereOperator = $expression[$whereIndex + 2];
				$whereValue = $expression[$whereIndex + 3];

				return $this->allWithWhere([$dbName, $tableName], $tableData, $whereField, $whereOperator, $whereValue);
			}
			return $this->all([$dbName, $tableName], $expression, $tableData);
		}

		if ($whereIndex !== false && $this->isValidWhereClause($expression, $whereIndex)) {
			$whereField = $expression[$whereIndex + 1];
			$whereOperator = $expression[$whereIndex + 2];
			$whereValue = $expression[$whereIndex + 3];

			return$this->especificWithWhere($selects, [$dbName, $tableName], $tableData, $whereField, $whereOperator, $whereValue);
		}
		return $this->especific($selects, [$dbName, $tableName], $tableData);
	}

	private function all(array $name, array $expression, array $tableData): array
	{
		$data = [];
		$logger = new Logger();
		list($dbName, $tableName) = $name;

		foreach ($tableData["data"] as $columnName => $columnData) {
			$data[$columnName] = [];
			foreach ($columnData as $key => $value) {
				$data[$columnName][$key] = $value;
			}
		}

		return $data;
	}

	private function especific(array $selects, array $name, array $tableData): array
	{
		$data = [];
		$logger = new Logger;
		list($dbName, $tableName) = $name;

		foreach ($selects as $select) {
			if (isset($tableData["data"][$select])) {
				$data[$select] = [];
				foreach ($tableData["data"][$select] as $key => $value) {
					$data[$select][$key] = $value;
				}
				continue;
			}
			$logger->warning("'{$select}' not found in table '{$dbName}.{$tableName}'.");
		}

		return $data;
	}

	private function allWithWhere(array $name, array $tableData, string $whereField, string $whereOperator, string $whereValue): array
	{
		$data = [];
		$logger = new Logger;
		list($dbName, $tableName) = $name;
		$fieldFound = false;

		foreach ($tableData["data"] as $columnName => $columnData) {
			if (isset($columnData[$whereField]) && $this->evaluateCondition($columnData[$whereField], $whereOperator, $whereValue)) {
				$fieldFound = true;
				$data[$columnName] = [];
				foreach ($columnData as $cK => $cD) {
					$data[$columnName][$cK] = $cD;
				}
			}
		}

		return $data;
	}

	private function especificWithWhere(array $selects, array $name, array $tableData, string $whereField, string $whereOperator, string $whereValue): array
	{
		$data = [];
		$logger = new Logger;
		list($dbName, $tableName) = $name;
		$fieldFound = false;

		foreach ($tableData["data"] as $columnName => $columnData) {
			if (isset($columnData[$whereField]) && $this->evaluateCondition($columnData[$whereField], $whereOperator, $whereValue)) {
				$fieldFound = true;
				$data[$columnName] = [];
				foreach ($selects as $select) {
					if (isset($columnData[$select])) {
						$data[$columnName][$select] = $columnData[$select];
						continue;
					}
					$logger->warning("'{$select}' not found in table '{$dbName}.{$tableName}'.");
				}
			}
		}

		return $data;
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

	private function isValidWhereClause(array $expression, int $whereIndex): bool
	{
		return isset($expression[$whereIndex + 3]) && in_array(strtoupper($expression[$whereIndex + 2]), ['=', '<', '>', '<=', '>=']);
	}
}
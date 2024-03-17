<?php

namespace Fernando\MyDB\Expressions;

use Fernando\MyDB\Utils\Logger;

final class Select
{
	private string $reservedWord = "FROM";
	private string $dirBase = __DIR__ . '/../../db/';

	public function run(array $expression): void
	{
		$logger = new Logger;
		if (empty($expression)) {
			$logger->error("Invalid expression '{$expression}'.");
			return;
		}

		$fromIndex = array_search(strtoupper($this->reservedWord), array_map('strtoupper', $expression));
		if ($fromIndex === false) {
			$logger->error("'FROM' not found in expression '{$expression}'.");
			return;
		}

		if (!isset($expression[$fromIndex + 1])) {
			$logger->error("The table was not passed '{$expression}'.");
			return;
		}

		$table = $expression[$fromIndex + 1];
		$selects = $this->parseSelects($expression, $fromIndex);

		$this->processSelect($selects, $table, $expression);
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

	private function processSelect(array $selects, string $table, array $expression): void
	{
		$logger = new Logger;
		if (empty($selects)) {
			$logger->error("The selection type was not passed '" . implode(" ", $expression) . "'.");
			return;
		}

		list($dbName, $tableName) = $this->getDatabaseAndTableName($table);

		if (!is_dir($this->dirBase . $dbName)) {
			$logger->error("Database not exists '{$dbName}'.");
			return;
		}

		$tableFile = $this->dirBase . $dbName . '/' . $tableName . '.json';

		if (!file_exists($tableFile)) {
			$logger->error("Table not exists '{$dbName}.{$tableName}'.");
			return;
		}

		$tableData = json_decode(file_get_contents($tableFile), true);

		$whereIndex = array_search(strtoupper("WHERE"), array_map('strtoupper', $expression));
		if (count($selects) === 1 && $selects[0] === "*") {
			if ($whereIndex !== false && $this->isValidWhereClause($expression, $whereIndex)) {
				$whereField = $expression[$whereIndex + 1];
				$whereOperator = $expression[$whereIndex + 2];
				$whereValue = $expression[$whereIndex + 3];

				$this->allWithWhere([$dbName, $tableName], $tableData, $whereField, $whereOperator, $whereValue);
				return;
			}
			$this->all([$dbName, $tableName], $expression, $tableData);
			return;
		}

		if ($whereIndex !== false && $this->isValidWhereClause($expression, $whereIndex)) {
			$whereField = $expression[$whereIndex + 1];
			$whereOperator = $expression[$whereIndex + 2];
			$whereValue = $expression[$whereIndex + 3];

			$this->especificWithWhere($selects, [$dbName, $tableName], $tableData, $whereField, $whereOperator, $whereValue);
		} else {
			$this->especific($selects, [$dbName, $tableName], $tableData);
		}
	}

	private function all(array $name, array $expression, array $tableData): void
	{
		$logger = new Logger;
		list($dbName, $tableName) = $name;

		foreach ($tableData["data"] as $columnName => $columnData) {
			$logger->success("{$columnName}:");
			foreach ($columnData as $key => $value) {
				echo "  - {$key}: {$value}\n";
			}
			echo PHP_EOL;
		}

		$logger->println("All table data from '{$dbName}.{$tableName}'.");
	}

	private function especific(array $selects, array $name, array $tableData): void
	{
		$logger = new Logger;
		list($dbName, $tableName) = $name;

		foreach ($selects as $select) {
			if (isset($tableData["data"][$select])) {
				$logger->success("{$select}:");
				foreach ($tableData["data"][$select] as $key => $value) {
					echo "  - {$key}: {$value}\n";
				}
				echo PHP_EOL;
			} else {
				$logger->error("'{$select}' not found in table '{$dbName}.{$tableName}'.");
			}
		}
	}

	private function allWithWhere(array $name, array $tableData, string $whereField, string $whereOperator, string $whereValue): void
	{
		$logger = new Logger;
		list($dbName, $tableName) = $name;
		$fieldFound = false;

		foreach ($tableData["data"] as $columnName => $columnData) {
			if (isset($columnData[$whereField]) && $this->evaluateCondition($columnData[$whereField], $whereOperator, $whereValue)) {
				$fieldFound = true;
				$logger->success("{$columnName}:");
				foreach ($columnData as $cK => $cD) {
					echo "  - {$cK}: {$cD}\n";
				}
				echo PHP_EOL;
			}
		}

		if (!$fieldFound) {
			$logger->warning("No data was found for the condition '{$whereField} {$whereOperator} {$whereValue}' in '{$dbName}.{$tableName}'.");
		}
	}

	private function especificWithWhere(array $selects, array $name, array $tableData, string $whereField, string $whereOperator, string $whereValue): void
	{
		$logger = new Logger;
		list($dbName, $tableName) = $name;
		$fieldFound = false;

		foreach ($tableData["data"] as $columnName => $columnData) {
			if (isset($columnData[$whereField]) && $this->evaluateCondition($columnData[$whereField], $whereOperator, $whereValue)) {
				$fieldFound = true;
				$logger->success("{$columnName}:");
				foreach ($selects as $select) {
					if (isset($columnData[$select])) {
						echo "  - {$select}: {$columnData[$select]}\n";
					} else {
						echo "Campo '{$select}' não encontrado em '{$dbName}.{$tableName}'.\n";
					}
				}
				echo PHP_EOL;
			}
		}

		if (!$fieldFound) {
			$logger->warning("No data was found for the condition '{$whereField} {$whereOperator} {$whereValue}' in '{$dbName}.{$tableName}'.");
		}
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
		// Verifica se há ao menos 3 tokens após WHERE e se o segundo e terceiro tokens formam uma expressão válida
		return isset($expression[$whereIndex + 3]) && in_array(strtoupper($expression[$whereIndex + 2]), ['=', '<', '>', '<=', '>=']);
	}
}